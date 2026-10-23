<?php
declare(strict_types=1);

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = require __DIR__ . '/config.php';
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $config['host'],
        (int) $config['port'],
        $config['database'],
        $config['charset']
    );

    $pdo = new PDO($dsn, $config['user'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

function purge_expired_meetings(): void
{
    $pdo = db();
    $rooms = $pdo->query(
        'SELECT room_number FROM meetings WHERE created_at < (NOW() - INTERVAL 90 DAY)'
    )->fetchAll(PDO::FETCH_COLUMN);

    foreach ($rooms as $room) {
        delete_meeting_room($pdo, (string) $room);
    }

    $pdo->exec(
        'DELETE m FROM meetup_members m
         LEFT JOIN meetings t ON t.room_number = m.room_number
         WHERE t.room_number IS NULL
           AND m.created_at < (NOW() - INTERVAL 90 DAY)'
    );
}

function delete_meeting_room(PDO $pdo, string $room): void
{
    $find = $pdo->prepare('SELECT id FROM meetings WHERE room_number = :room LIMIT 1');
    $find->execute(['room' => $room]);
    $meetingId = $find->fetchColumn();
    if ($meetingId !== false) {
        $votes = $pdo->prepare('DELETE FROM meeting_votes WHERE meeting_id = :meeting');
        $votes->execute(['meeting' => $meetingId]);
        $locations = $pdo->prepare('DELETE FROM member_locations WHERE meeting_id = :meeting');
        $locations->execute(['meeting' => $meetingId]);
    }
    $members = $pdo->prepare('DELETE FROM meetup_members WHERE room_number = :room');
    $members->execute(['room' => $room]);
    $meeting = $pdo->prepare('DELETE FROM meetings WHERE room_number = :room');
    $meeting->execute(['room' => $room]);
}

/**
 * @return array{ok: bool, expired: bool, meeting?: array<string, mixed>}
 */
function load_meeting(string $room): array
{
    $pdo = db();
    $statement = $pdo->prepare(
        'SELECT id, room_number, fixed_place, organizer_method, organizer_label, organizer_data,
                confirmed_date, confirmed_start, confirmed_end, created_at,
                (created_at < (NOW() - INTERVAL 90 DAY)) AS expired
         FROM meetings
         WHERE room_number = :room
         LIMIT 1'
    );
    $statement->execute(['room' => $room]);
    $meeting = $statement->fetch();
    if (!is_array($meeting)) {
        purge_expired_meetings();
        return ['ok' => false, 'expired' => false];
    }
    if ((int) $meeting['expired'] === 1) {
        delete_meeting_room($pdo, $room);
        purge_expired_meetings();
        return ['ok' => false, 'expired' => true];
    }
    purge_expired_meetings();
    return ['ok' => true, 'expired' => false, 'meeting' => $meeting];
}

function ensure_fixed_place_column(): void
{
    static $ready = false;
    if ($ready) {
        return;
    }
    $pdo = db();
    $column = $pdo->query("SHOW COLUMNS FROM meetings LIKE 'fixed_place'")->fetch();
    if (!$column) {
        $pdo->exec(
            'ALTER TABLE meetings ADD COLUMN fixed_place VARCHAR(255) NULL AFTER organizer_label'
        );
    }
    $columns = [
        'organizer_data' => 'JSON NULL AFTER organizer_label',
        'confirmed_date' => 'DATE NULL AFTER fixed_place',
        'confirmed_start' => 'TIME NULL AFTER confirmed_date',
        'confirmed_end' => 'TIME NULL AFTER confirmed_start',
    ];
    foreach ($columns as $name => $definition) {
        $exists = $pdo->query("SHOW COLUMNS FROM meetings LIKE '" . $name . "'")->fetch();
        if (!$exists) {
            $pdo->exec('ALTER TABLE meetings ADD COLUMN ' . $name . ' ' . $definition);
        }
    }
    ensure_meeting_action_tables($pdo);
    $ready = true;
}

function ensure_meeting_action_tables(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS meeting_votes (
            meeting_id INT UNSIGNED NOT NULL,
            member_id INT UNSIGNED NOT NULL,
            place VARCHAR(255) NOT NULL,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (meeting_id, member_id),
            KEY idx_meeting_votes_place (meeting_id, place)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS member_locations (
            meeting_id INT UNSIGNED NOT NULL,
            member_id INT UNSIGNED NOT NULL,
            latitude DECIMAL(10,7) NOT NULL,
            longitude DECIMAL(10,7) NOT NULL,
            shared_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (meeting_id, member_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

function ensure_venue_places(): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $pdo = db();
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS venue_places (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            city VARCHAR(40) NOT NULL,
            city_mm VARCHAR(40) NOT NULL,
            township VARCHAR(48) NOT NULL,
            township_mm VARCHAR(80) NOT NULL,
            township_sort SMALLINT UNSIGNED NOT NULL,
            meetup_style VARCHAR(40) NOT NULL,
            name VARCHAR(160) NOT NULL,
            address VARCHAR(255) NOT NULL,
            detail VARCHAR(500) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_venue (meetup_style, township, name),
            KEY idx_city_style (city, meetup_style, township_sort)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS venue_seed (
            id TINYINT UNSIGNED NOT NULL,
            version INT UNSIGNED NOT NULL,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $lock = $pdo->query("SELECT GET_LOCK('twae_venue_seed', 15)")->fetchColumn();
    if ((int) $lock !== 1) {
        return;
    }

    try {
        $version = 1;
        $current = $pdo->query('SELECT version FROM venue_seed WHERE id = 1')->fetch();
        $count = (int) $pdo->query('SELECT COUNT(*) FROM venue_places')->fetchColumn();
        if ($current && (int) $current['version'] === $version && $count > 0) {
            $ready = true;
            return;
        }

        $rows = require __DIR__ . '/yangon_venues.php';
        if (!is_array($rows) || $rows === []) {
            throw new RuntimeException('Yangon venue seed is empty.');
        }

        $pdo->beginTransaction();
        $pdo->exec('DELETE FROM venue_places');
        $insert = $pdo->prepare(
            'INSERT INTO venue_places
                (city, city_mm, township, township_mm, township_sort, meetup_style, name, address, detail)
             VALUES
                (:city, :city_mm, :township, :township_mm, :township_sort, :meetup_style, :name, :address, :detail)'
        );
        foreach ($rows as $row) {
            $insert->execute($row);
        }
        $save = $pdo->prepare('REPLACE INTO venue_seed (id, version) VALUES (1, :version)');
        $save->execute(['version' => $version]);
        $pdo->commit();
        $ready = true;
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    } finally {
        $pdo->query("SELECT RELEASE_LOCK('twae_venue_seed')");
    }
}

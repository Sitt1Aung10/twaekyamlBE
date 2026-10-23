<?php
declare(strict_types=1);

require __DIR__ . '/http.php';
require __DIR__ . '/db.php';

allow_cors();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    send_json(405, ['ok' => false, 'error' => 'GET သာ လက်ခံပါတယ်။']);
}

$room = normalize_room((string) ($_GET['room'] ?? ''));
if ($room === '') {
    send_json(400, ['ok' => false, 'error' => 'အခန်းနံပါတ်သည် အင်္ဂလိပ်အက္ခရာ ၁ လုံးမှ ၄ လုံးအထိ ဖြစ်ရပါမည်။']);
}

try {
    ensure_fixed_place_column();
    ensure_venue_places();
    $loaded = load_meeting($room);
    if (!$loaded['ok']) {
        send_json(404, [
            'ok' => false,
            'error' => $loaded['expired']
                ? 'ဤအခန်းကုဒ် သက်တမ်းကုန်သွားပါပြီ။'
                : 'ဤအခန်းကုဒ် မရှိသေးပါ။',
        ]);
    }
    $meetingRow = $loaded['meeting'];
    $statement = db()->prepare(
        'SELECT id, room_number, member_name, phone, suggest_location, meetup_style,
                available_time, expected_arrival, created_at, updated_at
         FROM meetup_members
         WHERE room_number = :room
         ORDER BY created_at ASC, id ASC'
    );
    $statement->execute(['room' => $room]);
    $members = $statement->fetchAll();
    $placeRows = db()->query(
        'SELECT city, city_mm, township, township_mm, meetup_style, name, address, detail
         FROM venue_places
         ORDER BY meetup_style ASC, township_sort ASC, id ASC'
    )->fetchAll();
    $voteQuery = db()->prepare(
        'SELECT v.place, COUNT(*) AS count
         FROM meeting_votes v JOIN meetings t ON t.id = v.meeting_id
         WHERE t.room_number = :room GROUP BY v.place ORDER BY v.place'
    );
    $voteQuery->execute(['room' => $room]);
    $placeVotes = array_map(static fn(array $vote): array => [
        'place' => $vote['place'],
        'count' => (int) $vote['count'],
    ], $voteQuery->fetchAll());
    $locations = [];
    $today = (new DateTimeImmutable('now', new DateTimeZone('Asia/Yangon')))->format('Y-m-d');
    if (($meetingRow['confirmed_date'] ?? '') === $today) {
        $locationQuery = db()->prepare(
            'SELECT m.member_name AS name, l.latitude, l.longitude, l.shared_at AS sharedAt
             FROM member_locations l
             JOIN meetings t ON t.id = l.meeting_id
             JOIN meetup_members m ON m.id = l.member_id
             WHERE t.room_number = :room ORDER BY l.shared_at ASC'
        );
        $locationQuery->execute(['room' => $room]);
        $locations = $locationQuery->fetchAll();
    }
} catch (Throwable $error) {
    send_json(500, ['ok' => false, 'error' => 'ဒေတာဘေ့စ်ကို ဖတ်မရပါ။']);
}

send_json(200, [
    'ok' => true,
    'room' => $room,
    'fixedPlace' => clean_text(is_array($meetingRow) ? (string) ($meetingRow['fixed_place'] ?? '') : ''),
    'members' => $members,
    'placeVotes' => $placeVotes,
    'confirmedDate' => $meetingRow['confirmed_date'] ?? '',
    'confirmedSlot' => empty($meetingRow['confirmed_date']) ? null : [
        'date' => $meetingRow['confirmed_date'],
        'start' => substr((string) $meetingRow['confirmed_start'], 0, 5),
        'end' => substr((string) $meetingRow['confirmed_end'], 0, 5),
    ],
    'locations' => $locations,
    'places' => array_map(static function (array $row): array {
        return [
            'city' => $row['city'],
            'cityMm' => $row['city_mm'],
            'township' => $row['township'],
            'townshipMm' => $row['township_mm'],
            'style' => $row['meetup_style'],
            'name' => $row['name'],
            'address' => $row['address'],
            'detail' => $row['detail'],
        ];
    }, $placeRows),
]);

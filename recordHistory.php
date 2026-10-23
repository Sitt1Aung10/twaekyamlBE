<?php
declare(strict_types=1);

require __DIR__ . '/http.php';
require __DIR__ . '/db.php';

allow_cors();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    send_json(405, ['ok' => false, 'error' => 'POST သာ လက်ခံပါတယ်။']);
}

$data = request_data();
$action = field($data, 'action');

try {
    if ($action === 'create_meeting') {
        create_meeting($data);
    }
    save_member($data);
} catch (Throwable $error) {
    if ($error instanceof RuntimeException) {
        send_json(400, ['ok' => false, 'error' => $error->getMessage()]);
    }
    send_json(500, ['ok' => false, 'error' => 'ဒေတာဘေ့စ်သို့ သိမ်းမရပါ။']);
}

function create_meeting(array $data): void
{
    $organizer = $data['organizer'] ?? null;
    if (!is_array($organizer)) {
        throw new RuntimeException('စီစဉ်သူ အချက်အလက် မပြည့်စုံပါ။');
    }

    $method = (string) ($organizer['method'] ?? '');
    if ($method === 'gmail') {
        $gmail = strtolower(trim((string) ($organizer['gmail'] ?? '')));
        if (preg_match('/^[a-z0-9._%+\-]+@gmail\.com$/', $gmail) !== 1) {
            throw new RuntimeException('@gmail.com ဖြင့် ဆုံးသော လိပ်စာသာ လက်ခံပါသည်။');
        }
        $label = $gmail;
    } elseif ($method === 'phone') {
        $name = clean_text((string) ($organizer['name'] ?? ''));
        $phone = normalize_phone((string) ($organizer['phone'] ?? ''));
        if ($name === '' || mb_strlen($name) > 120) {
            throw new RuntimeException('အမည် ထည့်ပါ။');
        }
        if ($phone === '') {
            throw new RuntimeException('ဖုန်းနံပါတ် မမှန်ပါ။ ၀၉… သို့မဟုတ် +၉၅၉… ပုံစံကို သုံးပါ။');
        }
        $label = $name . ' · ' . $phone;
    } else {
        throw new RuntimeException('စီစဉ်သူ အချက်အလက် မပြည့်စုံပါ။');
    }

    $place = clean_text((string) ($data['fixed_place'] ?? $data['fixedPlace'] ?? ''));
    if (mb_strlen($place) > 255) {
        throw new RuntimeException('နေရာ အလွန်ရှည်နေပါတယ်။');
    }

    $pdo = db();
    ensure_fixed_place_column();
    purge_expired_meetings();
    $code = unique_room($pdo);
    $insert = $pdo->prepare(
        'INSERT INTO meetings (room_number, organizer_method, organizer_label, fixed_place)
         VALUES (:room, :method, :label, :place)'
    );
    $insert->execute([
        'room' => $code,
        'method' => $method,
        'label' => $label,
        'place' => $place === '' ? null : $place,
    ]);

    send_json(201, [
        'ok' => true,
        'id' => (int) $pdo->lastInsertId(),
        'room' => $code,
        'code' => $code,
        'fixedPlace' => $place,
        'joinPath' => '/join/' . $code,
        'createdAt' => gmdate('c'),
    ]);
}

function save_member(array $data): void
{
    $room = normalize_room(field($data, 'room_number', 'room', 'roomNumber'));
    $name = clean_text(field($data, 'member_name', 'name', 'memberName'));
    $phone = normalize_phone(field($data, 'phone'));
    $place = clean_text(field($data, 'suggest_location', 'place', 'suggestLocation'));
    $style = field($data, 'meetup_style', 'style', 'meetupStyle');
    if ($style === 'tea') {
        $style = 'tea_house';
    }
    $arrivalRaw = field($data, 'expected_arrival', 'arrival', 'expectedArrival');
    $arrival = $arrivalRaw === '' ? null : normalize_time($arrivalRaw);
    $availability = normalize_availability($data['available_time'] ?? $data['availability'] ?? $data['availableTime'] ?? null);

    if ($room === '') {
        throw new RuntimeException('အခန်းနံပါတ်သည် အင်္ဂလိပ်အက္ခရာ ၁ လုံးမှ ၄ လုံးအထိ ဖြစ်ရပါမည်။');
    }
    if ($name === '') {
        throw new RuntimeException('အမည် ထည့်ပါ။');
    }
    if (mb_strlen($name) > 120) {
        throw new RuntimeException('အမည် အလွန်ရှည်နေပါတယ်။');
    }
    if ($phone === '') {
        throw new RuntimeException('ဖုန်းနံပါတ် မမှန်ပါ။ ၀၉… သို့မဟုတ် +၉၅၉… ပုံစံကို သုံးပါ။');
    }
    $pdo = db();
    ensure_fixed_place_column();
    $loaded = load_meeting($room);
    if (!$loaded['ok']) {
        throw new RuntimeException(
            $loaded['expired']
                ? 'ဤအခန်းကုဒ် သက်တမ်းကုန်သွားပါပြီ။'
                : 'ဤအခန်းကုဒ် မရှိသေးပါ။ စီစဉ်သူ ဖွင့်ထားသော ကုဒ်ကို သုံးပါ။'
        );
    }
    $meetingRow = $loaded['meeting'];
    $fixedPlace = clean_text((string) ($meetingRow['fixed_place'] ?? ''));
    if ($fixedPlace !== '') {
        $place = $fixedPlace;
        $style = '';
    } else {
        if ($place === '') {
            throw new RuntimeException('တွေ့ဆုံလိုသည့် နေရာ ထည့်ပါ။');
        }
        if (mb_strlen($place) > 255) {
            throw new RuntimeException('နေရာ အလွန်ရှည်နေပါတယ်။');
        }
        if (!in_array($style, STYLES, true)) {
            throw new RuntimeException('တွေ့ဆုံမည့် ပုံစံ တစ်ခု ရွေးပါ။');
        }
    }
    if (is_string($availability)) {
        throw new RuntimeException($availability);
    }
    if ($arrivalRaw !== '' && $arrival === '') {
        throw new RuntimeException('မျှော်မှန်းရောက်ရှိချိန် ရွေးပါ။');
    }

    $json = json_encode($availability, JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        throw new RuntimeException('ရနိုင်သည့် အချိန် ပုံစံ မမှန်ပါ။');
    }

    $existing = $pdo->prepare(
        'SELECT id FROM meetup_members WHERE room_number = :room AND phone = :phone LIMIT 1'
    );
    $existing->execute(['room' => $room, 'phone' => $phone]);
    $current = $existing->fetch();

    if ($current) {
        $update = $pdo->prepare(
            'UPDATE meetup_members
             SET member_name = :name,
                 suggest_location = :place,
                 meetup_style = :style,
                 available_time = :available_time,
                 expected_arrival = :arrival
             WHERE id = :id'
        );
        $update->execute([
            'name' => $name,
            'place' => $place,
            'style' => $style,
            'available_time' => $json,
            'arrival' => $arrival,
            'id' => $current['id'],
        ]);
        send_json(200, [
            'ok' => true,
            'id' => (int) $current['id'],
            'room' => $room,
            'updated' => true,
        ]);
    }

    $insert = $pdo->prepare(
        'INSERT INTO meetup_members
            (room_number, member_name, phone, suggest_location, meetup_style, available_time, expected_arrival)
         VALUES
            (:room, :name, :phone, :place, :style, :available_time, :arrival)'
    );
    $insert->execute([
        'room' => $room,
        'name' => $name,
        'phone' => $phone,
        'place' => $place,
        'style' => $style,
        'available_time' => $json,
        'arrival' => $arrival,
    ]);

    send_json(201, [
        'ok' => true,
        'id' => (int) $pdo->lastInsertId(),
        'room' => $room,
        'updated' => false,
    ]);
}

function unique_room(PDO $pdo): string
{
    $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $check = $pdo->prepare(
        'SELECT
            (SELECT COUNT(*) FROM meetings WHERE room_number = :meeting_room) +
            (SELECT COUNT(*) FROM meetup_members WHERE room_number = :member_room) AS taken'
    );
    for ($attempt = 0; $attempt < 40; $attempt++) {
        $code = '';
        for ($index = 0; $index < 4; $index++) {
            $code .= $letters[random_int(0, 25)];
        }
        $check->execute(['meeting_room' => $code, 'member_room' => $code]);
        $row = $check->fetch();
        if ((int) ($row['taken'] ?? 1) === 0) {
            return $code;
        }
    }
    throw new RuntimeException('အခန်းကုဒ် အသစ် မဖန်တီးနိုင်ပါ။');
}

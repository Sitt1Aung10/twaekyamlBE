<?php
declare(strict_types=1);

require __DIR__ . '/http.php';
require __DIR__ . '/db.php';

allow_cors();
header('Cache-Control: no-store');

$path = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?? '');
$path = preg_replace('#^/api(?:\.php)?#', '', $path) ?? '';
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
$data = request_data();

try {
    ensure_fixed_place_column();
    if (preg_match('#^/meetings/?$#', $path) === 1 && $method === 'POST') {
        api_create_meeting($data);
    }
    if (preg_match('#^/meetings/([A-Za-z]{4,5})(?:/(responses|votes|confirmation|locations))?/?$#', $path, $match) === 1) {
        $room = normalize_room($match[1]);
        $resource = $match[2] ?? '';
        if ($room === '') {
            api_error(400, 'validation', 'အခန်းကုဒ် မမှန်ပါ။', 'room');
        }
        if ($resource === '' && $method === 'GET') {
            api_get_meeting($room);
        }
        if ($resource === 'responses' && $method === 'GET') {
            api_get_responses($room);
        }
        if ($resource === 'responses' && $method === 'POST') {
            api_save_response($room, $data);
        }
        if ($resource === 'votes' && $method === 'POST') {
            api_vote($room, $data);
        }
        if ($resource === 'confirmation' && $method === 'POST') {
            api_confirm($room, $data);
        }
        if ($resource === 'locations' && $method === 'POST') {
            api_share_location($room, $data);
        }
    }
    api_error(404, 'not_found', 'API လမ်းကြောင်း မရှိပါ။');
} catch (Throwable $error) {
    if ($error instanceof PDOException) {
        api_error(500, 'server', 'ဒေတာဘေ့စ်သို့ ချိတ်ဆက်မရပါ။');
    }
    if ($error instanceof RuntimeException) {
        api_error(400, 'validation', $error->getMessage());
    }
    api_error(500, 'server', 'ဒေတာဘေ့စ်သို့ သိမ်းမရပါ။');
}

function api_error(int $status, string $code, string $message, ?string $field = null): never
{
    $payload = ['error' => $code, 'message' => $message];
    if ($field !== null) {
        $payload['field'] = $field;
    }
    send_json($status, $payload);
}

function api_create_meeting(array $data): never
{
    $organizer = $data['organizer'] ?? null;
    if (!is_array($organizer)) {
        api_error(400, 'validation', 'စီစဉ်သူ အချက်အလက် မပြည့်စုံပါ။', 'organizer');
    }
    $method = (string) ($organizer['method'] ?? '');
    if ($method === 'gmail') {
        $gmail = strtolower(trim((string) ($organizer['gmail'] ?? '')));
        if (preg_match('/^[a-z0-9._%+\-]+@gmail\.com$/', $gmail) !== 1) {
            api_error(400, 'validation', '@gmail.com ဖြင့် ဆုံးသော လိပ်စာသာ လက်ခံပါသည်။', 'gmail');
        }
        $organizer = ['method' => 'gmail', 'gmail' => $gmail];
        $label = $gmail;
    } elseif ($method === 'phone') {
        $name = clean_text((string) ($organizer['name'] ?? ''));
        $phone = normalize_phone((string) ($organizer['phone'] ?? ''));
        if ($name === '' || mb_strlen($name) > 120) {
            api_error(400, 'validation', 'အမည် ထည့်ပါ။', 'name');
        }
        if ($phone === '') {
            api_error(400, 'validation', 'ဖုန်းနံပါတ် မမှန်ပါ။', 'phone');
        }
        $organizer = ['method' => 'phone', 'name' => $name, 'phone' => $phone];
        $label = $name . ' · ' . $phone;
    } else {
        api_error(400, 'validation', 'စီစဉ်သူ အချက်အလက် မပြည့်စုံပါ။', 'organizer');
    }
    $place = clean_text((string) ($data['fixed_place'] ?? $data['fixedPlace'] ?? ''));
    if (mb_strlen($place) > 255) {
        api_error(400, 'validation', 'နေရာ အလွန်ရှည်နေပါတယ်။', 'place');
    }

    $pdo = db();
    purge_expired_meetings();
    $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $check = $pdo->prepare(
        'SELECT (SELECT COUNT(*) FROM meetings WHERE room_number=:room_meeting) +
                (SELECT COUNT(*) FROM meetup_members WHERE room_number=:room_member)'
    );
    $room = '';
    for ($attempt = 0; $attempt < 40; $attempt++) {
        $candidate = '';
        for ($i = 0; $i < 5; $i++) {
            $candidate .= $letters[random_int(0, 25)];
        }
        $check->execute(['room_meeting' => $candidate, 'room_member' => $candidate]);
        if ((int) $check->fetchColumn() === 0) {
            $room = $candidate;
            break;
        }
    }
    if ($room === '') {
        api_error(500, 'server', 'အခန်းကုဒ် အသစ် မဖန်တီးနိုင်ပါ။');
    }
    $insert = $pdo->prepare(
        'INSERT INTO meetings (room_number, organizer_method, organizer_label, organizer_data, fixed_place)
         VALUES (:room, :method, :label, :organizer, :place)'
    );
    $insert->execute([
        'room' => $room,
        'method' => $method,
        'label' => $label,
        'organizer' => json_encode($organizer, JSON_UNESCAPED_UNICODE),
        'place' => $place === '' ? null : $place,
    ]);
    send_json(201, [
        'code' => $room,
        'createdAt' => gmdate('c'),
        'organizer' => $organizer,
        'joinPath' => '/join/' . $room,
    ]);
}

function api_meeting(string $room): array
{
    $loaded = load_meeting($room);
    if (!$loaded['ok']) {
        api_error(404, $loaded['expired'] ? 'expired' : 'not_found', $loaded['expired'] ? 'ဤအခန်းကုဒ် သက်တမ်းကုန်သွားပါပြီ။' : 'ဤအခန်းကုဒ် မရှိသေးပါ။');
    }
    return $loaded['meeting'];
}

function api_get_meeting(string $room): never
{
    $meeting = api_meeting($room);
    $organizer = json_decode((string) ($meeting['organizer_data'] ?? ''), true);
    send_json(200, [
        'code' => $room,
        'createdAt' => $meeting['created_at'],
        'organizer' => is_array($organizer) ? $organizer : null,
        'responseCount' => api_response_count($room),
        'confirmedDate' => $meeting['confirmed_date'] ?? '',
        'confirmedSlot' => api_confirmed_slot($meeting),
    ]);
}

function api_response_rows(string $room): array
{
    $statement = db()->prepare(
        'SELECT id, member_name, phone, suggest_location, meetup_style, available_time,
                expected_arrival, created_at, updated_at
         FROM meetup_members WHERE room_number = :room ORDER BY created_at ASC, id ASC'
    );
    $statement->execute(['room' => $room]);
    return $statement->fetchAll();
}

function api_response_count(string $room): int
{
    $statement = db()->prepare('SELECT COUNT(*) FROM meetup_members WHERE room_number = :room');
    $statement->execute(['room' => $room]);
    return (int) $statement->fetchColumn();
}

function api_get_responses(string $room): never
{
    $meeting = api_meeting($room);
    $rows = api_response_rows($room);
    $responses = array_map(static function (array $row): array {
        $availability = json_decode((string) $row['available_time'], true);
        return [
            'id' => (int) $row['id'],
            'name' => $row['member_name'],
            'phone' => $row['phone'],
            'place' => $row['suggest_location'],
            'style' => $row['meetup_style'] === 'tea' ? 'tea_house' : $row['meetup_style'],
            'availability' => is_array($availability) ? $availability : [],
            'arrival' => $row['expected_arrival'] ?? '',
            'createdAt' => $row['created_at'],
            'submittedAt' => $row['updated_at'] ?: $row['created_at'],
            'updatedAt' => $row['updated_at'],
        ];
    }, $rows);
    $votes = db()->prepare('SELECT place, COUNT(*) AS count FROM meeting_votes WHERE meeting_id = :id GROUP BY place ORDER BY place');
    $votes->execute(['id' => $meeting['id']]);
    $locations = [];
    $today = (new DateTimeImmutable('now', new DateTimeZone('Asia/Yangon')))->format('Y-m-d');
    if (($meeting['confirmed_date'] ?? '') === $today) {
        $query = db()->prepare(
            'SELECT m.member_name AS name, l.latitude, l.longitude, l.shared_at AS sharedAt
             FROM member_locations l JOIN meetup_members m ON m.id = l.member_id
             WHERE l.meeting_id = :meeting ORDER BY l.shared_at ASC'
        );
        $query->execute(['meeting' => $meeting['id']]);
        $locations = $query->fetchAll();
    }
    send_json(200, [
        'code' => $room,
        'responses' => $responses,
        'suggestion' => api_suggestion($responses),
        'placeVotes' => array_map(static fn(array $vote): array => ['place' => $vote['place'], 'count' => (int) $vote['count']], $votes->fetchAll()),
        'confirmedDate' => $meeting['confirmed_date'] ?? '',
        'confirmedSlot' => api_confirmed_slot($meeting),
        'locations' => $locations,
    ]);
}

function api_save_response(string $room, array $data): never
{
    $meeting = api_meeting($room);
    $fixed = clean_text((string) ($meeting['fixed_place'] ?? ''));
    $name = clean_text((string) ($data['name'] ?? ''));
    $phone = normalize_phone((string) ($data['phone'] ?? ''));
    $place = $fixed !== '' ? $fixed : clean_text((string) ($data['place'] ?? ''));
    $style = (string) ($data['style'] ?? '');
    $arrival = normalize_time((string) ($data['arrival'] ?? ''));
    $availability = normalize_availability($data['availability'] ?? null);
    if ($name === '' || mb_strlen($name) > 120) api_error(400, 'validation', 'အမည် မမှန်ပါ။', 'name');
    if ($phone === '') api_error(400, 'validation', 'ဖုန်းနံပါတ် မမှန်ပါ။', 'phone');
    if ($fixed === '' && $place === '') api_error(400, 'validation', 'တွေ့ဆုံလိုသည့် နေရာ ထည့်ပါ။', 'place');
    if ($fixed === '' && !in_array($style, STYLES, true)) api_error(400, 'validation', 'တွေ့ဆုံမည့် ပုံစံ တစ်ခု ရွေးပါ။', 'style');
    if (is_string($availability)) api_error(400, 'validation', $availability, 'availability');
    if ($arrival === '') api_error(400, 'validation', 'မျှော်မှန်းရောက်ရှိချိန် ရွေးပါ။', 'arrival');
    $json = json_encode($availability, JSON_UNESCAPED_UNICODE);
    if ($json === false) api_error(400, 'validation', 'ရနိုင်သည့် အချိန် ပုံစံ မမှန်ပါ။', 'availability');

    $pdo = db();
    $existing = $pdo->prepare('SELECT id FROM meetup_members WHERE room_number = :room AND phone = :phone LIMIT 1');
    $existing->execute(['room' => $room, 'phone' => $phone]);
    $member = $existing->fetch();
    if ($member) {
        $save = $pdo->prepare('UPDATE meetup_members SET member_name=:name, suggest_location=:place, meetup_style=:style, available_time=:availability, expected_arrival=:arrival WHERE id=:id');
        $save->execute(['name' => $name, 'place' => $place, 'style' => $style, 'availability' => $json, 'arrival' => $arrival, 'id' => $member['id']]);
        send_json(200, ['updated' => true, 'id' => (int) $member['id']]);
    }
    $count = api_response_count($room);
    if ($count >= 30) api_error(400, 'full', 'ဒီအခန်းမှာ လူပြည့်သွားပါပြီ။');
    $save = $pdo->prepare('INSERT INTO meetup_members (room_number,member_name,phone,suggest_location,meetup_style,available_time,expected_arrival) VALUES (:room,:name,:phone,:place,:style,:availability,:arrival)');
    $save->execute(['room' => $room, 'name' => $name, 'phone' => $phone, 'place' => $place, 'style' => $style, 'availability' => $json, 'arrival' => $arrival]);
    send_json(201, ['updated' => false, 'id' => (int) $pdo->lastInsertId()]);
}

function api_member(array $meeting, array $data): array
{
    $name = clean_text((string) ($data['name'] ?? ''));
    $phone = normalize_phone((string) ($data['phone'] ?? ''));
    if ($name === '' || $phone === '') api_error(400, 'validation', 'အမည်နှင့် ဖုန်းနံပါတ် ထည့်ပါ။');
    $statement = db()->prepare('SELECT id, member_name FROM meetup_members WHERE room_number=:room AND phone=:phone LIMIT 1');
    $statement->execute(['room' => $meeting['room_number'], 'phone' => $phone]);
    $member = $statement->fetch();
    if (!$member || mb_strtolower(clean_text((string) $member['member_name'])) !== mb_strtolower($name)) {
        api_error(403, 'member', 'ဒီအခန်းမှာ ဖြည့်ထားသော အမည်နှင့် ဖုန်းနံပါတ်ကို သုံးပါ။');
    }
    return $member;
}

function api_vote(string $room, array $data): never
{
    $meeting = api_meeting($room);
    $member = api_member($meeting, $data);
    $place = clean_text((string) ($data['place'] ?? ''));
    $check = db()->prepare('SELECT 1 FROM meetup_members WHERE room_number=:room AND suggest_location=:place LIMIT 1');
    $check->execute(['room' => $room, 'place' => $place]);
    if (!$check->fetchColumn()) api_error(400, 'place', 'မဲပေးမည့် နေရာကို စာရင်းထဲမှ ရွေးပါ။', 'place');
    $save = db()->prepare('REPLACE INTO meeting_votes (meeting_id, member_id, place) VALUES (:meeting, :member, :place)');
    $save->execute(['meeting' => $meeting['id'], 'member' => $member['id'], 'place' => $place]);
    $totals = db()->prepare('SELECT place, COUNT(*) AS count FROM meeting_votes WHERE meeting_id=:meeting GROUP BY place ORDER BY place');
    $totals->execute(['meeting' => $meeting['id']]);
    send_json(200, ['placeVotes' => array_map(static fn(array $vote): array => ['place' => $vote['place'], 'count' => (int) $vote['count']], $totals->fetchAll())]);
}

function api_confirm(string $room, array $data): never
{
    $meeting = api_meeting($room);
    $organizer = $data['organizer'] ?? null;
    $saved = json_decode((string) ($meeting['organizer_data'] ?? ''), true);
    $authorized = is_array($organizer) && is_array($saved) && $organizer === $saved;
    if (!$authorized && is_array($organizer) && !is_array($saved)) {
        if (($meeting['organizer_method'] ?? '') === 'gmail') {
            $authorized = strtolower(trim((string) ($organizer['gmail'] ?? ''))) === strtolower((string) $meeting['organizer_label']);
        } elseif (($meeting['organizer_method'] ?? '') === 'phone') {
            $authorized = normalize_phone((string) ($organizer['phone'] ?? '')) !== ''
                && normalize_phone((string) ($organizer['phone'] ?? '')) === normalize_phone((string) substr((string) $meeting['organizer_label'], strrpos((string) $meeting['organizer_label'], '·') + 1))
                && mb_strtolower(clean_text((string) ($organizer['name'] ?? ''))) === mb_strtolower(clean_text((string) trim(explode('·', (string) $meeting['organizer_label'])[0])));
        }
    }
    if (!$authorized) api_error(403, 'organizer', 'စီစဉ်သူအဖြစ် ဝင်ထားသော အကောင့်ဖြင့် အတည်ပြုပါ။');
    $suggestion = api_suggestion(array_map(static function (array $row): array {
        $availability = json_decode((string) $row['available_time'], true);
        return ['id' => (int) $row['id'], 'arrival' => $row['expected_arrival'] ?? '', 'availability' => is_array($availability) ? $availability : []];
    }, api_response_rows($room)));
    $time = $suggestion['time'] ?? [];
    $date = (string) ($data['date'] ?? '');
    $start = normalize_time((string) ($data['start'] ?? ''));
    $end = normalize_time((string) ($data['end'] ?? ''));
    if (($time['date'] ?? '') !== $date || ($time['start'] ?? '') !== $start || ($time['end'] ?? '') !== $end) api_error(400, 'date', 'အကြံပြုထားသော ရက်နှင့်အချိန်ကို ရွေးပါ။', 'date');
    $save = db()->prepare('UPDATE meetings SET confirmed_date=:date, confirmed_start=:start, confirmed_end=:end WHERE id=:id');
    $save->execute(['date' => $date, 'start' => $start, 'end' => $end, 'id' => $meeting['id']]);
    send_json(200, ['confirmedDate' => $date, 'confirmedSlot' => ['date' => $date, 'start' => $start, 'end' => $end]]);
}

function api_share_location(string $room, array $data): never
{
    $meeting = api_meeting($room);
    $today = (new DateTimeImmutable('now', new DateTimeZone('Asia/Yangon')))->format('Y-m-d');
    if (($meeting['confirmed_date'] ?? '') !== $today) api_error(403, 'date', 'အတည်ပြုထားသော တွေ့ဆုံမည့်နေ့တွင်သာ တည်နေရာမျှဝေနိုင်ပါသည်။');
    $member = api_member($meeting, $data);
    $latitude = filter_var($data['latitude'] ?? null, FILTER_VALIDATE_FLOAT);
    $longitude = filter_var($data['longitude'] ?? null, FILTER_VALIDATE_FLOAT);
    if ($latitude === false || $longitude === false || abs($latitude) > 90 || abs($longitude) > 180) api_error(400, 'location', 'တည်နေရာ မမှန်ပါ။', 'location');
    $save = db()->prepare('REPLACE INTO member_locations (meeting_id,member_id,latitude,longitude) VALUES (:meeting,:member,:latitude,:longitude)');
    $save->execute(['meeting' => $meeting['id'], 'member' => $member['id'], 'latitude' => $latitude, 'longitude' => $longitude]);
    $query = db()->prepare('SELECT m.member_name AS name,l.latitude,l.longitude,l.shared_at AS sharedAt FROM member_locations l JOIN meetup_members m ON m.id=l.member_id WHERE l.meeting_id=:meeting ORDER BY l.shared_at ASC');
    $query->execute(['meeting' => $meeting['id']]);
    send_json(200, ['locations' => $query->fetchAll()]);
}

function api_confirmed_slot(array $meeting): ?array
{
    if (empty($meeting['confirmed_date'])) return null;
    return ['date' => $meeting['confirmed_date'], 'start' => substr((string) $meeting['confirmed_start'], 0, 5), 'end' => substr((string) $meeting['confirmed_end'], 0, 5)];
}

function api_suggestion(array $responses): ?array
{
    if ($responses === []) return null;
    $styleLabels = ['karaoke'=>'Karaoke ကာရာအိုကေ','zoo'=>'Zoo တိရစ္ဆာန်ရုံ','staycation'=>'Staycation စတေးကေးရှင်း','camp'=>'Camp စခန်းချ','bar'=>'Bar ဘား','coffee'=>'Coffee ကော်ဖီဆိုင်','restaurant'=>'Restaurant စားသောက်ဆိုင်','fine_dining'=>'Fine Dining ဖိုင်းဒိုင်နင်း','tea_house'=>'Tea House လက်ဖက်ရည်ဆိုင်','movie'=>'Movie ရုပ်ရှင်','show'=>'Show ရှိုး'];
    $counts = [];
    $places = [];
    foreach ($responses as $response) {
        $style = (string) ($response['style'] ?? '');
        if ($style !== '') $counts[$style] = ($counts[$style] ?? 0) + 1;
        $place = (string) ($response['place'] ?? '');
        if ($place !== '') $places[$place] = ($places[$place] ?? 0) + 1;
    }
    $styleOrder = array_keys($styleLabels);
    $votes = [];
    foreach ($styleOrder as $style) if (isset($counts[$style])) $votes[] = ['style'=>$style,'label'=>$styleLabels[$style],'count'=>$counts[$style]];
    usort($votes, static fn(array $a,array $b): int => ($b['count'] <=> $a['count']) ?: (array_search($a['style'], $styleOrder, true) <=> array_search($b['style'], $styleOrder, true)));
    $mentioned = [];
    foreach ($places as $place => $count) $mentioned[] = ['place'=>$place,'count'=>$count];
    usort($mentioned, static fn(array $a,array $b): int => ($b['count'] <=> $a['count']) ?: strcmp($a['place'],$b['place']));

    $total = count($responses);
    $candidate = api_best_time($responses);
    if ($candidate === null) {
        $time = ['everyone'=>false,'count'=>0,'total'=>$total,'date'=>'','unavailable'=>$total,'start'=>'','end'=>'','text'=>'ရွေးထားသော ပြက္ခဒိန်ရက်များထဲမှာ ဆုံနိုင်သော အချိန် မရှိသေးပါ။'];
    } else {
        $time = ['everyone'=>$candidate['count'] === $total,'count'=>$candidate['count'],'total'=>$total,'date'=>$candidate['date'],'unavailable'=>$total-$candidate['count'],'start'=>$candidate['start'],'end'=>$candidate['end'],'text'=>$candidate['count'] === $total ? 'လူအားလုံး ရွေးထားသော ရက်နှင့်အချိန်တွင် တွေ့နိုင်ပါတယ်။' : 'အများဆုံး အဖွဲ့ဝင်များ တွေ့နိုင်သော ရက်နှင့်အချိန် ဖြစ်ပါတယ်။'];
    }
    return ['total'=>$total,'votes'=>$votes,'tied'=>count($votes)>1 && $votes[0]['count']===$votes[1]['count'],'headline'=>'အဖွဲ့ဝင်များ ဖြည့်ထားသော အချက်အလက်အပေါ် အခြေခံထားပါသည်။','mentionedPlaces'=>$mentioned,'time'=>$time,'arrival'=>['text'=>'အဖွဲ့ဝင်များ၏ မျှော်မှန်းရောက်ရှိချိန်ကို ကြည့်ပါ။']];
}

function api_best_time(array $responses): ?array
{
    $dates = [];
    foreach ($responses as $response) foreach (($response['availability'] ?? []) as $slot) if (!empty($slot['date'])) $dates[$slot['date']] = true;
    $candidates = [];
    foreach (array_keys($dates) as $date) {
        $intervals = [];
        foreach ($responses as $response) {
            $slot = null;
            foreach (($response['availability'] ?? []) as $entry) if (($entry['date'] ?? '') === $date) { $slot = $entry; break; }
            if (!$slot) continue;
            $start = api_minutes((string) $slot['start']);
            $end = api_minutes((string) $slot['end']);
            $arrival = api_minutes((string) ($response['arrival'] ?? ''));
            $start = max($start, $arrival);
            if ($start < $end) $intervals[] = [$start, $end];
        }
        if (count($intervals) === count($responses)) {
            $start = max(array_column($intervals, 0));
            $end = min(array_column($intervals, 1));
            if ($start < $end) $candidates[] = ['date'=>$date,'start'=>$start,'end'=>$end,'count'=>count($responses)];
        }
        $points = [];
        foreach ($intervals as [$start,$end]) { $points[]=$start; $points[]=$end; }
        sort($points);
        for ($i=0; $i<count($points)-1; $i++) {
            $start=$points[$i]; $end=$points[$i+1]; if ($start >= $end) continue;
            $count=0; foreach ($intervals as [$from,$to]) if ($from <= $start && $to >= $end) $count++;
            if ($count>0) $candidates[]=['date'=>$date,'start'=>$start,'end'=>$end,'count'=>$count];
        }
    }
    if ($candidates === []) return null;
    usort($candidates, static fn(array $a,array $b): int => ($b['count'] <=> $a['count']) ?: (($b['end']-$b['start']) <=> ($a['end']-$a['start'])) ?: strcmp($a['date'],$b['date']) ?: ($a['start'] <=> $b['start']));
    $best=$candidates[0];
    return ['date'=>$best['date'],'start'=>sprintf('%02d:%02d',intdiv($best['start'],60),$best['start']%60),'end'=>sprintf('%02d:%02d',intdiv($best['end'],60),$best['end']%60),'count'=>$best['count']];
}

function api_minutes(string $time): int
{
    $parts = array_map('intval', explode(':', $time));
    return ($parts[0] ?? 0) * 60 + ($parts[1] ?? 0);
}

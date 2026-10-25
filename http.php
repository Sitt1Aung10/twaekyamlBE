<?php
declare(strict_types=1);

require_once __DIR__ . '/cors.php';

const STYLES = [
    'karaoke',
    'zoo',
    'staycation',
    'camp',
    'bar',
    'coffee',
    'restaurant',
    'fine_dining',
    'tea_house',
    'movie',
    'show',
];

const DAYS = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

function send_json(int $status, array $payload): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function request_data(): array
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
    if (str_contains($contentType, 'application/json')) {
        $raw = file_get_contents('php://input');
        if ($raw === false || trim($raw) === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    return $_POST;
}

function field(array $data, string ...$keys): string
{
    foreach ($keys as $key) {
        if (isset($data[$key]) && !is_array($data[$key])) {
            return trim((string) $data[$key]);
        }
    }
    return '';
}

function normalize_room(string $value): string
{
    $room = strtoupper(trim($value));
    return preg_match('/^[A-Z]{4,5}$/', $room) === 1 ? $room : '';
}

function normalize_phone(string $value): string
{
    $compact = preg_replace('/[\s\-().]/', '', trim($value)) ?? '';
    if (preg_match('/^\+959(\d{7,9})$/', $compact, $match) === 1) {
        return '09' . $match[1];
    }
    if (preg_match('/^09\d{7,9}$/', $compact) === 1) {
        return $compact;
    }
    return '';
}

function normalize_time(string $value): string
{
    if (preg_match('/^(\d{1,2}):(\d{2})$/', trim($value), $match) !== 1) {
        return '';
    }
    $hour = (int) $match[1];
    $minute = (int) $match[2];
    if ($hour > 23 || $minute > 59) {
        return '';
    }
    return sprintf('%02d:%02d', $hour, $minute);
}

function clean_text(string $value): string
{
    $text = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $value) ?? '';
    $text = preg_replace('/\s+/u', ' ', trim($text)) ?? '';
    return $text;
}

function normalize_availability(mixed $value): array|string
{
    if (is_string($value)) {
        $decoded = json_decode($value, true);
        if (!is_array($decoded)) {
            return 'ရနိုင်သည့် အချိန် ပုံစံ မမှန်ပါ။';
        }
        $value = $decoded;
    }
    if (!is_array($value) || $value === []) {
        return 'ရနိုင်သည့်နေ့ အနည်းဆုံး တစ်နေ့ ရွေးပါ။';
    }

    $seen = [];
    $slots = [];
    foreach ($value as $item) {
        if (!is_array($item)) {
            return 'ရနိုင်သည့် အချိန် ပုံစံ မမှန်ပါ။';
        }
        $date = (string) ($item['date'] ?? '');
        $day = (string) ($item['day'] ?? '');
        if ($date !== '') {
            $parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
            if (!$parsedDate || $parsedDate->format('Y-m-d') !== $date) {
                return 'နေ့ ရွေးချယ်မှု မမှန်ပါ။';
            }
            $key = $date;
        } elseif (in_array($day, DAYS, true)) {
            $key = $day;
        } else {
            return 'နေ့ ရွေးချယ်မှု မမှန်ပါ။';
        }
        if (isset($seen[$key])) {
            return 'နေ့တစ်နေ့ကို တစ်ကြိမ်သာ ရွေးပါ။';
        }
        $seen[$key] = true;
        $start = normalize_time((string) ($item['start'] ?? ''));
        $end = normalize_time((string) ($item['end'] ?? ''));
        if ($start === '' || $end === '') {
            return 'အချိန် ပုံစံ မမှန်ပါ။';
        }
        if ($start >= $end) {
            return 'ပြီးဆုံးချိန်သည် စတင်ချိန်ထက် နောက်ကျရပါမည်။';
        }
        $slots[] = $date !== ''
            ? ['date' => $date, 'start' => $start, 'end' => $end]
            : ['day' => $day, 'start' => $start, 'end' => $end];
    }

    usort($slots, static fn(array $a, array $b): int => strcmp((string) ($a['date'] ?? $a['day']), (string) ($b['date'] ?? $b['day'])));
    return $slots;
}

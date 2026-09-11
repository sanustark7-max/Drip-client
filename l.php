<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$DB = __DIR__ . '/keys.json';
function load_db($f) {
    if (!file_exists($f)) return ['keys' => []];
    $j = json_decode(@file_get_contents($f), true);
    return is_array($j) ? $j : ['keys' => []];
}
function save_db($f, $d) {
    @file_put_contents($f, json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

$game = trim($_POST['game'] ?? $_GET['game'] ?? '');
$key  = trim($_POST['user_key'] ?? $_GET['user_key'] ?? $_POST['key'] ?? '');
$serial = trim($_POST['serial'] ?? $_GET['serial'] ?? '');

if ($key === '' || $game === '') {
    echo json_encode(['status' => false, 'reason' => 'Bad Parameter']);
    exit;
}

$db = load_db($DB);
$keys = $db['keys'] ?? [];

if (!isset($keys[$key])) {
    echo json_encode(['status' => false, 'reason' => 'USER OR GAME NOT REGISTERED']);
    exit;
}

$e = $keys[$key];
if (empty($e['active'])) {
    echo json_encode(['status' => false, 'reason' => 'Key rejected for this device or product.']);
    exit;
}
if (!empty($e['game']) && strcasecmp($e['game'], $game) !== 0) {
    echo json_encode(['status' => false, 'reason' => 'USER OR GAME NOT REGISTERED']);
    exit;
}
if (!empty($e['hwid']) && $serial !== '' && strcasecmp($e['hwid'], $serial) !== 0) {
    echo json_encode(['status' => false, 'reason' => 'Key rejected for this device or product.']);
    exit;
}

$days = intval($e['days'] ?? 0);
$created = strtotime($e['created'] ?? 'now');
if ($days > 0 && time() > $created + ($days * 86400)) {
    echo json_encode(['status' => false, 'reason' => 'Key expired.']);
    exit;
}

$exp_ts = $days > 0 ? ($created + $days * 86400) : (time() + 3650 * 86400);
$keys[$key]['used'] = intval($e['used'] ?? 0) + 1;
$keys[$key]['last_serial'] = $serial;
$keys[$key]['last_login'] = date('c');
$db['keys'] = $keys;
save_db($DB, $db);

// Match official FIXED APK response shape
echo json_encode([
    'status' => true,
    'data' => [
        'token' => md5($key . $serial . time()),
        'rng'   => time() - 60000,
        'ts'    => (int)(microtime(true) * 1000),
        'EXP'   => date('Y-m-d H:i:s', $exp_ts),
        'loop'  => $e['loop'] ?? 'MbeId00d',
        'verml' => $e['verml'] ?? '237958928',
    ],
    'ext' => [
        'os' => (string)crc32($serial ?: 'os'),
        'bd' => (string)crc32($key . 'bd'),
        'bm' => (string)crc32($key . 'bm'),
    ],
]);

<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, X-Admin-Token');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$DB = __DIR__ . '/keys.json';
$ADMIN_PASS = 'dripadmin123'; // change this

function load_db($f) {
    if (!file_exists($f)) return ['keys' => []];
    $j = json_decode(@file_get_contents($f), true);
    return is_array($j) ? $j : ['keys' => []];
}
function save_db($f, $d) {
    file_put_contents($f, json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}
function rand_block() {
    $c = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $s = '';
    for ($i = 0; $i < 4; $i++) $s .= $c[random_int(0, strlen($c)-1)];
    return $s;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$token = $_SERVER['HTTP_X_ADMIN_TOKEN'] ?? $_POST['token'] ?? '';

if ($action !== 'login' && $token !== $ADMIN_PASS) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'unauthorized']);
    exit;
}

$db = load_db($DB);

if ($action === 'login') {
    $pass = $_POST['password'] ?? '';
    echo json_encode(['ok' => $pass === $ADMIN_PASS, 'token' => $pass === $ADMIN_PASS ? $ADMIN_PASS : null]);
    exit;
}

if ($action === 'list') {
    echo json_encode(['ok' => true, 'keys' => $db['keys'] ?? []]);
    exit;
}

if ($action === 'create') {
    $in = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $prefix = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $in['prefix'] ?? 'DRIP')) ?: 'DRIP';
    $days = intval($in['days'] ?? 7);
    $game = $in['game'] ?? 'freefire';
    $qty = max(1, min(50, intval($in['qty'] ?? 1)));
    $custom = trim($in['custom'] ?? '');
    $note = $in['note'] ?? '';
    $hwid = $in['hwid'] ?? '';
    $created = [];
    if (!isset($db['keys'])) $db['keys'] = [];
    for ($i = 0; $i < $qty; $i++) {
        if ($custom !== '' && $i === 0) $k = $custom;
        else {
            $tag = $days === 0 ? 'LT' : str_pad((string)$days, 2, '0', STR_PAD_LEFT);
            $k = $prefix . '-' . rand_block() . '-' . rand_block() . '-' . rand_block() . '-' . $tag;
        }
        $db['keys'][$k] = [
            'active' => true,
            'days' => $days,
            'game' => $game,
            'hwid' => $hwid ?: null,
            'note' => $note ?: null,
            'created' => gmdate('c'),
            'used' => 0,
            'loop' => 'MbeId00d',
            'verml' => '237958928',
        ];
        $created[] = $k;
    }
    save_db($DB, $db);
    echo json_encode(['ok' => true, 'created' => $created]);
    exit;
}

if ($action === 'toggle') {
    $in = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $k = $in['key'] ?? '';
    if (!isset($db['keys'][$k])) {
        echo json_encode(['ok' => false, 'error' => 'not found']);
        exit;
    }
    $db['keys'][$k]['active'] = empty($in['active']) ? false : true;
    save_db($DB, $db);
    echo json_encode(['ok' => true]);
    exit;
}

if ($action === 'delete') {
    $in = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $k = $in['key'] ?? '';
    unset($db['keys'][$k]);
    save_db($DB, $db);
    echo json_encode(['ok' => true]);
    exit;
}

echo json_encode(['ok' => false, 'error' => 'unknown action']);

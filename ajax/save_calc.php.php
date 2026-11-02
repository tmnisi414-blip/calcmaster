<?php
// ajax/save_calc.php – called by JS after each =
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    // Guest: update cookie-based recent list
    $expr   = substr(trim($_POST['expression'] ?? ''), 0, 200);
    $result = substr(trim($_POST['result']     ?? ''), 0, 100);

    if ($expr !== '' && $result !== '') {
        $recent   = json_decode($_COOKIE['recent_calcs'] ?? '[]', true) ?: [];
        $recent[] = ['expr' => $expr, 'result' => $result];
        $recent   = array_slice($recent, -10); // keep last 10
        setcookie('recent_calcs', json_encode($recent), time() + 60*60*24*30, '/');
    }
    echo json_encode(['status' => 'guest']);
    exit;
}

// Verify CSRF
$token = $_POST['csrf'] ?? '';
if (!hash_equals(csrfToken(), $token)) {
    http_response_code(403);
    echo json_encode(['error' => 'csrf']);
    exit;
}

$expr   = substr(trim($_POST['expression'] ?? ''), 0, 500);
$result = substr(trim($_POST['result']     ?? ''), 0, 255);

if ($expr === '' || $result === '') {
    echo json_encode(['error' => 'empty']);
    exit;
}

$opType = detectOpType($expr);
$stmt   = db()->prepare(
    'INSERT INTO calculations (user_id, expression, result, op_type) VALUES (?, ?, ?, ?)'
);
$stmt->bind_param('isss', $_SESSION['user_id'], $expr, $result, $opType);
$stmt->execute();

echo json_encode(['status' => 'saved', 'id' => db()->insert_id]);

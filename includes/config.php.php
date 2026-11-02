<?php
// ─────────────────────────────────────────────────────────────
//  config.php  –  DB credentials & shared helpers
// ─────────────────────────────────────────────────────────────

define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // ← change to your AMPPS MySQL user
define('DB_PASS', 'mysql');       // ← change to your AMPPS MySQL password
define('DB_NAME', 'calcmaster');
define('COOKIE_NAME', 'calcmaster_session');
define('COOKIE_EXPIRE', time() + 60 * 60 * 24 * 7); // 7 days

// ── Connect ──────────────────────────────────────────────────
function db(): mysqli {
    static $conn = null;
    if ($conn === null) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}

// ── Session bootstrap ────────────────────────────────────────
session_name('calcmaster');
session_start();

// ── Auth helpers ─────────────────────────────────────────────
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function currentUser(): ?array {
    if (!isLoggedIn()) return null;
    $stmt = db()->prepare('SELECT id, username, email, theme FROM users WHERE id = ?');
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// ── CSRF ─────────────────────────────────────────────────────
function csrfToken(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function verifyCsrf(): void {
    $token = $_POST['csrf'] ?? '';
    if (!hash_equals(csrfToken(), $token)) {
        http_response_code(403);
        die('CSRF validation failed.');
    }
}

// ── Flash messages ────────────────────────────────────────────
function flash(string $msg, string $type = 'success'): void {
    $_SESSION['flash'] = ['msg' => $msg, 'type' => $type];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

// ── Determine operation type ──────────────────────────────────
function detectOpType(string $expr): string {
    if (preg_match('/sqrt|cbrt/', $expr))           return 'root';
    if (preg_match('/\*\*|\^|pow/i', $expr))        return 'power';
    if (str_contains($expr, '/'))                   return 'division';
    if (str_contains($expr, '*'))                   return 'multiplication';
    if (str_contains($expr, '-'))                   return 'subtraction';
    if (str_contains($expr, '+'))                   return 'addition';
    return 'arithmetic';
}

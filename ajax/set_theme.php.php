<?php
// ajax/set_theme.php
require_once __DIR__ . '/../includes/config.php';

$theme = $_GET['theme'] === 'light' ? 'light' : 'dark';

if (isLoggedIn()) {
    $stmt = db()->prepare('UPDATE users SET theme = ? WHERE id = ?');
    $stmt->bind_param('si', $theme, $_SESSION['user_id']);
    $stmt->execute();
}

setcookie('theme', $theme, time() + 60*60*24*365, '/');
echo 'ok';

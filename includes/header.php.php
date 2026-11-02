<?php
// includes/header.php
$user = currentUser();
$flash = getFlash();
$theme = $user['theme'] ?? ($_COOKIE['theme'] ?? 'dark');
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?= htmlspecialchars($theme) ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CalcMaster <?= isset($pageTitle) ? '– '.htmlspecialchars($pageTitle) : '' ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Space+Mono:wght@400;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/calcmaster/css/style.css">
</head>
<body>

<nav class="navbar">
  <a class="brand" href="/calcmaster/index.php">
    <span class="brand-icon">⌗</span> CalcMaster
  </a>
  <div class="nav-links">
    <?php if (isLoggedIn()): ?>
      <a href="/calcmaster/index.php">Calculator</a>
      <a href="/calcmaster/history.php">History</a>
      <a href="/calcmaster/favourites.php">★ Favourites</a>
      <span class="nav-user">👤 <?= htmlspecialchars($user['username']) ?></span>
      <a href="/calcmaster/logout.php" class="btn-nav-logout">Logout</a>
    <?php else: ?>
      <a href="/calcmaster/login.php">Login</a>
      <a href="/calcmaster/register.php" class="btn-nav-register">Register</a>
    <?php endif; ?>
    <button id="themeToggle" title="Toggle theme">☀</button>
  </div>
</nav>

<main class="container">
<?php if ($flash): ?>
  <div class="alert alert-<?= $flash['type'] ?>">
    <?= htmlspecialchars($flash['msg']) ?>
  </div>
<?php endif; ?>

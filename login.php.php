<?php
require_once __DIR__ . '/includes/config.php';
$pageTitle = 'Login';

if (isLoggedIn()) { header('Location: index.php'); exit; }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $login    = trim($_POST['login']    ?? '');
    $password =       $_POST['password'] ?? '';

    // ── Find user by username or email ────────────────────────
    $stmt = db()->prepare('SELECT id, username, password FROM users WHERE username = ? OR email = ?');
    $stmt->bind_param('ss', $login, $login);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        // ── Regenerate session (prevents fixation) ────────────
        session_regenerate_id(true);
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $user['username'];

        // ── Persistent login cookie ───────────────────────────
        if (!empty($_POST['remember'])) {
            $token = bin2hex(random_bytes(32));
            setcookie(COOKIE_NAME, $token, COOKIE_EXPIRE, '/', '', false, true);
            // Optionally store token in DB for "remember me" verification
        }

        flash('Welcome back, ' . $user['username'] . '!', 'success');
        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid username/email or password.';
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="auth-wrap">
  <div class="auth-title">⌗ Sign In</div>

  <?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="card">
    <form method="POST">
      <input type="hidden" name="csrf" value="<?= csrfToken() ?>">

      <div class="form-group">
        <label>Username or Email</label>
        <input type="text" name="login" value="<?= htmlspecialchars($_POST['login'] ?? '') ?>" required autocomplete="username">
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" required autocomplete="current-password">
      </div>
      <div class="form-group" style="display:flex;align-items:center;gap:.6rem;">
        <input type="checkbox" name="remember" id="remember" style="width:auto;">
        <label for="remember" style="text-transform:none;font-size:.9rem;color:var(--text);">Remember me for 7 days</label>
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
        Sign In
      </button>
    </form>
  </div>

  <p style="text-align:center;margin-top:1rem;color:var(--text2);">
    No account? <a href="register.php" style="color:var(--accent2)">Register here</a>
  </p>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

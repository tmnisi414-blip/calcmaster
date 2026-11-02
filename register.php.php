<?php
require_once __DIR__ . '/includes/config.php';
$pageTitle = 'Register';

if (isLoggedIn()) { header('Location: index.php'); exit; }

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password =       $_POST['password'] ?? '';
    $confirm  =       $_POST['confirm']  ?? '';

    // ── Validation ────────────────────────────────────────────
    if (strlen($username) < 3 || strlen($username) > 50)
        $errors[] = 'Username must be 3–50 characters.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = 'Enter a valid email address.';
    if (strlen($password) < 8)
        $errors[] = 'Password must be at least 8 characters.';
    if ($password !== $confirm)
        $errors[] = 'Passwords do not match.';

    // ── Check uniqueness ──────────────────────────────────────
    if (!$errors) {
        $stmt = db()->prepare('SELECT id FROM users WHERE username = ? OR email = ?');
        $stmt->bind_param('ss', $username, $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0)
            $errors[] = 'Username or email already in use.';
    }

    // ── Create user ───────────────────────────────────────────
    if (!$errors) {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = db()->prepare('INSERT INTO users (username, email, password) VALUES (?, ?, ?)');
        $stmt->bind_param('sss', $username, $email, $hash);
        $stmt->execute();
        flash('Account created! Please log in.', 'success');
        header('Location: login.php');
        exit;
    }
}

include __DIR__ . '/includes/header.php';
?>

<div class="auth-wrap">
  <div class="auth-title">⌗ Create Account</div>

  <?php foreach ($errors as $e): ?>
    <div class="alert alert-error"><?= htmlspecialchars($e) ?></div>
  <?php endforeach; ?>

  <div class="card">
    <form method="POST">
      <input type="hidden" name="csrf" value="<?= csrfToken() ?>">

      <div class="form-group">
        <label>Username</label>
        <input type="text" name="username" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autocomplete="username">
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autocomplete="email">
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" required autocomplete="new-password">
      </div>
      <div class="form-group">
        <label>Confirm Password</label>
        <input type="password" name="confirm" required autocomplete="new-password">
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">
        Create Account
      </button>
    </form>
  </div>

  <p style="text-align:center;margin-top:1rem;color:var(--text2);">
    Already have an account? <a href="login.php" style="color:var(--accent2)">Log in</a>
  </p>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

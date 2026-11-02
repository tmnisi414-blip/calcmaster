<?php
require_once __DIR__ . '/includes/config.php';
requireLogin();
$pageTitle = 'Favourites';

// ── Toggle fav / delete via POST ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);

    $check = db()->prepare('SELECT id FROM calculations WHERE id = ? AND user_id = ?');
    $check->bind_param('ii', $id, $_SESSION['user_id']);
    $check->execute();
    if ($check->get_result()->num_rows) {
        if ($action === 'unfav') {
            $stmt = db()->prepare('UPDATE calculations SET is_favourite = 0 WHERE id = ? AND user_id = ?');
            $stmt->bind_param('ii', $id, $_SESSION['user_id']);
            $stmt->execute();
            flash('Removed from favourites.', 'info');
        }
        if ($action === 'use') {
            // Redirect back to calculator with the expression pre-filled via URL
            $expr = urlencode($_POST['expression'] ?? '');
            header("Location: index.php?load=$expr");
            exit;
        }
    }
    header('Location: favourites.php');
    exit;
}

// ── Fetch favourites ──────────────────────────────────────────
$stmt = db()->prepare(
    'SELECT * FROM calculations WHERE user_id = ? AND is_favourite = 1 ORDER BY updated_at DESC'
);
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$favs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/includes/header.php';
?>

<div class="page-title">★ Favourites</div>

<?php if (!$favs): ?>
  <div class="card" style="text-align:center;padding:3rem;">
    <p style="color:var(--text2);font-size:1rem;">No favourites yet.</p>
    <p style="color:var(--text2);font-size:.9rem;margin-top:.5rem;">
      Mark a calculation ★ in your <a href="history.php" style="color:var(--accent2)">history</a>.
    </p>
  </div>
<?php else: ?>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1rem;">
    <?php foreach ($favs as $r): ?>
      <div class="card" style="position:relative;">
        <div style="position:absolute;top:1rem;right:1rem;color:var(--yellow);font-size:1.2rem;">★</div>
        <div class="mono" style="font-size:.9rem;color:var(--text2);margin-bottom:.3rem;">
          <?= htmlspecialchars($r['expression']) ?>
        </div>
        <div class="mono" style="font-size:1.6rem;font-weight:700;color:var(--accent2);">
          = <?= htmlspecialchars($r['result']) ?>
        </div>
        <div style="margin:.6rem 0;">
          <span class="badge badge-<?= $r['op_type'] ?>"><?= $r['op_type'] ?></span>
        </div>
        <div style="color:var(--text2);font-size:.8rem;margin-bottom:1rem;">
          Saved <?= date('M j Y', strtotime($r['created_at'])) ?>
        </div>
        <div class="actions">
          <!-- Quick-use: loads expression into calculator -->
          <form method="POST" style="display:inline">
            <input type="hidden" name="csrf"       value="<?= csrfToken() ?>">
            <input type="hidden" name="action"     value="use">
            <input type="hidden" name="id"         value="<?= $r['id'] ?>">
            <input type="hidden" name="expression" value="<?= htmlspecialchars($r['expression']) ?>">
            <button type="submit" class="btn btn-primary btn-sm">▶ Use</button>
          </form>
          <!-- Remove from favourites -->
          <form method="POST" style="display:inline">
            <input type="hidden" name="csrf"   value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="unfav">
            <input type="hidden" name="id"     value="<?= $r['id'] ?>">
            <button type="submit" class="btn btn-ghost btn-sm">☆ Unfavourite</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>

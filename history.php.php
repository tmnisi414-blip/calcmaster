<?php
require_once __DIR__ . '/includes/config.php';
requireLogin();
$pageTitle = 'History';
$user = currentUser();

// ── Handle POST actions ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);

    // Ensure the row belongs to this user
    $check = db()->prepare('SELECT id FROM calculations WHERE id = ? AND user_id = ?');
    $check->bind_param('ii', $id, $_SESSION['user_id']);
    $check->execute();

    if ($check->get_result()->num_rows === 0) {
        flash('Not found.', 'error');
        header('Location: history.php');
        exit;
    }

    switch ($action) {
        // ── Delete ────────────────────────────────────────────
        case 'delete':
            $stmt = db()->prepare('DELETE FROM calculations WHERE id = ? AND user_id = ?');
            $stmt->bind_param('ii', $id, $_SESSION['user_id']);
            $stmt->execute();
            flash('Calculation deleted.', 'success');
            break;

        // ── Edit / Update ─────────────────────────────────────
        case 'edit':
            $newExpr   = trim($_POST['expression'] ?? '');
            $newResult = trim($_POST['result']     ?? '');
            $newType   = detectOpType($newExpr);
            if ($newExpr !== '' && $newResult !== '') {
                $stmt = db()->prepare(
                    'UPDATE calculations SET expression = ?, result = ?, op_type = ? WHERE id = ? AND user_id = ?'
                );
                $stmt->bind_param('sssii', $newExpr, $newResult, $newType, $id, $_SESSION['user_id']);
                $stmt->execute();
                flash('Calculation updated.', 'success');
            }
            break;

        // ── Toggle favourite ──────────────────────────────────
        case 'fav':
            $stmt = db()->prepare(
                'UPDATE calculations SET is_favourite = NOT is_favourite WHERE id = ? AND user_id = ?'
            );
            $stmt->bind_param('ii', $id, $_SESSION['user_id']);
            $stmt->execute();
            break;
    }

    header('Location: history.php?' . http_build_query($_GET));
    exit;
}

// ── Search / filter ───────────────────────────────────────────
$search   = trim($_GET['q']       ?? '');
$opFilter = trim($_GET['op_type'] ?? '');
$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo   = trim($_GET['date_to']   ?? '');
$favOnly  = isset($_GET['fav']);

$where  = ['user_id = ?'];
$params = [$_SESSION['user_id']];
$types  = 'i';

if ($search !== '') {
    $where[]  = '(expression LIKE ? OR result LIKE ?)';
    $like     = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $types   .= 'ss';
}
if ($opFilter !== '') {
    $where[]  = 'op_type = ?';
    $params[] = $opFilter;
    $types   .= 's';
}
if ($dateFrom !== '') {
    $where[]  = 'DATE(created_at) >= ?';
    $params[] = $dateFrom;
    $types   .= 's';
}
if ($dateTo !== '') {
    $where[]  = 'DATE(created_at) <= ?';
    $params[] = $dateTo;
    $types   .= 's';
}
if ($favOnly) {
    $where[] = 'is_favourite = 1';
}

$sql  = 'SELECT * FROM calculations WHERE ' . implode(' AND ', $where) . ' ORDER BY created_at DESC';
$stmt = db()->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

include __DIR__ . '/includes/header.php';
?>

<div class="page-title">📋 Calculation History</div>

<!-- ── Search / Filter form ─────────────────────────────────── -->
<form method="GET" class="search-bar">
  <input type="text" name="q" placeholder="Search expression or result…"
         value="<?= htmlspecialchars($search) ?>">

  <select name="op_type">
    <option value="">All types</option>
    <?php foreach (['arithmetic','addition','subtraction','multiplication','division','power','root'] as $t): ?>
      <option value="<?= $t ?>" <?= $opFilter === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
    <?php endforeach; ?>
  </select>

  <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>" title="From date">
  <input type="date" name="date_to"   value="<?= htmlspecialchars($dateTo) ?>"   title="To date">

  <label style="display:flex;align-items:center;gap:.4rem;color:var(--text2);">
    <input type="checkbox" name="fav" <?= $favOnly ? 'checked' : '' ?>> ★ Favourites
  </label>

  <button type="submit" class="btn btn-primary">Filter</button>
  <a href="history.php" class="btn btn-ghost">Reset</a>
</form>

<!-- ── Results table ─────────────────────────────────────────── -->
<div class="card">
  <?php if (!$rows): ?>
    <p style="color:var(--text2);text-align:center;padding:2rem 0;">No calculations found.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>★</th>
            <th>Expression</th>
            <th>Result</th>
            <th>Type</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $r): ?>

          <!-- ── View row ──────────────────────────────────── -->
          <tr id="view-<?= $r['id'] ?>">
            <td>
              <form method="POST" style="display:inline">
                <input type="hidden" name="csrf"   value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="fav">
                <input type="hidden" name="id"     value="<?= $r['id'] ?>">
                <button type="submit" class="star" style="background:none;border:none;"
                        title="<?= $r['is_favourite'] ? 'Unfavourite' : 'Mark favourite' ?>">
                  <?= $r['is_favourite'] ? '★' : '☆' ?>
                </button>
              </form>
            </td>
            <td class="mono"><?= htmlspecialchars($r['expression']) ?></td>
            <td class="mono" style="font-weight:700;"><?= htmlspecialchars($r['result']) ?></td>
            <td><span class="badge badge-<?= $r['op_type'] ?>"><?= $r['op_type'] ?></span></td>
            <td style="color:var(--text2);font-size:.85rem;"><?= date('M j Y, H:i', strtotime($r['created_at'])) ?></td>
            <td>
              <div class="actions">
                <button class="btn btn-ghost btn-sm btn-edit-toggle" data-id="<?= $r['id'] ?>">✏ Edit</button>
                <form method="POST" class="confirm-delete" style="display:inline">
                  <input type="hidden" name="csrf"   value="<?= csrfToken() ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id"     value="<?= $r['id'] ?>">
                  <button type="submit" class="btn btn-red btn-sm">🗑 Delete</button>
                </form>
              </div>
            </td>
          </tr>

          <!-- ── Inline edit row ───────────────────────────── -->
          <tr id="edit-<?= $r['id'] ?>" style="display:none;" class="edit-row">
            <td colspan="6">
              <form method="POST" style="display:flex;gap:.6rem;align-items:center;flex-wrap:wrap;padding:.4rem 0;">
                <input type="hidden" name="csrf"   value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id"     value="<?= $r['id'] ?>">
                <input type="text" name="expression" class="mono"
                       value="<?= htmlspecialchars($r['expression']) ?>"
                       style="flex:2;min-width:160px;" required>
                <input type="text" name="result" class="mono"
                       value="<?= htmlspecialchars($r['result']) ?>"
                       style="flex:1;min-width:100px;" required>
                <button type="submit" class="btn btn-green btn-sm">✔ Save</button>
                <button type="button" class="btn btn-ghost btn-sm btn-cancel-edit" data-id="<?= $r['id'] ?>">✕ Cancel</button>
              </form>
            </td>
          </tr>

          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<script>window._csrf = '<?= csrfToken() ?>';</script>
<?php include __DIR__ . '/includes/footer.php'; ?>

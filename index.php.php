<?php
require_once __DIR__ . '/includes/config.php';
$pageTitle = 'Calculator';

// ── Recent calcs from cookie (guests) ────────────────────────
$recent = [];
if (!isLoggedIn() && isset($_COOKIE['recent_calcs'])) {
    $recent = json_decode($_COOKIE['recent_calcs'], true) ?: [];
}

// ── Recent calcs from DB (logged-in) ─────────────────────────
$dbRecent = [];
if (isLoggedIn()) {
    $stmt = db()->prepare(
        'SELECT expression, result, op_type, created_at FROM calculations
          WHERE user_id = ? ORDER BY created_at DESC LIMIT 5'
    );
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $dbRecent = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

include __DIR__ . '/includes/header.php';
?>

<div class="page-title">⌗ CalcMaster</div>

<div class="calc-wrap">

  <!-- ── Calculator pad ─────────────────────────────────────── -->
  <div class="calc-card">
    <div class="calc-display">
      <div class="calc-expr" id="calcExpr"></div>
      <div class="calc-result" id="calcDisplay">0</div>
    </div>

    <div class="calc-grid">
      <!-- Row 1: functions -->
      <button class="btn-calc fn" data-val="sqrt(">√x</button>
      <button class="btn-calc fn" data-val="cbrt(">∛x</button>
      <button class="btn-calc fn" data-val="x²">x²</button>
      <button class="btn-calc fn" data-val="x³">x³</button>

      <!-- Row 2: more functions -->
      <button class="btn-calc fn" data-val="π">π</button>
      <button class="btn-calc fn" data-val="e">e</button>
      <button class="btn-calc fn" data-val="%">%</button>
      <button class="btn-calc fn" data-val="1/x">1/x</button>

      <!-- Row 3 -->
      <button class="btn-calc clear" data-val="C">C</button>
      <button class="btn-calc" data-val="(">(</button>
      <button class="btn-calc" data-val=")">)</button>
      <button class="btn-calc op" data-val="/">÷</button>

      <!-- Row 4 -->
      <button class="btn-calc" data-val="7">7</button>
      <button class="btn-calc" data-val="8">8</button>
      <button class="btn-calc" data-val="9">9</button>
      <button class="btn-calc op" data-val="*">×</button>

      <!-- Row 5 -->
      <button class="btn-calc" data-val="4">4</button>
      <button class="btn-calc" data-val="5">5</button>
      <button class="btn-calc" data-val="6">6</button>
      <button class="btn-calc op" data-val="-">−</button>

      <!-- Row 6 -->
      <button class="btn-calc" data-val="1">1</button>
      <button class="btn-calc" data-val="2">2</button>
      <button class="btn-calc" data-val="3">3</button>
      <button class="btn-calc op" data-val="+">+</button>

      <!-- Row 7 -->
      <button class="btn-calc" data-val="⌫">⌫</button>
      <button class="btn-calc" data-val="0">0</button>
      <button class="btn-calc" data-val=".">.</button>
      <button class="btn-calc equals" data-val="=">=</button>
    </div>

    <?php if (!isLoggedIn()): ?>
      <p style="margin-top:1rem; color:var(--text2); font-size:.85rem;">
        💡 <a href="/calcmaster/login.php" style="color:var(--accent2)">Log in</a> to save your calculations.
      </p>
    <?php endif; ?>
  </div>

  <!-- ── Recent calculations panel ─────────────────────────── -->
  <div>
    <div class="card">
      <h3 style="font-family:var(--font-mono);margin-bottom:1rem;font-size:1rem;color:var(--text2)">
        Recent Calculations
      </h3>

      <?php if (isLoggedIn() && $dbRecent): ?>
        <ul style="list-style:none;display:flex;flex-direction:column;gap:.6rem;">
          <?php foreach ($dbRecent as $r): ?>
            <li style="background:var(--bg3);border-radius:9px;padding:.7rem 1rem;">
              <div class="mono" style="font-size:.9rem;color:var(--accent2)">
                <?= htmlspecialchars($r['expression']) ?>
              </div>
              <div class="mono" style="font-size:1.1rem;font-weight:700;">
                = <?= htmlspecialchars($r['result']) ?>
              </div>
              <div style="font-size:.75rem;color:var(--text2);margin-top:.25rem;">
                <span class="badge badge-<?= $r['op_type'] ?>"><?= $r['op_type'] ?></span>
                &nbsp;<?= date('M j, H:i', strtotime($r['created_at'])) ?>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
        <a href="/calcmaster/history.php" class="btn btn-ghost" style="margin-top:1rem;width:100%;justify-content:center;">
          View Full History →
        </a>

      <?php elseif (!isLoggedIn() && $recent): ?>
        <ul style="list-style:none;display:flex;flex-direction:column;gap:.5rem;">
          <?php foreach (array_reverse($recent) as $r): ?>
            <li class="mono" style="font-size:.9rem;color:var(--text2);">
              <?= htmlspecialchars($r['expr']) ?> = <strong><?= htmlspecialchars($r['result']) ?></strong>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p style="color:var(--text2);font-size:.9rem;">No calculations yet. Start computing!</p>
      <?php endif; ?>
    </div>
  </div>

</div>

<!-- Pass CSRF token to JS -->
<script>window._csrf = '<?= csrfToken() ?>';</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

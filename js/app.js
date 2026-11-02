/* ── CalcMaster app.js ─────────────────────────────────────── */

// ── Theme toggle ─────────────────────────────────────────────
const themeBtn = document.getElementById('themeToggle');
if (themeBtn) {
  themeBtn.addEventListener('click', () => {
    const html  = document.documentElement;
    const next  = html.dataset.theme === 'dark' ? 'light' : 'dark';
    html.dataset.theme  = next;
    themeBtn.textContent = next === 'dark' ? '☀' : '☾';
    document.cookie = `theme=${next};path=/;max-age=${60*60*24*365}`;

    // Persist server-side if logged in
    fetch('/calcmaster/ajax/set_theme.php?theme=' + next);
  });

  // Set initial icon
  const cur = document.documentElement.dataset.theme;
  themeBtn.textContent = cur === 'dark' ? '☀' : '☾';
}

// ── Calculator logic ──────────────────────────────────────────
(function () {
  const display = document.getElementById('calcDisplay');
  const exprEl  = document.getElementById('calcExpr');
  if (!display) return;

  let expr = '';
  let justEvaled = false;

  function render() {
    display.textContent = expr || '0';
  }

  // Button clicks
  document.querySelectorAll('[data-val]').forEach(btn => {
    btn.addEventListener('click', () => {
      const v = btn.dataset.val;

      if (justEvaled && !isNaN(v) || justEvaled && v === '(') {
        expr = '';
      }
      justEvaled = false;

      if (v === 'C')           { expr = ''; exprEl.textContent = ''; }
      else if (v === '⌫')     { expr = expr.slice(0, -1); }
      else if (v === '=')      { evaluate(); return; }
      else if (v === 'sqrt(')  { expr += 'sqrt('; }
      else if (v === 'cbrt(')  { expr += 'cbrt('; }
      else if (v === 'x²')     { expr += '**2'; }
      else if (v === 'x³')     { expr += '**3'; }
      else if (v === '1/x')    { expr = '1/(' + expr + ')'; }
      else if (v === '%')      { expr += '/100'; }
      else if (v === 'π')      { expr += '3.141592653589793'; }
      else if (v === 'e')      { expr += '2.718281828459045'; }
      else                     { expr += v; }

      render();
    });
  });

  // Keyboard support
  document.addEventListener('keydown', e => {
    if (!display) return;
    const key = e.key;
    if (key === 'Enter' || key === '=') { evaluate(); return; }
    if (key === 'Escape') { expr = ''; exprEl.textContent = ''; render(); return; }
    if (key === 'Backspace') { expr = expr.slice(0, -1); render(); return; }
    if (/^[0-9+\-*/.()%^]$/.test(key)) { expr += key; render(); }
  });

  function evaluate() {
    if (!expr) return;
    const original = expr;
    try {
      // Replace ^ with ** for exponentiation
      let safe = expr
        .replace(/\^/g, '**')
        .replace(/sqrt\(/g, 'Math.sqrt(')
        .replace(/cbrt\(/g, 'Math.cbrt(')
        .replace(/log\(/g,  'Math.log10(')
        .replace(/ln\(/g,   'Math.log(')
        .replace(/sin\(/g,  'Math.sin(')
        .replace(/cos\(/g,  'Math.cos(')
        .replace(/tan\(/g,  'Math.tan(')
        .replace(/abs\(/g,  'Math.abs(')
        .replace(/π/g,      'Math.PI')
        .replace(/e(?!\d)/g,'Math.E');

      // Security: only allow safe chars
      if (/[^0-9+\-*/().%\sMath.sqrtcblgasnioPE\^_]/.test(safe)) throw new Error('Invalid');

      // eslint-disable-next-line no-new-func
      const result = Function('"use strict"; return (' + safe + ')')();

      if (!isFinite(result)) throw new Error('Math error');
      if (isNaN(result))     throw new Error('Invalid expression');

      const rounded = +parseFloat(result.toFixed(10));
      exprEl.textContent = original + ' =';
      expr = String(rounded);
      render();
      justEvaled = true;

      // Save to server if logged in
      saveCalc(original, rounded);

    } catch (err) {
      exprEl.textContent = expr;
      display.textContent = err.message || 'Error';
      expr = '';
      justEvaled = true;
    }
  }

  function saveCalc(expression, result) {
    const form = new FormData();
    form.append('expression', expression);
    form.append('result', result);
    form.append('csrf', window._csrf || '');
    fetch('/calcmaster/ajax/save_calc.php', { method: 'POST', body: form });
  }
})();

// ── Confirm delete ────────────────────────────────────────────
document.querySelectorAll('.confirm-delete').forEach(f => {
  f.addEventListener('submit', e => {
    if (!confirm('Delete this calculation? This cannot be undone.')) e.preventDefault();
  });
});

// ── Inline edit toggle ────────────────────────────────────────
document.querySelectorAll('.btn-edit-toggle').forEach(btn => {
  btn.addEventListener('click', () => {
    const id      = btn.dataset.id;
    const viewRow = document.getElementById('view-' + id);
    const editRow = document.getElementById('edit-' + id);
    if (viewRow) viewRow.style.display = 'none';
    if (editRow) editRow.style.display = 'table-row';
  });
});

document.querySelectorAll('.btn-cancel-edit').forEach(btn => {
  btn.addEventListener('click', () => {
    const id      = btn.dataset.id;
    const viewRow = document.getElementById('view-' + id);
    const editRow = document.getElementById('edit-' + id);
    if (viewRow) viewRow.style.display = 'table-row';
    if (editRow) editRow.style.display = 'none';
  });
});

<?php
session_start();
if (!isset($_SESSION['history'])) {
    $_SESSION['history'] = [];
}
// Save posted calculation (expression and result)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['expr']) && isset($_POST['result'])) {
    $expr = substr(trim($_POST['expr']), 0, 255);
    $result = substr(trim($_POST['result']), 0, 255);
    if ($expr !== '') {
        $_SESSION['history'][] = [
            'expr' => htmlspecialchars($expr, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'result' => htmlspecialchars($result, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            'time' => date('Y-m-d H:i:s')
        ];
        // Keep history to last 10 entries
        if (count($_SESSION['history']) > 10) {
            array_shift($_SESSION['history']);
        }
    }
    // Redirect to avoid repost on refresh
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

function clear_history() {
    $_SESSION['history'] = [];
}
if (isset($_GET['clear_history']) && $_GET['clear_history'] == '1') {
    clear_history();
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Responsive Calculator</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    :root{
      --panel-bg: linear-gradient(145deg,#ffffff 0%, #f1f6ff 100%);
      --accent: #4f46e5;
      --shadow: 0 8px 20px rgba(79,70,229,0.12);
      --glass: rgba(255,255,255,0.6);
    }
    body{
      background: radial-gradient(circle at 10% 10%, #eef2ff 0%, #ffffff 30%), linear-gradient(180deg,#f8fafc, #f1f5f9);
      min-height:100vh;
      display:flex;
      align-items:center;
      justify-content:center;
      padding:1rem;
      margin:0;
      font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
    }
    .calculator-card{
      max-width:980px;
      width:100%;
      border-radius:18px;
      box-shadow: var(--shadow);
      overflow:hidden;
    }
    .calc-panel{
      background: var(--panel-bg);
      padding:1.5rem;
      min-height:420px;
    }
    .display{
      background: rgba(255,255,255,0.85);
      border-radius:12px;
      padding:1rem;
      text-align:right;
      font-size:2rem;
      font-weight:600;
      box-shadow: 0 6px 12px rgba(15,23,42,0.06) inset, 0 6px 20px rgba(2,6,23,0.04);
      white-space:nowrap;
      overflow:hidden;
      text-overflow:ellipsis;
    }
    .sub-display{
      font-size:0.9rem;
      color:#6b7280;
      opacity:0.9;
    }
    .btn-calc{
      border-radius:12px;
      padding:0.9rem 0.75rem;
      font-size:1.25rem;
      box-shadow: none;
      border: none;
    }
    .btn-op{ background: linear-gradient(180deg,var(--accent), #3730a3); color:#fff; }
    .btn-fn{ background: linear-gradient(180deg,#f3f4f6,#e6eefc); }
    .btn-num{ background: linear-gradient(180deg,#ffffff,#f8fafc); }
    .btn-calc:active{ transform: translateY(1px); }
    .history-card{
      background:rgba(255,255,255,0.9);
      border-radius:12px;
      padding:1rem;
      max-height:420px;
      overflow:auto;
    }
    .history-item{ font-family: 'Courier New', monospace; font-size:0.95rem; }
    @media (max-width: 767px){
      .display{font-size:1.6rem}
      .btn-calc{font-size:1rem;padding:0.6rem 0.5rem}
    }
  </style>
</head>
<body>
  <div class="container d-flex justify-content-center align-items-center">
    <div class="calculator-card row g-0 shadow-sm">
      <div class="col-lg-7 calc-panel">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <h4 class="mb-0">Calculator</h4>
            <small class="text-muted">Responsive • Keyboard support • History</small>
          </div>
          <div class="text-end">
            <small class="text-muted">Tip: Press <kbd>Enter</kbd> to evaluate</small>
          </div>
        </div>

        <div class="display mb-3" id="display" aria-live="polite">
          <div class="sub-display" id="subDisplay">&nbsp;</div>
          <div id="mainDisplay">0</div>
        </div>

        <form id="saveForm" method="post" class="d-none">
          <input type="hidden" name="expr" id="exprInput">
          <input type="hidden" name="result" id="resultInput">
        </form>

        <div class="row gx-2 gy-2">
          <!-- Row 1 -->
          <div class="col-3"><button class="btn btn-fn w-100 btn-calc" data-action="clear">C</button></div>
          <div class="col-3"><button class="btn btn-fn w-100 btn-calc" data-action="back">⌫</button></div>
          <div class="col-3"><button class="btn btn-fn w-100 btn-calc" data-action="percent">%</button></div>
          <div class="col-3"><button class="btn btn-op w-100 btn-calc" data-value="/">÷</button></div>

          <!-- Row 2 -->
          <div class="col-3"><button class="btn btn-num w-100 btn-calc" data-value="7">7</button></div>
          <div class="col-3"><button class="btn btn-num w-100 btn-calc" data-value="8">8</button></div>
          <div class="col-3"><button class="btn btn-num w-100 btn-calc" data-value="9">9</button></div>
          <div class="col-3"><button class="btn btn-op w-100 btn-calc" data-value="*">×</button></div>

          <!-- Row 3 -->
          <div class="col-3"><button class="btn btn-num w-100 btn-calc" data-value="4">4</button></div>
          <div class="col-3"><button class="btn btn-num w-100 btn-calc" data-value="5">5</button></div>
          <div class="col-3"><button class="btn btn-num w-100 btn-calc" data-value="6">6</button></div>
          <div class="col-3"><button class="btn btn-op w-100 btn-calc" data-value="-">−</button></div>

          <!-- Row 4 -->
          <div class="col-3"><button class="btn btn-num w-100 btn-calc" data-value="1">1</button></div>
          <div class="col-3"><button class="btn btn-num w-100 btn-calc" data-value="2">2</button></div>
          <div class="col-3"><button class="btn btn-num w-100 btn-calc" data-value="3">3</button></div>
          <div class="col-3"><button class="btn btn-op w-100 btn-calc" data-value="+">+</button></div>

          <!-- Row 5 -->
          <div class="col-6"><button class="btn btn-num w-100 btn-calc" data-value="0">0</button></div>
          <div class="col-3"><button class="btn btn-num w-100 btn-calc" data-value=".">.</button></div>
          <div class="col-3"><button class="btn btn-op w-100 btn-calc" data-action="equals">=</button></div>
        </div>

      </div>

      <div class="col-lg-5 p-4 border-start d-flex flex-column">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="mb-0">History</h5>
          <div>
            <a href="?clear_history=1" class="btn btn-sm btn-light"><i class="bi bi-trash"></i> Clear</a>
          </div>
        </div>
        <div class="history-card mb-3">
          <?php if (empty($_SESSION['history'])): ?>
            <div class="text-muted">No history yet — calculate something!</div>
          <?php else: ?>
            <?php foreach (array_reverse($_SESSION['history']) as $h): ?>
              <div class="mb-2">
                <div class="history-item"><?php echo $h['expr']; ?> = <strong><?php echo $h['result']; ?></strong></div>
                <small class="text-muted"><?php echo $h['time']; ?></small>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <div class="mt-auto text-center text-muted small">
          Built with PHP • HTML • Bootstrap • JS
        </div>
      </div>
    </div>
  </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function(){
  const mainDisplay = document.getElementById('mainDisplay');
  const subDisplay = document.getElementById('subDisplay');
  const exprInput = document.getElementById('exprInput');
  const resultInput = document.getElementById('resultInput');
  const saveForm = document.getElementById('saveForm');

  let expr = '';
  let lastResult = null;

  function render(){
    subDisplay.textContent = expr || '\u00A0';
    mainDisplay.textContent = expr === '' ? '0' : expr;
  }

  function append(val){
    // Prevent multiple operators in a row (simple rule)
    if (/^[+\-*/.]$/.test(val)){
      if (expr === '' && val !== '-') return; // prevent starting with +*/.
      if (/[*+\-/]$/.test(expr) && /^[+\-*/]$/.test(val)){
        // replace last operator
        expr = expr.slice(0, -1) + val;
        render();
        return;
      }
    }
    expr += val;
    render();
  }

  function clearAll(){ expr = ''; render(); }
  function backspace(){ expr = expr.slice(0,-1); render(); }
  function percent(){
    try{
      const val = evaluateExpression(expr);
      expr = (val / 100).toString();
      render();
    }catch(e){ mainDisplay.textContent = 'Error'; }
  }

  function evaluateExpression(input){
    if (!input) return 0;
    // Replace unicode multiply/divide signs if present
    const safe = input.replace(/×/g,'*').replace(/÷/g,'/');
    // Disallow letters to avoid code execution
    if (/[a-zA-Z]/.test(safe)) throw new Error('Invalid');
    // Evaluate using Function (better than eval) but still limited — returns numeric result
    // NOTE: This is intended for basic arithmetic expressions only.
    /* eslint-disable no-new-func */
    const fn = new Function('return ' + safe);
    const r = fn();
    if (typeof r !== 'number' || !isFinite(r)) throw new Error('Math error');
    return Math.round((r + Number.EPSILON) * 1e12) / 1e12; // round to 12 decimal places
  }

  function doEquals(){
    try{
      const result = evaluateExpression(expr);
      lastResult = result;
      // show result and prepare history save
      mainDisplay.textContent = result;
      subDisplay.textContent = expr + ' =';
      // set inputs and submit to PHP to save to session history
      exprInput.value = expr;
      resultInput.value = String(result);
      // submit after a short delay so user sees result (instant enough)
      saveForm.submit();
      expr = String(result);
    }catch(e){
      mainDisplay.textContent = 'Error';
      setTimeout(()=>{ render(); }, 1000);
    }
  }

  // Button clicks
  document.querySelectorAll('.btn-calc').forEach(btn=>{
    btn.addEventListener('click', (ev)=>{
      ev.preventDefault();
      const v = btn.getAttribute('data-value');
      const action = btn.getAttribute('data-action');
      if (action === 'clear') return clearAll();
      if (action === 'back') return backspace();
      if (action === 'percent') return percent();
      if (action === 'equals') return doEquals();
      if (v) return append(v);
    });
  });

  // Keyboard support
  window.addEventListener('keydown', (e)=>{
    if (e.key === 'Enter') { e.preventDefault(); doEquals(); }
    else if (e.key === 'Backspace') { e.preventDefault(); backspace(); }
    else if (e.key === 'Escape') { e.preventDefault(); clearAll(); }
    else if (/^[0-9.+\-*/%()]$/.test(e.key)){
      e.preventDefault(); // prevent typing into page
      if (e.key === '%') percent(); else append(e.key === '%' ? '%' : e.key);
    }
  });

  // initial render
  render();
})();
</script>
</body>
</html>

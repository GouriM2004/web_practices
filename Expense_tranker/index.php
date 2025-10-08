<?php
// Expense Tracker - single-file PHP app
// Save this as index.php and run on a PHP-enabled server (e.g. XAMPP, MAMP, LAMP)
session_start();
if (!isset($_SESSION['expenses'])) {
    $_SESSION['expenses'] = [];
}

// Helper: send JSON response
function json_response($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add') {
        $date = trim($_POST['date'] ?? '');
        $category = trim($_POST['category'] ?? 'Other');
        $desc = trim($_POST['description'] ?? '');
        $amount = floatval($_POST['amount'] ?? 0);

        if ($amount <= 0 || $date === '') {
            json_response(['success' => false, 'message' => 'Please provide a valid date and amount > 0.']);
        }

        $id = uniqid();
        $item = [
            'id' => $id,
            'date' => $date,
            'category' => htmlspecialchars($category, ENT_QUOTES),
            'description' => htmlspecialchars($desc, ENT_QUOTES),
            'amount' => $amount
        ];

        array_unshift($_SESSION['expenses'], $item); // newest first
        json_response(['success' => true, 'item' => $item]);

    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? '';
        $found = false;
        foreach ($_SESSION['expenses'] as $k => $e) {
            if ($e['id'] === $id) {
                unset($_SESSION['expenses'][$k]);
                $_SESSION['expenses'] = array_values($_SESSION['expenses']);
                $found = true;
                break;
            }
        }
        json_response(['success' => $found]);

    } elseif ($action === 'clear') {
        $_SESSION['expenses'] = [];
        json_response(['success' => true]);
    }
}

// Non-AJAX: serve page HTML
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Expense Tracker</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    :root{--accent:#6f42c1}
    body{background:linear-gradient(180deg,#f8f9fa 0%,#ffffff 100%);min-height:100vh}
    .card{border-radius:1rem;box-shadow:0 6px 18px rgba(20,20,50,0.06)}
    .accent{color:var(--accent)}
    .btn-accent{background:var(--accent);color:#fff}
    .category-badge{font-size:.75rem}
    .empty-state{opacity:.7}
    .currency{font-weight:700}
    .truncate{max-width:220px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
  <div class="container">
    <a class="navbar-brand fw-bold accent" href="#">ExpenseTracker</a>
    <div class="ms-auto"> 
      <small class="text-muted">Simple • Fast • Secure (session)</small>
    </div>
  </div>
</nav>

<main class="container py-5">
  <div class="row g-4">
    <div class="col-lg-5">
      <div class="card p-4">
        <h5 class="mb-3">Add Expense</h5>
        <form id="expenseForm" autocomplete="off">
          <div class="row g-2">
            <div class="col-6">
              <label class="form-label">Date</label>
              <input type="date" id="date" name="date" class="form-control" required>
            </div>
            <div class="col-6">
              <label class="form-label">Amount</label>
              <input type="number" id="amount" name="amount" class="form-control" step="0.01" min="0" placeholder="0.00" required>
            </div>
            <div class="col-12">
              <label class="form-label">Category</label>
              <select id="category" name="category" class="form-select">
                <option>Food</option>
                <option>Transport</option>
                <option>Shopping</option>
                <option>Bills</option>
                <option>Health</option>
                <option>Others</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label">Description</label>
              <input type="text" id="description" name="description" class="form-control" placeholder="Coffee, Uber, Groceries...">
            </div>
          </div>

          <div class="d-flex gap-2 mt-3">
            <button class="btn btn-accent" type="submit">Add Expense</button>
            <button type="button" id="clearBtn" class="btn btn-outline-secondary">Clear All</button>
            <div class="ms-auto text-end">
              <div class="small text-muted">Total</div>
              <div id="grandTotal" class="fs-4 currency">₹0.00</div>
            </div>
          </div>
        </form>

        <hr>
        <div class="small text-muted">Tip: Use browser's date selector to choose date quickly.</div>
      </div>

      <div class="mt-4 card p-3">
        <h6 class="mb-2">Spending Summary</h6>
        <div id="summaryList" class="list-group list-group-flush"></div>
      </div>
    </div>

    <div class="col-lg-7">
      <div class="card p-3">
        <div class="d-flex align-items-center mb-3">
          <h5 class="mb-0 me-auto">Recent Expenses</h5>
          <div class="text-muted small">Showing latest first</div>
        </div>

        <div id="expenseList" class="table-responsive"></div>

      </div>
    </div>
  </div>
</main>

<footer class="text-center py-4 text-muted small">
  Built with ❤️ • Demo uses PHP session only — refresh will retain session data until browser closed or session cleared.
</footer>

<script>
// Helpers
function formatCurrency(v){
  return '₹' + Number(v).toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2});
}

async function api(action, data={}){
  data.action = action;
  const form = new URLSearchParams(data);
  const res = await fetch(location.href, {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: form});
  return res.json();
}

function el(html){ const div = document.createElement('div'); div.innerHTML = html; return div.firstElementChild; }

// State
let expenses = [];

function renderExpenses(){
  const list = document.getElementById('expenseList');
  if (!expenses.length){
    list.innerHTML = `<div class="px-4 py-5 text-center empty-state">
      <h6 class="mb-1">No expenses yet</h6>
      <p class="mb-0">Add an expense to see it listed here and update totals dynamically.</p>
    </div>`;
    document.getElementById('grandTotal').textContent = formatCurrency(0);
    renderSummary();
    return;
  }

  let html = `<table class="table align-middle">
    <thead><tr><th>Date</th><th>Category</th><th>Description</th><th class="text-end">Amount</th><th></th></tr></thead><tbody>`;
  let total = 0;
  for (const e of expenses){
    total += Number(e.amount);
    html += `<tr>
      <td>${e.date}</td>
      <td><span class="badge bg-light text-dark category-badge">${e.category}</span></td>
      <td class="truncate" title="${e.description}">${e.description || '—'}</td>
      <td class="text-end currency">${formatCurrency(e.amount)}</td>
      <td class="text-end"><button class="btn btn-sm btn-outline-danger" data-id="${e.id}">Delete</button></td>
    </tr>`;
  }
  html += `</tbody></table>`;
  list.innerHTML = html;
  document.getElementById('grandTotal').textContent = formatCurrency(total);
  // attach delete handlers
  list.querySelectorAll('button[data-id]').forEach(btn=>{
    btn.addEventListener('click', async ()=>{
      const id = btn.getAttribute('data-id');
      const r = await api('delete',{id});
      if (r.success){
        expenses = expenses.filter(x=>x.id!==id);
        renderExpenses();
        renderSummary();
      }
    });
  });
  renderSummary();
}

function renderSummary(){
  const map = {};
  for (const e of expenses){
    map[e.category] = (map[e.category] || 0) + Number(e.amount);
  }
  const container = document.getElementById('summaryList');
  if (!Object.keys(map).length){
    container.innerHTML = '<div class="text-center empty-state py-3">No summary available</div>';
    return;
  }
  let html = '';
  const entries = Object.entries(map).sort((a,b)=>b[1]-a[1]);
  const grand = entries.reduce((s,[,v])=>s+v,0);
  for (const [cat,sum] of entries){
    const pct = Math.round((sum/grand)*100);
    html += `<div class="mb-2">
      <div class="d-flex justify-content-between"><div class="small text-muted">${cat}</div><div class="small">${formatCurrency(sum)} <span class="text-muted">(${pct}%)</span></div></div>
      <div class="progress mt-1" style="height:8px"><div class="progress-bar" role="progressbar" style="width: ${pct}%" aria-valuenow="${pct}" aria-valuemin="0" aria-valuemax="100"></div></div>
    </div>`;
  }
  container.innerHTML = html;
}

// Load initial data from server-rendered session into JS by calling a tiny endpoint
async function loadInitial(){
  // We fetch by reading a hidden JSON generated by the server: embed via data attribute
  try{
    const embedded = document.getElementById('initialData');
    if (embedded){
      expenses = JSON.parse(embedded.textContent || '[]');
    }
  }catch(e){ expenses = []; }
  renderExpenses();
}

// Form
document.getElementById('expenseForm').addEventListener('submit', async (ev)=>{
  ev.preventDefault();
  const date = document.getElementById('date').value;
  const amount = document.getElementById('amount').value;
  const category = document.getElementById('category').value;
  const description = document.getElementById('description').value;

  if (!date || !amount || Number(amount) <= 0){
    alert('Please provide a valid date and amount greater than 0.');
    return;
  }

  const res = await api('add', {date, amount, category, description});
  if (res.success){
    expenses.unshift(res.item);
    renderExpenses();
    document.getElementById('expenseForm').reset();
    // set date to today for convenience
    document.getElementById('date').valueAsDate = new Date();
  } else {
    alert(res.message || 'Something went wrong');
  }
});

// Clear all
document.getElementById('clearBtn').addEventListener('click', async ()=>{
  if (!confirm('Clear all expenses from this session?')) return;
  const r = await api('clear');
  if (r.success){ expenses = []; renderExpenses(); }
});

// set default date
document.addEventListener('DOMContentLoaded', ()=>{
  const d = new Date();
  const iso = d.toISOString().slice(0,10);
  document.getElementById('date').value = iso;
  loadInitial();
});
</script>

<!-- Embed initial server session data for client to read -->
<script id="initialData" type="application/json"><?php echo json_encode(array_values($_SESSION['expenses'])); ?></script>

</body>
</html>

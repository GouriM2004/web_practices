<?php
// view_group.php
session_start();
require_once 'includes/autoload.php';
if (!isset($_SESSION['user_id'])) header('Location: index.php');
$user_id = $_SESSION['user_id'];
$groupModel = new Group();
$expenseModel = new Expense();
$calculator = new Calculator();
$settModel = new Settlement();
$group_id = intval($_GET['id'] ?? 0);
if ($group_id <= 0) header('Location: dashboard.php');
$group = $groupModel->getGroup($group_id, $user_id);
if (!$group) header('Location: dashboard.php?msg=' . urlencode('You are not a member of this group'));
$members = $groupModel->getMembers($group_id);
$expenses = $expenseModel->getGroupExpenses($group_id);

// Handle delete group (only creator allowed)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_group'])) {
  if ($groupModel->delete($group_id, $user_id)) {
    // log deletion
    $lg = new Log();
    $lg->add($user_id, $group_id, 'Deleted the group');
    header('Location: dashboard.php?msg=' . urlencode('Group deleted'));
    exit;
  } else {
    $err = 'Failed to delete group. Only the group creator can delete the group.';
  }
}

// Handle remove member (creator can remove others, member can leave self)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_member'])) {
  $target = intval($_POST['target_user_id'] ?? 0);
  if ($target > 0) {
    if ($groupModel->removeMember($group_id, $target, $user_id)) {
      // notify remaining members
      $notif = new Notification();
      $rem = $groupModel->getMembers($group_id); // fresh list
      $uids = [];
      foreach ($rem as $r) $uids[] = intval($r['id']);
      $actor = ($user_id == $target) ? 'left' : 'was removed from';
      // get target name
      $tname = '';
      foreach ($rem as $r) {
        if (intval($r['id']) == $target) {
          $tname = $r['name'];
          break;
        }
      }
      // fallback: fetch name if missing
      if (empty($tname)) {
        $stmt = (Database::getConnection())->prepare('SELECT name FROM users WHERE id = ?');
        if ($stmt) {
          $stmt->bind_param('i', $target);
          $stmt->execute();
          $rr = $stmt->get_result()->fetch_assoc();
          $tname = $rr['name'] ?? '';
          $stmt->close();
        }
      }
      $message = sprintf('%s %s the group %s', $tname, $actor, $group['name']);
      if (!empty($uids)) $notif->addNotifications($uids, $message, $group_id);
      // log activity
      $lg = new Log();
      $lg->add($user_id, $group_id, sprintf('User %d %s the group', $target, ($user_id == $target ? 'left' : 'was removed from')));

      header('Location: view_group.php?id=' . $group_id . '&msg=' . urlencode('Member removed'));
      exit;
    } else {
      $err = 'Failed to remove member. You must be the group creator to remove others, and the creator cannot be removed.';
    }
  }
}

// Handle transfer ownership (creator can transfer to another member)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['transfer_owner'])) {
  $target = intval($_POST['target_user_id'] ?? 0);
  if ($target > 0) {
    if ($groupModel->transferOwnership($group_id, $target, $user_id)) {
      header('Location: view_group.php?id=' . $group_id . '&msg=' . urlencode('Ownership transferred'));
      exit;
    } else {
      $err = 'Failed to transfer ownership. Only the creator can transfer and the target must be a member.';
    }
  }
}

// Handle marking a suggested settlement as paid
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_settled'])) {
  $from = intval($_POST['from_user'] ?? 0);
  $to = intval($_POST['to_user'] ?? 0);
  $amt = floatval($_POST['amount'] ?? 0);
  if ($from > 0 && $to > 0 && $amt > 0) {
    // only allow payer to mark as paid
    if ($user_id === $from) {
      if ($settModel->addSettlement($group_id, $from, $to, $amt)) {
        header('Location: view_group.php?id=' . $group_id . '&msg=' . urlencode('Marked as settled'));
        exit;
      } else {
        $err = 'Failed to record settlement.';
      }
    } else {
      $err = 'Only the payer can mark this as settled.';
    }
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_expense'])) {
  $title = trim($_POST['title'] ?? '');
  $amount = floatval($_POST['amount'] ?? 0);
  $paid_by = intval($_POST['paid_by'] ?? 0);
  $category = trim($_POST['category'] ?? '');
  $shared_with = $_POST['shared_with'] ?? [];
  $split_mode = $_POST['split_mode'] ?? 'equal';
  $split_values = null;

  if ($split_mode === 'percentage') {
    $split_values = [];
    foreach ($members as $m) {
      $id = $m['id'];
      if (in_array($id, $shared_with)) {
        $pct = floatval($_POST['pct_' . $id] ?? 0);
        $split_values[$id] = $pct;
      }
    }
  } elseif ($split_mode === 'custom') {
    $split_values = [];
    foreach ($members as $m) {
      $id = $m['id'];
      if (in_array($id, $shared_with)) {
        $amt_val = floatval($_POST['amt_' . $id] ?? 0);
        $split_values[$id] = $amt_val;
      }
    }
  }

  if ($title && $amount > 0 && $paid_by > 0 && is_array($shared_with) && count($shared_with) > 0) {
    $expenseModel->addExpense($group_id, $title, $amount, $paid_by, $shared_with, $split_mode, $split_values, $category ?: null);
    header('Location: view_group.php?id=' . $group_id . '&msg=' . urlencode('Expense added'));
    exit;
  } else {
    $err = 'Fill valid expense details';
  }
}

$balances = $calculator->computeBalances($group_id);
$settlements = $calculator->settleBalances($balances);
// check if balances are all (near) zero
$allSettled = true;
foreach ($balances as $b) {
  if (abs($b) > 0.01) {
    $allSettled = false;
    break;
  }
}

// --- Analytics: totals per user, monthly trend (last 6 months), category totals ---
$db = Database::getConnection();

// totals per user (only members) - include zeroes
$userTotals = [];
foreach ($members as $m) $userTotals[$m['id']] = 0.0;
$stmt = $db->prepare('SELECT paid_by, SUM(amount) as total FROM expenses WHERE group_id = ? GROUP BY paid_by');
if ($stmt) {
  $stmt->bind_param('i', $group_id);
  $stmt->execute();
  $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
  foreach ($res as $r) {
    $pid = intval($r['paid_by']);
    $userTotals[$pid] = round((float)$r['total'], 2);
  }
}

// monthly trend for last 6 months (YYYY-MM)
$months = [];
for ($i = 5; $i >= 0; $i--) {
  $m = date('Y-m', strtotime("-{$i} months"));
  $months[$m] = 0.0;
}
$stmt = $db->prepare("SELECT DATE_FORMAT(created_at, '%Y-%m') as ym, SUM(amount) as total FROM expenses WHERE group_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH) GROUP BY ym ORDER BY ym");
if ($stmt) {
  $stmt->bind_param('i', $group_id);
  $stmt->execute();
  $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
  foreach ($res as $r) {
    $ym = $r['ym'];
    if (isset($months[$ym])) $months[$ym] = round((float)$r['total'], 2);
  }
}

// category-wise totals
$catTotals = [];
$stmt = $db->prepare('SELECT COALESCE(category, \'Uncategorized\') as category, SUM(amount) as total FROM expenses WHERE group_id = ? GROUP BY category');
if ($stmt) {
  $stmt->bind_param('i', $group_id);
  $stmt->execute();
  $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
  foreach ($res as $r) {
    $catTotals[$r['category']] = round((float)$r['total'], 2);
  }
}

// summary insights for current user this month
$userMonthTotal = 0.0;
$userCat = [];
$stmt = $db->prepare('SELECT SUM(amount) as total FROM expenses WHERE group_id = ? AND paid_by = ? AND MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())');
if ($stmt) {
  $stmt->bind_param('ii', $group_id, $user_id);
  $stmt->execute();
  $r = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  $userMonthTotal = round((float)($r['total'] ?? 0), 2);
}
$stmt = $db->prepare('SELECT COALESCE(category, \'Uncategorized\') as category, SUM(amount) as total FROM expenses WHERE group_id = ? AND paid_by = ? AND MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE()) GROUP BY category ORDER BY total DESC');
if ($stmt) {
  $stmt->bind_param('ii', $group_id, $user_id);
  $stmt->execute();
  $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
  $stmt->close();
  foreach ($res as $r) $userCat[$r['category']] = round((float)$r['total'], 2);
}

// prepare JS data
$userLabels = [];
$userData = [];
foreach ($members as $m) {
  $userLabels[] = $m['name'];
  $userData[] = $userTotals[$m['id']] ?? 0.0;
}
$monthLabels = array_keys($months);
$monthData = array_values($months);
$catLabels = array_keys($catTotals);
$catData = array_values($catTotals);

// build summary sentence
$summaryText = '';
if ($userMonthTotal > 0) {
  // pick top 3 categories
  $top = array_slice($userCat, 0, 3, true);
  $pieces = [];
  foreach ($top as $c => $amt) {
    $pct = round(($amt / $userMonthTotal) * 100);
    $pieces[] = "$pct% on $c";
  }
  $summaryText = sprintf('You spent ₹%s this month — %s.', number_format($userMonthTotal, 2), implode(', ', $pieces));
}
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <title>Group: <?= htmlspecialchars($group['name']) ?></title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
  <?php include 'includes/header.php'; ?>
  <div class="container py-4">
    <?php if (isset($_GET['msg'])): ?>
      <div class="alert alert-success"><?= htmlspecialchars($_GET['msg']) ?></div>
    <?php endif; ?>
    <?php if (isset($err)): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>

    <div class="row">
      <div class="col-md-7">
        <h4>Group: <?= htmlspecialchars($group['name']) ?></h4>
        <div class="card mb-3">
          <div class="card-body">
            <h6>Expenses</h6>
            <?php if (count($expenses) === 0): ?>
              <p class="text-muted">No expenses yet.</p>
            <?php else: ?>
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th>Title</th>
                    <th>Amount</th>
                    <th>Paid by</th>
                    <th>Date</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($expenses as $e): ?>
                    <tr>
                      <td><?= htmlspecialchars($e['title']) ?></td>
                      <td>₹<?= number_format($e['amount'], 2) ?></td>
                      <td><?= htmlspecialchars($e['paid_by_name']) ?></td>
                      <td><?= htmlspecialchars($e['created_at']) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php endif; ?>
          </div>
        </div>

        <div class="card mb-3">
          <div class="card-body">
            <h6>Spending Overview</h6>
            <div style="height:260px;">
              <canvas id="chartUsers"></canvas>
              <div id="chartUsersEmpty" class="text-center text-muted" style="display:none;padding-top:60px;">No spending data to show</div>
            </div>
            <hr>
            <div style="height:240px;">
              <canvas id="chartMonths"></canvas>
              <div id="chartMonthsEmpty" class="text-center text-muted" style="display:none;padding-top:60px;">No monthly data to show</div>
            </div>
            <hr>
            <div style="height:240px;">
              <canvas id="chartCategories"></canvas>
              <div id="chartCategoriesEmpty" class="text-center text-muted" style="display:none;padding-top:60px;">No category data to show</div>
            </div>
          </div>
        </div>

        <h6>Add Expense</h6>
        <form method="post" class="card card-body mb-4">
          <div class="mb-2"><input name="title" class="form-control" placeholder="Expense title"></div>
          <div class="mb-2"><input name="amount" type="number" step="0.01" class="form-control" placeholder="Amount"></div>
          <div class="mb-2">
            <label>Category (optional)</label>
            <select name="category" class="form-select">
              <option value="">Uncategorized</option>
              <option value="Food">Food</option>
              <option value="Travel">Travel</option>
              <option value="Rent">Rent</option>
              <option value="Utilities">Utilities</option>
              <option value="Other">Other</option>
            </select>
          </div>
          <div class="mb-2">
            <label>Paid by</label>
            <select name="paid_by" class="form-select">
              <?php foreach ($members as $m): ?>
                <option value="<?= $m['id'] ?>" <?= ($m['id'] == $user_id ? 'selected' : '') ?>><?= htmlspecialchars($m['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-2">
            <label>Split options</label>
            <div class="mb-2">
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="split_mode" id="split_equal" value="equal" checked>
                <label class="form-check-label" for="split_equal">Split equally</label>
              </div>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="split_mode" id="split_percent" value="percentage">
                <label class="form-check-label" for="split_percent">Split by percentage</label>
              </div>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="split_mode" id="split_custom" value="custom">
                <label class="form-check-label" for="split_custom">Custom amounts</label>
              </div>
            </div>

            <div class="row">
              <?php foreach ($members as $m): ?>
                <div class="col-12 col-md-6 mb-2">
                  <div class="d-flex align-items-center">
                    <div class="form-check me-2">
                      <input class="form-check-input member-check" type="checkbox" name="shared_with[]" value="<?= $m['id'] ?>" id="u<?= $m['id'] ?>" checked>
                    </div>
                    <label class="form-check-label me-2" for="u<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?></label>
                    <div class="split-inputs d-flex">
                      <input type="number" step="0.01" min="0" name="pct_<?= $m['id'] ?>" placeholder="%" class="form-control form-control-sm me-2 pct-input" style="width:100px; display:none;">
                      <input type="number" step="0.01" min="0" name="amt_<?= $m['id'] ?>" placeholder="amt" class="form-control form-control-sm me-2 amt-input" style="width:120px; display:none;">
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
            <small class="text-muted">Choose who participates and pick a split mode. For percentage/custom, fill values for selected members.</small>
          </div>

          <button name="add_expense" class="btn btn-primary">Add Expense</button>
        </form>
      </div>

      <div class="col-md-5">
        <?php if (!empty($summaryText)): ?>
          <div class="card mb-3">
            <div class="card-body">
              <h6>Summary</h6>
              <p class="mb-0"><?= htmlspecialchars($summaryText) ?></p>
            </div>
          </div>
        <?php endif; ?>
        <div class="card mb-3">
          <div class="card-body">
            <h6>Members</h6>
            <ul class="list-group">
              <?php foreach ($members as $m): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <div>
                    <?= htmlspecialchars($m['name']) ?>
                    <?php if ($m['id'] == $group['created_by']): ?>
                      <small class="text-muted"> (creator)</small>
                    <?php endif; ?>
                  </div>
                  <div class="d-flex align-items-center">
                    <span class="badge bg-secondary me-2">₹<?= number_format($balances[$m['id']] ?? 0, 2) ?></span>
                    <?php // Show remove button to creator for other members, or show "Leave" to non-creator members 
                    ?>
                    <?php if ($user_id == $group['created_by'] && $m['id'] != $group['created_by']): ?>
                      <form method="post" style="display:inline;margin-right:6px;">
                        <input type="hidden" name="target_user_id" value="<?= $m['id'] ?>">
                        <button name="transfer_owner" class="btn btn-sm btn-outline-success" onclick="return confirm('Make <?= htmlspecialchars($m['name']) ?> the new group owner?')">Make owner</button>
                      </form>
                      <form method="post" style="display:inline;">
                        <input type="hidden" name="target_user_id" value="<?= $m['id'] ?>">
                        <button name="remove_member" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove <?= htmlspecialchars($m['name']) ?> from the group?')">Remove</button>
                      </form>
                    <?php elseif ($m['id'] == $user_id && $user_id != $group['created_by']): ?>
                      <form method="post" style="display:inline;">
                        <input type="hidden" name="target_user_id" value="<?= $m['id'] ?>">
                        <button name="remove_member" class="btn btn-sm btn-outline-secondary" onclick="return confirm('Leave the group?')">Leave</button>
                      </form>
                    <?php endif; ?>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>

        <div class="card mb-3">
          <div class="card-body">
            <h6>Settle Up Suggestions</h6>
            <?php if ($allSettled): ?>
              <div class="alert alert-success">All Settled</div>
            <?php else: ?>
              <?php if (count($settlements) === 0): ?>
                <p class="text-muted">No suggested settlements at the moment.</p>
              <?php else: ?>
                <ul class="list-group">
                  <?php
                  $names = [];
                  foreach ($members as $m) $names[$m['id']] = $m['name'];
                  foreach ($settlements as $t):
                  ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                      <div>
                        <strong><?= htmlspecialchars($names[$t['from']] ?? $t['from']) ?></strong>
                        pays
                        <strong><?= htmlspecialchars($names[$t['to']] ?? $t['to']) ?></strong>
                      </div>
                      <div class="d-flex align-items-center">
                        <span class="me-2">₹<?= number_format($t['amount'], 2) ?></span>
                        <?php if ($user_id === $t['from']): // payer can mark as settled 
                        ?>
                          <form method="post" style="display:inline;">
                            <input type="hidden" name="from_user" value="<?= intval($t['from']) ?>">
                            <input type="hidden" name="to_user" value="<?= intval($t['to']) ?>">
                            <input type="hidden" name="amount" value="<?= htmlspecialchars($t['amount']) ?>">
                            <button name="mark_settled" class="btn btn-sm btn-primary" onclick="return confirm('Mark this transaction as settled?')">Mark settled</button>
                          </form>
                        <?php endif; ?>
                      </div>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>

        <div class="card">
          <div class="card-body">
            <h6>Invite / Join</h6>
            <p class="small text-muted">Group ID: <strong><?= $group_id ?></strong></p>
            <form method="post" action="manage_members.php">
              <input type="hidden" name="group_id" value="<?= $group_id ?>">
              <div class="mb-2"><input name="email" class="form-control" placeholder="Friend's email to invite (adds if exists)"></div>
              <button name="invite" class="btn btn-outline-primary w-100">Invite (simple add)</button>
            </form>
            <?php if (isset($group['created_by']) && $group['created_by'] == $user_id): ?>
              <hr>
              <form method="post" onsubmit="return confirm('Are you sure? Deleting the group will remove all expenses and membership records.');">
                <input type="hidden" name="group_id" value="<?= $group_id ?>">
                <button name="delete_group" class="btn btn-danger w-100">Delete Group</button>
              </form>
            <?php endif; ?>
          </div>
        </div>

      </div>
    </div>
  </div>
</body>

</html>
<script>
  // Split mode UI handling
  document.addEventListener('DOMContentLoaded', function() {
    function updateInputs() {
      const mode = document.querySelector('input[name="split_mode"]:checked').value;
      const checks = Array.from(document.querySelectorAll('.member-check')).filter(c => c.checked);
      const cnt = checks.length || 1;
      const amountInput = document.querySelector('input[name="amount"]');
      const amount = parseFloat(amountInput ? amountInput.value : 0) || 0;
      document.querySelectorAll('.pct-input').forEach(function(el) {
        el.style.display = (mode === 'percentage') ? 'inline-block' : 'none';
      });
      document.querySelectorAll('.amt-input').forEach(function(el) {
        el.style.display = (mode === 'custom') ? 'inline-block' : 'none';
      });
      if (mode === 'percentage') {
        const val = +(100 / cnt).toFixed(2);
        checks.forEach(function(c) {
          const id = c.value;
          const el = document.querySelector('input[name="pct_' + id + '"]');
          if (el) el.value = val;
        });
      }
      if (mode === 'custom') {
        const val = amount > 0 ? +(amount / cnt).toFixed(2) : '';
        checks.forEach(function(c) {
          const id = c.value;
          const el = document.querySelector('input[name="amt_' + id + '"]');
          if (el) el.value = val;
        });
      }
    }
    document.querySelectorAll('input[name="split_mode"]').forEach(function(r) {
      r.addEventListener('change', updateInputs);
    });
    document.querySelectorAll('.member-check').forEach(function(c) {
      c.addEventListener('change', updateInputs);
    });
    const amountEl = document.querySelector('input[name="amount"]');
    if (amountEl) amountEl.addEventListener('input', updateInputs);
    // initial
    updateInputs();
  });
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  // Chart data from server
  const chartUserLabels = <?= json_encode($userLabels) ?>;
  const chartUserData = <?= json_encode($userData) ?>;
  const chartMonthLabels = <?= json_encode($monthLabels) ?>;
  const chartMonthData = <?= json_encode($monthData) ?>;
  const chartCatLabels = <?= json_encode($catLabels) ?>;
  const chartCatData = <?= json_encode($catData) ?>;

  function renderCharts() {
    // Users bar chart
    const ctxU = document.getElementById('chartUsers');
    const usersSum = chartUserData.reduce((s, v) => s + (parseFloat(v) || 0), 0);
    if (ctxU && chartUserLabels.length > 0 && usersSum > 0) {
      new Chart(ctxU.getContext('2d'), {
        type: 'bar',
        data: {
          labels: chartUserLabels,
          datasets: [{
            label: 'Total spent',
            data: chartUserData,
            backgroundColor: 'rgba(54,162,235,0.6)'
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false
        }
      });
    } else if (document.getElementById('chartUsersEmpty')) {
      document.getElementById('chartUsers').style.display = 'none';
      document.getElementById('chartUsersEmpty').style.display = 'block';
    }

    // Monthly line chart
    const ctxM = document.getElementById('chartMonths');
    const monthsSum = chartMonthData.reduce((s, v) => s + (parseFloat(v) || 0), 0);
    if (ctxM && chartMonthLabels.length > 0 && monthsSum > 0) {
      new Chart(ctxM.getContext('2d'), {
        type: 'line',
        data: {
          labels: chartMonthLabels,
          datasets: [{
            label: 'Monthly spending',
            data: chartMonthData,
            borderColor: 'rgba(75,192,192,1)',
            fill: false
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false
        }
      });
    } else if (document.getElementById('chartMonthsEmpty')) {
      document.getElementById('chartMonths').style.display = 'none';
      document.getElementById('chartMonthsEmpty').style.display = 'block';
    }

    // Category pie chart
    const ctxC = document.getElementById('chartCategories');
    const catsSum = chartCatData.reduce((s, v) => s + (parseFloat(v) || 0), 0);
    if (ctxC && chartCatLabels.length > 0 && catsSum > 0) {
      // generate colors if needed
      const colors = chartCatLabels.map((_, i) => ['#ff6384', '#36a2eb', '#ffcd56', '#4bc0c0', '#9966ff'][i % 5]);
      new Chart(ctxC.getContext('2d'), {
        type: 'pie',
        data: {
          labels: chartCatLabels,
          datasets: [{
            data: chartCatData,
            backgroundColor: colors
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false
        }
      });
    } else if (document.getElementById('chartCategoriesEmpty')) {
      document.getElementById('chartCategories').style.display = 'none';
      document.getElementById('chartCategoriesEmpty').style.display = 'block';
    }
  }

  // Render after DOM ready
  document.addEventListener('DOMContentLoaded', renderCharts);
</script>
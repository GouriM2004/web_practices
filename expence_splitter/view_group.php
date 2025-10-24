<?php
// view_group.php
session_start();
require_once 'includes/autoload.php';
if (!isset($_SESSION['user_id'])) header('Location: index.php');
$user_id = $_SESSION['user_id'];
$groupModel = new Group();
$expenseModel = new Expense();
$calculator = new Calculator();
$group_id = intval($_GET['id'] ?? 0);
if ($group_id <= 0) header('Location: dashboard.php');
$group = $groupModel->getGroup($group_id, $user_id);
if (!$group) header('Location: dashboard.php?msg=' . urlencode('You are not a member of this group'));
$members = $groupModel->getMembers($group_id);
$expenses = $expenseModel->getGroupExpenses($group_id);

// Handle delete group (only creator allowed)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_group'])) {
  if ($groupModel->delete($group_id, $user_id)) {
    header('Location: dashboard.php?msg=' . urlencode('Group deleted'));
    exit;
  } else {
    $err = 'Failed to delete group. Only the group creator can delete the group.';
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_expense'])) {
  $title = trim($_POST['title'] ?? '');
  $amount = floatval($_POST['amount'] ?? 0);
  $paid_by = intval($_POST['paid_by'] ?? 0);
  $shared_with = $_POST['shared_with'] ?? [];
  if ($title && $amount > 0 && $paid_by > 0 && is_array($shared_with) && count($shared_with) > 0) {
    $expenseModel->addExpense($group_id, $title, $amount, $paid_by, $shared_with);
    header('Location: view_group.php?id=' . $group_id . '&msg=' . urlencode('Expense added'));
    exit;
  } else {
    $err = 'Fill valid expense details';
  }
}

$balances = $calculator->computeBalances($group_id);
$settlements = $calculator->settleBalances($balances);
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

        <h6>Add Expense</h6>
        <form method="post" class="card card-body mb-4">
          <div class="mb-2"><input name="title" class="form-control" placeholder="Expense title"></div>
          <div class="mb-2"><input name="amount" type="number" step="0.01" class="form-control" placeholder="Amount"></div>
          <div class="mb-2">
            <label>Paid by</label>
            <select name="paid_by" class="form-select">
              <?php foreach ($members as $m): ?>
                <option value="<?= $m['id'] ?>" <?= ($m['id'] == $user_id ? 'selected' : '') ?>><?= htmlspecialchars($m['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-2">
            <label>Shared with (select one or more)</label>
            <div class="row">
              <?php foreach ($members as $m): ?>
                <div class="col-6 col-md-4">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="shared_with[]" value="<?= $m['id'] ?>" id="u<?= $m['id'] ?>" checked>
                    <label class="form-check-label" for="u<?= $m['id'] ?>"><?= htmlspecialchars($m['name']) ?></label>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <button name="add_expense" class="btn btn-primary">Add Expense</button>
        </form>
      </div>

      <div class="col-md-5">
        <div class="card mb-3">
          <div class="card-body">
            <h6>Members</h6>
            <ul class="list-group">
              <?php foreach ($members as $m): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <?= htmlspecialchars($m['name']) ?>
                  <span class="badge bg-secondary">₹<?= number_format($balances[$m['id']] ?? 0, 2) ?></span>
                </li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>

        <div class="card mb-3">
          <div class="card-body">
            <h6>Settle Up Suggestions</h6>
            <?php if (count($settlements) === 0): ?>
              <p class="text-muted">All settled up!</p>
            <?php else: ?>
              <ul class="list-group">
                <?php
                $names = [];
                foreach ($members as $m) $names[$m['id']] = $m['name'];
                foreach ($settlements as $t):
                ?>
                  <li class="list-group-item">
                    <strong><?= htmlspecialchars($names[$t['from']] ?? $t['from']) ?></strong>
                    pays
                    <strong><?= htmlspecialchars($names[$t['to']] ?? $t['to']) ?></strong>
                    <span class="float-end">₹<?= number_format($t['amount'], 2) ?></span>
                  </li>
                <?php endforeach; ?>
              </ul>
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
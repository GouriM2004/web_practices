<?php
// dashboard.php
session_start();
require_once 'includes/autoload.php';
if (!isset($_SESSION['user_id'])) header('Location: index.php');
$user_id = $_SESSION['user_id'];
$groupModel = new Group();
$groups = $groupModel->getUserGroups($user_id);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_group'])) {
    $gname = trim($_POST['group_name'] ?? '');
    if ($gname !== '') {
        $gid = $groupModel->create($gname, $user_id);
        if ($gid) header('Location: dashboard.php?msg=' . urlencode('Group created'));
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_group'])) {
    $gid = intval($_POST['group_id'] ?? 0);
    if ($gid > 0) {
        $groupModel->addMember($gid, $user_id);
        header('Location: dashboard.php?msg=' . urlencode('Joined group (if exists)'));
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Dashboard - Expense Splitter</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="container py-4">
  <?php if(isset($_GET['msg'])): ?>
    <div class="alert alert-success"><?=htmlspecialchars($_GET['msg'])?></div>
  <?php endif; ?>

  <div class="row">
    <div class="col-md-6">
      <h4>Your Groups</h4>
      <?php if(count($groups) === 0): ?>
        <p class="text-muted">You have no groups yet. Create or join one.</p>
      <?php else: ?>
        <ul class="list-group">
          <?php foreach($groups as $g): ?>
            <li class="list-group-item d-flex justify-content-between align-items-center">
              <div>
                <strong><?=htmlspecialchars($g['name'])?></strong>
                <div class="text-muted small">Created: <?=htmlspecialchars($g['created_at'])?></div>
              </div>
              <div>
                <a class="btn btn-sm btn-outline-primary" href="view_group.php?id=<?=$g['id']?>">Open</a>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>

    <div class="col-md-6">
      <h4>Create Group</h4>
      <form method="post" class="mb-3">
        <div class="mb-2"><input name="group_name" class="form-control" placeholder="Group name (e.g., Goa Trip)"></div>
        <button name="create_group" class="btn btn-success">Create</button>
      </form>

      <h4>Join Group</h4>
      <form method="post">
        <div class="mb-2"><input name="group_id" type="number" class="form-control" placeholder="Group ID"></div>
        <button name="join_group" class="btn btn-primary">Join</button>
      </form>
      <p class="text-muted mt-2">Tip: share Group ID with friends to let them join.</p>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

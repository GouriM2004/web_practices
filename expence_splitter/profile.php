<?php
// profile.php
session_start();
require_once 'includes/autoload.php';
if (!isset($_SESSION['user_id'])) header('Location: index.php');
$userModel = new User();
$user = $userModel->getById($_SESSION['user_id']);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Profile - <?=htmlspecialchars($user['name'])?></title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="container py-4">
  <div class="card">
    <div class="card-body">
      <h4>Your Profile</h4>
      <p><strong>Name:</strong> <?=htmlspecialchars($user['name'])?></p>
      <p><strong>Email:</strong> <?=htmlspecialchars($user['email'])?></p>
      <a href="dashboard.php" class="btn btn-secondary">Back</a>
    </div>
  </div>
</div>
</body>
</html>

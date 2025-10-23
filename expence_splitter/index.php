<?php
// index.php (landing)
session_start();
require_once 'includes/autoload.php';
if (isset($_SESSION['user_id'])) header('Location: dashboard.php');
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Expense Splitter - OOP</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
  <div class="row">
    <div class="col-md-6 offset-md-3">
      <div class="card shadow-sm">
        <div class="card-body">
          <h3 class="card-title text-center mb-3">Expense Splitter (OOP)</h3>
          <?php if(isset($_GET['msg'])): ?><div class="alert alert-info"><?=htmlspecialchars($_GET['msg'])?></div><?php endif; ?>

          <ul class="nav nav-tabs" id="authTab" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#login">Login</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#register">Register</button></li>
          </ul>

          <div class="tab-content mt-3">
            <div class="tab-pane fade show active" id="login">
              <form action="login.php" method="post">
                <div class="mb-3"><label>Email</label><input required name="email" type="email" class="form-control"></div>
                <div class="mb-3"><label>Password</label><input required name="password" type="password" class="form-control"></div>
                <button class="btn btn-primary w-100">Login</button>
              </form>
            </div>
            <div class="tab-pane fade" id="register">
              <form action="register.php" method="post">
                <div class="mb-3"><label>Name</label><input required name="name" type="text" class="form-control"></div>
                <div class="mb-3"><label>Email</label><input required name="email" type="email" class="form-control"></div>
                <div class="mb-3"><label>Password</label><input required name="password" type="password" class="form-control"></div>
                <button class="btn btn-success w-100">Register</button>
              </form>
            </div>
          </div>

        </div>
      </div>
      <p class="text-center text-muted mt-3">OOP refactor of the Expense Splitter project.</p>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

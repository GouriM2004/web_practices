<?php
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/Controllers/AuthController.php';
include __DIR__ . '/includes/header.php';

$message = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $user = AuthController::login($email, $password);
    if ($user) {
        header('Location: dashboard.php');
        exit;
    } else {
        $message = 'Login failed';
    }
}
?>

<div class="card mx-auto" style="max-width:480px;">
    <div class="card-body">
        <h3 class="card-title">Login</h3>
        <?php if ($message): ?><div class="alert alert-danger"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <form method="post">
            <div class="mb-3">
                <input class="form-control" type="email" name="email" placeholder="Email" required />
            </div>
            <div class="mb-3">
                <input class="form-control" type="password" name="password" placeholder="Password" required />
            </div>
            <button class="btn btn-primary">Login</button>
        </form>
        <div class="mt-3 small">Don't have an account? <a href="register.php">Register</a></div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
<?php
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/Controllers/AuthController.php';

$message = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $user = AuthController::login($email, $password);
    if ($user) {
        header('Location: index.php');
        exit;
    } else {
        $message = 'Login failed';
    }
}
?>

<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Login</title>
</head>

<body>
    <h2>Login</h2>
    <?php if ($message) echo '<p>' . htmlspecialchars($message) . '</p>'; ?>
    <form method="post">
        <input type="email" name="email" placeholder="Email" required /><br />
        <input type="password" name="password" placeholder="Password" required /><br />
        <button type="submit">Login</button>
    </form>
    <p>Or <a href="register.php">Register</a></p>
</body>

</html>
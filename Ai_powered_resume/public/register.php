<?php
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/Controllers/AuthController.php';
include __DIR__ . '/includes/header.php';

$message = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    try {
        $id = AuthController::register($name, $email, $password);
        $message = 'Registered. You can login now.';
    } catch (Exception $e) {
        $message = $e->getMessage();
    }
}
?>

<div class="card mx-auto" style="max-width:480px;">
    <div class="card-body">
        <h3 class="card-title">Register</h3>
        <?php if ($message): ?><div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
        <form method="post">
            <div class="mb-3">
                <input class="form-control" type="text" name="name" placeholder="Name" required />
            </div>
            <div class="mb-3">
                <input class="form-control" type="email" name="email" placeholder="Email" required />
            </div>
            <div class="mb-3">
                <input class="form-control" type="password" name="password" placeholder="Password" required />
            </div>
            <button class="btn btn-primary">Register</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
<?php
require_once __DIR__ . '/../src/bootstrap.php';
require_once __DIR__ . '/../src/Controllers/AuthController.php';

$message = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    try {
        $id = AuthController::register($name, $email, $password);
        $message = 'Registered. You can login now.';
    } catch (Exception $e) {
        $message = $e->getMessage();
    }
}
?>

<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <title>Register</title>
</head>

<body>
    <h2>Register</h2>
    <?php if ($message) echo '<p>' . htmlspecialchars($message) . '</p>'; ?>
    <form method="post">
        <input type="text" name="name" placeholder="Name" required /><br />
        <input type="email" name="email" placeholder="Email" required /><br />
        <input type="password" name="password" placeholder="Password" required /><br />
        <button type="submit">Register</button>
    </form>
</body>

</html>
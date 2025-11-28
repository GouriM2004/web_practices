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
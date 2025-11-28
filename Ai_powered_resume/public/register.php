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
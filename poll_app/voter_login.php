<?php
session_start();
require_once __DIR__ . '/includes/bootstrap.php';

$error = '';
$redirect = $_GET['redirect'] ?? 'index.php';
$pollId = isset($_GET['poll_id']) ? (int)$_GET['poll_id'] : 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $password = $_POST['password'] ?? '';
    $redirect = $_POST['redirect'] ?? 'index.php';
    $pollId = (int)($_POST['poll_id'] ?? 0);

    if ($name === '' || $password === '') {
        $error = 'Please provide both name and password.';
    } else {
        if (VoterAuth::loginOrRegister($name, $password)) {
            $target = $redirect ?: 'index.php';
            if ($pollId) {
                // keep poll context for voting
                $target .= (strpos($target, '?') === false ? '?' : '&') . 'poll_id=' . $pollId;
            }
            header('Location: ' . $target);
            exit;
        } else {
            $error = 'Invalid credentials.';
        }
    }
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Voter Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>

<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">Poll System</a>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card shadow-sm">
                    <div class="card-header bg-white text-center">
                        <h4 class="mb-0">Voter Login</h4>
                        <small class="text-muted">New here? Enter a name and password to create your account.</small>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        <form method="post">
                            <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirect) ?>">
                            <input type="hidden" name="poll_id" value="<?= $pollId ?>">
                            <div class="mb-3">
                                <label class="form-label">Display Name</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Login / Register</button>
                        </form>
                    </div>
                </div>
                <p class="text-center mt-3">
                    <a href="index.php" class="text-decoration-none">&laquo; Back to Polls</a>
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
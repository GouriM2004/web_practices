<?php
// Shared header for pages
?>
<!doctype html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Resume Tailor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/app.css" rel="stylesheet">
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">Resume Tailor</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain" aria-controls="navMain" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navMain">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="upload_resume.php">Upload</a></li>
                    <li class="nav-item"><a class="nav-link" href="analyze.php">Analyze</a></li>
                    <li class="nav-item"><a class="nav-link" href="tailor.php">Tailor</a></li>
                </ul>
                <div class="d-flex">
                    <?php
                    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
                    $userName = $_SESSION['user_name'] ?? null;
                    if ($userName):
                    ?>
                        <span class="navbar-text text-light me-2">Hello, <?php echo htmlspecialchars($userName); ?></span>
                        <a href="logout.php" class="btn btn-outline-light btn-sm">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline-light btn-sm me-2">Login</a>
                        <a href="register.php" class="btn btn-light btn-sm">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <main class="container my-4">
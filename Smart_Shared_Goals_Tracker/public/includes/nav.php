<?php
// simple nav; relies on $pdo available via bootstrap in header include
if (session_status() === PHP_SESSION_NONE) session_start();
$user = null;
if (!empty($_SESSION['user_id'])) {
    $stmt = $pdo->prepare('SELECT id, name, email FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="/smart_shared_goals_tracker/public/">Smart Goals</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMain" aria-controls="navMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="goals.php">Goals</a></li>
                <li class="nav-item"><a class="nav-link" href="groups.php">Groups</a></li>
                <li class="nav-item"><a class="nav-link" href="activity.php">Activity</a></li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <?php if ($user): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false"><?= htmlspecialchars($user['name']) ?></a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                            <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                            <li><a class="dropdown-item" href="settings.php">Settings</a></li>
                            <li><a class="dropdown-item" href="goals.php">My Goals</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" id="logoutLink" href="#">Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/outbox.js"></script>
<script src="assets/js/api.js"></script>
<script>
    // logout link wiring
    const logoutLink = document.getElementById('logoutLink');
    if (logoutLink) {
        logoutLink.addEventListener('click', async (e) => {
            e.preventDefault();
            await fetch('api.php/logout', {
                method: 'POST',
                credentials: 'include'
            });
            location.href = 'login.php';
        });
    }
</script>
<?php
$page_title = 'Activity';
$page_scripts = ['assets/js/activity.js'];
include __DIR__ . '/includes/header.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>

<div class="card">
    <div class="card-body">
        <h2 class="card-title">Recent activity</h2>
        <div id="activityList">
            <p>Loading…</p>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
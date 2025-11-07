<?php
$page_title = 'Create Goal';
include __DIR__ . '/includes/header.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$userId = $_SESSION['user_id'];
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $cadence = $_POST['cadence'] ?? 'daily';
    $unit = trim($_POST['unit'] ?? '');
    $start_date = $_POST['start_date'] ?: null;
    $end_date = $_POST['end_date'] ?: null;
    if ($title === '') {
        $error = 'Title is required';
    } else {
        $stmt = $pdo->prepare('INSERT INTO goals (title, description, created_by, cadence, unit, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$title, $desc, $userId, $cadence, $unit ?: null, $start_date, $end_date]);
        header('Location: goals.php');
        exit;
    }
}
?>
<div class="card">
    <div class="card-body">
        <h2>Create Goal</h2>
        <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post">
            <div class="mb-3">
                <label class="form-label">Title</label>
                <input name="title" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control"></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Cadence</label>
                <select name="cadence" class="form-select">
                    <option value="daily">Daily</option>
                    <option value="weekly">Weekly</option>
                    <option value="monthly">Monthly</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Unit (optional)</label>
                <input name="unit" class="form-control">
            </div>
            <div class="mb-3 row">
                <div class="col">
                    <label class="form-label">Start date</label>
                    <input name="start_date" type="date" class="form-control">
                </div>
                <div class="col">
                    <label class="form-label">End date</label>
                    <input name="end_date" type="date" class="form-control">
                </div>
            </div>
            <button class="btn btn-primary">Create</button>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

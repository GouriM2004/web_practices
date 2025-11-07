<?php
$page_title = 'Goal';
include __DIR__ . '/includes/header.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$userId = $_SESSION['user_id'];
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    echo '<div class="alert alert-danger">Goal not found</div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}
// fetch goal
$stmt = $pdo->prepare('SELECT * FROM goals WHERE id = ? AND active = 1');
$stmt->execute([$id]);
$goal = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$goal) {
    echo '<div class="alert alert-danger">Goal not found</div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}
// fetch last 60 checkins for chart
$stmt2 = $pdo->prepare('SELECT date, value, note, user_id FROM checkins WHERE goal_id = ? ORDER BY date DESC LIMIT 60');
$stmt2->execute([$id]);
$checkins = array_reverse($stmt2->fetchAll(PDO::FETCH_ASSOC));
?>
<div class="row">
    <div class="col-md-8">
        <h2><?= htmlspecialchars($goal['title']) ?></h2>
        <p class="text-muted"><?= htmlspecialchars($goal['description']) ?></p>
        <canvas id="chart" height="120"></canvas>
        <h4 class="mt-4">Recent Check-ins</h4>
        <ul class="list-group">
            <?php foreach ($checkins as $c): ?>
                <li class="list-group-item d-flex justify-content-between align-items-start">
                    <div><strong><?= htmlspecialchars($c['date']) ?></strong><br><?= htmlspecialchars($c['note']) ?></div>
                    <div><?= $c['value'] !== null ? htmlspecialchars($c['value']) : '&mdash;' ?></div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5>Quick Check-in</h5>
                <p><button id="quickCheck" class="btn btn-primary">Check in for today</button></p>
                <p><a href="goals.php" class="btn btn-link">Back to goals</a></p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const checkins = <?= json_encode($checkins) ?>;
    const labels = checkins.map(c => c.date);
    const data = checkins.map(c => c.value === null ? null : Number(c.value));
    const ctx = document.getElementById('chart');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Value',
                data,
                spanGaps: true,
                tension: 0.2
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    document.getElementById('quickCheck').addEventListener('click', async () => {
        const res = await window.checkIn(<?= $id ?>, {
            value: null,
            note: 'Quick check-in'
        });
        UI.toast(JSON.stringify(res));
    });
</script>
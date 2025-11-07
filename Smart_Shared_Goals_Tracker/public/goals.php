<?php
$page_title = 'My Goals';
include __DIR__ . '/includes/header.php';
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$userId = $_SESSION['user_id'];

// Fetch personal and group goals the user can see (simple: personal + created_by)
$stmt = $pdo->prepare("SELECT g.id, g.title, g.description, g.cadence, g.unit, g.start_date, g.end_date, m.current_streak, m.longest_streak, m.last_checkin
 FROM goals g
 LEFT JOIN goal_user_meta m ON m.goal_id = g.id AND m.user_id = :uid
 WHERE g.active = 1 AND (g.created_by = :uid OR g.group_id IS NULL)
 ORDER BY g.created_at DESC");
$stmt->execute(['uid' => $userId]);
$goals = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div>
    <h2>Your Goals</h2>
    <p>Quick check-in and outbox status: <span id="outboxCount">0</span> queued</p>
    <div id="goals">
        <?php if (!$goals): ?>
            <p>No goals yet. Create one from the dashboard.</p>
        <?php else: ?>
            <ul class="list-group">
                <?php foreach ($goals as $g): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-start" data-id="<?= htmlspecialchars($g['id']) ?>">
                        <div>
                            <div class="fw-bold"><a href="goal.php?id=<?= htmlspecialchars($g['id']) ?>"><?= htmlspecialchars($g['title']) ?></a></div>
                            <div class="small text-muted">Cadence: <?= htmlspecialchars($g['cadence']) ?> • Unit: <?= htmlspecialchars($g['unit'] ?? '') ?></div>
                        </div>
                        <div class="text-end">
                            <div>Streak: <strong><?= (int)$g['current_streak'] ?></strong></div>
                            <div>Longest: <strong><?= (int)$g['longest_streak'] ?></strong></div>
                            <div class="mt-2"><button class="btn btn-sm btn-outline-primary checkinBtn">Check in</button></div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
    <p class="mt-3"><a href="dashboard.php">Back to dashboard</a></p>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
<script src="assets/js/outbox.js"></script>
<script src="assets/js/app.js"></script>
<script>
    // Wire check-in buttons
    document.querySelectorAll('.checkinBtn').forEach(btn => {
        btn.addEventListener('click', async (e) => {
            const li = e.target.closest('[data-id]');
            const goalId = li.dataset.id;
            const value = prompt('Optional value (e.g., km, pages). Leave blank for simple completion:');
            const note = prompt('Optional note:');
            const res = await window.checkIn(goalId, {
                value: value || null,
                note: note || null
            });
            UI.toast(JSON.stringify(res));
            updateOutboxCount();
        });
    });

    async function updateOutboxCount() {
        if (!window.Outbox) return;
        const items = await Outbox.getAll();
        document.getElementById('outboxCount').textContent = items.length;
    }

    // initial count
    updateOutboxCount();

    navigator.serviceWorker && navigator.serviceWorker.addEventListener('message', ev => {
        if (ev.data && ev.data.type === 'sync-outbox') updateOutboxCount();
    });
</script>
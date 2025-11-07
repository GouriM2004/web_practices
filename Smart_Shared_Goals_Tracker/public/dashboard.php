<?php
$page_title = 'Dashboard';
include __DIR__ . '/includes/header.php';
?>
<div class="row">
    <div class="col-md-8">
        <h2>Dashboard</h2>
        <div id="user">Loading...</div>
        <p class="mt-3"><a href="goals.php" class="btn btn-outline-primary">My Goals</a>
            <a href="create_goal.php" class="btn btn-primary">Create Goal</a>
        </p>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5>Outbox</h5>
                <p>Queued check-ins: <span id="outboxCount">0</span></p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
<script>
    async function loadMe() {
        try {
            const json = await Api.get('api.php/me');
            const u = json.user;
            document.getElementById('user').innerHTML = `<strong>${u.name}</strong><br>${u.email}`;
        } catch (err) {
            location.href = 'login.php';
        }
    }
    loadMe();
    async function updateOutbox() {
        if (!window.Outbox) return;
        const items = await Outbox.getAll();
        document.getElementById('outboxCount').textContent = items.length;
    }
    updateOutbox();
</script>
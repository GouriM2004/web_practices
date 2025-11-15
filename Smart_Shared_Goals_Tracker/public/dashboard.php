<?php
$page_title = 'Dashboard';
$page_scripts = ['assets/js/quotes.js'];
include __DIR__ . '/includes/header.php';
?>
<div class="row">
    <div class="col-md-8">
        <h2>Dashboard</h2>
        <div id="user">Loading...</div>
        <p class="mt-3">
            <button id="myGoalsBtn" class="btn btn-outline-primary">My Goals</button>
            <button id="createGoalBtn" class="btn btn-primary">Create Goal</button>
        </p>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h5>Daily Motivation</h5>
                <div id="quoteCard">
                    <div id="quoteText" class="mb-2 text-muted">Loading quote...</div>
                    <div id="quoteAuthor" class="small text-end text-primary"></div>
                    <div class="mt-2 text-end"><button id="newQuoteBtn" class="btn btn-sm btn-outline-secondary">New</button></div>
                </div>
                <hr>
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
            let html = `<strong>${u.name}</strong><br>${u.email}`;
            // show XP / level if present
            if (u.xp) {
                const xp = u.xp.xp_total || 0;
                const lvl = u.xp.level || 0;
                const nextLevelXp = (lvl + 1) * 100;
                const pct = Math.min(100, Math.round((xp / nextLevelXp) * 100));
                html += `<div class="mt-2">Level ${lvl} — ${xp} XP</div>`;
                html += `<div class="progress mt-1" style="height:10px;"><div class="progress-bar" role="progressbar" style="width:${pct}%" aria-valuenow="${pct}" aria-valuemin="0" aria-valuemax="100"></div></div>`;
            }
            document.getElementById('user').innerHTML = html;
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
    // wire dashboard buttons to navigate without anchors
    document.getElementById('myGoalsBtn')?.addEventListener('click', function() {
        location.href = 'goals.php';
    });
    document.getElementById('createGoalBtn')?.addEventListener('click', function() {
        location.href = 'create_goal.php';
    });
</script>
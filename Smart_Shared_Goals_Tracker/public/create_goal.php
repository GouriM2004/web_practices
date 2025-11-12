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
$groupId = isset($_GET['group_id']) ? (int)$_GET['group_id'] : null;
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
        if (!empty($_POST['group_id'])) {
            $gId = (int)$_POST['group_id'];
            $stmt = $pdo->prepare('INSERT INTO goals (title, description, created_by, group_id, cadence, unit, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$title, $desc, $userId, $gId, $cadence, $unit ?: null, $start_date, $end_date]);
        } else {
            $stmt = $pdo->prepare('INSERT INTO goals (title, description, created_by, cadence, unit, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$title, $desc, $userId, $cadence, $unit ?: null, $start_date, $end_date]);
        }
        header('Location: goals.php');
        exit;
    }
}
?>
<div class="card">
    <div class="card-body">
        <h2>Create Goal</h2>
        <div class="mb-3">
            <label class="form-label">Start from a template (optional)</label>
            <select id="templatePicker" class="form-select">
                <option value="">-- Choose a template --</option>
            </select>
            <div id="templateHelp" class="form-text">Pick a template to prefill the form. You can still edit values before creating.</div>
        </div>
        <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <form method="post">
            <?php if ($groupId): ?>
                <input type="hidden" name="group_id" value="<?= (int)$groupId ?>">
            <?php endif; ?>
            <div class="mb-3">
                <label class="form-label">Title</label>
                <input name="title" id="title" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" id="description" class="form-control"></textarea>
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
                <input name="unit" id="unit" class="form-control">
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

<script>
    // load templates and wire picker to prefill the form
    (async function() {
        try {
            const res = await fetch('api.php/templates', {
                credentials: 'include'
            });
            if (!res.ok) return;
            const payload = await res.json();
            const sel = document.getElementById('templatePicker');
            if (!payload.templates || !payload.templates.length) return;
            payload.templates.forEach(t => {
                const opt = document.createElement('option');
                opt.value = t.id;
                opt.textContent = t.title + (t.cadence ? ' (' + t.cadence + ')' : '');
                sel.appendChild(opt);
            });

            sel.addEventListener('change', async function() {
                const tid = this.value;
                if (!tid) return;
                const r = await fetch('api.php/templates/' + encodeURIComponent(tid), {
                    credentials: 'include'
                });
                if (!r.ok) return;
                const p = await r.json();
                const t = p.template;
                if (!t) return;
                // prefill fields
                document.getElementById('title').value = t.title || document.getElementById('title').value;
                document.getElementById('description').value = t.description || document.getElementById('description').value;
                document.querySelector('select[name="cadence"]').value = t.cadence || document.querySelector('select[name="cadence"]').value;
                document.getElementById('unit').value = t.unit || document.getElementById('unit').value;
            });
        } catch (e) {
            console.warn('Failed to load templates', e);
        }
    })();
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
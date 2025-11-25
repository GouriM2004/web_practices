<?php
$page_title = 'Goal';
$page_scripts = ['assets/js/chat.js'];
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
// determine whether current user can change visibility (owner or group admin)
$canEditVisibility = false;
$goalVisibility = $goal['visibility'] ?? 'private';
if ((int)$goal['created_by'] === (int)$userId) {
    $canEditVisibility = true;
} elseif (!empty($goal['group_id'])) {
    $stmtRole = $pdo->prepare('SELECT role FROM group_members WHERE group_id = ? AND user_id = ?');
    $stmtRole->execute([$goal['group_id'], $userId]);
    $role = $stmtRole->fetchColumn();
    if (in_array($role, ['admin', 'owner'])) $canEditVisibility = true;
}
// recompute streaks for current user and persist (ensure goal_user_meta is up-to-date)
try {
    $streakSvc = new \Services\StreakService($pdo);
    $streaks = $streakSvc->computeAndStoreStreaks($userId, $id);
} catch (Exception $e) {
    $streaks = ['current' => 0, 'longest' => 0, 'last_checkin' => null];
}
// fetch last 60 checkins for chart
$stmt2 = $pdo->prepare('SELECT date, value, note, user_id FROM checkins WHERE goal_id = ? ORDER BY date DESC LIMIT 60');
$stmt2->execute([$id]);
$checkins = array_reverse($stmt2->fetchAll(PDO::FETCH_ASSOC));
// fetch checkins for last year for heatmap & stats (current user)
$stmtHeat = $pdo->prepare('SELECT date FROM checkins WHERE goal_id = ? AND user_id = ? AND date >= DATE_SUB(CURDATE(), INTERVAL 365 DAY)');
$stmtHeat->execute([$id, $userId]);
$heatRows = $stmtHeat->fetchAll(PDO::FETCH_COLUMN);

// utility: convert PHP dates into JS-friendly arrays
$heatDates = array_map(function ($d) {
    return $d;
}, $heatRows);
?>
<div class="row">
    <div class="col-md-8">
        <h2><?= htmlspecialchars($goal['title']) ?></h2>
        <?php if (!empty($streaks['current'])): ?>
            <div class="mt-2 mb-2"><span class="badge bg-warning text-dark">🔥 <?= (int)$streaks['current'] ?>-day streak!</span></div>
        <?php endif; ?>
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
                <hr>
                <h6>Progress</h6>
                <div id="progressMetrics">
                    <div>Completion (30d): <strong id="pctComplete">—</strong></div>
                    <div>Consistency (30d): <strong id="consistency">—</strong></div>
                    <div>Current streak: <strong id="currentStreak">—</strong></div>
                    <div>Longest streak: <strong id="longestStreak">—</strong></div>
                </div>
                <hr>
                <div class="mb-2">
                    Visibility: <span id="goalVisibilityBadge" class="badge <?= $goalVisibility === 'public' ? 'bg-success' : 'bg-secondary' ?>"><?= htmlspecialchars(ucfirst($goalVisibility)) ?></span>
                    <?php if ($canEditVisibility): ?>
                        <button id="toggleVisibilityBtn" class="btn btn-sm btn-outline-primary ms-2"><?= $goalVisibility === 'public' ? 'Make private' : 'Make public' ?></button>
                    <?php endif; ?>
                </div>
                <hr>
                <h6>Streak heatmap (past year)</h6>
                <div id="heatmap" style="width:100%; overflow:auto;">
                    <div id="heatmapGrid" style="display:grid; grid-auto-flow:column; grid-template-rows: repeat(7,12px); grid-auto-columns: 12px; gap:4px; align-items:start;"></div>
                </div>
                <hr>
                <div id="teamProgress" class="mt-3">
                    <!-- Team progress & leaderboard (populated by JS) -->
                </div>
                <hr>
                <div id="dependenciesSection" class="mt-3">
                    <h6>Dependencies</h6>
                    <div id="depsList">
                        <div class="small text-muted">Loading dependencies…</div>
                    </div>
                    <div class="mt-2">
                        <form id="addDependencyForm" class="row gx-2">
                            <div class="col-6">
                                <select id="depTargetSelect" class="form-select form-select-sm">
                                    <option value="">Select a goal...</option>
                                </select>
                            </div>
                            <div class="col-4">
                                <select id="depTypeSelect" class="form-select form-select-sm">
                                    <option value="requires">Requires (must do first)</option>
                                    <option value="triggers">Triggers (auto-create)</option>
                                </select>
                            </div>
                            <div class="col-2">
                                <button id="addDepBtn" class="btn btn-sm btn-primary w-100" type="submit">Add</button>
                            </div>
                        </form>
                        <div class="form-text small text-muted mt-1">Dependencies help chain goals; only goal owners or group admins can manage them.</div>
                    </div>
                </div>
                <hr>
                <h6>Discussion</h6>
                <div id="goalChat" class="card" data-goal-id="<?= $id ?>">
                    <div class="card-body">
                        <div id="messagesContainer" style="max-height:240px; overflow:auto;" class="mb-2"></div>
                        <div class="input-group">
                            <input id="chatInput" type="text" class="form-control" placeholder="Write a message to the group...">
                            <button id="sendChatBtn" class="btn btn-primary">Send</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const serverStreaks = <?= json_encode($streaks) ?>;
    const checkins = <?= json_encode($checkins) ?>;
    const heatDates = <?= json_encode($heatDates) ?>; // array of YYYY-MM-DD strings
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

    // Progress metrics & heatmap
    (function renderProgress() {
        // helper: set text
        function setId(id, txt) {
            const el = document.getElementById(id);
            if (el) el.textContent = txt;
        }

        // build set of dates
        const checked = new Set(heatDates || []);
        const today = new Date();
        // compute last 30 days window
        const daysWindow = 30;
        const msDay = 24 * 60 * 60 * 1000;
        let haveCount = 0;
        for (let i = 0; i < daysWindow; i++) {
            const d = new Date(Date.now() - (daysWindow - 1 - i) * msDay);
            const key = d.toISOString().slice(0, 10);
            if (checked.has(key)) haveCount++;
        }
        const pct = Math.round((haveCount / daysWindow) * 100);
        setId('pctComplete', pct + '% (' + haveCount + '/' + daysWindow + ')');

        // consistency = ratio of weeks with at least one check-in over last 4 weeks
        const weeks = 4;
        let weeksWith = 0;
        for (let w = 0; w < weeks; w++) {
            const start = new Date();
            start.setDate(start.getDate() - (w * 7 + (7 - 1)));
            const end = new Date();
            end.setDate(end.getDate() - (w * 7));
            // normalize to YYYY-MM-DD
            let found = false;
            for (let d = new Date(start); d <= end; d.setDate(d.getDate() + 1)) {
                const k = d.toISOString().slice(0, 10);
                if (checked.has(k)) {
                    found = true;
                    break;
                }
            }
            if (found) weeksWith++;
        }
        const consistencyPct = Math.round((weeksWith / weeks) * 100);
        setId('consistency', consistencyPct + '% (' + weeksWith + '/' + weeks + ' weeks)');

        // streaks: compute current and longest streak from heatDates
        // build sorted array of unique dates
        const sorted = Array.from(checked).sort();
        let longest = 0;
        let current = 0;
        let prev = null;
        // compute longest streak
        for (let d of sorted) {
            if (!prev) {
                current = 1;
            } else {
                const prevDate = new Date(prev);
                const curDate = new Date(d);
                const diff = (curDate - prevDate) / msDay;
                if (diff === 1) {
                    current += 1;
                } else {
                    if (current > longest) longest = current;
                    current = 1;
                }
            }
            prev = d;
        }
        if (current > longest) longest = current;

        // compute current streak ending today
        let curStreak = 0;
        for (let i = 0; i < 365; i++) {
            const d = new Date();
            d.setDate(d.getDate() - i);
            const k = d.toISOString().slice(0, 10);
            if (checked.has(k)) curStreak++;
            else break;
        }
        setId('currentStreak', curStreak + ' days');
        setId('longestStreak', longest + ' days');

        // render heatmap (past year)
        const startDate = new Date();
        startDate.setDate(startDate.getDate() - 364);
        const grid = document.getElementById('heatmapGrid');
        if (!grid) return;
        // clear
        grid.innerHTML = '';
        for (let d = new Date(startDate); d <= new Date(); d.setDate(d.getDate() + 1)) {
            const k = d.toISOString().slice(0, 10);
            const el = document.createElement('div');
            el.title = k;
            el.dataset.date = k;
            el.style.width = '12px';
            el.style.height = '12px';
            el.style.borderRadius = '3px';
            el.style.display = 'inline-block';
            el.style.boxSizing = 'border-box';
            el.style.cursor = 'default';
            if (checked.has(k)) {
                el.style.background = '#216e39';
            } else {
                el.style.background = '#ebedf0';
            }
            grid.appendChild(el);
        }
    })();

    // Team progress & leaderboard (for group goals / collaborative goals)
    (async function renderTeamProgress() {
        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
        }
        try {
            const res = await fetch('api.php/goals/<?= $id ?>/stats', {
                credentials: 'include'
            });
            if (!res.ok) return;
            const payload = await res.json();
            if (!payload || !payload.stats || !payload.stats.length) return;
            const container = document.getElementById('teamProgress');
            if (!container) return;
            container.innerHTML = '';
            const heading = document.createElement('h6');
            heading.textContent = 'Team progress & leaderboard';
            container.appendChild(heading);

            // compute max for scaling
            let maxMetric = 0;
            payload.stats.forEach(s => {
                const metric = s.total_value || s.checkins;
                if (metric > maxMetric) maxMetric = metric;
            });
            if (maxMetric === 0) maxMetric = 1;

            const list = document.createElement('div');
            list.className = 'list-group mb-2';
            payload.stats.forEach((s, idx) => {
                const item = document.createElement('div');
                item.className = 'list-group-item';
                const title = document.createElement('div');
                title.className = 'd-flex justify-content-between align-items-center';
                title.innerHTML = '<div><strong>' + escapeHtml(s.name) + '</strong> <div class="small text-muted">' + (s.email || '') + '</div></div><div class="text-end"><small>' + (s.pct !== null ? s.pct + '%' : (s.checkins + ' checks')) + '</small></div>';
                item.appendChild(title);

                const metric = s.total_value || s.checkins;
                const pct = Math.round((metric / maxMetric) * 100);
                const barWrap = document.createElement('div');
                barWrap.className = 'progress mt-2';
                const bar = document.createElement('div');
                bar.className = 'progress-bar';
                bar.setAttribute('role', 'progressbar');
                bar.style.width = pct + '%';
                bar.textContent = metric;
                barWrap.appendChild(bar);
                item.appendChild(barWrap);

                list.appendChild(item);
            });

            container.appendChild(list);

            const top = payload.stats[0];
            if (top) {
                const trophy = document.createElement('div');
                trophy.className = 'small text-muted';
                trophy.innerHTML = 'Top: <strong>' + escapeHtml(top.name) + '</strong> — ' + (top.total_value || top.checkins) + '';
                container.appendChild(trophy);
            }
        } catch (e) {
            console.warn('Failed to load team stats', e);
        }
    })();

    // Visibility toggle handler
    (function() {
        const visBadge = document.getElementById('goalVisibilityBadge');
        const toggleBtn = document.getElementById('toggleVisibilityBtn');
        if (!toggleBtn || !visBadge) return;
        toggleBtn.addEventListener('click', async () => {
            const current = visBadge.textContent.trim().toLowerCase();
            const newVis = current === 'public' ? 'private' : 'public';
            try {
                const r = await fetch('api.php/goals/<?= $id ?>/visibility', {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        visibility: newVis
                    })
                });
                const payload = await r.json();
                if (!r.ok) {
                    if (window.UI && UI.toast) UI.toast(payload.error || 'Failed to update visibility');
                    else alert(payload.error || 'Failed to update visibility');
                    return;
                }
                const v = payload.visibility;
                visBadge.textContent = v.charAt(0).toUpperCase() + v.slice(1);
                visBadge.className = v === 'public' ? 'badge bg-success' : 'badge bg-secondary';
                toggleBtn.textContent = v === 'public' ? 'Make private' : 'Make public';
                if (window.UI && UI.toast) UI.toast('Visibility updated');
            } catch (e) {
                console.error(e);
                if (window.UI && UI.toast) UI.toast('Network error');
                else alert('Network error');
            }
        });
    })();

    // Dependencies management (list / add / delete)
    (function() {
        const subjectId = <?= $id ?>;
        const depsList = document.getElementById('depsList');
        const depTargetSelect = document.getElementById('depTargetSelect');
        const depTypeSelect = document.getElementById('depTypeSelect');
        const addDependencyForm = document.getElementById('addDependencyForm');
        const addDepBtn = document.getElementById('addDepBtn');

        async function loadGoalOptions() {
            try {
                const r = await fetch('api.php/goals', {
                    credentials: 'include'
                });
                if (!r.ok) {
                    console.warn('Failed loading goals for dependency select', r.status);
                    depTargetSelect.innerHTML = '<option value="">Failed to load goals</option>';
                    depTargetSelect.disabled = true;
                    if (addDepBtn) addDepBtn.disabled = true;
                    return;
                }
                const j = await r.json();
                const goals = j.goals || [];
                depTargetSelect.innerHTML = '<option value="">Select a goal...</option>';
                let count = 0;
                goals.forEach(g => {
                    if (g.id === subjectId) return;
                    const opt = document.createElement('option');
                    opt.value = g.id;
                    opt.textContent = g.title + (g.visibility === 'public' ? ' (public)' : '');
                    depTargetSelect.appendChild(opt);
                    count++;
                });
                if (count === 0) {
                    depTargetSelect.innerHTML = '<option value="">No other goals available</option>';
                    depTargetSelect.disabled = true;
                    if (addDepBtn) addDepBtn.disabled = true;
                } else {
                    depTargetSelect.disabled = false;
                    if (addDepBtn) addDepBtn.disabled = false;
                }
            } catch (e) {
                console.warn('Could not load goals for dependency select', e);
                depTargetSelect.innerHTML = '<option value="">Failed to load goals</option>';
                depTargetSelect.disabled = true;
                if (addDepBtn) addDepBtn.disabled = true;
            }
        }

        async function loadDependencies() {
            depsList.innerHTML = '<div class="small text-muted">Loading dependencies…</div>';
            try {
                const r = await fetch('api.php/goals/' + subjectId + '/dependencies', {
                    credentials: 'include'
                });
                if (!r.ok) {
                    depsList.innerHTML = '<div class="text-danger small">Failed to load</div>';
                    return;
                }
                const j = await r.json();
                const outgoing = j.outgoing || [];
                const incoming = j.incoming || [];
                const wrap = document.createElement('div');

                if (outgoing.length) {
                    const h = document.createElement('div');
                    h.className = 'small fw-bold';
                    h.textContent = 'Outgoing (this goal depends on)';
                    wrap.appendChild(h);
                    const list = document.createElement('div');
                    list.className = 'list-group mb-2';
                    outgoing.forEach(d => {
                        const it = document.createElement('div');
                        it.className = 'list-group-item d-flex justify-content-between align-items-center p-2';
                        it.innerHTML = '<div><strong>' + escapeHtml(d.object_title) + '</strong><div class="small text-muted">' + escapeHtml(d.relation_type) + '</div></div>';
                        const del = document.createElement('button');
                        del.className = 'btn btn-sm btn-outline-danger';
                        del.textContent = 'Delete';
                        del.addEventListener('click', async () => {
                            if (!confirm('Delete this dependency?')) return;
                            try {
                                const rr = await fetch('api.php/goals/' + subjectId + '/dependencies/' + d.id, {
                                    method: 'DELETE',
                                    credentials: 'include'
                                });
                                const pj = await rr.json();
                                if (!rr.ok) {
                                    UI.toast(pj.error || 'Failed to delete');
                                    return;
                                }
                                UI.toast('Dependency removed');
                                await loadDependencies();
                            } catch (e) {
                                console.error(e);
                                UI.toast('Network error');
                            }
                        });
                        it.appendChild(del);
                        list.appendChild(it);
                    });
                    wrap.appendChild(list);
                } else {
                    const no = document.createElement('div');
                    no.className = 'small text-muted mb-2';
                    no.textContent = 'No outgoing dependencies';
                    wrap.appendChild(no);
                }

                if (incoming.length) {
                    const h2 = document.createElement('div');
                    h2.className = 'small fw-bold';
                    h2.textContent = 'Incoming (other goals depend on this)';
                    wrap.appendChild(h2);
                    const list2 = document.createElement('div');
                    list2.className = 'list-group';
                    incoming.forEach(d => {
                        const it = document.createElement('div');
                        it.className = 'list-group-item p-2';
                        it.innerHTML = '<div><strong>' + escapeHtml(d.subject_title) + '</strong><div class="small text-muted">' + escapeHtml(d.relation_type) + '</div></div>';
                        list2.appendChild(it);
                    });
                    wrap.appendChild(list2);
                }

                depsList.innerHTML = '';
                depsList.appendChild(wrap);
            } catch (e) {
                console.error(e);
                depsList.innerHTML = '<div class="text-danger small">Error loading dependencies</div>';
            }
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\"/g, '&quot;').replace(/'/g, '&#39;');
        }

        addDependencyForm.addEventListener('submit', async (ev) => {
            ev.preventDefault();
            const target = Number(depTargetSelect.value || 0);
            const rel = depTypeSelect.value || 'requires';
            if (!target) {
                UI.toast('Select a target goal');
                return;
            }
            try {
                const r = await fetch('api.php/goals/' + subjectId + '/dependencies', {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        object_goal_id: target,
                        relation_type: rel
                    })
                });
                const j = await r.json();
                if (!r.ok) {
                    UI.toast(j.error || 'Failed to add dependency');
                    return;
                }
                UI.toast('Dependency added');
                depTargetSelect.value = '';
                depTypeSelect.value = 'requires';
                await loadDependencies();
            } catch (e) {
                console.error(e);
                UI.toast('Network error');
            }
        });

        // init
        loadGoalOptions();
        loadDependencies();
    })();
</script>
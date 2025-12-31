<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trending Polls - Poll Battles</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .trending-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            padding: 40px;
            margin: 40px auto;
            max-width: 1000px;
        }

        .trending-header {
            text-align: center;
            margin-bottom: 40px;
            animation: fadeIn 0.5s ease-in;
        }

        .trending-header h1 {
            font-size: 2.5em;
            color: #667eea;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .trending-header p {
            font-size: 1.1em;
            color: #666;
        }

        .tabs {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            border-bottom: 2px solid #eee;
            flex-wrap: wrap;
        }

        .tab-button {
            background: none;
            border: none;
            padding: 10px 20px;
            font-size: 1em;
            color: #999;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 500;
            border-bottom: 3px solid transparent;
        }

        .tab-button.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }

        .tab-button:hover {
            color: #667eea;
        }

        .leaderboard-table {
            width: 100%;
            border-collapse: collapse;
        }

        .leaderboard-table thead {
            background: #f8f9fa;
        }

        .leaderboard-table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #667eea;
            border-bottom: 2px solid #eee;
        }

        .leaderboard-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }

        .leaderboard-table tbody tr:hover {
            background: #f8f9fa;
            transition: all 0.3s ease;
        }

        .rank-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 35px;
            height: 35px;
            border-radius: 50%;
            font-weight: bold;
            color: white;
            font-size: 1.1em;
        }

        .rank-1 {
            background: linear-gradient(135deg, #ffd700, #ffed4e);
            color: #333;
        }

        .rank-2 {
            background: linear-gradient(135deg, #c0c0c0, #e8e8e8);
            color: #333;
        }

        .rank-3 {
            background: linear-gradient(135deg, #cd7f32, #d4a574);
        }

        .rank-other {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .poll-title {
            font-weight: 600;
            color: #333;
            max-width: 400px;
        }

        .stat-badge {
            display: inline-block;
            background: #f0f0f0;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            color: #667eea;
            font-weight: 600;
            margin-right: 10px;
        }

        .win-rate {
            font-size: 1.2em;
            font-weight: bold;
            color: #667eea;
        }

        .battle-row {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 15px;
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 20px;
            align-items: center;
            transition: all 0.3s ease;
        }

        .battle-row:hover {
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
        }

        .battle-opponent {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .opponent-label {
            font-weight: 600;
            color: #667eea;
            min-width: 40px;
        }

        .opponent-question {
            color: #333;
            font-size: 0.95em;
        }

        .vs-text {
            text-align: center;
            color: #999;
            font-weight: bold;
            padding: 0 10px;
        }

        .battle-result {
            text-align: center;
        }

        .battle-winner {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9em;
        }

        .battle-date {
            color: #999;
            font-size: 0.85em;
            margin-top: 5px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state-icon {
            font-size: 3em;
            color: #ddd;
            margin-bottom: 20px;
        }

        .loading-spinner {
            text-align: center;
            padding: 40px;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .nav-buttons {
            text-align: center;
            margin-top: 30px;
        }

        .nav-buttons a {
            margin: 0 10px;
        }

        @media (max-width: 768px) {
            .trending-container {
                padding: 20px;
            }

            .trending-header h1 {
                font-size: 1.8em;
            }

            .leaderboard-table th,
            .leaderboard-table td {
                padding: 10px;
                font-size: 0.9em;
            }

            .poll-title {
                max-width: 200px;
            }

            .battle-row {
                grid-template-columns: 1fr;
                gap: 10px;
            }
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark" style="background: rgba(0, 0, 0, 0.3);">
        <div class="container">
            <a class="navbar-brand" href="index.php">🏆 Poll Battles Trending</a>
            <div class="d-flex gap-2">
                <a href="poll_battles.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-gamepad"></i> Battle Arena
                </a>
                <a href="index.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-home"></i> Home
                </a>
            </div>
        </div>
    </nav>

    <div class="trending-container">
        <div class="trending-header">
            <h1>🔥 TRENDING POLLS</h1>
            <p>Battle Champions Ranked by Victory</p>
        </div>

        <div class="tabs">
            <button class="tab-button active" onclick="switchTab('leaderboard')">
                <i class="fas fa-trophy"></i> Leaderboard
            </button>
            <button class="tab-button" onclick="switchTab('history')">
                <i class="fas fa-history"></i> Battle History
            </button>
        </div>

        <!-- Leaderboard Tab -->
        <div id="leaderboard-tab" class="tab-content">
            <div id="leaderboard-loading" class="loading-spinner">
                <div class="spinner"></div>
                <p class="mt-3">Loading Leaderboard...</p>
            </div>
            <div id="leaderboard-content" style="display: none;">
                <table class="leaderboard-table">
                    <thead>
                        <tr>
                            <th width="5%">Rank</th>
                            <th width="50%">Poll Question</th>
                            <th width="15%">Wins</th>
                            <th width="15%">Losses</th>
                            <th width="15%">Win Rate</th>
                        </tr>
                    </thead>
                    <tbody id="leaderboard-body">
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Battle History Tab -->
        <div id="history-tab" class="tab-content" style="display: none;">
            <div id="history-loading" class="loading-spinner">
                <div class="spinner"></div>
                <p class="mt-3">Loading Battle History...</p>
            </div>
            <div id="history-content" style="display: none;">
                <div id="history-body"></div>
            </div>
        </div>

        <div class="nav-buttons">
            <a href="poll_battles.php" class="btn btn-primary btn-lg">
                <i class="fas fa-gamepad"></i> Enter Battle Arena
            </a>
            <a href="index.php" class="btn btn-outline-primary btn-lg">
                <i class="fas fa-home"></i> Back to Home
            </a>
        </div>
    </div>

    <script>
        function switchTab(tab) {
            // Hide all tabs
            document.getElementById('leaderboard-tab').style.display = 'none';
            document.getElementById('history-tab').style.display = 'none';

            // Remove active class from all buttons
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });

            // Show selected tab
            if (tab === 'leaderboard') {
                document.getElementById('leaderboard-tab').style.display = 'block';
                event.target.classList.add('active');
                loadLeaderboard();
            } else if (tab === 'history') {
                document.getElementById('history-tab').style.display = 'block';
                event.target.classList.add('active');
                loadHistory();
            }
        }

        function getRankClass(rank) {
            if (rank === 1) return 'rank-1';
            if (rank === 2) return 'rank-2';
            if (rank === 3) return 'rank-3';
            return 'rank-other';
        }

        function loadLeaderboard() {
            fetch('api_poll_battles.php?action=leaderboard')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        displayLeaderboard(data.leaderboard);
                    }
                })
                .catch(err => console.error(err));
        }

        function displayLeaderboard(leaderboard) {
            const tbody = document.getElementById('leaderboard-body');
            tbody.innerHTML = '';

            if (leaderboard.length === 0) {
                document.getElementById('leaderboard-loading').style.display = 'none';
                document.getElementById('leaderboard-content').innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="fas fa-inbox"></i></div>
                    <p>No battles yet. Start voting to create the first battle!</p>
                </div>
            `;
                return;
            }

            leaderboard.forEach((poll, index) => {
                const rank = index + 1;
                const totalBattles = poll.total_battles || 0;
                const winRate = poll.battle_win_rate || 0;

                const row = `
                <tr>
                    <td>
                        <span class="rank-badge ${getRankClass(rank)}">
                            ${rank === 1 ? '👑' : rank === 2 ? '🥈' : rank === 3 ? '🥉' : rank}
                        </span>
                    </td>
                    <td class="poll-title">${poll.question}</td>
                    <td>
                        <span class="stat-badge">
                            <i class="fas fa-trophy" style="color: #f5576c;"></i> ${poll.battle_wins}
                        </span>
                    </td>
                    <td>
                        <span class="stat-badge">
                            <i class="fas fa-times-circle" style="color: #999;"></i> ${poll.battle_losses}
                        </span>
                    </td>
                    <td>
                        <span class="win-rate">${winRate.toFixed(1)}%</span>
                        <br/>
                        <small style="color: #999;">${totalBattles} battles</small>
                    </td>
                </tr>
            `;
                tbody.innerHTML += row;
            });

            document.getElementById('leaderboard-loading').style.display = 'none';
            document.getElementById('leaderboard-content').style.display = 'block';
        }

        function loadHistory() {
            fetch('api_poll_battles.php?action=battle_history')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        displayHistory(data.history);
                    }
                })
                .catch(err => console.error(err));
        }

        function displayHistory(history) {
            const body = document.getElementById('history-body');
            body.innerHTML = '';

            if (history.length === 0) {
                document.getElementById('history-loading').style.display = 'none';
                document.getElementById('history-content').innerHTML = `
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="fas fa-inbox"></i></div>
                    <p>No completed battles yet.</p>
                </div>
            `;
                return;
            }

            history.forEach((battle, index) => {
                const isWinnerA = battle.winner_id === battle.poll_a_id;
                const winnerLabel = isWinnerA ? 'A' : 'B';
                const loserVotes = isWinnerA ? battle.votes_b : battle.votes_a;
                const winnerVotes = isWinnerA ? battle.votes_a : battle.votes_b;

                const battleDate = new Date(battle.created_at).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });

                const row = `
                <div class="battle-row">
                    <div class="battle-opponent">
                        <span class="opponent-label">A</span>
                        <span class="opponent-question">${battle.poll_a_question}</span>
                    </div>
                    <div class="vs-text">VS</div>
                    <div class="battle-opponent">
                        <span class="opponent-label">B</span>
                        <span class="opponent-question">${battle.poll_b_question}</span>
                    </div>
                    <div class="battle-result">
                        <div class="battle-winner">
                            <i class="fas fa-crown"></i> Winner: ${winnerLabel}
                        </div>
                        <small class="battle-date">${battleDate}</small>
                    </div>
                </div>
            `;
                body.innerHTML += row;
            });

            document.getElementById('history-loading').style.display = 'none';
            document.getElementById('history-content').style.display = 'block';
        }

        // Load leaderboard on page load
        loadLeaderboard();
    </script>

</body>

</html>
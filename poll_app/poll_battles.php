<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Poll Battles - Vote for Your Favorite</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .battle-arena {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            padding: 40px;
            margin: 40px auto;
            max-width: 900px;
        }

        .battle-header {
            text-align: center;
            margin-bottom: 40px;
            animation: fadeIn 0.5s ease-in;
        }

        .battle-header h1 {
            font-size: 2.5em;
            color: #667eea;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .battle-header p {
            font-size: 1.1em;
            color: #666;
        }

        .versus-line {
            display: flex;
            align-items: center;
            gap: 20px;
            margin: 30px 0;
        }

        .versus-line::before,
        .versus-line::after {
            content: '';
            flex: 1;
            height: 2px;
            background: linear-gradient(to right, #667eea, transparent);
        }

        .versus-text {
            color: #667eea;
            font-weight: bold;
            font-size: 1.2em;
            padding: 0 20px;
        }

        .poll-card {
            border: 3px solid #eee;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }

        .poll-card:hover {
            border-color: #667eea;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.2);
            transform: translateY(-5px);
        }

        .poll-card.selected {
            border-color: #667eea;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
        }

        .poll-number {
            display: inline-block;
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 50%;
            line-height: 50px;
            font-size: 1.5em;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .poll-question {
            font-size: 1.3em;
            font-weight: 600;
            color: #333;
            margin: 15px 0;
            line-height: 1.4;
        }

        .vote-count {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin: 15px 0;
        }

        .vote-badge {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 1.1em;
            font-weight: bold;
        }

        .progress-bar-custom {
            height: 30px;
            border-radius: 15px;
            background: linear-gradient(to right, #667eea, #764ba2);
            transition: width 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }

        .vote-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: bold;
            font-size: 1.1em;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 15px;
            width: 100%;
        }

        .vote-button:hover:not(:disabled) {
            transform: scale(1.05);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        .vote-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .battle-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin: 30px 0;
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

        .alert-box {
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
            animation: fadeIn 0.3s ease;
        }

        .stats-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
            text-align: center;
        }

        .stat-item {
            display: inline-block;
            margin: 10px 20px;
        }

        .stat-label {
            color: #666;
            font-size: 0.9em;
        }

        .stat-value {
            color: #667eea;
            font-size: 1.5em;
            font-weight: bold;
        }

        .winner-badge {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: bold;
            display: inline-block;
            margin-top: 10px;
        }

        @media (max-width: 768px) {
            .battle-arena {
                padding: 20px;
            }

            .battle-header h1 {
                font-size: 1.8em;
            }

            .battle-row {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .poll-question {
                font-size: 1em;
            }
        }
    </style>
</head>

<body>

    <nav class="navbar navbar-expand-lg navbar-dark" style="background: rgba(0, 0, 0, 0.3);">
        <div class="container">
            <a class="navbar-brand" href="index.php">⚡ Poll Battles</a>
            <div class="d-flex gap-2">
                <a href="trending.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-fire"></i> Trending
                </a>
                <a href="index.php" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-home"></i> Home
                </a>
            </div>
        </div>
    </nav>

    <div class="battle-arena">
        <div class="battle-header">
            <h1>⚔️ POLL BATTLES</h1>
            <p>Two polls compete. You decide the winner!</p>
        </div>

        <div id="loading" class="loading-spinner">
            <div class="spinner"></div>
            <p class="mt-3">Loading Battle...</p>
        </div>

        <div id="battle-content" style="display: none;">
            <div id="alert-container"></div>

            <div class="battle-row">
                <div class="poll-card" id="poll-a-card">
                    <div class="poll-number">A</div>
                    <div class="poll-question" id="poll-a-question"></div>
                    <div class="vote-count">
                        <span class="vote-badge">
                            <i class="fas fa-check-circle"></i>
                            <span id="poll-a-votes">0</span> votes
                        </span>
                    </div>
                    <div class="progress-bar-custom" style="width: 0%; margin: 15px 0;" id="poll-a-progress">
                        <span id="poll-a-percent">0%</span>
                    </div>
                    <button class="vote-button" onclick="voteBattle('a')" id="btn-vote-a">
                        <i class="fas fa-vote-yea"></i> Vote for A
                    </button>
                </div>

                <div class="versus-line">
                    <span class="versus-text">VS</span>
                </div>

                <div class="poll-card" id="poll-b-card">
                    <div class="poll-number">B</div>
                    <div class="poll-question" id="poll-b-question"></div>
                    <div class="vote-count">
                        <span class="vote-badge">
                            <i class="fas fa-check-circle"></i>
                            <span id="poll-b-votes">0</span> votes
                        </span>
                    </div>
                    <div class="progress-bar-custom" style="width: 0%; margin: 15px 0;" id="poll-b-progress">
                        <span id="poll-b-percent">0%</span>
                    </div>
                    <button class="vote-button" onclick="voteBattle('b')" id="btn-vote-b">
                        <i class="fas fa-vote-yea"></i> Vote for B
                    </button>
                </div>
            </div>

            <div class="stats-section">
                <div class="stat-item">
                    <div class="stat-label">Total Votes</div>
                    <div class="stat-value" id="total-votes">0</div>
                </div>
                <div class="stat-item">
                    <div class="stat-label">Battle Status</div>
                    <div class="stat-value" id="battle-status">Ongoing</div>
                </div>
            </div>

            <div id="winner-announcement" style="display: none; text-align: center; margin-top: 30px;">
                <h2 style="color: #f5576c; margin-bottom: 20px;">🏆 Battle Complete!</h2>
                <div id="winner-info"></div>
                <button class="btn btn-primary btn-lg" onclick="loadBattle()" style="margin-top: 20px;">
                    <i class="fas fa-redo"></i> Next Battle
                </button>
            </div>
        </div>
    </div>

    <script>
        let currentBattle = null;
        let hasVoted = false;

        function showAlert(message, type = 'info') {
            const alertHtml = `
            <div class="alert alert-${type} alert-box" role="alert">
                <i class="fas fa-info-circle"></i> ${message}
            </div>
        `;
            document.getElementById('alert-container').innerHTML = alertHtml;
        }

        function loadBattle() {
            fetch('api_poll_battles.php?action=get_battle')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        currentBattle = data.battle;
                        displayBattle();
                        hasVoted = false;
                        updateProgress();
                    } else {
                        showAlert('No active battle available', 'warning');
                    }
                })
                .catch(err => {
                    console.error(err);
                    showAlert('Error loading battle', 'danger');
                });
        }

        function displayBattle() {
            document.getElementById('loading').style.display = 'none';
            document.getElementById('battle-content').style.display = 'block';
            document.getElementById('winner-announcement').style.display = 'none';

            document.getElementById('poll-a-question').textContent = currentBattle.poll_a.question;
            document.getElementById('poll-b-question').textContent = currentBattle.poll_b.question;

            updateProgress();
        }

        function updateProgress() {
            const totalVotes = currentBattle.poll_a.votes + currentBattle.poll_b.votes;
            const percentA = totalVotes > 0 ? Math.round((currentBattle.poll_a.votes / totalVotes) * 100) : 50;
            const percentB = 100 - percentA;

            document.getElementById('poll-a-votes').textContent = currentBattle.poll_a.votes;
            document.getElementById('poll-b-votes').textContent = currentBattle.poll_b.votes;
            document.getElementById('poll-a-progress').style.width = percentA + '%';
            document.getElementById('poll-a-percent').textContent = percentA + '%';
            document.getElementById('poll-b-progress').style.width = percentB + '%';
            document.getElementById('poll-b-percent').textContent = percentB + '%';
            document.getElementById('total-votes').textContent = totalVotes;

            // Check if battle is complete
            if (currentBattle.winner_id > 0) {
                document.getElementById('battle-status').textContent = 'Finished';
                showWinner();
            }
        }

        function voteBattle(choice) {
            if (hasVoted) {
                showAlert('You have already voted in this battle', 'warning');
                return;
            }

            const pollId = choice === 'a' ? currentBattle.poll_a.id : currentBattle.poll_b.id;

            fetch('api_poll_battles.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=vote&battle_id=' + currentBattle.id + '&poll_id=' + pollId
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        hasVoted = true;
                        currentBattle.poll_a.votes = data.votes_a;
                        currentBattle.poll_b.votes = data.votes_b;
                        currentBattle.winner_id = data.winner_id;
                        currentBattle.margin_of_victory = data.margin_of_victory;

                        document.getElementById('poll-a-card').classList.add('selected');
                        document.getElementById('poll-b-card').classList.add('selected');

                        updateProgress();
                        showAlert('✓ Vote recorded successfully!', 'success');

                        if (currentBattle.winner_id > 0) {
                            setTimeout(showWinner, 500);
                        }
                    } else {
                        showAlert(data.error || 'Error recording vote', 'danger');
                    }
                })
                .catch(err => {
                    console.error(err);
                    showAlert('Error recording vote', 'danger');
                });
        }

        function showWinner() {
            const winnerName = currentBattle.winner_id === currentBattle.poll_a.id ? 'A' : 'B';
            const winnerQuestion = currentBattle.winner_id === currentBattle.poll_a.id ?
                currentBattle.poll_a.question : currentBattle.poll_b.question;

            const winnerHtml = `
            <p style="font-size: 1.2em; color: #333; margin-bottom: 15px;">
                Poll <span class="winner-badge">${winnerName}</span> wins the battle!
            </p>
            <p style="font-size: 1em; color: #666; font-style: italic;">
                "${winnerQuestion}"
            </p>
            <p style="margin-top: 15px; color: #667eea; font-weight: bold;">
                🎯 Margin of Victory: ${currentBattle.margin_of_victory} votes
            </p>
        `;

            document.getElementById('winner-info').innerHTML = winnerHtml;
            document.getElementById('winner-announcement').style.display = 'block';
        }

        // Auto-refresh battle every 3 seconds
        setInterval(() => {
            if (currentBattle) {
                fetch('api_poll_battles.php?action=get_battle')
                    .then(res => res.json())
                    .then(data => {
                        if (data.success && data.battle.id === currentBattle.id) {
                            currentBattle.poll_a.votes = data.battle.poll_a.votes;
                            currentBattle.poll_b.votes = data.battle.poll_b.votes;
                            currentBattle.winner_id = data.battle.winner_id;
                            updateProgress();
                        }
                    })
                    .catch(err => console.error(err));
            }
        }, 3000);

        // Load initial battle
        loadBattle();
    </script>

</body>

</html>
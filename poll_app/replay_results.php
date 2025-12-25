<?php
session_start();
require_once __DIR__ . '/includes/bootstrap.php';
$pollModel = new Poll();

$poll_id = (int)($_GET['poll_id'] ?? 0);
$poll = $pollModel->getPollById($poll_id);
if (!$poll) {
    die("Poll not found.");
}

$options = $pollModel->getOptions($poll_id);
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Time-Based Result Replay - Poll System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .timeline-container {
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .timeline-slider {
            width: 100%;
            margin: 1rem 0;
        }

        .timeline-controls {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .play-button {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: none;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .play-button:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }

        .play-button:active {
            transform: scale(0.95);
        }

        .speed-control {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .speed-btn {
            padding: 0.375rem 0.75rem;
            border: 1px solid #ddd;
            background: white;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .speed-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .speed-btn:hover {
            border-color: #667eea;
        }

        .chart-container {
            position: relative;
            height: 400px;
            margin: 2rem 0;
        }

        .timestamp-display {
            text-align: center;
            font-size: 1.25rem;
            font-weight: 600;
            color: #667eea;
            margin: 1rem 0;
            min-height: 2rem;
        }

        .stats-display {
            display: flex;
            justify-content: space-around;
            margin-top: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }

        .stat-label {
            font-size: 0.875rem;
            color: #6c757d;
            text-transform: uppercase;
        }

        .progress-bar-animated {
            transition: width 0.5s ease;
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .loading-overlay.hidden {
            display: none;
        }

        .results-list {
            margin-top: 2rem;
        }

        .result-item {
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: white;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .result-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .result-text {
            font-weight: 500;
            flex: 1;
        }

        .result-stats {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .vote-count {
            font-weight: bold;
            color: #667eea;
        }

        .percentage {
            color: #6c757d;
        }
    </style>
</head>

<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">Poll System</a>
            <div class="d-flex gap-2">
                <a href="results.php?poll_id=<?= $poll_id ?>" class="btn btn-light btn-sm">
                    <i class="fas fa-chart-bar"></i> Current Results
                </a>
                <a href="live_dashboard.php?poll_id=<?= $poll_id ?>" class="btn btn-light btn-sm">
                    <i class="fas fa-tachometer-alt"></i> Live Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="loading-overlay" id="loadingOverlay">
        <div class="text-center">
            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <div class="mt-3">Loading historical data...</div>
        </div>
    </div>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <!-- Header -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="mb-1">
                                    <i class="fas fa-history text-primary"></i> Time-Based Result Replay
                                </h3>
                                <p class="text-muted mb-0"><?= htmlspecialchars($poll['question']) ?></p>
                            </div>
                            <div class="text-end">
                                <div class="badge bg-primary fs-6">Poll #<?= $poll_id ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats Display -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="stats-display">
                            <div class="stat-item">
                                <div class="stat-value" id="currentTotalVotes">0</div>
                                <div class="stat-label">Total Votes</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value" id="snapshotIndex">0</div>
                                <div class="stat-label">Time Point</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value" id="totalSnapshots">0</div>
                                <div class="stat-label">Total Snapshots</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Timeline Controls -->
                <div class="timeline-container">
                    <div class="timestamp-display" id="timestampDisplay">
                        Select a point in time
                    </div>

                    <input type="range" class="timeline-slider form-range" id="timelineSlider"
                        min="0" max="100" value="0" step="1">

                    <div class="timeline-controls">
                        <button class="btn btn-outline-secondary" id="resetButton" title="Reset to start">
                            <i class="fas fa-backward"></i>
                        </button>

                        <button class="play-button" id="playButton">
                            <i class="fas fa-play"></i>
                        </button>

                        <button class="btn btn-outline-secondary" id="endButton" title="Jump to end">
                            <i class="fas fa-forward"></i>
                        </button>
                    </div>

                    <div class="speed-control justify-content-center mt-3">
                        <small class="text-muted me-2">Playback Speed:</small>
                        <button class="speed-btn" data-speed="0.5">0.5x</button>
                        <button class="speed-btn active" data-speed="1">1x</button>
                        <button class="speed-btn" data-speed="2">2x</button>
                        <button class="speed-btn" data-speed="4">4x</button>
                    </div>
                </div>

                <!-- Charts -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Distribution</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="pieChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Vote Count</h5>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="barChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Detailed Results -->
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="fas fa-list"></i> Detailed Results</h5>
                    </div>
                    <div class="card-body">
                        <div class="results-list" id="resultsList">
                            <!-- Populated by JavaScript -->
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script>
        const pollId = <?= $poll_id ?>;
        let snapshotsData = [];
        let currentIndex = 0;
        let isPlaying = false;
        let playInterval = null;
        let playbackSpeed = 1; // 1x speed
        let pieChart = null;
        let barChart = null;

        // Chart colors
        const chartColors = [
            '#667eea', '#764ba2', '#f093fb', '#4facfe',
            '#43e97b', '#fa709a', '#fee140', '#30cfd0'
        ];

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            loadHistoricalData();
            setupEventListeners();
        });

        async function loadHistoricalData() {
            try {
                const response = await fetch(`api_poll_replay.php?poll_id=${pollId}&snapshots=30`);
                const data = await response.json();

                if (data.error) {
                    alert('Error: ' + data.error);
                    return;
                }

                if (!data.snapshots || data.snapshots.length === 0) {
                    document.getElementById('timestampDisplay').textContent =
                        data.message || 'No voting data available yet';
                    document.getElementById('loadingOverlay').classList.add('hidden');
                    return;
                }

                snapshotsData = data.snapshots;

                // Setup slider
                const slider = document.getElementById('timelineSlider');
                slider.max = snapshotsData.length - 1;
                slider.value = 0;

                document.getElementById('totalSnapshots').textContent = snapshotsData.length;

                // Initialize charts
                initializeCharts();

                // Show first snapshot
                updateDisplay(0);

                document.getElementById('loadingOverlay').classList.add('hidden');
            } catch (error) {
                console.error('Error loading historical data:', error);
                alert('Failed to load historical data');
                document.getElementById('loadingOverlay').classList.add('hidden');
            }
        }

        function setupEventListeners() {
            const slider = document.getElementById('timelineSlider');
            const playButton = document.getElementById('playButton');
            const resetButton = document.getElementById('resetButton');
            const endButton = document.getElementById('endButton');

            slider.addEventListener('input', function() {
                currentIndex = parseInt(this.value);
                updateDisplay(currentIndex);
                if (isPlaying) {
                    stopPlayback();
                }
            });

            playButton.addEventListener('click', togglePlayback);
            resetButton.addEventListener('click', () => {
                currentIndex = 0;
                updateDisplay(0);
                document.getElementById('timelineSlider').value = 0;
            });

            endButton.addEventListener('click', () => {
                currentIndex = snapshotsData.length - 1;
                updateDisplay(currentIndex);
                document.getElementById('timelineSlider').value = currentIndex;
            });

            // Speed controls
            document.querySelectorAll('.speed-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    document.querySelectorAll('.speed-btn').forEach(b =>
                        b.classList.remove('active'));
                    this.classList.add('active');
                    playbackSpeed = parseFloat(this.dataset.speed);

                    // If currently playing, restart with new speed
                    if (isPlaying) {
                        stopPlayback();
                        startPlayback();
                    }
                });
            });
        }

        function initializeCharts() {
            const pieCtx = document.getElementById('pieChart').getContext('2d');
            const barCtx = document.getElementById('barChart').getContext('2d');

            pieChart = new Chart(pieCtx, {
                type: 'pie',
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: chartColors,
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const percentage = context.dataset.data.reduce((a, b) => a + b, 0) > 0 ?
                                        ((value / context.dataset.data.reduce((a, b) => a + b, 0)) * 100).toFixed(1) :
                                        0;
                                    return `${label}: ${value} votes (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });

            barChart = new Chart(barCtx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Votes',
                        data: [],
                        backgroundColor: chartColors[0],
                        borderColor: chartColors[0],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        }

        function updateDisplay(index) {
            if (!snapshotsData || snapshotsData.length === 0) return;

            const snapshot = snapshotsData[index];

            // Update timestamp
            document.getElementById('timestampDisplay').textContent = snapshot.formatted_time;

            // Update stats
            document.getElementById('currentTotalVotes').textContent = snapshot.total_votes;
            document.getElementById('snapshotIndex').textContent = index + 1;

            // Update charts
            const labels = snapshot.options.map(opt => opt.text);
            const votes = snapshot.options.map(opt => opt.votes);

            pieChart.data.labels = labels;
            pieChart.data.datasets[0].data = votes;
            pieChart.update('none'); // No animation for smooth slider

            barChart.data.labels = labels;
            barChart.data.datasets[0].data = votes;
            barChart.update('none');

            // Update detailed results list
            updateResultsList(snapshot.options, snapshot.total_votes);
        }

        function updateResultsList(options, totalVotes) {
            const container = document.getElementById('resultsList');

            if (totalVotes === 0) {
                container.innerHTML = '<p class="text-muted text-center">No votes at this time point</p>';
                return;
            }

            container.innerHTML = options.map((opt, idx) => `
                <div class="result-item">
                    <div class="result-header">
                        <div class="result-text">${opt.text}</div>
                        <div class="result-stats">
                            <span class="vote-count">${opt.votes} votes</span>
                            <span class="percentage">${opt.percentage}%</span>
                        </div>
                    </div>
                    <div class="progress" style="height: 20px;">
                        <div class="progress-bar progress-bar-animated" 
                             style="width: ${opt.percentage}%; background-color: ${chartColors[idx % chartColors.length]};"
                             role="progressbar" 
                             aria-valuenow="${opt.percentage}" 
                             aria-valuemin="0" 
                             aria-valuemax="100">
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function togglePlayback() {
            if (isPlaying) {
                stopPlayback();
            } else {
                startPlayback();
            }
        }

        function startPlayback() {
            isPlaying = true;
            document.getElementById('playButton').innerHTML = '<i class="fas fa-pause"></i>';

            const baseInterval = 500; // Base interval in ms
            const interval = baseInterval / playbackSpeed;

            playInterval = setInterval(() => {
                currentIndex++;
                if (currentIndex >= snapshotsData.length) {
                    currentIndex = snapshotsData.length - 1;
                    stopPlayback();
                    return;
                }

                document.getElementById('timelineSlider').value = currentIndex;
                updateDisplay(currentIndex);
            }, interval);
        }

        function stopPlayback() {
            isPlaying = false;
            document.getElementById('playButton').innerHTML = '<i class="fas fa-play"></i>';

            if (playInterval) {
                clearInterval(playInterval);
                playInterval = null;
            }
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
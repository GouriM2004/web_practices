<?php
session_start();
require_once __DIR__ . '/includes/bootstrap.php';
$pollModel = new Poll();

$poll_id = (int)($_GET['poll_id'] ?? 0);
$poll = $poll_id ? $pollModel->getPollById($poll_id) : $pollModel->getActivePoll();

if (!$poll) {
    die("Poll not found.");
}

$poll_id = $poll['id'];
$options = $pollModel->getOptions($poll_id);
$publicVoters = $pollModel->getPublicVoters($poll_id);
$totalVotes = 0;
foreach ($options as $o) $totalVotes += $o['votes'];
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Live Dashboard - Poll System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .chart-container {
            position: relative;
            height: 400px;
            margin-bottom: 2rem;
        }

        .refresh-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            background: #28a745;
            border-radius: 50%;
            margin-right: 5px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.3;
            }
        }
    </style>
</head>

<body class="bg-light">

    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">Poll System</a>
            <div class="d-flex align-items-center text-white">
                <span class="refresh-indicator"></span>
                <small>Live Updates</small>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-0">Live Results Dashboard</h4>
                            <p class="mb-0 text-muted" id="pollQuestion"><?= htmlspecialchars($poll['question']) ?></p>
                        </div>
                        <div class="text-end">
                            <h5 class="mb-0" id="totalVotes"><?= $totalVotes ?></h5>
                            <small class="text-muted">Total Votes</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Pie Chart -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Vote Distribution (Pie)</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="pieChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bar Chart -->
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Vote Count (Bar)</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="barChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Results Table -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Detailed Results</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped mb-0" id="resultsTable">
                                <thead>
                                    <tr>
                                        <th>Option</th>
                                        <th>Votes</th>
                                        <th>Percentage</th>
                                        <th>Progress</th>
                                    </tr>
                                </thead>
                                <tbody id="resultsTableBody">
                                    <!-- Populated by JS -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Layered Results -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Layered Results (Expert / Student / Public)</h5>
                        <small class="text-muted" id="layeredWeights">Weights updating...</small>
                    </div>
                    <div class="card-body" id="layeredResultsBody">
                        <p class="text-muted mb-0">Waiting for layered votes...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Public Voters -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Public Voters <span class="badge bg-primary" id="voterCount">0</span></h5>
                    </div>
                    <div class="card-body">
                        <div id="publicVotersList" class="d-flex flex-wrap gap-2">
                            <!-- Populated by JS -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Confidence Indicator -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Voter Confidence Distribution</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container" style="height: 300px;">
                            <canvas id="confidenceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Confidence Breakdown</h5>
                    </div>
                    <div class="card-body" id="confidenceBreakdown">
                        <!-- Populated by JS -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Geographical Voting Map -->
        <div class="row mt-4" id="geoSection" style="display: none;">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Geographical Voting Breakdown</h5>
                    </div>
                    <div class="card-body">
                        <div id="geoBreakdown">
                            <!-- Populated by JS -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-4">
            <a href="index.php" class="btn btn-outline-secondary">Back to Poll</a>
            <a href="results.php?poll_id=<?= $poll_id ?>" class="btn btn-outline-primary">Static Results</a>
        </div>
    </div>

    <script>
        const pollId = <?= $poll_id ?>;
        let pieChart, barChart, confidenceChart;

        // Color palette
        const colors = [
            'rgba(54, 162, 235, 0.8)',
            'rgba(255, 99, 132, 0.8)',
            'rgba(75, 192, 192, 0.8)',
            'rgba(255, 206, 86, 0.8)',
            'rgba(153, 102, 255, 0.8)',
            'rgba(255, 159, 64, 0.8)',
            'rgba(199, 199, 199, 0.8)',
            'rgba(83, 102, 255, 0.8)'
        ];

        const borderColors = colors.map(c => c.replace('0.8', '1'));

        function initCharts(data) {
            const labels = data.options.map(o => o.text);
            const votes = data.options.map(o => o.votes);

            // Pie Chart
            const pieCtx = document.getElementById('pieChart').getContext('2d');
            pieChart = new Chart(pieCtx, {
                type: 'pie',
                data: {
                    labels: labels,
                    datasets: [{
                        data: votes,
                        backgroundColor: colors,
                        borderColor: borderColors,
                        borderWidth: 2
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
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                    return `${label}: ${value} votes (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });

            // Bar Chart
            const barCtx = document.getElementById('barChart').getContext('2d');
            barChart = new Chart(barCtx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Votes',
                        data: votes,
                        backgroundColor: colors,
                        borderColor: borderColors,
                        borderWidth: 2
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

        function updateCharts(data) {
            const labels = data.options.map(o => o.text);
            const votes = data.options.map(o => o.votes);

            if (pieChart) {
                pieChart.data.labels = labels;
                pieChart.data.datasets[0].data = votes;
                pieChart.update('none'); // no animation for smooth updates
            }

            if (barChart) {
                barChart.data.labels = labels;
                barChart.data.datasets[0].data = votes;
                barChart.update('none');
            }
        }

        function initConfidenceChart(data) {
            if (!data.confidence || !data.confidence.overall || data.confidence.overall.length === 0) {
                return;
            }

            const confidenceLabels = {
                'very_sure': 'Very sure 😊',
                'somewhat_sure': 'Somewhat sure 🤔',
                'just_guessing': 'Just guessing 🤷'
            };

            const confidenceColors = {
                'very_sure': 'rgba(40, 167, 69, 0.8)',
                'somewhat_sure': 'rgba(255, 193, 7, 0.8)',
                'just_guessing': 'rgba(108, 117, 125, 0.8)'
            };

            const labels = data.confidence.overall.map(c => confidenceLabels[c.level] || c.level);
            const counts = data.confidence.overall.map(c => c.count);
            const bgColors = data.confidence.overall.map(c => confidenceColors[c.level] || 'rgba(128, 128, 128, 0.8)');

            const confidenceCtx = document.getElementById('confidenceChart').getContext('2d');
            confidenceChart = new Chart(confidenceCtx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: counts,
                        backgroundColor: bgColors,
                        borderColor: bgColors.map(c => c.replace('0.8', '1')),
                        borderWidth: 2
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
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                                    return `${label}: ${value} votes (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }

        function updateConfidenceChart(data) {
            if (!data.confidence || !data.confidence.overall || data.confidence.overall.length === 0) {
                return;
            }

            const confidenceLabels = {
                'very_sure': 'Very sure 😊',
                'somewhat_sure': 'Somewhat sure 🤔',
                'just_guessing': 'Just guessing 🤷'
            };

            const confidenceColors = {
                'very_sure': 'rgba(40, 167, 69, 0.8)',
                'somewhat_sure': 'rgba(255, 193, 7, 0.8)',
                'just_guessing': 'rgba(108, 117, 125, 0.8)'
            };

            const labels = data.confidence.overall.map(c => confidenceLabels[c.level] || c.level);
            const counts = data.confidence.overall.map(c => c.count);
            const bgColors = data.confidence.overall.map(c => confidenceColors[c.level] || 'rgba(128, 128, 128, 0.8)');

            if (confidenceChart) {
                confidenceChart.data.labels = labels;
                confidenceChart.data.datasets[0].data = counts;
                confidenceChart.data.datasets[0].backgroundColor = bgColors;
                confidenceChart.update('none');
            }

            // Update confidence breakdown text
            const breakdownContainer = document.getElementById('confidenceBreakdown');
            let html = '';

            data.confidence.overall.forEach(stat => {
                const badgeColor = {
                    'very_sure': 'success',
                    'somewhat_sure': 'warning',
                    'just_guessing': 'secondary'
                } [stat.level] || 'info';

                const icon = {
                    'very_sure': '😊',
                    'somewhat_sure': '🤔',
                    'just_guessing': '🤷'
                } [stat.level] || '';

                const label = confidenceLabels[stat.level] || stat.level;

                html += `
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span>${icon} ${label}</span>
                            <span class="badge bg-${badgeColor}">${stat.count} (${stat.percentage}%)</span>
                        </div>
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar bg-${badgeColor}" role="progressbar" 
                                 style="width: ${stat.percentage}%;" 
                                 aria-valuenow="${stat.percentage}" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                            </div>
                        </div>
                    </div>
                `;
            });

            breakdownContainer.innerHTML = html;
        }

        function updateResultsTable(data) {
            const tbody = document.getElementById('resultsTableBody');
            tbody.innerHTML = '';

            data.options.forEach((opt, idx) => {
                const row = document.createElement('tr');
                row.innerHTML = `
      <td><strong>${escapeHtml(opt.text)}</strong></td>
      <td>${opt.votes}</td>
      <td>${opt.percentage}%</td>
      <td>
        <div class="progress" style="height: 25px;">
          <div class="progress-bar" role="progressbar" 
               style="width: ${opt.percentage}%; background-color: ${colors[idx % colors.length]}"
               aria-valuenow="${opt.percentage}" aria-valuemin="0" aria-valuemax="100">
            ${opt.percentage}%
          </div>
        </div>
      </td>
    `;
                tbody.appendChild(row);
            });
        }

        function updatePublicVoters(data) {
            const container = document.getElementById('publicVotersList');
            const count = document.getElementById('voterCount');

            count.textContent = data.public_voters.length;

            if (data.public_voters.length === 0) {
                container.innerHTML = '<p class="text-muted mb-0">No public voters yet</p>';
                return;
            }

            container.innerHTML = data.public_voters.map(name =>
                `<span class="badge bg-secondary">${escapeHtml(name)}</span>`
            ).join('');
        }

        function updateGeographicalBreakdown(data) {
            const container = document.getElementById('geoBreakdown');
            const section = document.getElementById('geoSection');

            if (!data.geographical || data.geographical.length === 0) {
                section.style.display = 'none';
                return;
            }

            section.style.display = 'block';

            let html = '<div class="row">';

            data.geographical.forEach(geo => {
                const totalLocationVotes = geo.votes.reduce((sum, v) => sum + v.votes, 0);
                const leadingOption = geo.votes.reduce((max, v) => v.votes > max.votes ? v : max, geo.votes[0]);

                html += `
      <div class="col-md-6 col-lg-4 mb-3">
        <div class="card h-100">
          <div class="card-header bg-light">
            <h6 class="mb-0"><i class="bi bi-geo-alt-fill"></i> ${escapeHtml(geo.location)}</h6>
            <small class="text-muted">${totalLocationVotes} votes</small>
          </div>
          <div class="card-body">
            <p class="mb-2"><strong>Leading:</strong> <span class="text-primary">${escapeHtml(leadingOption.option)}</span></p>
            <ul class="list-unstyled mb-0">
    `;

                geo.votes.forEach(v => {
                    const percent = totalLocationVotes > 0 ? ((v.votes / totalLocationVotes) * 100).toFixed(0) : 0;
                    html += `
        <li class="mb-2">
          <div class="d-flex justify-content-between mb-1">
            <small>${escapeHtml(v.option)}</small>
            <small>${v.votes} (${percent}%)</small>
          </div>
          <div class="progress" style="height: 6px;">
            <div class="progress-bar bg-info" style="width: ${percent}%"></div>
          </div>
        </li>
      `;
                });

                html += `
            </ul>
          </div>
        </div>
      </div>
    `;
            });

            html += '</div>';
            container.innerHTML = html;
        }

        function renderLayeredResults(data) {
            const body = document.getElementById('layeredResultsBody');
            const weightsLabel = document.getElementById('layeredWeights');

            if (!body) return;

            if (!data.segments || !Array.isArray(data.segments.options)) {
                body.innerHTML = '<p class="text-muted mb-0">Layered data unavailable.</p>';
                return;
            }

            const weights = data.segments.weights || {
                expert: 2,
                student: 1.5,
                public: 1
            };
            if (weightsLabel) {
                weightsLabel.textContent = `Expert x${weights.expert} | Student x${weights.student} | Public x${weights.public}`;
            }

            if (data.segments.options.length === 0) {
                body.innerHTML = '<p class="text-muted mb-0">No layered votes yet.</p>';
                return;
            }

            const totals = data.segments.totals || {};
            const byType = totals.by_type || {};
            const weightedTotal = totals.weighted_total || 0;

            let html = '<div class="d-flex flex-wrap gap-2 mb-3">';
            html += `<span class="badge bg-primary">Experts: ${byType.expert || 0}</span>`;
            html += `<span class="badge bg-info text-dark">Students: ${byType.student || 0}</span>`;
            html += `<span class="badge bg-secondary">Public: ${byType.public || 0}</span>`;
            html += `<span class="badge bg-dark">Weighted total: ${Number(weightedTotal).toFixed(1)}</span>`;
            html += '</div>';

            data.segments.options.forEach(opt => {
                const byTypeOpt = opt.by_type || {};
                const expertCount = byTypeOpt.expert ? (byTypeOpt.expert.count || 0) : 0;
                const studentCount = byTypeOpt.student ? (byTypeOpt.student.count || 0) : 0;
                const publicCount = byTypeOpt.public ? (byTypeOpt.public.count || 0) : 0;
                const weightedVotes = Number(opt.weighted_votes || 0).toFixed(1);
                const weightedPct = opt.weighted_percentage || 0;

                html += `
            <div class="mb-3">
                <div class="d-flex justify-content-between">
                    <strong>${escapeHtml(opt.text)}</strong>
                    <span>${weightedVotes} weighted (${weightedPct}%)</span>
                </div>
                <div class="progress mb-2" style="height: 8px;">
                    <div class="progress-bar bg-primary" style="width: ${weightedPct}%;"></div>
                </div>
                <div class="d-flex flex-wrap gap-3 small text-muted">
                    <span>Expert: ${expertCount} (x${weights.expert})</span>
                    <span>Student: ${studentCount} (x${weights.student})</span>
                    <span>Public: ${publicCount} (x${weights.public})</span>
                </div>
            </div>
        `;
            });

            body.innerHTML = html;
        }

        function updateDashboard() {
            fetch(`api_poll_results.php?poll_id=${pollId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error('API Error:', data.error);
                        return;
                    }

                    // Update total votes
                    document.getElementById('totalVotes').textContent = data.poll.total_votes;

                    // Update charts
                    updateCharts(data);

                    // Update confidence chart
                    updateConfidenceChart(data);

                    // Update table
                    updateResultsTable(data);

                    // Update public voters
                    updatePublicVoters(data);

                    // Update geographical breakdown
                    updateGeographicalBreakdown(data);

                    // Update layered results
                    renderLayeredResults(data);
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                });
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Initial load
        fetch(`api_poll_results.php?poll_id=${pollId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert('Error loading poll data: ' + data.error);
                    return;
                }

                // Initialize charts
                initCharts(data);
                initConfidenceChart(data);

                // Populate table and voters
                updateResultsTable(data);
                updatePublicVoters(data);
                updateGeographicalBreakdown(data);
                updateConfidenceChart(data);
                renderLayeredResults(data);

                // Start auto-refresh every 3 seconds
                setInterval(updateDashboard, 3000);
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to load poll data');
            });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
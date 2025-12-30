<?php
require_once 'includes/bootstrap.php';

$pageTitle = 'Weather-Based Polls';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .weather-container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .weather-header {
            background: white;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
        }
        
        .weather-header h1 {
            margin: 0 0 20px;
            color: #333;
            font-size: 2.5em;
        }
        
        .weather-info {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .weather-icon {
            width: 100px;
            height: 100px;
        }
        
        .weather-details {
            text-align: left;
        }
        
        .weather-condition {
            font-size: 1.8em;
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        
        .weather-temp {
            font-size: 3em;
            font-weight: 700;
            color: #667eea;
            margin: 10px 0;
        }
        
        .weather-location {
            color: #777;
            font-size: 1.1em;
        }
        
        .loading {
            background: white;
            border-radius: 12px;
            padding: 60px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .loading-spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .polls-grid {
            display: grid;
            gap: 25px;
        }
        
        .poll-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transition: transform 0.3s;
        }
        
        .poll-card:hover {
            transform: translateY(-5px);
        }
        
        .poll-meta {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        
        .poll-badge {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: 600;
        }
        
        .badge-weather {
            background: #3498db;
            color: white;
        }
        
        .badge-new {
            background: #2ecc71;
            color: white;
        }
        
        .poll-question {
            font-size: 1.5em;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
        }
        
        .poll-options {
            display: grid;
            gap: 12px;
        }
        
        .poll-option {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .poll-option:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }
        
        .poll-option.selected {
            border-color: #667eea;
            background: #667eea;
            color: white;
        }
        
        .option-text {
            font-weight: 500;
            font-size: 1.1em;
        }
        
        .option-percent {
            font-weight: 600;
            font-size: 1em;
        }
        
        .option-bar {
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            margin-top: 8px;
            overflow: hidden;
        }
        
        .option-bar-fill {
            height: 100%;
            background: #667eea;
            border-radius: 4px;
            transition: width 0.5s;
        }
        
        .poll-option.selected .option-bar-fill {
            background: white;
        }
        
        .vote-btn {
            width: 100%;
            padding: 15px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
            transition: all 0.3s;
        }
        
        .vote-btn:hover {
            background: #5568d3;
        }
        
        .vote-btn:disabled {
            background: #95a5a6;
            cursor: not-allowed;
        }
        
        .no-polls {
            background: white;
            border-radius: 12px;
            padding: 60px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .no-polls h2 {
            color: #666;
            margin-bottom: 15px;
        }
        
        .no-polls p {
            color: #999;
        }
        
        .error {
            background: #fff5f5;
            border: 2px solid #fc8181;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            color: #c53030;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 30px;
            padding: 12px 30px;
            background: rgba(255,255,255,0.2);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .back-link:hover {
            background: rgba(255,255,255,0.3);
        }
        
        @media (max-width: 768px) {
            .weather-header h1 {
                font-size: 1.8em;
            }
            
            .weather-temp {
                font-size: 2.5em;
            }
            
            .poll-question {
                font-size: 1.2em;
            }
        }
    </style>
</head>
<body>
    <div class="weather-container">
        <div id="app">
            <div class="loading">
                <div class="loading-spinner"></div>
                <p>Checking the weather...</p>
            </div>
        </div>
        
        <div style="text-align: center;">
            <a href="index.php" class="back-link">← Back to Home</a>
        </div>
    </div>

    <script>
        const app = document.getElementById('app');
        let pollData = {};
        let selectedOptions = {};
        
        // Load weather and polls
        async function loadWeatherPolls() {
            try {
                const response = await fetch('api_weather_polls.php');
                const data = await response.json();
                
                if (!data.success) {
                    throw new Error(data.error || 'Failed to load weather data');
                }
                
                pollData = data;
                renderWeatherPolls(data);
            } catch (error) {
                app.innerHTML = `
                    <div class="error">
                        <h2>⚠️ Error</h2>
                        <p>${error.message}</p>
                        <button onclick="location.reload()" class="vote-btn" style="max-width: 200px; margin: 20px auto 0;">
                            Try Again
                        </button>
                    </div>
                `;
            }
        }
        
        // Render weather and polls
        function renderWeatherPolls(data) {
            const { weather, polls } = data;
            
            let html = `
                <div class="weather-header">
                    <h1>☁️ Weather-Based Polls</h1>
                    <div class="weather-info">
                        <img src="${weather.icon_url}" alt="${weather.description}" class="weather-icon">
                        <div class="weather-details">
                            <p class="weather-condition">${weather.description}</p>
                            <div class="weather-temp">${weather.temperature}°C</div>
                            <p class="weather-location">📍 ${weather.location}</p>
                        </div>
                    </div>
                    ${weather.is_mock ? '<p style="color: #e74c3c; margin-top: 15px; font-size: 0.9em;">Demo mode - using mock weather data</p>' : ''}
                </div>
            `;
            
            if (polls.length === 0) {
                html += `
                    <div class="no-polls">
                        <h2>No polls for current weather</h2>
                        <p>Check back when the weather changes!</p>
                        <p style="margin-top: 10px; color: #999; font-size: 0.9em;">
                            Current condition: <strong>${weather.condition}</strong>
                        </p>
                    </div>
                `;
            } else {
                html += '<div class="polls-grid">';
                
                polls.forEach(poll => {
                    const isNew = (new Date() - new Date(poll.generated_at)) < 3600000; // Less than 1 hour old
                    html += renderPoll(poll, isNew, weather.condition);
                });
                
                html += '</div>';
            }
            
            app.innerHTML = html;
        }
        
        // Render individual poll
        function renderPoll(poll, isNew, weatherCondition) {
            const pollId = poll.poll_id;
            const options = JSON.parse(poll.options);
            const results = poll.results || {};
            const totalVotes = results.total_votes || 0;
            
            let html = `
                <div class="poll-card">
                    <div class="poll-meta">
                        <span class="poll-badge badge-weather">${weatherCondition}</span>
                        ${isNew ? '<span class="poll-badge badge-new">NEW</span>' : ''}
                    </div>
                    <div class="poll-question">${poll.question}</div>
                    <div class="poll-options" id="options-${pollId}">
            `;
            
            options.forEach((option, index) => {
                const votes = results.options?.[option] || 0;
                const percentage = totalVotes > 0 ? Math.round((votes / totalVotes) * 100) : 0;
                
                html += `
                    <div class="poll-option" onclick="selectOption(${pollId}, ${index})">
                        <div style="flex: 1;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span class="option-text">${option}</span>
                                <span class="option-percent">${percentage}%</span>
                            </div>
                            <div class="option-bar">
                                <div class="option-bar-fill" style="width: ${percentage}%"></div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += `
                    </div>
                    <button class="vote-btn" onclick="submitVote(${pollId})">
                        Vote Now
                    </button>
                    <div style="text-align: center; margin-top: 15px; color: #999; font-size: 0.9em;">
                        ${totalVotes} vote${totalVotes !== 1 ? 's' : ''}
                    </div>
                </div>
            `;
            
            return html;
        }
        
        // Select poll option
        function selectOption(pollId, optionIndex) {
            selectedOptions[pollId] = optionIndex;
            
            // Update UI
            const container = document.getElementById(`options-${pollId}`);
            const allOptions = container.querySelectorAll('.poll-option');
            
            allOptions.forEach((opt, idx) => {
                if (idx === optionIndex) {
                    opt.classList.add('selected');
                } else {
                    opt.classList.remove('selected');
                }
            });
        }
        
        // Submit vote
        async function submitVote(pollId) {
            if (selectedOptions[pollId] === undefined) {
                alert('Please select an option first');
                return;
            }
            
            // Find the poll to get options
            const poll = pollData.polls.find(p => p.poll_id == pollId);
            if (!poll) return;
            
            const options = JSON.parse(poll.options);
            const selectedOption = options[selectedOptions[pollId]];
            
            try {
                const response = await fetch('vote.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `poll_id=${pollId}&option=${encodeURIComponent(selectedOption)}`
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('✅ Vote submitted successfully!');
                    // Reload to show updated results
                    loadWeatherPolls();
                } else {
                    alert('❌ ' + (result.error || 'Failed to submit vote'));
                }
            } catch (error) {
                alert('❌ Error submitting vote: ' + error.message);
            }
        }
        
        // Auto-refresh every 5 minutes
        setInterval(loadWeatherPolls, 300000);
        
        // Initial load
        loadWeatherPolls();
    </script>
</body>
</html>

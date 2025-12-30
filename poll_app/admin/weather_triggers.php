<?php
require_once '../includes/bootstrap.php';
require_once '../includes/admin_guard.php';
require_once '../includes/Models/WeatherTrigger.php';

$weatherTrigger = new WeatherTrigger($db);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create' || $action === 'update') {
        $data = [
            'trigger_name' => trim($_POST['trigger_name']),
            'weather_condition' => $_POST['weather_condition'],
            'temperature_min' => !empty($_POST['temperature_min']) ? floatval($_POST['temperature_min']) : null,
            'temperature_max' => !empty($_POST['temperature_max']) ? floatval($_POST['temperature_max']) : null,
            'poll_question' => trim($_POST['poll_question']),
            'poll_options' => array_filter(array_map('trim', explode("\n", $_POST['poll_options']))),
            'priority' => intval($_POST['priority'] ?? 0),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        
        if ($action === 'create') {
            $success = $weatherTrigger->create($data);
            $message = $success ? 'Weather trigger created successfully!' : 'Failed to create trigger.';
        } else {
            $triggerId = intval($_POST['trigger_id']);
            $success = $weatherTrigger->update($triggerId, $data);
            $message = $success ? 'Weather trigger updated successfully!' : 'Failed to update trigger.';
        }
    } elseif ($action === 'delete') {
        $triggerId = intval($_POST['trigger_id']);
        $success = $weatherTrigger->delete($triggerId);
        $message = $success ? 'Weather trigger deleted successfully!' : 'Failed to delete trigger.';
    }
    
    header('Location: weather_triggers.php?msg=' . urlencode($message));
    exit;
}

// Get all triggers
$triggers = $weatherTrigger->getAll();
$statistics = $weatherTrigger->getStatistics();

// Get trigger for editing if ID provided
$editTrigger = null;
if (isset($_GET['edit'])) {
    $editTrigger = $weatherTrigger->getById(intval($_GET['edit']));
}

$pageTitle = 'Weather-Based Poll Triggers';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .weather-admin {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }
        
        .stat-card h3 {
            font-size: 2.5em;
            margin: 0;
        }
        
        .stat-card p {
            margin: 5px 0 0;
            opacity: 0.9;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        
        .form-section, .triggers-list {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .form-section h2, .triggers-list h2 {
            margin-top: 0;
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            color: #555;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
            font-family: monospace;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: auto;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5568d3;
        }
        
        .btn-cancel {
            background: #95a5a6;
            color: white;
            margin-left: 10px;
        }
        
        .trigger-item {
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        
        .trigger-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .trigger-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 10px;
        }
        
        .trigger-title {
            font-weight: 600;
            font-size: 1.1em;
            color: #333;
        }
        
        .trigger-badges {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin: 8px 0;
        }
        
        .badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: 500;
        }
        
        .badge-condition {
            background: #3498db;
            color: white;
        }
        
        .badge-active {
            background: #2ecc71;
            color: white;
        }
        
        .badge-inactive {
            background: #95a5a6;
            color: white;
        }
        
        .badge-priority {
            background: #e74c3c;
            color: white;
        }
        
        .trigger-question {
            font-style: italic;
            color: #555;
            margin: 10px 0;
        }
        
        .trigger-options {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 10px 0;
        }
        
        .option-pill {
            background: #ecf0f1;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.9em;
        }
        
        .trigger-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        
        .btn-small {
            padding: 6px 12px;
            font-size: 0.85em;
        }
        
        .btn-edit {
            background: #3498db;
            color: white;
        }
        
        .btn-delete {
            background: #e74c3c;
            color: white;
        }
        
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .help-text {
            font-size: 0.85em;
            color: #777;
            margin-top: 5px;
        }
        
        @media (max-width: 968px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="weather-admin">
        <h1>☁️ <?php echo $pageTitle; ?></h1>
        
        <?php if (isset($_GET['msg'])): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($_GET['msg']); ?>
            </div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $statistics['total_triggers']; ?></h3>
                <p>Total Triggers</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $statistics['active_triggers']; ?></h3>
                <p>Active Triggers</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $statistics['active_polls']; ?></h3>
                <p>Active Weather Polls</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $statistics['total_polls_generated']; ?></h3>
                <p>Total Generated</p>
            </div>
        </div>
        
        <div class="content-grid">
            <div class="form-section">
                <h2><?php echo $editTrigger ? '✏️ Edit' : '➕ Create'; ?> Weather Trigger</h2>
                
                <form method="POST" action="">
                    <input type="hidden" name="action" value="<?php echo $editTrigger ? 'update' : 'create'; ?>">
                    <?php if ($editTrigger): ?>
                        <input type="hidden" name="trigger_id" value="<?php echo $editTrigger['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label>Trigger Name</label>
                        <input type="text" name="trigger_name" required 
                               value="<?php echo $editTrigger ? htmlspecialchars($editTrigger['trigger_name']) : ''; ?>"
                               placeholder="e.g., Rainy Day Beverage">
                    </div>
                    
                    <div class="form-group">
                        <label>Weather Condition</label>
                        <select name="weather_condition" required>
                            <option value="">Select Condition</option>
                            <?php 
                            $conditions = ['rain', 'clear', 'clouds', 'snow', 'thunderstorm', 'drizzle', 'mist'];
                            foreach ($conditions as $condition): 
                                $selected = $editTrigger && $editTrigger['weather_condition'] === $condition ? 'selected' : '';
                            ?>
                                <option value="<?php echo $condition; ?>" <?php echo $selected; ?>>
                                    <?php echo ucfirst($condition); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Min Temperature (°C)</label>
                            <input type="number" step="0.1" name="temperature_min" 
                                   value="<?php echo $editTrigger ? $editTrigger['temperature_min'] : ''; ?>"
                                   placeholder="Optional">
                            <div class="help-text">Leave empty for no minimum</div>
                        </div>
                        
                        <div class="form-group">
                            <label>Max Temperature (°C)</label>
                            <input type="number" step="0.1" name="temperature_max" 
                                   value="<?php echo $editTrigger ? $editTrigger['temperature_max'] : ''; ?>"
                                   placeholder="Optional">
                            <div class="help-text">Leave empty for no maximum</div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Poll Question</label>
                        <input type="text" name="poll_question" required 
                               value="<?php echo $editTrigger ? htmlspecialchars($editTrigger['poll_question']) : ''; ?>"
                               placeholder="e.g., It's raining — Tea or Coffee?">
                    </div>
                    
                    <div class="form-group">
                        <label>Poll Options (one per line)</label>
                        <textarea name="poll_options" required placeholder="Tea&#10;Coffee&#10;Hot Chocolate&#10;Neither"><?php 
                            echo $editTrigger ? implode("\n", $editTrigger['poll_options']) : ''; 
                        ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Priority (higher = shown first)</label>
                            <input type="number" name="priority" 
                                   value="<?php echo $editTrigger ? $editTrigger['priority'] : '0'; ?>">
                        </div>
                        
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" name="is_active" id="is_active" 
                                       <?php echo (!$editTrigger || $editTrigger['is_active']) ? 'checked' : ''; ?>>
                                <label for="is_active">Active</label>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <?php echo $editTrigger ? 'Update' : 'Create'; ?> Trigger
                    </button>
                    
                    <?php if ($editTrigger): ?>
                        <a href="weather_triggers.php" class="btn btn-cancel">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <div class="triggers-list">
                <h2>📋 Existing Triggers</h2>
                
                <?php if (empty($triggers)): ?>
                    <p style="color: #999; text-align: center; padding: 40px 0;">
                        No weather triggers yet. Create your first one!
                    </p>
                <?php else: ?>
                    <?php foreach ($triggers as $trigger): ?>
                        <div class="trigger-item">
                            <div class="trigger-header">
                                <div class="trigger-title">
                                    <?php echo htmlspecialchars($trigger['trigger_name']); ?>
                                </div>
                            </div>
                            
                            <div class="trigger-badges">
                                <span class="badge badge-condition">
                                    <?php echo ucfirst($trigger['weather_condition']); ?>
                                </span>
                                <span class="badge <?php echo $trigger['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                    <?php echo $trigger['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                                <?php if ($trigger['priority'] > 0): ?>
                                    <span class="badge badge-priority">Priority: <?php echo $trigger['priority']; ?></span>
                                <?php endif; ?>
                                <?php if ($trigger['temperature_min'] || $trigger['temperature_max']): ?>
                                    <span class="badge" style="background: #f39c12; color: white;">
                                        <?php 
                                        if ($trigger['temperature_min'] && $trigger['temperature_max']) {
                                            echo $trigger['temperature_min'] . '°C - ' . $trigger['temperature_max'] . '°C';
                                        } elseif ($trigger['temperature_min']) {
                                            echo '> ' . $trigger['temperature_min'] . '°C';
                                        } else {
                                            echo '< ' . $trigger['temperature_max'] . '°C';
                                        }
                                        ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="trigger-question">
                                "<?php echo htmlspecialchars($trigger['poll_question']); ?>"
                            </div>
                            
                            <div class="trigger-options">
                                <?php foreach ($trigger['poll_options'] as $option): ?>
                                    <span class="option-pill"><?php echo htmlspecialchars($option); ?></span>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="trigger-actions">
                                <a href="?edit=<?php echo $trigger['id']; ?>" class="btn btn-small btn-edit">
                                    Edit
                                </a>
                                <form method="POST" style="display: inline;" 
                                      onsubmit="return confirm('Delete this trigger?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="trigger_id" value="<?php echo $trigger['id']; ?>">
                                    <button type="submit" class="btn btn-small btn-delete">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <div style="margin-top: 30px; text-align: center;">
            <a href="dashboard.php" class="btn btn-cancel">← Back to Dashboard</a>
            <a href="../weather_polls.php" target="_blank" class="btn btn-primary" style="margin-left: 10px;">
                View Weather Polls →
            </a>
        </div>
    </div>
</body>
</html>

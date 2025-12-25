<?php
// API endpoint for time-based poll result replay
header('Content-Type: application/json');
require_once __DIR__ . '/includes/bootstrap.php';

$poll_id = (int)($_GET['poll_id'] ?? 0);
if (!$poll_id) {
    echo json_encode(['error' => 'Invalid poll ID']);
    exit;
}

$pollModel = new Poll();
$poll = $pollModel->getPollById($poll_id);
if (!$poll) {
    echo json_encode(['error' => 'Poll not found']);
    exit;
}

// Get number of snapshots requested (default 20)
$snapshots = min((int)($_GET['snapshots'] ?? 20), 100); // Max 100 snapshots

$historicalData = $pollModel->getHistoricalSnapshots($poll_id, $snapshots);

echo json_encode($historicalData, JSON_PRETTY_PRINT);
    
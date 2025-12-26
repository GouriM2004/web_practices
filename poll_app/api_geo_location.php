<?php
// api_geo_location.php
// Validates voter location against geo-fenced polls

header('Content-Type: application/json');
require_once __DIR__ . '/includes/bootstrap.php';

$poll_id = (int)($_POST['poll_id'] ?? $_GET['poll_id'] ?? 0);
$voter_lat = (float)($_POST['latitude'] ?? $_GET['latitude'] ?? 0);
$voter_lon = (float)($_POST['longitude'] ?? $_GET['longitude'] ?? 0);
$accuracy = (int)($_POST['accuracy'] ?? $_GET['accuracy'] ?? 0);

if (!$poll_id || !$voter_lat || !$voter_lon) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Missing required parameters: poll_id, latitude, longitude'
    ]);
    exit;
}

$geoFence = new GeoFence();

// Check if poll exists and has geo-fencing enabled
$poll = new Poll();
$pollData = $poll->getPollById($poll_id);

if (!$pollData) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => 'Poll not found'
    ]);
    exit;
}

// Validate location against geo-fence
$validation = $geoFence->validateLocation($poll_id, $voter_lat, $voter_lon);

// Record voter location if authenticated
if (isset($_SESSION['voter_id'])) {
    $geoFence->recordVoterLocation(
        $_SESSION['voter_id'],
        $_SERVER['REMOTE_ADDR'],
        $voter_lat,
        $voter_lon,
        $accuracy,
        'web'
    );
}

// Return validation result
echo json_encode([
    'success' => $validation['allowed'],
    'poll_id' => $poll_id,
    'allowed' => $validation['allowed'],
    'reason' => $validation['reason'],
    'distance_km' => $validation['distance'],
    'location_type' => $validation['location_type'] ?? null,
    'poll_question' => $pollData['question'],
    'location_name' => $pollData['location_name'] ?? null,
    'timestamp' => date('c')
]);

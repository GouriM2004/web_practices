<?php
// vote_geo_fenced.php
// Voting page with geo-fencing support

session_start();
require_once __DIR__ . '/includes/bootstrap.php';

$poll_id = (int)($_GET['poll_id'] ?? 0);
$error = '';
$poll_data = null;
$options = [];
$voter_location = null;
$location_verified = false;

$poll = new Poll();
$geoFence = new GeoFence();

// Get active poll if not specified
if (!$poll_id) {
    $active = $poll->getActivePoll();
    if ($active) {
        $poll_id = $active['id'];
    }
}

if ($poll_id) {
    $poll_data = $poll->getPollById($poll_id);
    if ($poll_data) {
        $options = $poll->getOptions($poll_id);
    }
}

// Check if voter is authenticated
$is_authenticated = isset($_SESSION['voter_id']);
$voter_id = $_SESSION['voter_id'] ?? null;
$voter_name = $_SESSION['voter_name'] ?? null;

// Handle location submission via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'submit_vote' && $poll_data) {
        $option_ids = $_POST['options'] ?? [];
        if (!is_array($option_ids)) {
            $option_ids = [$option_ids];
        }

        if (empty($option_ids)) {
            echo json_encode(['success' => false, 'error' => 'Please select an option']);
            exit;
        }

        // Check geo-fencing
        if ($geoFence->isGeoFenced($poll_id)) {
            $voter_lat = (float)($_POST['latitude'] ?? 0);
            $voter_lon = (float)($_POST['longitude'] ?? 0);

            if (!$voter_lat || !$voter_lon) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Location access required for this poll',
                    'require_location' => true
                ]);
                exit;
            }

            $validation = $geoFence->validateLocation($poll_id, $voter_lat, $voter_lon);
            if (!$validation['allowed']) {
                echo json_encode([
                    'success' => false,
                    'error' => $validation['reason'],
                    'location_denied' => true
                ]);
                exit;
            }

            // Record location for audit
            if ($voter_id) {
                $geoFence->recordVoterLocation($voter_id, $_SERVER['REMOTE_ADDR'], $voter_lat, $voter_lon, (int)($_POST['accuracy'] ?? 0), 'web');
            }
        }

        // Record vote
        $result = $poll->recordVote(
            $poll_id,
            $option_ids,
            $_SERVER['REMOTE_ADDR'],
            $voter_id,
            $voter_name,
            isset($_SESSION['voter_id']) ? 0 : 1,
            $_POST['voter_location'] ?? null,
            $_POST['confidence_level'] ?? 'somewhat_sure',
            $_SESSION['voter_type'] ?? 'public'
        );

        if ($result['ok']) {
            echo json_encode([
                'success' => true,
                'message' => 'Vote recorded successfully',
                'redirect' => 'results.php?poll_id=' . $poll_id
            ]);
        } else {
            $error_map = [
                'locked' => 'You have already voted on this poll',
                'error' => 'An error occurred while recording your vote'
            ];
            echo json_encode([
                'success' => false,
                'error' => $error_map[$result['reason']] ?? 'Error recording vote'
            ]);
        }
        exit;
    }
}

// If no poll found
if (!$poll_data) {
    header('Location: index.php');
    exit;
}

$geo_config = $geoFence->getGeoFenceConfig($poll_id);
$is_geo_fenced = $geo_config && $geo_config['geo_fencing_enabled'];
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($poll_data['question']) ?> - Vote</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .geo-fence-alert {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }

        .location-badge {
            font-size: 0.9em;
            padding: 0.5rem 1rem;
        }

        .location-status {
            font-size: 0.95em;
            margin: 1rem 0;
        }

        .location-success {
            color: #28a745;
            font-weight: bold;
        }

        .location-error {
            color: #dc3545;
            font-weight: bold;
        }

        .location-pending {
            color: #ffc107;
            font-weight: bold;
        }

        .option-card {
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid #e9ecef;
        }

        .option-card:hover {
            border-color: #0d6efd;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }

        .option-card.selected {
            border-color: #0d6efd;
            background-color: #e7f1ff;
        }
    </style>
</head>

<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">Poll System</a>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <?php if (isset($_SESSION['voter_id'])): ?>
                        <a class="nav-link" href="voter_logout.php">Logout (<?= htmlspecialchars($_SESSION['voter_name']) ?>)</a>
                    <?php else: ?>
                        <a class="nav-link" href="voter_login.php?poll_id=<?= $poll_id ?>">Login</a>
                    <?php endif; ?>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container mt-4 mb-5">
        <div class="row">
            <div class="col-md-8">
                <!-- Geo-Fence Notice -->
                <?php if ($is_geo_fenced): ?>
                    <div class="alert geo-fence-alert alert-dismissible fade show" role="alert">
                        <h5 class="mb-2">📍 Location-Based Poll</h5>
                        <p class="mb-0">This poll is only available for voters in <strong><?= htmlspecialchars($geo_config['location_name']) ?></strong>
                            (<span class="badge bg-light text-dark location-badge"><?= ucfirst($geo_config['location_type']) ?></span>)</p>
                        <small>You'll need to share your location to vote on this poll.</small>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>

                    <!-- Location Status -->
                    <div class="card mb-3 border-warning">
                        <div class="card-body">
                            <div class="location-status">
                                <span id="locationStatus" class="location-pending">⏳ Checking location...</span>
                            </div>
                            <div id="locationDetails" style="display: none; margin-top: 1rem; font-size: 0.9em;">
                                <p><strong>Your Location:</strong> <span id="locationCoords"></span></p>
                                <p><strong>Distance from poll:</strong> <span id="locationDistance"></span></p>
                            </div>
                            <button type="button" id="getLocationBtn" class="btn btn-sm btn-primary" onclick="requestUserLocation()">
                                Share My Location
                            </button>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Poll Question -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0"><?= htmlspecialchars($poll_data['question']) ?></h3>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">
                            <?= $poll_data['allow_multiple'] ? '✓ Multiple choices allowed' : '◉ Single choice only' ?>
                            | Category: <?= htmlspecialchars($poll_data['category'] ?? 'General') ?>
                        </p>
                    </div>
                </div>

                <!-- Voting Form -->
                <form id="voteForm">
                    <!-- Options -->
                    <div class="mb-4">
                        <?php foreach ($options as $option): ?>
                            <div class="option-card card mb-3 p-3" onclick="selectOption(this, <?= $option['id'] ?>)">
                                <div class="form-check">
                                    <input
                                        class="form-check-input option-checkbox"
                                        type="<?= $poll_data['allow_multiple'] ? 'checkbox' : 'radio' ?>"
                                        name="options"
                                        value="<?= $option['id'] ?>"
                                        id="option_<?= $option['id'] ?>"
                                        onchange="selectOption(document.querySelector('.option-card[onclick*=\'<?= $option['id'] ?>\']'), <?= $option['id'] ?>)">
                                    <label class="form-check-label" for="option_<?= $option['id'] ?>">
                                        <strong><?= htmlspecialchars($option['option_text']) ?></strong>
                                        <span class="badge bg-secondary float-end"><?= $option['votes'] ?> votes</span>
                                    </label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Confidence Level -->
                    <div class="mb-4">
                        <label class="form-label"><strong>How confident are you about this answer?</strong></label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="confidence_level" id="veryConfident" value="very_sure" checked>
                            <label class="btn btn-outline-success" for="veryConfident">Very Sure</label>

                            <input type="radio" class="btn-check" name="confidence_level" id="somewhat" value="somewhat_sure">
                            <label class="btn btn-outline-warning" for="somewhat">Somewhat Sure</label>

                            <input type="radio" class="btn-check" name="confidence_level" id="guessing" value="just_guessing">
                            <label class="btn btn-outline-danger" for="guessing">Just Guessing</label>
                        </div>
                    </div>

                    <!-- Hidden Location Fields -->
                    <input type="hidden" id="latitude" name="latitude">
                    <input type="hidden" id="longitude" name="longitude">
                    <input type="hidden" id="accuracy" name="accuracy">
                    <input type="hidden" id="voter_location" name="voter_location">
                    <input type="hidden" id="action" name="action" value="submit_vote">
                    <input type="hidden" id="poll_id" name="poll_id" value="<?= $poll_id ?>">

                    <div id="errorMessage" class="alert alert-danger" style="display: none;"></div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                            <?= $is_geo_fenced ? '📍 Vote & Share Location' : 'Submit Vote' ?>
                        </button>
                    </div>
                </form>

                <?php if (!$is_authenticated): ?>
                    <div class="alert alert-info mt-3">
                        <strong>Note:</strong> Your vote will be recorded anonymously unless you <a href="voter_login.php?poll_id=<?= $poll_id ?>">log in</a>.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="col-md-4">
                <?php if ($is_geo_fenced): ?>
                    <div class="card bg-light">
                        <div class="card-header">
                            <h5 class="mb-0">Location Requirements</h5>
                        </div>
                        <div class="card-body small">
                            <h6 class="text-primary">📍 <?= ucfirst($geo_config['location_type']) ?></h6>
                            <p><?= htmlspecialchars($geo_config['location_name']) ?></p>

                            <div class="alert alert-info py-2 small">
                                <strong>Radius:</strong> <?= $geo_config['radius_km'] ?> km<br>
                                <strong>Why?</strong> This ensures votes come from the intended location only.
                            </div>

                            <p class="text-muted"><small>
                                    Your device will request location permission. This is used only to validate you're in the allowed area,
                                    and the data is securely stored.
                                </small></p>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="card bg-light mt-3">
                    <div class="card-header">
                        <h5 class="mb-0">Poll Info</h5>
                    </div>
                    <div class="card-body small">
                        <p><strong>Created:</strong> <?= date('M d, Y H:i', strtotime($poll_data['created_at'])) ?></p>
                        <p><strong>Status:</strong> <?= $poll_data['is_active'] ? '🟢 Active' : '🔴 Closed' ?></p>
                        <a href="results.php?poll_id=<?= $poll_id ?>" class="btn btn-sm btn-outline-primary">View Results</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let userLocation = null;

        function selectOption(element, optionId) {
            const isMultiple = <?= $poll_data['allow_multiple'] ? 'true' : 'false' ?>;

            if (!isMultiple) {
                document.querySelectorAll('.option-card').forEach(card => {
                    card.classList.remove('selected');
                    card.querySelector('input').checked = false;
                });
            }

            element.classList.toggle('selected');
            element.querySelector('input').checked = !element.querySelector('input').checked;
        }

        function requestUserLocation() {
            const submitBtn = document.getElementById('submitBtn');
            const locationStatus = document.getElementById('locationStatus');
            const getLocationBtn = document.getElementById('getLocationBtn');

            if (!navigator.geolocation) {
                showError('Geolocation is not supported by your browser');
                return;
            }

            getLocationBtn.disabled = true;
            locationStatus.textContent = '⏳ Accessing location...';

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    userLocation = {
                        lat: position.coords.latitude,
                        lon: position.coords.longitude,
                        accuracy: Math.round(position.coords.accuracy)
                    };

                    document.getElementById('latitude').value = userLocation.lat;
                    document.getElementById('longitude').value = userLocation.lon;
                    document.getElementById('accuracy').value = userLocation.accuracy;
                    document.getElementById('voter_location').value = userLocation.lat + ',' + userLocation.lon;

                    // Verify location with server
                    verifyLocation(userLocation.lat, userLocation.lon);
                },
                function(error) {
                    getLocationBtn.disabled = false;
                    const errorMessages = {
                        1: 'Permission denied. Please allow location access to vote.',
                        2: 'Position unavailable. Try again later.',
                        3: 'Request timeout. Please try again.'
                    };
                    showError(errorMessages[error.code] || 'Unable to get location');
                    locationStatus.textContent = '❌ Location access denied';
                }
            );
        }

        function verifyLocation(lat, lon) {
            const formData = new FormData();
            formData.append('poll_id', document.getElementById('poll_id').value);
            formData.append('latitude', lat);
            formData.append('longitude', lon);

            fetch('api_geo_location.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    const locationStatus = document.getElementById('locationStatus');
                    const locationDetails = document.getElementById('locationDetails');
                    const submitBtn = document.getElementById('submitBtn');
                    const getLocationBtn = document.getElementById('getLocationBtn');

                    if (data.allowed) {
                        locationStatus.textContent = '✅ Location verified';
                        locationStatus.className = 'location-status location-success';
                        locationDetails.style.display = 'block';
                        document.getElementById('locationCoords').textContent =
                            lat.toFixed(4) + ', ' + lon.toFixed(4) + ' (±' + data.distance_km + ' km away)';
                        document.getElementById('locationDistance').textContent =
                            (data.distance_km * 1000).toFixed(0) + ' meters';
                        submitBtn.disabled = false;
                    } else {
                        locationStatus.textContent = '❌ ' + data.reason;
                        locationStatus.className = 'location-status location-error';
                        locationDetails.style.display = 'block';
                        document.getElementById('locationDistance').textContent = data.reason;
                        showError(data.reason);
                        submitBtn.disabled = true;
                    }
                    getLocationBtn.disabled = false;
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('Failed to verify location');
                    document.getElementById('getLocationBtn').disabled = false;
                });
        }

        function showError(message) {
            const errorDiv = document.getElementById('errorMessage');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
        }

        document.getElementById('voteForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const selectedOptions = Array.from(document.querySelectorAll('.option-checkbox:checked')).map(cb => cb.value);

            if (selectedOptions.length === 0) {
                showError('Please select an option');
                return;
            }

            <?php if ($is_geo_fenced): ?>
                if (!userLocation) {
                    showError('Please share your location to vote');
                    document.getElementById('getLocationBtn').click();
                    return;
                }
            <?php endif; ?>

            const formData = new FormData();
            formData.append('action', 'submit_vote');
            formData.append('poll_id', document.getElementById('poll_id').value);
            formData.append('confidence_level', document.querySelector('input[name="confidence_level"]:checked').value);

            selectedOptions.forEach(id => formData.append('options[]', id));

            if (userLocation) {
                formData.append('latitude', userLocation.lat);
                formData.append('longitude', userLocation.lon);
                formData.append('accuracy', userLocation.accuracy);
                formData.append('voter_location', userLocation.lat + ',' + userLocation.lon);
            }

            fetch(window.location.pathname, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success and redirect
                        alert('Vote submitted successfully!');
                        window.location.href = data.redirect;
                    } else {
                        showError(data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showError('Failed to submit vote');
                });
        });

        // Auto-request location on page load if geo-fenced
        <?php if ($is_geo_fenced): ?>
            document.addEventListener('DOMContentLoaded', function() {
                requestUserLocation();
            });
        <?php endif; ?>
    </script>
</body>

</html>
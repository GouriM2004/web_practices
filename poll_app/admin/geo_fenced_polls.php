<?php
// admin/geo_fenced_polls.php
// Manage geo-fenced polls configuration

session_start();
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/admin_guard.php';

$action = $_GET['action'] ?? 'list';
$poll_id = (int)($_GET['poll_id'] ?? $_POST['poll_id'] ?? 0);

$geoFence = new GeoFence();
$poll = new Poll();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'list';

    if ($action === 'save_geo_fence') {
        $poll_id = (int)$_POST['poll_id'];
        $enabled = (int)($_POST['geo_fencing_enabled'] ?? 0);

        if ($enabled) {
            $location_type = $_POST['location_type'] ?? 'campus';
            $location_name = trim($_POST['location_name'] ?? '');
            $latitude = (float)$_POST['latitude'];
            $longitude = (float)$_POST['longitude'];
            $radius_km = (float)$_POST['radius_km'];

            if (!$location_name || !$latitude || !$longitude || !$radius_km) {
                $error = 'All location fields are required.';
            } else {
                $geoFence->enableGeoFence($poll_id, $location_type, $location_name, $latitude, $longitude, $radius_km);
                $success = 'Geo-fencing enabled for poll.';
            }
        } else {
            $geoFence->disableGeoFence($poll_id);
            $success = 'Geo-fencing disabled for poll.';
        }
    } elseif ($action === 'add_zone') {
        $poll_id = (int)$_POST['poll_id'];
        $zone_name = trim($_POST['zone_name'] ?? '');
        $location_type = $_POST['location_type'] ?? 'campus';
        $latitude = (float)$_POST['latitude'];
        $longitude = (float)$_POST['longitude'];
        $radius_km = (float)$_POST['radius_km'];

        if (!$zone_name || !$latitude || !$longitude || !$radius_km) {
            $error = 'All zone fields are required.';
        } else {
            $geoFence->addZone($poll_id, $zone_name, $location_type, $latitude, $longitude, $radius_km);
            $success = 'Additional zone added.';
        }
    }
}

// Get list of active polls for geo-fencing configuration
if ($action === 'list') {
    $activePolls = $poll->getActivePolls(50);
}

// Get details for specific poll if editing
if ($action === 'edit' && $poll_id) {
    $pollData = $poll->getPollById($poll_id);
    $geoConfig = $geoFence->getGeoFenceConfig($poll_id);
    $zones = $geoFence->getGeoFenceZones($poll_id);
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Geo-Fenced Polls Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .location-type-badge {
            font-size: 0.85em;
        }

        .distance-info {
            font-size: 0.9em;
            color: #666;
        }

        .geo-fence-card {
            border-left: 4px solid #0d6efd;
        }

        .radius-input {
            width: 100%;
        }
    </style>
</head>

<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">Admin Dashboard</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Polls</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="geo_fenced_polls.php">Geo-Fenced Polls</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($action === 'list'): ?>
            <div class="row mb-4">
                <div class="col-md-12">
                    <h2>Geo-Fenced Polls Management</h2>
                    <p class="text-muted">Configure location-based access for polls (Campus, City, Event)</p>
                </div>
            </div>

            <div class="row">
                <?php if (!empty($activePolls)): ?>
                    <?php foreach ($activePolls as $p): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card geo-fence-card h-100">
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($p['question']) ?></h5>
                                    <p class="card-text text-muted small">ID: <?= $p['id'] ?> | Category: <?= htmlspecialchars($p['category'] ?? 'General') ?></p>

                                    <?php if ($p['geo_fencing_enabled']): ?>
                                        <div class="alert alert-info py-2 small mb-3">
                                            <strong><?= htmlspecialchars($p['location_name']) ?></strong><br>
                                            Type: <span class="badge bg-secondary location-type-badge"><?= ucfirst($p['location_type']) ?></span><br>
                                            Radius: <span class="distance-info"><?= $p['radius_km'] ?> km</span>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-warning py-2 small mb-3">
                                            Geo-fencing not configured
                                        </div>
                                    <?php endif; ?>

                                    <a href="geo_fenced_polls.php?action=edit&poll_id=<?= $p['id'] ?>" class="btn btn-sm btn-primary">
                                        Configure
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-info">
                            No active polls found. <a href="create_poll.php">Create a poll</a> first.
                        </div>
                    </div>
                <?php endif; ?>
            </div>

        <?php elseif ($action === 'edit' && $pollData): ?>
            <div class="row mb-4">
                <div class="col-md-8">
                    <h2><?= htmlspecialchars($pollData['question']) ?></h2>
                    <p class="text-muted">Poll ID: <?= $pollData['id'] ?></p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="geo_fenced_polls.php" class="btn btn-secondary">Back to List</a>
                </div>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <!-- Main Geo-Fence Configuration -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Primary Location Zone</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="save_geo_fence">
                                <input type="hidden" name="poll_id" value="<?= $poll_id ?>">

                                <div class="form-check mb-3">
                                    <input
                                        type="checkbox"
                                        name="geo_fencing_enabled"
                                        id="geoFencingEnabled"
                                        value="1"
                                        class="form-check-input"
                                        <?= ($geoConfig && $geoConfig['geo_fencing_enabled']) ? 'checked' : '' ?>
                                        onchange="document.getElementById('geoFenceForm').style.display = this.checked ? 'block' : 'none';">
                                    <label class="form-check-label" for="geoFencingEnabled">
                                        Enable Geo-Fencing for this Poll
                                    </label>
                                </div>

                                <div id="geoFenceForm" style="display: <?= ($geoConfig && $geoConfig['geo_fencing_enabled']) ? 'block' : 'none' ?>;">
                                    <div class="mb-3">
                                        <label for="locationType" class="form-label">Location Type</label>
                                        <select name="location_type" id="locationType" class="form-select" required>
                                            <option value="">Select type...</option>
                                            <option value="campus" <?= ($geoConfig && $geoConfig['location_type'] === 'campus') ? 'selected' : '' ?>>Campus</option>
                                            <option value="city" <?= ($geoConfig && $geoConfig['location_type'] === 'city') ? 'selected' : '' ?>>City</option>
                                            <option value="event" <?= ($geoConfig && $geoConfig['location_type'] === 'event') ? 'selected' : '' ?>>Event Location</option>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="locationName" class="form-label">Location Name</label>
                                        <input
                                            type="text"
                                            name="location_name"
                                            id="locationName"
                                            class="form-control"
                                            placeholder="e.g., Main Campus, Downtown, Tech Summit 2025"
                                            value="<?= $geoConfig ? htmlspecialchars($geoConfig['location_name']) : '' ?>">
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="latitude" class="form-label">Latitude</label>
                                            <input
                                                type="number"
                                                name="latitude"
                                                id="latitude"
                                                class="form-control"
                                                step="0.00000001"
                                                placeholder="e.g., 40.7128"
                                                value="<?= $geoConfig ? $geoConfig['latitude'] : '' ?>">
                                            <small class="text-muted">e.g., 40.7128 (New York)</small>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="longitude" class="form-label">Longitude</label>
                                            <input
                                                type="number"
                                                name="longitude"
                                                id="longitude"
                                                class="form-control"
                                                step="0.00000001"
                                                placeholder="e.g., -74.0060"
                                                value="<?= $geoConfig ? $geoConfig['longitude'] : '' ?>">
                                            <small class="text-muted">e.g., -74.0060 (New York)</small>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="radiusKm" class="form-label">Allowed Radius (km)</label>
                                        <input
                                            type="number"
                                            name="radius_km"
                                            id="radiusKm"
                                            class="form-control radius-input"
                                            step="0.1"
                                            min="0.1"
                                            placeholder="e.g., 0.5"
                                            value="<?= $geoConfig ? $geoConfig['radius_km'] : '0.5' ?>">
                                        <small class="text-muted">Default: 0.5 km (500 meters). For campus: 2-5 km, for city: 5-10 km, for event: 0.5-1 km</small>
                                    </div>

                                    <button type="submit" class="btn btn-primary">Save Configuration</button>
                                </div>

                                <?php if (!$geoConfig || !$geoConfig['geo_fencing_enabled']): ?>
                                    <button type="submit" class="btn btn-outline-secondary">Disable Geo-Fencing</button>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>

                    <!-- Additional Zones -->
                    <?php if ($geoConfig && $geoConfig['geo_fencing_enabled']): ?>
                        <div class="card mb-4">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0">Additional Location Zones</h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted small">Add multiple location zones to allow voting from different areas (useful for multi-campus events)</p>

                                <?php if (!empty($zones)): ?>
                                    <div class="mb-3">
                                        <h6>Current Zones</h6>
                                        <ul class="list-group">
                                            <?php foreach ($zones as $zone): ?>
                                                <li class="list-group-item">
                                                    <strong><?= htmlspecialchars($zone['zone_name']) ?></strong>
                                                    <span class="badge bg-secondary"><?= ucfirst($zone['location_type']) ?></span>
                                                    <br>
                                                    <small class="text-muted">
                                                        Lat: <?= $zone['latitude'] ?>, Lon: <?= $zone['longitude'] ?> | Radius: <?= $zone['radius_km'] ?> km
                                                    </small>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" class="mt-3">
                                    <input type="hidden" name="action" value="add_zone">
                                    <input type="hidden" name="poll_id" value="<?= $poll_id ?>">

                                    <div class="mb-3">
                                        <label for="zoneName" class="form-label">Zone Name</label>
                                        <input
                                            type="text"
                                            name="zone_name"
                                            id="zoneName"
                                            class="form-control"
                                            placeholder="e.g., North Campus, Downtown Branch">
                                    </div>

                                    <div class="mb-3">
                                        <label for="zoneLocationType" class="form-label">Location Type</label>
                                        <select name="location_type" id="zoneLocationType" class="form-select">
                                            <option value="campus">Campus</option>
                                            <option value="city">City</option>
                                            <option value="event">Event Location</option>
                                        </select>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="zoneLatitude" class="form-label">Latitude</label>
                                            <input
                                                type="number"
                                                name="latitude"
                                                id="zoneLatitude"
                                                class="form-control"
                                                step="0.00000001"
                                                placeholder="e.g., 40.8090">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="zoneLongitude" class="form-label">Longitude</label>
                                            <input
                                                type="number"
                                                name="longitude"
                                                id="zoneLongitude"
                                                class="form-control"
                                                step="0.00000001"
                                                placeholder="e.g., -74.0090">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="zoneRadiusKm" class="form-label">Allowed Radius (km)</label>
                                        <input
                                            type="number"
                                            name="radius_km"
                                            id="zoneRadiusKm"
                                            class="form-control"
                                            step="0.1"
                                            min="0.1"
                                            placeholder="e.g., 0.5">
                                    </div>

                                    <button type="submit" class="btn btn-info">Add Zone</button>
                                </form>
                            </div>
                        </div>

                        <!-- Statistics -->
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">Geo-Fence Statistics</h5>
                            </div>
                            <div class="card-body">
                                <?php
                                $stats = $geoFence->getGeoFenceStats($poll_id);
                                if ($stats && $stats['total_votes'] > 0):
                                ?>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <h6>Total Votes</h6>
                                            <p class="h4"><?= $stats['total_votes'] ?></p>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <h6>Location Verified</h6>
                                            <p class="h4"><?= $stats['verified_votes'] ?? 0 ?></p>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <h6>Average Distance</h6>
                                            <p class="h4"><?= $stats['avg_distance_km'] ? round($stats['avg_distance_km'], 2) . ' km' : 'N/A' ?></p>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <h6>Max Distance</h6>
                                            <p class="h4"><?= $stats['max_distance_km'] ? round($stats['max_distance_km'], 2) . ' km' : 'N/A' ?></p>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted">No location-verified votes yet.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Help Panel -->
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-header">
                            <h5 class="mb-0">Location Types</h5>
                        </div>
                        <div class="card-body small">
                            <h6>🏫 Campus</h6>
                            <p>For university/school-wide polls. Typical radius: 2-5 km</p>

                            <h6>🏙️ City</h6>
                            <p>For city-wide polls. Typical radius: 5-15 km</p>

                            <h6>🎉 Event Location</h6>
                            <p>For event-specific polls. Typical radius: 0.5-1 km</p>
                        </div>
                    </div>

                    <div class="card bg-light mt-3">
                        <div class="card-header">
                            <h5 class="mb-0">Finding Coordinates</h5>
                        </div>
                        <div class="card-body small">
                            <p><strong>Google Maps:</strong> Right-click location → coordinates appear</p>
                            <p><strong>OpenStreetMap:</strong> Click location → see lat/lon in address bar</p>
                            <p><a href="https://www.google.com/maps" target="_blank">Open Google Maps</a></p>
                        </div>
                    </div>
                </div>
            </div>

        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
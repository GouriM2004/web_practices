<?php
session_start();

// Sample safe location data
$safeLocations = [
    [
        'id' => 1,
        'name' => 'Central Police Station',
        'type' => 'police',
        'address' => '123 Main Street, Downtown',
        'phone' => '+1-555-0101',
        'lat' => 40.7128,
        'lng' => -74.0060,
        'rating' => 5.0,
        'open_24_7' => true
    ],
    [
        'id' => 2,
        'name' => 'City Hospital Emergency',
        'type' => 'hospital',
        'address' => '456 Health Ave, Medical District',
        'phone' => '+1-555-0102',
        'lat' => 40.7589,
        'lng' => -73.9851,
        'rating' => 4.8,
        'open_24_7' => true
    ],
    [
        'id' => 3,
        'name' => 'Safe Haven Women\'s Center',
        'type' => 'shelter',
        'address' => '789 Support Lane, North District',
        'phone' => '+1-555-0103',
        'lat' => 40.7831,
        'lng' => -73.9712,
        'rating' => 4.9,
        'open_24_7' => false
    ],
    [
        'id' => 4,
        'name' => '24/7 Convenience Store',
        'type' => 'store',
        'address' => '321 Night Street, Commercial Area',
        'phone' => '+1-555-0104',
        'lat' => 40.7505,
        'lng' => -73.9934,
        'rating' => 4.2,
        'open_24_7' => true
    ]
];

// Emergency contacts
$emergencyContacts = [
    ['name' => 'Emergency Services', 'number' => '911', 'type' => 'emergency'],
    ['name' => 'Women\'s Safety Helpline', 'number' => '1-800-799-7233', 'type' => 'helpline'],
    ['name' => 'Local Police', 'number' => '+1-555-POLICE', 'type' => 'police'],
    ['name' => 'Crisis Support', 'number' => '988', 'type' => 'crisis']
];

// Handle emergency alert
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['emergency_alert'])) {
    $user_location = htmlspecialchars($_POST['location'] ?? 'Unknown');
    $emergency_type = htmlspecialchars($_POST['emergency_type'] ?? 'General');
    $message = htmlspecialchars($_POST['message'] ?? 'Emergency assistance needed');
    
    // In a real application, this would send alerts to emergency contacts and authorities
    $alert_sent = true;
    $alert_message = "Emergency alert sent successfully! Help is on the way.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafePath - Women Safety Route Recommender</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
        :root {
            --primary-color: #e91e63;
            --secondary-color: #f8bbd9;
            --accent-color: #4caf50;
            --warning-color: #ff9800;
            --danger-color: #f44336;
            --dark-color: #2c3e50;
            --light-color: #f8f9fa;
            --border-color: #e9ecef;
            --text-muted: #6c757d;
            --emergency-color: #dc3545;
            --safe-color: #28a745;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .main-content {
            flex: 1;
        }

        /* Header Styles */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 15px 0;
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header h1 {
            color: var(--primary-color);
            font-weight: 700;
            font-size: 1.8rem;
            margin: 0;
        }

        .emergency-btn {
            background: var(--emergency-color);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
        }

        .emergency-btn:hover {
            background: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(220, 53, 69, 0.4);
            color: white;
        }

        .emergency-btn i {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        /* Main Container */
        .app-container {
            background: white;
            border-radius: 20px;
            margin: 20px auto;
            max-width: 1400px;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        /* Control Panel */
        .control-panel {
            background: var(--light-color);
            padding: 25px;
            border-bottom: 1px solid var(--border-color);
        }

        .route-form {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .form-control {
            border-radius: 10px;
            border: 1px solid var(--border-color);
            padding: 12px 15px;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(233, 30, 99, 0.25);
        }

        .btn-primary {
            background: var(--primary-color);
            border: none;
            border-radius: 10px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: #c2185b;
            transform: translateY(-1px);
        }

        /* Map Container */
        .map-section {
            display: flex;
            height: 600px;
        }

        #map {
            flex: 2;
            min-height: 600px;
        }

        .info-panel {
            flex: 1;
            background: white;
            padding: 20px;
            overflow-y: auto;
            border-left: 1px solid var(--border-color);
        }

        /* Safe Locations */
        .safe-location {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .safe-location:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .location-type {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        .type-police { background: #e3f2fd; color: #1976d2; }
        .type-hospital { background: #e8f5e8; color: #388e3c; }
        .type-shelter { background: #fff3e0; color: #f57c00; }
        .type-store { background: #f3e5f5; color: #7b1fa2; }

        .location-name {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 5px;
        }

        .location-address {
            color: var(--text-muted);
            font-size: 13px;
            margin-bottom: 8px;
        }

        .location-rating {
            color: var(--warning-color);
        }

        .location-status {
            font-size: 12px;
            font-weight: 600;
        }

        .status-open { color: var(--safe-color); }
        .status-closed { color: var(--text-muted); }

        /* Emergency Contacts */
        .emergency-contacts {
            background: #fff5f5;
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .emergency-contact {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #fecaca;
        }

        .emergency-contact:last-child {
            border-bottom: none;
        }

        .contact-name {
            font-weight: 600;
            color: var(--dark-color);
        }

        .contact-number {
            color: var(--emergency-color);
            font-weight: 600;
            text-decoration: none;
        }

        .contact-number:hover {
            color: #dc3545;
        }

        /* Safety Features */
        .safety-features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .safety-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .safety-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .safety-icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 15px;
            background: var(--secondary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: var(--primary-color);
        }

        /* Route Information */
        .route-info {
            background: #e8f5e8;
            border: 1px solid #c8e6c9;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .route-metrics {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 15px;
        }

        .metric {
            text-align: center;
            padding: 10px;
            background: white;
            border-radius: 8px;
        }

        .metric-value {
            font-size: 18px;
            font-weight: 700;
            color: var(--primary-color);
        }

        .metric-label {
            font-size: 12px;
            color: var(--text-muted);
            text-transform: uppercase;
        }

        /* Emergency Modal */
        .modal-header {
            background: var(--emergency-color);
            color: white;
        }

        .modal-header .btn-close {
            filter: invert(1);
        }

        /* Alerts */
        .alert {
            border-radius: 10px;
            border: none;
            padding: 15px 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .map-section {
                flex-direction: column;
                height: auto;
            }

            #map {
                height: 400px;
            }

            .info-panel {
                border-left: none;
                border-top: 1px solid var(--border-color);
            }

            .safety-features {
                grid-template-columns: 1fr;
            }

            .route-metrics {
                grid-template-columns: 1fr;
            }
        }

        /* Loading Animation */
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .spinner-border {
            color: var(--primary-color);
        }

        /* Floating Action Buttons */
        .fab-container {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }

        .fab {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            border: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }

        .fab-emergency {
            background: var(--emergency-color);
            color: white;
        }

        .fab-location {
            background: var(--accent-color);
            color: white;
        }

        .fab:hover {
            transform: scale(1.1);
        }
    </style>
</head>
<body>
    <div class="main-content">
        <!-- Header -->
        <div class="header">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1><i class="bi bi-shield-check me-2"></i>SafePath</h1>
                        <p class="mb-0 text-muted">Your trusted companion for safe travel</p>
                    </div>
                    <div class="col-md-4 text-end">
                        <button class="btn emergency-btn" data-bs-toggle="modal" data-bs-target="#emergencyModal">
                            <i class="bi bi-exclamation-triangle me-2"></i>Emergency Alert
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success Alert -->
        <?php if (isset($alert_sent) && $alert_sent): ?>
            <div class="container mt-3">
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i><?php echo $alert_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        <?php endif; ?>

        <!-- Main Container -->
        <div class="container">
            <div class="app-container">
                <!-- Control Panel -->
                <div class="control-panel">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="route-form">
                                <h5 class="mb-3"><i class="bi bi-geo-alt me-2"></i>Plan Your Safe Route</h5>
                                <form id="routeForm">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">From</label>
                                            <input type="text" class="form-control" id="fromLocation" placeholder="Enter starting location">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">To</label>
                                            <input type="text" class="form-control" id="toLocation" placeholder="Enter destination">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Travel Mode</label>
                                            <select class="form-control" id="travelMode">
                                                <option value="walking">Walking</option>
                                                <option value="driving">Driving</option>
                                                <option value="transit">Public Transit</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Time of Day</label>
                                            <select class="form-control" id="timeOfDay">
                                                <option value="morning">Morning (6AM-12PM)</option>
                                                <option value="afternoon">Afternoon (12PM-6PM)</option>
                                                <option value="evening">Evening (6PM-10PM)</option>
                                                <option value="night">Night (10PM-6AM)</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">&nbsp;</label>
                                            <button type="submit" class="btn btn-primary w-100">
                                                <i class="bi bi-search me-2"></i>Find Safe Route
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="safety-features">
                                <div class="safety-card">
                                    <div class="safety-icon">
                                        <i class="bi bi-shield-check"></i>
                                    </div>
                                    <h6>Safe Routes</h6>
                                    <p class="text-muted small mb-0">Well-lit, populated paths</p>
                                </div>
                                <div class="safety-card">
                                    <div class="safety-icon">
                                        <i class="bi bi-geo-alt"></i>
                                    </div>
                                    <h6>Safety Points</h6>
                                    <p class="text-muted small mb-0">Police, hospitals, shelters</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Map and Info Section -->
                <div class="map-section">
                    <div id="map"></div>
                    <div class="info-panel">
                        <div class="loading" id="loadingIndicator">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Finding the safest route...</p>
                        </div>

                        <!-- Emergency Contacts -->
                        <div class="emergency-contacts">
                            <h6 class="mb-3"><i class="bi bi-telephone me-2"></i>Emergency Contacts</h6>
                            <?php foreach ($emergencyContacts as $contact): ?>
                                <div class="emergency-contact">
                                    <span class="contact-name"><?php echo htmlspecialchars($contact['name']); ?></span>
                                    <a href="tel:<?php echo $contact['number']; ?>" class="contact-number">
                                        <?php echo $contact['number']; ?>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Route Information -->
                        <div class="route-info" id="routeInfo" style="display: none;">
                            <h6><i class="bi bi-map me-2"></i>Route Information</h6>
                            <p class="mb-2">Recommended safe route found with optimal safety features.</p>
                            <div class="route-metrics">
                                <div class="metric">
                                    <div class="metric-value" id="routeDistance">2.3 km</div>
                                    <div class="metric-label">Distance</div>
                                </div>
                                <div class="metric">
                                    <div class="metric-value" id="routeTime">28 min</div>
                                    <div class="metric-label">Duration</div>
                                </div>
                                <div class="metric">
                                    <div class="metric-value" id="safetyScore">9.2/10</div>
                                    <div class="metric-label">Safety Score</div>
                                </div>
                            </div>
                        </div>

                        <!-- Nearby Safe Locations -->
                        <div class="safe-locations">
                            <h6 class="mb-3"><i class="bi bi-house-check me-2"></i>Nearby Safe Locations</h6>
                            <?php foreach ($safeLocations as $location): ?>
                                <div class="safe-location" data-lat="<?php echo $location['lat']; ?>" data-lng="<?php echo $location['lng']; ?>">
                                    <span class="location-type type-<?php echo $location['type']; ?>">
                                        <?php echo ucfirst($location['type']); ?>
                                    </span>
                                    <div class="location-name"><?php echo htmlspecialchars($location['name']); ?></div>
                                    <div class="location-address"><?php echo htmlspecialchars($location['address']); ?></div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="location-rating">
                                            <?php for ($i = 0; $i < floor($location['rating']); $i++): ?>
                                                <i class="bi bi-star-fill"></i>
                                            <?php endfor; ?>
                                            <span class="ms-1"><?php echo $location['rating']; ?></span>
                                        </div>
                                        <div class="location-status <?php echo $location['open_24_7'] ? 'status-open' : 'status-closed'; ?>">
                                            <?php echo $location['open_24_7'] ? '24/7 Open' : 'Limited Hours'; ?>
                                        </div>
                                    </div>
                                    <div class="mt-2">
                                        <a href="tel:<?php echo $location['phone']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-telephone me-1"></i>Call
                                        </a>
                                        <button class="btn btn-sm btn-outline-secondary" onclick="showOnMap(<?php echo $location['lat']; ?>, <?php echo $location['lng']; ?>)">
                                            <i class="bi bi-geo-alt me-1"></i>Show on Map
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Emergency Modal -->
    <div class="modal fade" id="emergencyModal" tabindex="-1" aria-labelledby="emergencyModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="emergencyModalLabel">
                        <i class="bi bi-exclamation-triangle me-2"></i>Emergency Alert
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <div class="alert alert-warning">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Important:</strong> In case of immediate danger, call 911 directly.
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Emergency Type</label>
                            <select class="form-control" name="emergency_type" required>
                                <option value="">Select emergency type</option>
                                <option value="harassment">Harassment</option>
                                <option value="stalking">Stalking</option>
                                <option value="unsafe_area">Unsafe Area</option>
                                <option value="medical">Medical Emergency</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Current Location</label>
                            <input type="text" class="form-control" name="location" id="currentLocation" placeholder="Your current location">
                            <small class="form-text text-muted">
                                <button type="button" class="btn btn-link p-0" onclick="getCurrentLocation()">
                                    <i class="bi bi-geo-alt me-1"></i>Use my current location
                                </button>
                            </small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Additional Information</label>
                            <textarea class="form-control" name="message" rows="3" placeholder="Describe the situation (optional)"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="emergency_alert" class="btn btn-danger">
                            <i class="bi bi-broadcast me-2"></i>Send Alert
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Floating Action Buttons -->
    <div class="fab-container">
        <button class="fab fab-location" title="Get Current Location" onclick="getCurrentLocation()">
            <i class="bi bi-geo-alt"></i>
        </button>
        <button class="fab fab-emergency" title="Emergency Alert" data-bs-toggle="modal" data-bs-target="#emergencyModal">
            <i class="bi bi-exclamation-triangle"></i>
        </button>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // Initialize map
        let map;
        let userLocation = null;
        let routeLayer = null;
        let safeLocationMarkers = [];

        // Sample safe locations data for JavaScript
        const safeLocationsData = <?php echo json_encode($safeLocations); ?>;

        // Initialize the application
        document.addEventListener('DOMContentLoaded', function() {
            initializeMap();
            addSafeLocationMarkers();
            setupEventListeners();
        });

        function initializeMap() {
            // Initialize map centered on New York City
            map = L.map('map').setView([40.7128, -74.0060], 13);

            // Add OpenStreetMap tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            // Try to get user's current location
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    userLocation = [position.coords.latitude, position.coords.longitude];
                    map.setView(userLocation, 15);
                    
                    // Add user location marker
                    L.marker(userLocation, {
                        icon: L.divIcon({
                            className: 'user-location-marker',
                            html: '<div style="background: #e91e63; width: 20px; height: 20px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3);"></div>',
                            iconSize: [20, 20],
                            iconAnchor: [10, 10]
                        })
                    }).addTo(map).bindPopup('Your Location');
                });
            }
        }

        function addSafeLocationMarkers() {
            safeLocationsData.forEach(function(location) {
                const iconColor = getIconColor(location.type);
                const marker = L.marker([location.lat, location.lng], {
                    icon: L.divIcon({
                        className: 'safe-location-marker',
                        html: `<div style="background: ${iconColor}; color: white; width: 30px; height: 30px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 16px; border: 2px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3);">
                                ${getIconSymbol(location.type)}
                               </div>`,
                        iconSize: [30, 30],
                        iconAnchor: [15, 15]
                    })
                }).addTo(map);

                const popupContent = `
                    <div style="min-width: 200px;">
                        <h6 style="margin: 0 0 8px 0; color: #2c3e50;">${location.name}</h6>
                        <p style="margin: 0 0 5px 0; font-size: 13px; color: #6c757d;">${location.address}</p>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <span style="font-size: 12px; color: #f39c12;">★ ${location.rating}</span>
                            <span style="font-size: 11px; color: ${location.open_24_7 ? '#28a745' : '#6c757d'};">
                                ${location.open_24_7 ? '24/7 Open' : 'Limited Hours'}
                            </span>
                        </div>
                        <a href="tel:${location.phone}" style="display: inline-block; background: #e91e63; color: white; padding: 4px 8px; border-radius: 4px; text-decoration: none; font-size: 12px;">
                            📞 Call
                        </a>
                    </div>
                `;
                
                marker.bindPopup(popupContent);
                safeLocationMarkers.push(marker);
            });
        }

        function getIconColor(type) {
            const colors = {
                'police': '#1976d2',
                'hospital': '#388e3c',
                'shelter': '#f57c00',
                'store': '#7b1fa2'
            };
            return colors[type] || '#6c757d';
        }

        function getIconSymbol(type) {
            const symbols = {
                'police': '🚔',
                'hospital': '🏥',
                'shelter': '🏠',
                'store': '🏪'
            };
            return symbols[type] || '📍';
        }

        function setupEventListeners() {
            // Route form submission
            document.getElementById('routeForm').addEventListener('submit', function(e) {
                e.preventDefault();
                findSafeRoute();
            });

            // Safe location cards click
            document.querySelectorAll('.safe-location').forEach(function(card) {
                card.addEventListener('click', function() {
                    const lat = parseFloat(this.dataset.lat);
                    const lng = parseFloat(this.dataset.lng);
                    showOnMap(lat, lng);
                });
            });
        }

        function findSafeRoute() {
            const fromLocation = document.getElementById('fromLocation').value;
            const toLocation = document.getElementById('toLocation').value;
            
            if (!fromLocation || !toLocation) {
                alert('Please enter both starting location and destination.');
                return;
            }

            // Show loading indicator
            document.getElementById('loadingIndicator').style.display = 'block';
            
            // Simulate route finding (in real app, this would call a routing API)
            setTimeout(function() {
                document.getElementById('loadingIndicator').style.display = 'none';
                document.getElementById('routeInfo').style.display = 'block';
                
                // Simulate drawing a route on the map
                drawSampleRoute();
                
                // Update route metrics with random values for demo
                document.getElementById('routeDistance').textContent = (Math.random() * 5 + 1).toFixed(1) + ' km';
                document.getElementById('routeTime').textContent = Math.floor(Math.random() * 30 + 15) + ' min';
                document.getElementById('safetyScore').textContent = (Math.random() * 2 + 8).toFixed(1) + '/10';
                
                // Show success message
                showNotification('Safe route found! Check the map for your recommended path.', 'success');
            }, 2000);
        }

        function drawSampleRoute() {
            // Remove existing route
            if (routeLayer) {
                map.removeLayer(routeLayer);
            }

            // Draw a sample route (in real app, this would be actual route coordinates)
            const sampleRoute = [
                [40.7128, -74.0060],
                [40.7200, -73.9900],
                [40.7300, -73.9850],
                [40.7400, -73.9800]
            ];

            routeLayer = L.polyline(sampleRoute, {
                color: '#e91e63',
                weight: 4,
                opacity: 0.8
            }).addTo(map);

            // Fit map to show the route
            map.fitBounds(routeLayer.getBounds(), { padding: [20, 20] });
        }

        function showOnMap(lat, lng) {
            map.setView([lat, lng], 16);
            
            // Find and open the popup for this location
            safeLocationMarkers.forEach(function(marker) {
                const markerLat = marker.getLatLng().lat;
                const markerLng = marker.getLatLng().lng;
                
                if (Math.abs(markerLat - lat) < 0.001 && Math.abs(markerLng - lng) < 0.001) {
                    marker.openPopup();
                }
            });
        }

        function getCurrentLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    
                    // Update the location input in emergency modal
                    document.getElementById('currentLocation').value = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
                    
                    // Update map view
                    if (map) {
                        map.setView([lat, lng], 16);
                    }
                    
                    showNotification('Location updated successfully!', 'success');
                }, function(error) {
                    showNotification('Unable to get your location. Please enter manually.', 'error');
                });
            } else {
                showNotification('Geolocation is not supported by this browser.', 'error');
            }
        }

        function showNotification(message, type) {
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 300px;';
            notification.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(notification);
            
            // Auto-remove after 5 seconds
            setTimeout(function() {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 5000);
        }

        // Keyboard shortcuts for emergency
        document.addEventListener('keydown', function(e) {
            // Ctrl + Shift + E for emergency alert
            if (e.ctrlKey && e.shiftKey && e.key === 'E') {
                e.preventDefault();
                const emergencyModal = new bootstrap.Modal(document.getElementById('emergencyModal'));
                emergencyModal.show();
            }
        });

        // Auto-dismiss alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert:not(.position-fixed)');
            alerts.forEach(function(alert) {
                if (alert.classList.contains('show')) {
                    alert.classList.remove('show');
                    setTimeout(function() {
                        if (alert.parentNode) {
                            alert.parentNode.removeChild(alert);
                        }
                    }, 500);
                }
            });
        }, 5000);
    </script>
</body>
</html>
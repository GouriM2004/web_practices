<?php
// includes/Models/GeoFence.php
// Manages geo-fenced poll validation and location-based access control

require_once __DIR__ . '/../Database.php';

class GeoFence
{
    private $db;
    const EARTH_RADIUS_KM = 6371;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
     * Returns distance in kilometers
     */
    public static function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $lat1_rad = deg2rad($lat1);
        $lon1_rad = deg2rad($lon1);
        $lat2_rad = deg2rad($lat2);
        $lon2_rad = deg2rad($lon2);

        $dlat = $lat2_rad - $lat1_rad;
        $dlon = $lon2_rad - $lon1_rad;

        $a = sin($dlat / 2) * sin($dlat / 2) +
            cos($lat1_rad) * cos($lat2_rad) *
            sin($dlon / 2) * sin($dlon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return self::EARTH_RADIUS_KM * $c;
    }

    /**
     * Check if poll has geo-fencing enabled
     */
    public function isGeoFenced($poll_id)
    {
        $stmt = $this->db->prepare("SELECT geo_fencing_enabled FROM polls WHERE id = ?");
        $stmt->bind_param("i", $poll_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $res && $res['geo_fencing_enabled'] ? true : false;
    }

    /**
     * Get geo-fencing configuration for a poll
     */
    public function getGeoFenceConfig($poll_id)
    {
        $stmt = $this->db->prepare("
            SELECT 
                id,
                geo_fencing_enabled,
                location_type,
                location_name,
                latitude,
                longitude,
                radius_km
            FROM polls
            WHERE id = ?
        ");
        $stmt->bind_param("i", $poll_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $res ?: null;
    }

    /**
     * Get all geo-fence zones for a poll (for multiple location zones)
     */
    public function getGeoFenceZones($poll_id)
    {
        $stmt = $this->db->prepare("
            SELECT 
                id,
                zone_name,
                location_type,
                latitude,
                longitude,
                radius_km,
                is_active
            FROM geo_fence_zones
            WHERE poll_id = ? AND is_active = 1
            ORDER BY zone_name ASC
        ");
        $stmt->bind_param("i", $poll_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $res ?: [];
    }

    /**
     * Validate if voter location is within allowed geo-fence
     * Returns array with 'allowed' boolean and 'distance' in km
     */
    public function validateLocation($poll_id, $voter_lat, $voter_lon)
    {
        if (!is_numeric($voter_lat) || !is_numeric($voter_lon)) {
            return ['allowed' => false, 'reason' => 'Invalid coordinates', 'distance' => null];
        }

        $config = $this->getGeoFenceConfig($poll_id);
        if (!$config || !$config['geo_fencing_enabled']) {
            return ['allowed' => true, 'reason' => 'Geo-fencing disabled', 'distance' => null];
        }

        // Check primary location zone
        $distance = self::calculateDistance(
            $voter_lat,
            $voter_lon,
            $config['latitude'],
            $config['longitude']
        );

        if ($distance <= $config['radius_km']) {
            return [
                'allowed' => true,
                'reason' => 'Within ' . $config['location_name'],
                'distance' => round($distance, 3),
                'location_type' => $config['location_type']
            ];
        }

        // Check additional zones if configured
        $zones = $this->getGeoFenceZones($poll_id);
        foreach ($zones as $zone) {
            $zone_distance = self::calculateDistance(
                $voter_lat,
                $voter_lon,
                $zone['latitude'],
                $zone['longitude']
            );

            if ($zone_distance <= $zone['radius_km']) {
                return [
                    'allowed' => true,
                    'reason' => 'Within ' . $zone['zone_name'],
                    'distance' => round($zone_distance, 3),
                    'location_type' => $zone['location_type']
                ];
            }
        }

        return [
            'allowed' => false,
            'reason' => 'Outside allowed location (' . round($distance, 1) . 'km away)',
            'distance' => round($distance, 3),
            'location_type' => $config['location_type']
        ];
    }

    /**
     * Enable geo-fencing for a poll
     */
    public function enableGeoFence($poll_id, $location_type, $location_name, $latitude, $longitude, $radius_km = 0.5)
    {
        $stmt = $this->db->prepare("
            UPDATE polls
            SET 
                geo_fencing_enabled = 1,
                location_type = ?,
                location_name = ?,
                latitude = ?,
                longitude = ?,
                radius_km = ?
            WHERE id = ?
        ");
        $stmt->bind_param("ssddi", $location_type, $location_name, $latitude, $longitude, $radius_km, $poll_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Disable geo-fencing for a poll
     */
    public function disableGeoFence($poll_id)
    {
        $stmt = $this->db->prepare("
            UPDATE polls
            SET geo_fencing_enabled = 0
            WHERE id = ?
        ");
        $stmt->bind_param("i", $poll_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Add an additional geo-fence zone to a poll
     */
    public function addZone($poll_id, $zone_name, $location_type, $latitude, $longitude, $radius_km)
    {
        $stmt = $this->db->prepare("
            INSERT INTO geo_fence_zones 
            (poll_id, zone_name, location_type, latitude, longitude, radius_km, is_active)
            VALUES (?, ?, ?, ?, ?, ?, 1)
        ");
        $stmt->bind_param("issddd", $poll_id, $zone_name, $location_type, $latitude, $longitude, $radius_km);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Record voter location for audit trail
     */
    public function recordVoterLocation($voter_id, $voter_ip, $latitude, $longitude, $accuracy_meters, $device_type = 'web')
    {
        $stmt = $this->db->prepare("
            INSERT INTO voter_locations
            (voter_id, voter_ip, latitude, longitude, accuracy_meters, device_type)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("isddis", $voter_id, $voter_ip, $latitude, $longitude, $accuracy_meters, $device_type);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Mark vote as location verified
     */
    public function verifyVoteLocation($vote_id, $latitude, $longitude, $distance_km)
    {
        $stmt = $this->db->prepare("
            UPDATE poll_votes
            SET 
                location_verified = 1,
                voter_latitude = ?,
                voter_longitude = ?,
                distance_km = ?
            WHERE id = ?
        ");
        $stmt->bind_param("dddi", $latitude, $longitude, $distance_km, $vote_id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    /**
     * Get geo-fence statistics for a poll
     */
    public function getGeoFenceStats($poll_id)
    {
        $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_votes,
                SUM(CASE WHEN location_verified = 1 THEN 1 ELSE 0 END) as verified_votes,
                AVG(distance_km) as avg_distance_km,
                MIN(distance_km) as min_distance_km,
                MAX(distance_km) as max_distance_km
            FROM poll_votes
            WHERE poll_id = ? AND location_verified = 1
        ");
        $stmt->bind_param("i", $poll_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $res ?: null;
    }

    /**
     * Get all geo-fenced polls
     */
    public function getGeoFencedPolls()
    {
        $stmt = $this->db->prepare("
            SELECT 
                id,
                question,
                location_type,
                location_name,
                latitude,
                longitude,
                radius_km,
                is_active,
                created_at
            FROM polls
            WHERE geo_fencing_enabled = 1 AND is_active = 1
            ORDER BY created_at DESC
        ");
        $stmt->execute();
        $res = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $res ?: [];
    }
}

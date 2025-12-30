<?php
/**
 * Weather Service - Integrates with OpenWeatherMap API
 * Fetches current weather conditions with caching to minimize API calls
 */

class WeatherService {
    private $db;
    private $apiKey;
    private $cacheLifetime = 600; // 10 minutes cache
    
    public function __construct($db) {
        $this->db = $db;
        $this->apiKey = Config::get('WEATHER_API_KEY', '');
    }
    
    /**
     * Get current weather for a location
     * @param string $location City name or coordinates
     * @return array|null Weather data or null on failure
     */
    public function getCurrentWeather($location = 'auto') {
        // Auto-detect location from IP if not specified
        if ($location === 'auto') {
            $location = $this->detectLocation();
        }
        
        // Check cache first
        $cached = $this->getCachedWeather($location);
        if ($cached) {
            return $cached;
        }
        
        // Fetch fresh data from API
        $weatherData = $this->fetchWeatherFromAPI($location);
        
        if ($weatherData) {
            $this->cacheWeather($location, $weatherData);
        }
        
        return $weatherData;
    }
    
    /**
     * Fetch weather from OpenWeatherMap API
     */
    private function fetchWeatherFromAPI($location) {
        if (empty($this->apiKey)) {
            // Return mock data for demo if no API key
            return $this->getMockWeather();
        }
        
        $url = "https://api.openweathermap.org/data/2.5/weather?q=" . urlencode($location) . 
               "&appid={$this->apiKey}&units=metric";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200 || !$response) {
            return $this->getMockWeather();
        }
        
        $data = json_decode($response, true);
        
        if (!$data || !isset($data['weather'][0])) {
            return null;
        }
        
        return [
            'condition' => strtolower($data['weather'][0]['main']),
            'description' => $data['weather'][0]['description'],
            'temperature' => round($data['main']['temp'], 1),
            'feels_like' => round($data['main']['feels_like'], 1),
            'humidity' => $data['main']['humidity'],
            'location' => $data['name'],
            'icon' => $data['weather'][0]['icon'],
            'timestamp' => time()
        ];
    }
    
    /**
     * Get cached weather data
     */
    private function getCachedWeather($location) {
        $stmt = $this->db->prepare(
            "SELECT weather_data, cached_at FROM weather_cache 
             WHERE location = ? AND expires_at > NOW()"
        );
        $stmt->execute([$location]);
        $cache = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($cache) {
            return json_decode($cache['weather_data'], true);
        }
        
        return null;
    }
    
    /**
     * Cache weather data
     */
    private function cacheWeather($location, $data) {
        $expiresAt = date('Y-m-d H:i:s', time() + $this->cacheLifetime);
        
        $stmt = $this->db->prepare(
            "INSERT INTO weather_cache (location, weather_data, expires_at)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE 
             weather_data = VALUES(weather_data),
             cached_at = CURRENT_TIMESTAMP,
             expires_at = VALUES(expires_at)"
        );
        
        $stmt->execute([
            $location,
            json_encode($data),
            $expiresAt
        ]);
    }
    
    /**
     * Detect location from IP address
     */
    private function detectLocation() {
        // Try to get IP-based location
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        
        // For localhost, return default location
        if ($ip === '127.0.0.1' || $ip === '::1') {
            return 'London'; // Default city
        }
        
        // Use ip-api.com for free IP geolocation
        $url = "http://ip-api.com/json/{$ip}";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 3
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        if ($response) {
            $data = json_decode($response, true);
            if ($data && $data['status'] === 'success' && isset($data['city'])) {
                return $data['city'];
            }
        }
        
        return 'London'; // Fallback
    }
    
    /**
     * Get mock weather data for demo purposes
     */
    private function getMockWeather() {
        $conditions = ['rain', 'clear', 'clouds', 'snow', 'thunderstorm'];
        $randomCondition = $conditions[array_rand($conditions)];
        
        return [
            'condition' => $randomCondition,
            'description' => ucfirst($randomCondition) . ' weather',
            'temperature' => rand(15, 28),
            'feels_like' => rand(15, 28),
            'humidity' => rand(40, 80),
            'location' => 'Demo Location',
            'icon' => '01d',
            'timestamp' => time(),
            'is_mock' => true
        ];
    }
    
    /**
     * Get weather icon URL
     */
    public function getIconUrl($iconCode) {
        return "https://openweathermap.org/img/wn/{$iconCode}@2x.png";
    }
    
    /**
     * Clear expired cache entries
     */
    public function clearExpiredCache() {
        $stmt = $this->db->prepare("DELETE FROM weather_cache WHERE expires_at < NOW()");
        $stmt->execute();
    }
}

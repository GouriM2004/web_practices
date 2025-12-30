<?php
/**
 * WeatherTrigger Model
 * Manages weather-triggered polls including auto-generation based on conditions
 */

class WeatherTrigger {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Create a new weather trigger
     */
    public function create($data) {
        $stmt = $this->db->prepare(
            "INSERT INTO weather_triggers 
            (trigger_name, weather_condition, temperature_min, temperature_max, 
             poll_question, poll_options, priority, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        
        return $stmt->execute([
            $data['trigger_name'],
            $data['weather_condition'],
            $data['temperature_min'] ?? null,
            $data['temperature_max'] ?? null,
            $data['poll_question'],
            json_encode($data['poll_options']),
            $data['priority'] ?? 0,
            $data['is_active'] ?? true
        ]);
    }
    
    /**
     * Get all weather triggers
     */
    public function getAll($activeOnly = false) {
        $sql = "SELECT * FROM weather_triggers";
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY priority DESC, created_at DESC";
        
        $stmt = $this->db->query($sql);
        $triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Decode JSON options
        foreach ($triggers as &$trigger) {
            $trigger['poll_options'] = json_decode($trigger['poll_options'], true);
        }
        
        return $triggers;
    }
    
    /**
     * Get trigger by ID
     */
    public function getById($id) {
        $stmt = $this->db->prepare("SELECT * FROM weather_triggers WHERE id = ?");
        $stmt->execute([$id]);
        $trigger = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($trigger) {
            $trigger['poll_options'] = json_decode($trigger['poll_options'], true);
        }
        
        return $trigger;
    }
    
    /**
     * Update weather trigger
     */
    public function update($id, $data) {
        $stmt = $this->db->prepare(
            "UPDATE weather_triggers SET
            trigger_name = ?,
            weather_condition = ?,
            temperature_min = ?,
            temperature_max = ?,
            poll_question = ?,
            poll_options = ?,
            priority = ?,
            is_active = ?
            WHERE id = ?"
        );
        
        return $stmt->execute([
            $data['trigger_name'],
            $data['weather_condition'],
            $data['temperature_min'] ?? null,
            $data['temperature_max'] ?? null,
            $data['poll_question'],
            json_encode($data['poll_options']),
            $data['priority'] ?? 0,
            $data['is_active'] ?? true,
            $id
        ]);
    }
    
    /**
     * Delete weather trigger
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM weather_triggers WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Find matching triggers for current weather conditions
     */
    public function findMatchingTriggers($weatherData) {
        if (!$weatherData) {
            return [];
        }
        
        $condition = $weatherData['condition'];
        $temperature = $weatherData['temperature'] ?? null;
        
        // Base query
        $sql = "SELECT * FROM weather_triggers 
                WHERE is_active = 1 
                AND weather_condition = ?";
        
        $params = [$condition];
        
        // Add temperature conditions if available
        if ($temperature !== null) {
            $sql .= " AND (
                (temperature_min IS NULL OR temperature_min <= ?) 
                AND (temperature_max IS NULL OR temperature_max >= ?)
            )";
            $params[] = $temperature;
            $params[] = $temperature;
        }
        
        $sql .= " ORDER BY priority DESC LIMIT 5";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $triggers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Decode JSON options
        foreach ($triggers as &$trigger) {
            $trigger['poll_options'] = json_decode($trigger['poll_options'], true);
        }
        
        return $triggers;
    }
    
    /**
     * Generate a poll from a weather trigger
     */
    public function generatePollFromTrigger($triggerId, $weatherData, $duration = 24) {
        $trigger = $this->getById($triggerId);
        
        if (!$trigger) {
            return null;
        }
        
        // Check if a poll for this trigger already exists and is active
        $existing = $this->getActivePollForTrigger($triggerId);
        if ($existing) {
            return $existing; // Return existing poll instead of creating duplicate
        }
        
        // Create the poll
        $pollModel = new Poll($this->db);
        
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$duration} hours"));
        
        $pollId = $pollModel->create([
            'question' => $trigger['poll_question'],
            'options' => $trigger['poll_options'],
            'expires_at' => $expiresAt,
            'is_multiple_choice' => false,
            'created_by' => 0 // System-generated
        ]);
        
        if (!$pollId) {
            return null;
        }
        
        // Record the weather-triggered poll
        $stmt = $this->db->prepare(
            "INSERT INTO weather_triggered_polls 
            (trigger_id, poll_id, weather_condition, temperature, location, expires_at)
            VALUES (?, ?, ?, ?, ?, ?)"
        );
        
        $stmt->execute([
            $triggerId,
            $pollId,
            $weatherData['condition'],
            $weatherData['temperature'] ?? null,
            $weatherData['location'] ?? null,
            $expiresAt
        ]);
        
        return [
            'poll_id' => $pollId,
            'trigger' => $trigger,
            'weather' => $weatherData
        ];
    }
    
    /**
     * Get active poll for a trigger (if exists)
     */
    private function getActivePollForTrigger($triggerId) {
        $stmt = $this->db->prepare(
            "SELECT wtp.*, p.question, p.options 
             FROM weather_triggered_polls wtp
             JOIN polls p ON wtp.poll_id = p.id
             WHERE wtp.trigger_id = ? 
             AND wtp.expires_at > NOW()
             ORDER BY wtp.generated_at DESC
             LIMIT 1"
        );
        
        $stmt->execute([$triggerId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get active weather-triggered polls
     */
    public function getActiveWeatherPolls() {
        $stmt = $this->db->query(
            "SELECT wtp.*, p.question, p.options, p.created_at,
                    wt.trigger_name, wt.weather_condition
             FROM weather_triggered_polls wtp
             JOIN polls p ON wtp.poll_id = p.id
             JOIN weather_triggers wt ON wtp.trigger_id = wt.id
             WHERE wtp.expires_at > NOW()
             ORDER BY wtp.generated_at DESC"
        );
        
        $polls = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Decode options
        foreach ($polls as &$poll) {
            $poll['options'] = json_decode($poll['options'], true);
        }
        
        return $polls;
    }
    
    /**
     * Auto-generate polls based on current weather
     */
    public function autoGeneratePolls($weatherData, $maxPolls = 3) {
        $triggers = $this->findMatchingTriggers($weatherData);
        $generatedPolls = [];
        
        $count = 0;
        foreach ($triggers as $trigger) {
            if ($count >= $maxPolls) {
                break;
            }
            
            $result = $this->generatePollFromTrigger($trigger['id'], $weatherData);
            if ($result) {
                $generatedPolls[] = $result;
                $count++;
            }
        }
        
        return $generatedPolls;
    }
    
    /**
     * Clean up expired weather polls
     */
    public function cleanupExpiredPolls() {
        // Delete expired weather-triggered poll records
        $stmt = $this->db->prepare(
            "DELETE FROM weather_triggered_polls WHERE expires_at < NOW()"
        );
        return $stmt->execute();
    }
    
    /**
     * Get statistics for weather triggers
     */
    public function getStatistics() {
        $stmt = $this->db->query(
            "SELECT 
                COUNT(*) as total_triggers,
                SUM(is_active = 1) as active_triggers,
                (SELECT COUNT(*) FROM weather_triggered_polls WHERE expires_at > NOW()) as active_polls,
                (SELECT COUNT(*) FROM weather_triggered_polls) as total_polls_generated
             FROM weather_triggers"
        );
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

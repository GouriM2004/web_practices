-- =====================================================
-- Weather-Based Poll Triggers Feature (ALL IN ONE)
-- =====================================================

-- -------------------------------
-- 1. Weather Triggers
-- -------------------------------
CREATE TABLE IF NOT EXISTS weather_triggers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trigger_name VARCHAR(100) NOT NULL,
    weather_condition VARCHAR(50) NOT NULL, -- rain, clear, clouds, snow, etc.
    temperature_min DECIMAL(5,2) NULL,
    temperature_max DECIMAL(5,2) NULL,
    poll_question TEXT NOT NULL,
    poll_options JSON NOT NULL, -- JSON array of options
    is_active BOOLEAN DEFAULT TRUE,
    priority INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_condition (weather_condition, is_active),
    INDEX idx_priority (priority DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------
-- 2. Triggered Polls Tracking
-- -------------------------------
CREATE TABLE IF NOT EXISTS weather_triggered_polls (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trigger_id INT NOT NULL,
    poll_id INT NOT NULL,
    weather_condition VARCHAR(50) NOT NULL,
    temperature DECIMAL(5,2) NULL,
    location VARCHAR(100) NULL,
    generated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NULL,
    FOREIGN KEY (trigger_id) REFERENCES weather_triggers(id) ON DELETE CASCADE,
    FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE,
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------
-- 3. Weather Cache (API Optimization)
-- -------------------------------
CREATE TABLE IF NOT EXISTS weather_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    location VARCHAR(100) NOT NULL,
    weather_data JSON NOT NULL,
    cached_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    UNIQUE KEY unique_location (location),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------
-- 4. Sample Weather Triggers
-- -------------------------------
INSERT INTO weather_triggers 
(trigger_name, weather_condition, temperature_min, temperature_max, poll_question, poll_options, priority)
VALUES
('Rainy Day Beverage', 'rain', NULL, NULL, 'It''s raining — Tea or Coffee?',
 '["Tea", "Coffee", "Hot Chocolate", "Neither"]', 10),

('Rainy Day Activity', 'rain', NULL, NULL, 'Rainy day plans?',
 '["Stay in & read", "Watch movies", "Cook something", "Take a walk anyway"]', 9),

('Hot Summer Day', 'clear', 30, NULL, 'It''s hot outside! What''s your go-to?',
 '["Ice cream", "Cold drink", "Pool/Beach", "Stay inside with AC"]', 8),

('Cold Winter Day', 'snow', NULL, 5, 'Snowy day comfort?',
 '["Hot cocoa", "Cozy blanket", "Fireplace", "Winter sports"]', 8),

('Stormy Weather', 'thunderstorm', NULL, NULL, 'Thunder & lightning! How do you feel?',
 '["Excited", "Scared", "Cozy", "Annoyed"]', 7),

('Cloudy Day', 'clouds', NULL, NULL, 'Cloudy skies — mood check?',
 '["Relaxed", "Gloomy", "Productive", "Creative"]', 5),

('Perfect Weather', 'clear', 20, 25, 'Perfect weather! What are you doing?',
 '["Outdoor activity", "Picnic", "Work outside", "Still staying in"]', 6),

('Foggy Morning', 'mist', NULL, NULL, 'Foggy morning vibes?',
 '["Mysterious", "Calming", "Annoying", "Perfect for coffee"]', 4);

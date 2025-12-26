-- Add Geo-Fencing to Polls
-- Supports Campus, City, and Event Location based polling

ALTER TABLE polls ADD COLUMN geo_fencing_enabled TINYINT(1) DEFAULT 0 AFTER location_tag;
ALTER TABLE polls ADD COLUMN location_type ENUM('campus', 'city', 'event') NULL AFTER geo_fencing_enabled;
ALTER TABLE polls ADD COLUMN location_name VARCHAR(255) NULL AFTER location_type;
ALTER TABLE polls ADD COLUMN latitude DECIMAL(10, 8) NULL AFTER location_name;
ALTER TABLE polls ADD COLUMN longitude DECIMAL(11, 8) NULL AFTER latitude;
ALTER TABLE polls ADD COLUMN radius_km DECIMAL(5, 2) DEFAULT 0.5 AFTER longitude;

-- Create geo_fence_zones table for more advanced location management
CREATE TABLE IF NOT EXISTS geo_fence_zones (
  id INT AUTO_INCREMENT PRIMARY KEY,
  poll_id INT NOT NULL,
  zone_name VARCHAR(255) NOT NULL,
  location_type ENUM('campus', 'city', 'event') NOT NULL,
  latitude DECIMAL(10, 8) NOT NULL,
  longitude DECIMAL(11, 8) NOT NULL,
  radius_km DECIMAL(5, 2) NOT NULL,
  is_active TINYINT(1) DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE,
  INDEX idx_poll_active (poll_id, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Store voter location history for auditing
CREATE TABLE IF NOT EXISTS voter_locations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  voter_id INT NULL,
  voter_ip VARCHAR(50),
  latitude DECIMAL(10, 8),
  longitude DECIMAL(11, 8),
  accuracy_meters INT,
  device_type VARCHAR(50),
  accessed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (voter_id) REFERENCES voters(id) ON DELETE SET NULL,
  INDEX idx_voter_id (voter_id),
  INDEX idx_ip (voter_ip)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add location_verified column to poll_votes
ALTER TABLE poll_votes ADD COLUMN location_verified TINYINT(1) DEFAULT 0 AFTER location;
ALTER TABLE poll_votes ADD COLUMN voter_latitude DECIMAL(10, 8) NULL AFTER location_verified;
ALTER TABLE poll_votes ADD COLUMN voter_longitude DECIMAL(11, 8) NULL AFTER voter_latitude;
ALTER TABLE poll_votes ADD COLUMN distance_km DECIMAL(8, 4) NULL AFTER voter_longitude;

-- Add index for geo-fenced poll queries
CREATE INDEX idx_geo_fencing ON polls(geo_fencing_enabled, location_type);

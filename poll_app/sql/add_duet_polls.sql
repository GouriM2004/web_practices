-- Migration: Add Duet Polls Feature
-- Description: Allows two creators to collaborate on a single poll
-- Date: 2026-01-02

USE poll_app;

-- Table to store duet poll information
CREATE TABLE IF NOT EXISTS duet_polls (
  id INT AUTO_INCREMENT PRIMARY KEY,
  poll_id INT NOT NULL UNIQUE,
  creator1_id INT NOT NULL,
  creator2_id INT NOT NULL,
  invitation_status ENUM('pending', 'accepted', 'declined') DEFAULT 'pending',
  invitation_sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  invitation_responded_at DATETIME NULL,
  collaboration_notes TEXT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE,
  FOREIGN KEY (creator1_id) REFERENCES admins(id) ON DELETE CASCADE,
  FOREIGN KEY (creator2_id) REFERENCES admins(id) ON DELETE CASCADE,
  INDEX idx_creator1 (creator1_id),
  INDEX idx_creator2 (creator2_id),
  INDEX idx_poll_id (poll_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table to track collaborative edits and contributions
CREATE TABLE IF NOT EXISTS duet_poll_activity (
  id INT AUTO_INCREMENT PRIMARY KEY,
  duet_poll_id INT NOT NULL,
  admin_id INT NOT NULL,
  activity_type ENUM('created', 'edited', 'added_option', 'removed_option', 'invited', 'accepted', 'commented') NOT NULL,
  activity_description TEXT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (duet_poll_id) REFERENCES duet_polls(id) ON DELETE CASCADE,
  FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
  INDEX idx_duet_poll (duet_poll_id),
  INDEX idx_admin (admin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add column to polls table to indicate if it's a duet poll
ALTER TABLE polls 
ADD COLUMN is_duet TINYINT(1) DEFAULT 0 AFTER allow_multiple;

-- Sample data: Insert test admins if needed
-- INSERT INTO admins (username, password) VALUES 
-- ('creator1', '$2y$10$YourHashedPassword1'),
-- ('creator2', '$2y$10$YourHashedPassword2');

-- Sample duet poll creation
-- INSERT INTO polls (question, is_active, allow_multiple, is_duet, category) 
-- VALUES ('What should we focus on next quarter?', 1, 0, 1, 'Business Strategy');
-- 
-- INSERT INTO duet_polls (poll_id, creator1_id, creator2_id, invitation_status) 
-- VALUES (LAST_INSERT_ID(), 1, 2, 'accepted');

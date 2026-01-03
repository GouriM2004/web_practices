CREATE DATABASE IF NOT EXISTS poll_app CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE poll_app;

-- Admins table
CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Polls table
CREATE TABLE IF NOT EXISTS polls (
  id INT AUTO_INCREMENT PRIMARY KEY,
  question VARCHAR(255) NOT NULL,
  is_active TINYINT(1) DEFAULT 1,
  allow_multiple TINYINT(1) DEFAULT 0,
  is_emoji_only TINYINT(1) DEFAULT 0,
  category VARCHAR(100) DEFAULT 'General',
  location_tag VARCHAR(100) NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Poll options
CREATE TABLE IF NOT EXISTS poll_options (
  id INT AUTO_INCREMENT PRIMARY KEY,
  poll_id INT NOT NULL,
  option_text VARCHAR(255) NOT NULL,
  votes INT DEFAULT 0,
  FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Voters
CREATE TABLE IF NOT EXISTS voters (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Poll votes (prevent duplicates by account or IP, capture anonymity preference)
CREATE TABLE IF NOT EXISTS poll_votes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  poll_id INT NOT NULL,
  option_id INT NOT NULL,
  voter_ip VARCHAR(50),
  voter_id INT NULL,
  voter_name VARCHAR(100),
  is_public TINYINT(1) DEFAULT 0,
  confidence_level ENUM('very_sure', 'somewhat_sure', 'just_guessing') DEFAULT 'somewhat_sure',
  location VARCHAR(100) NULL,
  voter_type ENUM('expert','student','public') DEFAULT 'public',
  voted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_poll_voter (poll_id, voter_id),
  UNIQUE KEY uniq_poll_ip (poll_id, voter_ip),
  FOREIGN KEY (poll_id) REFERENCES polls(id) ON DELETE CASCADE,
  FOREIGN KEY (option_id) REFERENCES poll_options(id) ON DELETE CASCADE,
  FOREIGN KEY (voter_id) REFERENCES voters(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert a default admin (username: admin, password: admin123)
INSERT INTO admins (username, password)
VALUES ('admin', '$2y$10$F4Fh1k7m/j2b7TOxxJSXXuD6yGxQxEP9Gn9DhV4H9vM2yhE4DXaoC')
ON DUPLICATE KEY UPDATE username = username;

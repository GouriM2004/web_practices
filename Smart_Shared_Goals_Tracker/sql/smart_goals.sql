-- ==============================
-- SMART GOALS DATABASE SCHEMA
-- ==============================

CREATE DATABASE IF NOT EXISTS smart_goals CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE smart_goals;

-- ==============================
-- USERS TABLE
-- ==============================
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  avatar VARCHAR(255),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==============================
-- GROUPS TABLE
-- ==============================
CREATE TABLE IF NOT EXISTS groups_tbl (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  description TEXT,
  code VARCHAR(20) UNIQUE,
  privacy ENUM('public','private') DEFAULT 'private',
  created_by INT DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==============================
-- GROUP MEMBERS TABLE
-- ==============================
CREATE TABLE IF NOT EXISTS group_members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  group_id INT NOT NULL,
  user_id INT NOT NULL,
  role ENUM('member','admin','owner') DEFAULT 'member',
  joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (group_id) REFERENCES groups_tbl(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY (group_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==============================
-- GROUP INVITES TABLE
-- ==============================
CREATE TABLE IF NOT EXISTS group_invites (
  id INT AUTO_INCREMENT PRIMARY KEY,
  group_id INT NOT NULL,
  email VARCHAR(150) NOT NULL,
  role ENUM('member','admin') DEFAULT 'member',
  invited_by INT DEFAULT NULL,
  token VARCHAR(64) NOT NULL,
  accepted_by INT DEFAULT NULL,
  accepted_at DATETIME DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (group_id) REFERENCES groups_tbl(id) ON DELETE CASCADE,
  FOREIGN KEY (invited_by) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (accepted_by) REFERENCES users(id) ON DELETE SET NULL,
  UNIQUE KEY (group_id, email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==============================
-- GOALS TABLE
-- ==============================
CREATE TABLE IF NOT EXISTS goals (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  description TEXT,
  created_by INT DEFAULT NULL,
  group_id INT DEFAULT NULL,
  cadence ENUM('daily','weekly','monthly') DEFAULT 'daily',
  target_value INT DEFAULT NULL,
  unit VARCHAR(50) DEFAULT NULL,
  start_date DATE DEFAULT NULL,
  end_date DATE DEFAULT NULL,
  active TINYINT(1) DEFAULT 1,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (group_id) REFERENCES groups_tbl(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==============================
-- CHECKINS TABLE
-- ==============================
CREATE TABLE IF NOT EXISTS checkins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  goal_id INT NOT NULL,
  user_id INT NOT NULL,
  value DECIMAL(10,2) DEFAULT NULL,
  note TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  date DATE NOT NULL,
  FOREIGN KEY (goal_id) REFERENCES goals(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  UNIQUE KEY (goal_id, user_id, date),
  INDEX idx_goal_id (goal_id),
  INDEX idx_user_id (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==============================
-- BADGES TABLE
-- ==============================
CREATE TABLE IF NOT EXISTS badges (
  id INT AUTO_INCREMENT PRIMARY KEY,
  slug VARCHAR(100) NOT NULL UNIQUE,
  title VARCHAR(150) NOT NULL,
  description TEXT,
  icon VARCHAR(255),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==============================
-- USER BADGES TABLE
-- ==============================
CREATE TABLE IF NOT EXISTS user_badges (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  badge_id INT NOT NULL,
  goal_id INT DEFAULT NULL,
  awarded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (badge_id) REFERENCES badges(id) ON DELETE CASCADE,
  FOREIGN KEY (goal_id) REFERENCES goals(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==============================
-- ACTIVITY LOG TABLE
-- ==============================
CREATE TABLE IF NOT EXISTS activity_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT DEFAULT NULL,
  group_id INT DEFAULT NULL,
  goal_id INT DEFAULT NULL,
  action VARCHAR(255),
  meta JSON DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
  FOREIGN KEY (group_id) REFERENCES groups_tbl(id) ON DELETE SET NULL,
  FOREIGN KEY (goal_id) REFERENCES goals(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==============================
-- GOAL USER META TABLE
-- ==============================
CREATE TABLE IF NOT EXISTS goal_user_meta (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  goal_id INT NOT NULL,
  current_streak INT DEFAULT 0,
  longest_streak INT DEFAULT 0,
  last_checkin DATE DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (goal_id) REFERENCES goals(id) ON DELETE CASCADE,
  UNIQUE KEY (user_id, goal_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==============================
-- GOAL TEMPLATES
-- Predefined templates users can clone
-- ==============================
CREATE TABLE IF NOT EXISTS goal_templates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(200) NOT NULL,
  description TEXT,
  cadence ENUM('daily','weekly','monthly') DEFAULT 'daily',
  unit VARCHAR(50) DEFAULT NULL,
  start_offset_days INT DEFAULT 0, -- days from today when goal should start when cloned
  duration_days INT DEFAULT NULL,  -- optional duration in days to compute end_date when cloning
  created_by INT DEFAULT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- sample templates
INSERT INTO goal_templates (title, description, cadence, unit, start_offset_days, duration_days) VALUES
('30-day fitness challenge', 'Daily workout for 30 days. Try to complete at least 20 minutes each day.', 'daily', 'minutes', 0, 30),
('Read 10 pages a day', 'Read at least 10 pages every day to build a reading habit.', 'daily', 'pages', 0, NULL)
ON DUPLICATE KEY UPDATE title = VALUES(title);

-- ==============================
-- MESSAGES / CHAT THREADS
-- Messages can be linked to a group or a goal and optionally threaded (parent_id)
-- ==============================
CREATE TABLE IF NOT EXISTS messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  group_id INT DEFAULT NULL,
  goal_id INT DEFAULT NULL,
  user_id INT NOT NULL,
  parent_id INT DEFAULT NULL,
  body TEXT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (group_id) REFERENCES groups_tbl(id) ON DELETE CASCADE,
  FOREIGN KEY (goal_id) REFERENCES goals(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (parent_id) REFERENCES messages(id) ON DELETE CASCADE,
  INDEX idx_group (group_id),
  INDEX idx_goal (goal_id),
  INDEX idx_user (user_id),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==============================
-- USER XP / LEVELS
-- Tracks accumulated XP and computed level for each user
-- ==============================
CREATE TABLE IF NOT EXISTS user_xp (
  user_id INT PRIMARY KEY,
  xp_total INT DEFAULT 0,
  level INT DEFAULT 0,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
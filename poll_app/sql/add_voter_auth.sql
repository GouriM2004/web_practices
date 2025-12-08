-- Migration to add voter authentication and anonymity controls
USE poll_app;

-- Voters table
CREATE TABLE IF NOT EXISTS voters (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) UNIQUE NOT NULL,
  password VARCHAR(255) NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Extend poll_votes to capture voter identity and visibility preferences
ALTER TABLE poll_votes
  ADD COLUMN voter_id INT NULL AFTER voter_ip,
  ADD COLUMN voter_name VARCHAR(100) AFTER voter_id,
  ADD COLUMN is_public TINYINT(1) DEFAULT 0 AFTER voter_name;

-- Add duplicate-prevention indexes
ALTER TABLE poll_votes
  ADD CONSTRAINT uniq_poll_voter UNIQUE (poll_id, voter_id),
  ADD CONSTRAINT uniq_poll_ip UNIQUE (poll_id, voter_ip);

-- Add FK
ALTER TABLE poll_votes
  ADD CONSTRAINT fk_poll_votes_voter FOREIGN KEY (voter_id) REFERENCES voters(id) ON DELETE SET NULL;

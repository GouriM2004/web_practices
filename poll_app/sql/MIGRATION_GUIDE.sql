-- Quick Setup Script for Multiple Choice + Voter Auth
-- Run this in phpMyAdmin after selecting the poll_app database

-- 1) Multiple choice column
SELECT COUNT(*) as column_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'poll_app' 
AND TABLE_NAME = 'polls' 
AND COLUMN_NAME = 'allow_multiple';

-- If the above returns 0, run this:
ALTER TABLE polls 
ADD COLUMN allow_multiple TINYINT(1) DEFAULT 0 AFTER is_active;

-- 2) Voter authentication tables/columns
CREATE TABLE IF NOT EXISTS voters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add identity columns and uniqueness constraints if missing
ALTER TABLE poll_votes
    ADD COLUMN IF NOT EXISTS voter_id INT NULL AFTER voter_ip,
    ADD COLUMN IF NOT EXISTS voter_name VARCHAR(100) AFTER voter_id,
    ADD COLUMN IF NOT EXISTS is_public TINYINT(1) DEFAULT 0 AFTER voter_name;

ALTER TABLE poll_votes
    ADD CONSTRAINT IF NOT EXISTS uniq_poll_voter UNIQUE (poll_id, voter_id),
    ADD CONSTRAINT IF NOT EXISTS uniq_poll_ip UNIQUE (poll_id, voter_ip);

ALTER TABLE poll_votes
    ADD CONSTRAINT IF NOT EXISTS fk_poll_votes_voter FOREIGN KEY (voter_id) REFERENCES voters(id) ON DELETE SET NULL;

-- Verify
DESCRIBE polls;
DESCRIBE poll_votes;
DESCRIBE voters;

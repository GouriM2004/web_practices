-- Poll Battles Feature
-- Allows two polls to compete with a winner determined by votes

ALTER TABLE polls ADD COLUMN IF NOT EXISTS battle_wins INT DEFAULT 0;
ALTER TABLE polls ADD COLUMN IF NOT EXISTS battle_losses INT DEFAULT 0;
ALTER TABLE polls ADD COLUMN IF NOT EXISTS battle_win_rate DECIMAL(5,2) DEFAULT 0.00;

-- Poll Battles Log
CREATE TABLE IF NOT EXISTS poll_battles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  poll_a_id INT NOT NULL,
  poll_b_id INT NOT NULL,
  winner_id INT NOT NULL,
  loser_id INT NOT NULL,
  votes_a INT NOT NULL DEFAULT 0,
  votes_b INT NOT NULL DEFAULT 0,
  margin_of_victory INT NOT NULL DEFAULT 0,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (poll_a_id) REFERENCES polls(id) ON DELETE CASCADE,
  FOREIGN KEY (poll_b_id) REFERENCES polls(id) ON DELETE CASCADE,
  FOREIGN KEY (winner_id) REFERENCES polls(id) ON DELETE CASCADE,
  FOREIGN KEY (loser_id) REFERENCES polls(id) ON DELETE CASCADE,
  INDEX idx_winner (winner_id),
  INDEX idx_loser (loser_id),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Battle votes tracking (per voter per battle)
CREATE TABLE IF NOT EXISTS battle_votes (
  id INT AUTO_INCREMENT PRIMARY KEY,
  battle_id INT NOT NULL,
  voter_ip VARCHAR(50),
  voter_id INT NULL,
  voted_for_id INT NOT NULL,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (battle_id) REFERENCES poll_battles(id) ON DELETE CASCADE,
  FOREIGN KEY (voted_for_id) REFERENCES polls(id) ON DELETE CASCADE,
  UNIQUE KEY unique_voter_battle (battle_id, voter_ip, voter_id),
  INDEX idx_battle (battle_id),
  INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

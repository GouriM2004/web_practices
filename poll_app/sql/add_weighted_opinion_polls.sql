-- Weighted opinion polls: capture voter type per vote
USE poll_app;

-- Add voter_type column to segment votes by audience
ALTER TABLE poll_votes 
  ADD COLUMN IF NOT EXISTS voter_type ENUM('expert','student','public') DEFAULT 'public' AFTER confidence_level;

-- Backfill existing rows
UPDATE poll_votes 
SET voter_type = 'public' 
WHERE voter_type IS NULL;

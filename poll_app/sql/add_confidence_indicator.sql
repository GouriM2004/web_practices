-- Add confidence indicator feature to poll_votes table
-- This allows voters to indicate how confident they are in their vote

USE poll_app;

-- Add confidence_level column to poll_votes table
ALTER TABLE poll_votes 
ADD COLUMN confidence_level ENUM('very_sure', 'somewhat_sure', 'just_guessing') 
DEFAULT 'somewhat_sure' 
AFTER is_public;

-- Update existing records to have a default confidence level
UPDATE poll_votes 
SET confidence_level = 'somewhat_sure' 
WHERE confidence_level IS NULL;

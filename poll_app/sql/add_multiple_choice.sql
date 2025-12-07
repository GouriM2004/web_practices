-- Migration to add multiple choice support
USE poll_app;

-- Add allow_multiple column to polls table
ALTER TABLE polls 
ADD COLUMN allow_multiple TINYINT(1) DEFAULT 0 AFTER is_active;

-- Quick Setup Script for Multiple Choice Feature
-- Run this in phpMyAdmin after selecting the poll_app database

-- Check if column already exists
SELECT COUNT(*) as column_exists 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'poll_app' 
  AND TABLE_NAME = 'polls' 
  AND COLUMN_NAME = 'allow_multiple';

-- If the above returns 0, run this:
ALTER TABLE polls 
ADD COLUMN allow_multiple TINYINT(1) DEFAULT 0 AFTER is_active;

-- Verify the change
DESCRIBE polls;

-- That's it! The feature is now ready to use.

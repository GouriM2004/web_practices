-- Migration: Add emoji-only polls support
-- Description: Adds a flag to mark polls where options must be emojis only
-- Date: 2026-01-03

USE poll_app;

-- Add the emoji-only flag to polls (safe to run multiple times)
ALTER TABLE polls
  ADD COLUMN IF NOT EXISTS is_emoji_only TINYINT(1) DEFAULT 0 AFTER allow_multiple;

-- Verify
DESCRIBE polls;

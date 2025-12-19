-- Add category and location_tag to polls for recommendations
USE poll_app;

ALTER TABLE polls
  ADD COLUMN category VARCHAR(100) DEFAULT 'General' AFTER allow_multiple,
  ADD COLUMN location_tag VARCHAR(100) NULL AFTER category;

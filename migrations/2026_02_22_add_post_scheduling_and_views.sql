-- Migration: Add post scheduling and view tracking to posts table
-- Date: 2026-02-22
-- Description: Add publish scheduling and view count tracking to enhance content management

ALTER TABLE posts
ADD COLUMN publish_at TIMESTAMP NULL COMMENT 'Scheduled publish time (NULL means publish immediately)',
ADD COLUMN view_count INT(11) DEFAULT 0 COMMENT 'Number of times post has been viewed';

-- Update existing published posts to have publish_at set to created_at
UPDATE posts SET publish_at = created_at WHERE status = 'Published' AND publish_at IS NULL;

-- Add index for publish scheduling
CREATE INDEX idx_posts_publish_at ON posts(publish_at);
CREATE INDEX idx_posts_view_count ON posts(view_count);

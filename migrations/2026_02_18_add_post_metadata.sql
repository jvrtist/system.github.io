-- Migration: Add SEO and metadata fields to posts table
-- Date: 2026-02-18
-- Description: Enhance posts table with SEO metadata, categorization, and content management features

ALTER TABLE posts
ADD COLUMN keywords TEXT NULL COMMENT 'SEO keywords for search optimization',
ADD COLUMN meta_description TEXT NULL COMMENT 'Meta description for SEO and previews',
ADD COLUMN tags TEXT NULL COMMENT 'Comma-separated tags for categorization',
ADD COLUMN category VARCHAR(100) NULL COMMENT 'Primary category for organization',
ADD COLUMN featured_image VARCHAR(255) NULL COMMENT 'Path to featured image file',
ADD COLUMN excerpt TEXT NULL COMMENT 'Short summary/excerpt for previews',
ADD COLUMN seo_title VARCHAR(255) NULL COMMENT 'Custom SEO title (defaults to post title)',
ADD COLUMN canonical_url VARCHAR(500) NULL COMMENT 'Canonical URL for SEO';

-- Add indexes for better performance
CREATE INDEX idx_posts_category ON posts(category);
CREATE INDEX idx_posts_tags ON posts(tags(255));
CREATE INDEX idx_posts_keywords ON posts(keywords(255));

-- Add some sample categories (optional - can be managed via admin interface later)
INSERT IGNORE INTO post_categories (name, slug, description) VALUES
('Investigations', 'investigations', 'Private investigation techniques and case studies'),
('Security', 'security', 'Corporate security and risk management'),
('Legal Updates', 'legal-updates', 'Legal developments and compliance'),
('Technology', 'technology', 'Digital forensics and surveillance technology'),
('Industry News', 'industry-news', 'Latest news and trends in private investigations');

-- Note: The post_categories table creation would be in a separate migration if needed
-- For now, we'll just store category as a simple string field

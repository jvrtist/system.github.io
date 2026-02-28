-- ISS Investigations - Create Post Comments Table
-- Migration: Create post_comments table for blog commenting functionality
-- Date: February 8, 2026

CREATE TABLE IF NOT EXISTS `post_comments` (
    `comment_id` INT(11) NOT NULL AUTO_INCREMENT,
    `post_id` INT(11) NOT NULL,
    `client_id` INT(11) NOT NULL,
    `comment` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`comment_id`),
    KEY `post_id` (`post_id`),
    KEY `client_id` (`client_id`),
    CONSTRAINT `fk_post_comments_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`) ON DELETE CASCADE,
    CONSTRAINT `fk_post_comments_client` FOREIGN KEY (`client_id`) REFERENCES `clients` (`client_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add comment to table
COMMENT ON TABLE `post_comments` IS 'Client comments on blog posts';

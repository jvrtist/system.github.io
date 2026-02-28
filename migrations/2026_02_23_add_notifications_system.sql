-- Migration: Create notifications table
-- Created: 2026-02-23
-- Description: Adds notification system for real-time alerts

CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'error', 'success') DEFAULT 'info',
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    related_entity_type ENUM('case', 'client', 'task', 'invoice', 'message', 'system') DEFAULT NULL,
    related_entity_id INT DEFAULT NULL,
    action_url VARCHAR(500) DEFAULT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,

    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_created (created_at),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add notification preferences to users table if not exists
ALTER TABLE users
ADD COLUMN IF NOT EXISTS notification_preferences JSON DEFAULT ('{"overdue_tasks": true, "new_messages": true, "case_updates": true, "system_alerts": true}') AFTER totp_secret;

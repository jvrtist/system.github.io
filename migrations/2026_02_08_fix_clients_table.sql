-- Migration: Fix clients table schema for Client Portal
-- Date: 2026-02-08
-- Purpose: Add missing columns for client authentication

-- Add password_hash column if it doesn't exist
ALTER TABLE clients ADD COLUMN password_hash VARCHAR(255) NULL COMMENT 'Hashed password for client portal access';

-- Add client_account_status column if it doesn't exist
ALTER TABLE clients ADD COLUMN client_account_status ENUM('Active', 'Disabled', 'Pending Activation', 'Password Reset') DEFAULT 'Active' COMMENT 'Status of the client account';

-- Add password reset token columns
ALTER TABLE clients ADD COLUMN password_reset_token VARCHAR(255) NULL COMMENT 'Token for password reset';
ALTER TABLE clients ADD COLUMN password_reset_token_expires_at DATETIME NULL COMMENT 'Expiration time for password reset token';

-- Add last_login_at column if it doesn't exist (used in login.php)
ALTER TABLE clients ADD COLUMN last_login_at DATETIME NULL COMMENT 'Timestamp of last successful login';

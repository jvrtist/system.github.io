-- Migration: Add Two-Factor Authentication (TOTP) support to users table
-- Date: 2026-02-08
-- Purpose: Enable TOTP-based 2FA for admin accounts

-- START TRANSACTION; -- Removed transaction to allow partial success/failure handling in runner

-- Add 2FA columns to users table
ALTER TABLE users ADD COLUMN totp_secret VARCHAR(32) NULL COMMENT 'Base32-encoded TOTP secret key';
ALTER TABLE users ADD COLUMN totp_enabled BOOLEAN DEFAULT 0 COMMENT 'Whether TOTP 2FA is enabled for this user';
ALTER TABLE users ADD COLUMN recovery_codes JSON NULL COMMENT 'JSON array of backup recovery codes (hashed)';
ALTER TABLE users ADD COLUMN recovery_codes_generated_at TIMESTAMP NULL COMMENT 'When recovery codes were generated';
ALTER TABLE users ADD COLUMN two_fa_enabled_at TIMESTAMP NULL COMMENT 'When 2FA was enabled';

-- Create audit log entry for 2FA changes
ALTER TABLE audit_log ADD COLUMN two_fa_action VARCHAR(50) NULL COMMENT 'Type of 2FA action (enabled, disabled, recovery_used)';

-- COMMIT;

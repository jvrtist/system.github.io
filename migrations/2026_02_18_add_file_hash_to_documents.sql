-- Migration to add file_hash column to documents table for chain of custody
-- Run this in your MySQL database

START TRANSACTION;

-- Add file_hash column to documents table
ALTER TABLE documents
ADD COLUMN file_hash VARCHAR(64) NULL COMMENT 'SHA-256 hash for document integrity and chain of custody' AFTER file_path;

-- Optional: Add index for performance if needed
-- ALTER TABLE documents ADD INDEX idx_file_hash (file_hash);

COMMIT;

-- Add visibility column to documents table for client access control
-- Run this in your MySQL database

START TRANSACTION;

-- Add visibility column to documents table
ALTER TABLE documents
ADD COLUMN visibility VARCHAR(20) NOT NULL DEFAULT 'Client Visible'
COMMENT 'Controls who can see this document: Client Visible, Staff Only, etc.'
AFTER description;

COMMIT;

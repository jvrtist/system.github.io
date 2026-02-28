-- Migration: Add Retainer Amount to Cases and File Hash to Documents
-- Date: 2026-02-08
-- Purpose: Enable Retainer Burn-Down Tracker and Digital Chain of Custody

-- Add retainer_amount to cases table
ALTER TABLE cases ADD COLUMN retainer_amount DECIMAL(10, 2) DEFAULT 0.00 COMMENT 'Initial retainer amount for the case';

-- Add file_hash to documents table
ALTER TABLE documents ADD COLUMN file_hash VARCHAR(64) NULL COMMENT 'SHA-256 hash of the file for chain of custody';

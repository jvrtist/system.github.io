-- Migration: Add internal_notes column to invoices table
-- Date: 2026-02-08
-- Purpose: Allow staff to add internal notes to invoices

-- Add internal_notes column if it doesn't exist
ALTER TABLE invoices ADD COLUMN internal_notes TEXT NULL COMMENT 'Private notes for staff use only';

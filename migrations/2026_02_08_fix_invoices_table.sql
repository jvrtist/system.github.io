-- Migration: Fix invoices table schema
-- Date: 2026-02-08
-- Purpose: Add missing payment_date column to invoices table

-- Add payment_date column if it doesn't exist (to track last payment date)
ALTER TABLE invoices ADD COLUMN payment_date DATE NULL COMMENT 'Date of the most recent payment or full payment';

-- Ensure amount_paid column exists
ALTER TABLE invoices ADD COLUMN amount_paid DECIMAL(10, 2) DEFAULT 0.00 COMMENT 'Total amount paid so far';

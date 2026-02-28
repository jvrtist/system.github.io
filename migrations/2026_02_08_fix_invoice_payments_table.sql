-- Migration: Fix invoice_payments table schema
-- Date: 2026-02-08
-- Purpose: Add missing columns to invoice_payments

-- Add payment_date column if it doesn't exist
ALTER TABLE invoice_payments ADD COLUMN payment_date DATE NOT NULL DEFAULT (CURRENT_DATE) COMMENT 'Date the payment was received';

-- Add received_by column if it doesn't exist (it's used in record_payment.php)
ALTER TABLE invoice_payments ADD COLUMN received_by INT NULL COMMENT 'User ID of the staff member who recorded the payment';

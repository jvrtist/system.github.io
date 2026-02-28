-- Migration: Fix payment_method column length
-- Date: 2026-02-08
-- Purpose: Ensure payment_method column is large enough for 'Retainer' and other values

-- Modify payment_method column to be VARCHAR(50)
ALTER TABLE invoice_payments MODIFY COLUMN payment_method VARCHAR(50) NOT NULL;

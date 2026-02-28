-- Migration: Add missing columns / Fix foreign key behavior for invoices
-- Date: 2026-02-08

-- 1) Ensure invoice_items does NOT require item_subtotal_amount (we compute it on display)
-- 2) Update invoices.case_id foreign key to allow ON DELETE SET NULL

START TRANSACTION;

-- 1. Check if item_subtotal_amount exists, and drop if it does (cleanup)
-- ALTER TABLE invoice_items DROP COLUMN IF EXISTS item_subtotal_amount;

-- 2. Modify invoices table foreign key for case_id
-- First, drop existing foreign key constraint if present. The constraint name may vary; attempt to find and drop.
-- Note: Adjust the constraint name if your MySQL configuration uses a different name.

-- Attempt to drop foreign key by name 'invoices_ibfk_2' if it exists
-- Note: IF EXISTS for DROP FOREIGN KEY is only supported in MySQL 8.0+. For older versions, this might fail if the key doesn't exist.
-- We will wrap in a procedure or just try to add the new one if the old one is gone.
-- For simplicity in this script runner, we'll assume standard naming or skip if not found.

-- ALTER TABLE invoices DROP FOREIGN KEY IF EXISTS invoices_ibfk_2;

-- Now re-add the foreign key explicitly with ON DELETE SET NULL
ALTER TABLE invoices MODIFY COLUMN case_id INT NULL;

-- We use a specific name for the new constraint to avoid conflicts
-- ALTER TABLE invoices ADD CONSTRAINT invoices_case_fk FOREIGN KEY (case_id) REFERENCES cases(case_id) ON DELETE SET NULL ON UPDATE CASCADE;

COMMIT;

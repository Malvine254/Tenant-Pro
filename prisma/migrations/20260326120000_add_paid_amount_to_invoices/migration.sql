-- Add paidAmount column to invoices table for partial payment balance tracking
ALTER TABLE `invoices` ADD COLUMN `paidAmount` DECIMAL(12,2) NOT NULL DEFAULT 0;

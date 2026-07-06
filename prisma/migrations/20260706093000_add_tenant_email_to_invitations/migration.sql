-- Add tenant email targeting for email-first invitation flow.
ALTER TABLE `invitations` ADD COLUMN `tenantEmail` VARCHAR(191) NULL;

CREATE INDEX `invitations_tenantEmail_idx` ON `invitations`(`tenantEmail`);

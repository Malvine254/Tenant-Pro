-- Add richer tenant profile fields (safe to re-run)
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `profileImageUrl` VARCHAR(191) NULL,
  ADD COLUMN IF NOT EXISTS `emergencyContactName` VARCHAR(191) NULL,
  ADD COLUMN IF NOT EXISTS `emergencyContactPhone` VARCHAR(191) NULL,
  ADD COLUMN IF NOT EXISTS `bio` TEXT NULL;

-- Extend invoice billing types
ALTER TABLE `invoices`
  MODIFY `billingType` ENUM('RENT', 'WATER', 'GARBAGE', 'OTHER') NOT NULL;

-- Notifications table
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` VARCHAR(191) NOT NULL,
  `userId` VARCHAR(191) NOT NULL,
  `type` ENUM('GENERAL', 'INVOICE', 'PAYMENT', 'MAINTENANCE', 'MESSAGE', 'PROFILE') NOT NULL DEFAULT 'GENERAL',
  `title` VARCHAR(191) NOT NULL,
  `message` TEXT NOT NULL,
  `isRead` BOOLEAN NOT NULL DEFAULT false,
  `readAt` DATETIME(3) NULL,
  `metadata` JSON NULL,
  `createdAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  `updatedAt` DATETIME(3) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `notifications_userId_isRead_createdAt_idx`(`userId`, `isRead`, `createdAt`),
  CONSTRAINT `notifications_userId_fkey`
    FOREIGN KEY (`userId`) REFERENCES `users`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Support conversations table
CREATE TABLE IF NOT EXISTS `support_conversations` (
  `id` VARCHAR(191) NOT NULL,
  `tenantUserId` VARCHAR(191) NOT NULL,
  `subject` VARCHAR(191) NULL,
  `topic` VARCHAR(191) NULL DEFAULT 'General',
  `isOpen` BOOLEAN NOT NULL DEFAULT true,
  `createdAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  `updatedAt` DATETIME(3) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `support_conversations_tenantUserId_updatedAt_idx`(`tenantUserId`, `updatedAt`),
  CONSTRAINT `support_conversations_tenantUserId_fkey`
    FOREIGN KEY (`tenantUserId`) REFERENCES `users`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Support messages table
CREATE TABLE IF NOT EXISTS `support_messages` (
  `id` VARCHAR(191) NOT NULL,
  `conversationId` VARCHAR(191) NOT NULL,
  `senderId` VARCHAR(191) NOT NULL,
  `topic` VARCHAR(191) NULL DEFAULT 'General',
  `body` TEXT NOT NULL,
  `attachmentName` VARCHAR(191) NULL,
  `attachmentUri` VARCHAR(191) NULL,
  `isFromTenant` BOOLEAN NOT NULL DEFAULT true,
  `status` VARCHAR(191) NOT NULL DEFAULT 'Sent',
  `createdAt` DATETIME(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3),
  `updatedAt` DATETIME(3) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `support_messages_conversationId_createdAt_idx`(`conversationId`, `createdAt`),
  INDEX `support_messages_senderId_idx`(`senderId`),
  CONSTRAINT `support_messages_conversationId_fkey`
    FOREIGN KEY (`conversationId`) REFERENCES `support_conversations`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `support_messages_senderId_fkey`
    FOREIGN KEY (`senderId`) REFERENCES `users`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

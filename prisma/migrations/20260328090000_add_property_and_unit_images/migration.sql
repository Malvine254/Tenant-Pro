ALTER TABLE `properties`
  ADD COLUMN `coverImageUrl` VARCHAR(191) NULL;

ALTER TABLE `units`
  ADD COLUMN `imageUrls` JSON NULL;

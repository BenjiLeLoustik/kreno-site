<?php

declare(strict_types=1);

namespace Migrations;

use NeoPHP\Package\Orm\Contract\AbstractMigration;

class Migration_01a1436d282802c9 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('CREATE TABLE `app_contact` (`id` INT NOT NULL AUTO_INCREMENT, `created_at` DATETIME NOT NULL, `updated_at` DATETIME NOT NULL, `topic` VARCHAR(20) NOT NULL, `email` VARCHAR(180) NOT NULL, `reference` VARCHAR(12) DEFAULT NULL, `status` VARCHAR(255) NOT NULL, `token` VARCHAR(64) NOT NULL, `last_message_at` DATETIME NOT NULL, `closed_at` DATETIME DEFAULT NULL, `user_id` INT DEFAULT NULL, `booking_id` INT DEFAULT NULL, PRIMARY KEY (`id`)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE UNIQUE INDEX `UNIQ_F9A345DE0F75ED6F` ON `app_contact` (`token`)');
        $this->addSql('CREATE INDEX `IDX_48F9EF75A219086B` ON `app_contact` (`status`, `last_message_at`)');
        $this->addSql('CREATE INDEX `IDX_1AF2DD7B537BA494` ON `app_contact` (`email`)');
        $this->addSql('CREATE INDEX `IDX_133300C7CE35679D` ON `app_contact` (`user_id`)');
        $this->addSql('CREATE INDEX `IDX_50E6F3F65B1BC069` ON `app_contact` (`booking_id`)');
        $this->addSql('CREATE TABLE `app_contact_message` (`id` INT NOT NULL AUTO_INCREMENT, `created_at` DATETIME NOT NULL, `updated_at` DATETIME NOT NULL, `author_type` VARCHAR(255) NOT NULL, `body` LONGTEXT NOT NULL, `contact_id` INT NOT NULL, `author_id` INT DEFAULT NULL, PRIMARY KEY (`id`)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE INDEX `IDX_1F55AE460404BA13` ON `app_contact_message` (`contact_id`)');
        $this->addSql('CREATE INDEX `IDX_2FA9D5C5FB3BFDB1` ON `app_contact_message` (`author_id`)');
        $this->addSql('ALTER TABLE `app_contact` ADD CONSTRAINT `FK_133300C7CE35679D` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `app_contact` ADD CONSTRAINT `FK_50E6F3F65B1BC069` FOREIGN KEY (`booking_id`) REFERENCES `booking` (`id`) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `app_contact_message` ADD CONSTRAINT `FK_1F55AE460404BA13` FOREIGN KEY (`contact_id`) REFERENCES `app_contact` (`id`) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `app_contact_message` ADD CONSTRAINT `FK_2FA9D5C5FB3BFDB1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE SET NULL');
    }

    public function down(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('ALTER TABLE `app_contact` DROP FOREIGN KEY `FK_133300C7CE35679D`');
        $this->addSql('ALTER TABLE `app_contact` DROP FOREIGN KEY `FK_50E6F3F65B1BC069`');
        $this->addSql('ALTER TABLE `app_contact_message` DROP FOREIGN KEY `FK_1F55AE460404BA13`');
        $this->addSql('ALTER TABLE `app_contact_message` DROP FOREIGN KEY `FK_2FA9D5C5FB3BFDB1`');
        $this->addSql('DROP TABLE `app_contact`');
        $this->addSql('DROP TABLE `app_contact_message`');
    }
}

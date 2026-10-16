<?php

declare(strict_types=1);

namespace Migrations;

use NeoPHP\Package\Orm\Contract\AbstractMigration;

class Migration_01a1434249cf881a extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('CREATE TABLE `app_log` (`id` INT NOT NULL AUTO_INCREMENT, `created_at` DATETIME NOT NULL, `updated_at` DATETIME NOT NULL, `fingerprint` VARCHAR(40) NOT NULL, `level` VARCHAR(255) NOT NULL, `status` VARCHAR(255) NOT NULL, `exception_class` VARCHAR(255) NOT NULL, `message` LONGTEXT NOT NULL, `file` VARCHAR(255) DEFAULT NULL, `line` INT DEFAULT NULL, `trace` LONGTEXT DEFAULT NULL, `status_code` INT NOT NULL, `method` VARCHAR(10) DEFAULT NULL, `url` VARCHAR(500) DEFAULT NULL, `occurrences` INT NOT NULL, `first_seen_at` DATETIME NOT NULL, `last_seen_at` DATETIME NOT NULL, `handled_at` DATETIME DEFAULT NULL, `note` LONGTEXT DEFAULT NULL, `user_id` INT DEFAULT NULL, `handled_by_id` INT DEFAULT NULL, PRIMARY KEY (`id`)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE UNIQUE INDEX `UNIQ_79BFE25ACDB16C31` ON `app_log` (`fingerprint`)');
        $this->addSql('CREATE INDEX `IDX_FC8DF54F3CD29A3F` ON `app_log` (`status`, `last_seen_at`)');
        $this->addSql('CREATE INDEX `IDX_8FBA4D19F57C55A3` ON `app_log` (`user_id`)');
        $this->addSql('CREATE INDEX `IDX_5719301B170CC0CF` ON `app_log` (`handled_by_id`)');
        $this->addSql('ALTER TABLE `app_log` ADD CONSTRAINT `FK_8FBA4D19F57C55A3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `app_log` ADD CONSTRAINT `FK_5719301B170CC0CF` FOREIGN KEY (`handled_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL');
    }

    public function down(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('ALTER TABLE `app_log` DROP FOREIGN KEY `FK_8FBA4D19F57C55A3`');
        $this->addSql('ALTER TABLE `app_log` DROP FOREIGN KEY `FK_5719301B170CC0CF`');
        $this->addSql('DROP TABLE `app_log`');
    }
}

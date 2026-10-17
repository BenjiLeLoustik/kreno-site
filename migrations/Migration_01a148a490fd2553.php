<?php

declare(strict_types=1);

namespace Migrations;

use NeoPHP\Package\Orm\Contract\AbstractMigration;

class Migration_01a148a490fd2553 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('CREATE TABLE `establishment_review` (`id` INT NOT NULL AUTO_INCREMENT, `created_at` DATETIME NOT NULL, `updated_at` DATETIME NOT NULL, `rating` INT NOT NULL, `comment` LONGTEXT DEFAULT NULL, `status` VARCHAR(255) NOT NULL, `reply` LONGTEXT DEFAULT NULL, `replied_at` DATETIME DEFAULT NULL, `report_reason` VARCHAR(500) DEFAULT NULL, `reported_at` DATETIME DEFAULT NULL, `moderation_note` VARCHAR(500) DEFAULT NULL, `moderated_at` DATETIME DEFAULT NULL, `establishment_id` INT NOT NULL, `booking_id` INT NOT NULL, `customer_id` INT DEFAULT NULL, `replied_by_id` INT DEFAULT NULL, `reported_by_id` INT DEFAULT NULL, `moderated_by_id` INT DEFAULT NULL, PRIMARY KEY (`id`)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE UNIQUE INDEX `UNIQ_3B7B80FD0C23B67B` ON `establishment_review` (`booking_id`)');
        $this->addSql('CREATE INDEX `IDX_BF80BB13AED91E53` ON `establishment_review` (`establishment_id`, `status`, `created_at`)');
        $this->addSql('CREATE INDEX `IDX_9CD4DEAD975D7992` ON `establishment_review` (`customer_id`, `created_at`)');
        $this->addSql('CREATE INDEX `IDX_6C1BB8C3B8EF56C3` ON `establishment_review` (`status`, `updated_at`)');
        $this->addSql('CREATE INDEX `IDX_99AD1AAAC2D2D9B6` ON `establishment_review` (`replied_by_id`)');
        $this->addSql('CREATE INDEX `IDX_C783F4A493AF2B6D` ON `establishment_review` (`reported_by_id`)');
        $this->addSql('CREATE INDEX `IDX_688408176E07CBF1` ON `establishment_review` (`moderated_by_id`)');
        $this->addSql('ALTER TABLE `establishment_review` ADD CONSTRAINT `FK_02DFE2702967A80D` FOREIGN KEY (`establishment_id`) REFERENCES `company_establishment` (`id`) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `establishment_review` ADD CONSTRAINT `FK_3B7B80FD0C23B67B` FOREIGN KEY (`booking_id`) REFERENCES `booking` (`id`) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `establishment_review` ADD CONSTRAINT `FK_AC53A23DB89981F1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `establishment_review` ADD CONSTRAINT `FK_99AD1AAAC2D2D9B6` FOREIGN KEY (`replied_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `establishment_review` ADD CONSTRAINT `FK_C783F4A493AF2B6D` FOREIGN KEY (`reported_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `establishment_review` ADD CONSTRAINT `FK_688408176E07CBF1` FOREIGN KEY (`moderated_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL');
    }

    public function down(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('ALTER TABLE `establishment_review` DROP FOREIGN KEY `FK_02DFE2702967A80D`');
        $this->addSql('ALTER TABLE `establishment_review` DROP FOREIGN KEY `FK_3B7B80FD0C23B67B`');
        $this->addSql('ALTER TABLE `establishment_review` DROP FOREIGN KEY `FK_AC53A23DB89981F1`');
        $this->addSql('ALTER TABLE `establishment_review` DROP FOREIGN KEY `FK_99AD1AAAC2D2D9B6`');
        $this->addSql('ALTER TABLE `establishment_review` DROP FOREIGN KEY `FK_C783F4A493AF2B6D`');
        $this->addSql('ALTER TABLE `establishment_review` DROP FOREIGN KEY `FK_688408176E07CBF1`');
        $this->addSql('DROP TABLE `establishment_review`');
    }
}

<?php

declare(strict_types=1);

namespace Migrations;

use NeoPHP\Package\Orm\Contract\AbstractMigration;

class Migration_01a14887229348fd extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('CREATE TABLE `faq_category` (`id` INT NOT NULL AUTO_INCREMENT, `created_at` DATETIME NOT NULL, `updated_at` DATETIME NOT NULL, `name` VARCHAR(80) NOT NULL, `audience` VARCHAR(255) NOT NULL, `visible` TINYINT(1) NOT NULL, `slug` VARCHAR(140) NOT NULL, `position` INT NOT NULL, PRIMARY KEY (`id`)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE UNIQUE INDEX `UNIQ_85DE2D30E2E2D1FF` ON `faq_category` (`slug`)');
        $this->addSql('CREATE INDEX `IDX_82EA015F1795C959` ON `faq_category` (`audience`, `visible`, `position`)');
        $this->addSql('CREATE TABLE `faq_question` (`id` INT NOT NULL AUTO_INCREMENT, `created_at` DATETIME NOT NULL, `updated_at` DATETIME NOT NULL, `question` VARCHAR(255) NOT NULL, `answer` LONGTEXT NOT NULL, `visible` TINYINT(1) NOT NULL, `position` INT NOT NULL, `category_id` INT NOT NULL, PRIMARY KEY (`id`)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE INDEX `IDX_F6B5A65C88F5E8B6` ON `faq_question` (`category_id`, `position`)');
        $this->addSql('ALTER TABLE `faq_question` ADD CONSTRAINT `FK_A944D5E204C28D09` FOREIGN KEY (`category_id`) REFERENCES `faq_category` (`id`) ON DELETE CASCADE');
    }

    public function down(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('ALTER TABLE `faq_question` DROP FOREIGN KEY `FK_A944D5E204C28D09`');
        $this->addSql('DROP TABLE `faq_category`');
        $this->addSql('DROP TABLE `faq_question`');
    }
}

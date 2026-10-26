<?php

declare(strict_types=1);

namespace Migrations;

use NeoPHP\Package\Orm\Contract\AbstractMigration;

class Migration_01a177c316471495 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('ALTER TABLE `category_service_suggestion_translation` DROP FOREIGN KEY `FK_961C0B691218F34D`');
        $this->addSql('DROP TABLE `category_service_suggestion_translation`');
        $this->addSql('CREATE TABLE `faq_category_translation` (`id` INT NOT NULL AUTO_INCREMENT, `locale` VARCHAR(5) NOT NULL, `name` VARCHAR(255) NOT NULL, `slug` VARCHAR(140) NOT NULL, `category_id` INT NOT NULL, PRIMARY KEY (`id`)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE UNIQUE INDEX `UNIQ_5523FB79B13A4531` ON `faq_category_translation` (`locale`, `slug`)');
        $this->addSql('CREATE INDEX `IDX_0F8CB8A6B05ED07C` ON `faq_category_translation` (`category_id`, `locale`)');
        $this->addSql('CREATE TABLE `faq_question_translation` (`id` INT NOT NULL AUTO_INCREMENT, `locale` VARCHAR(5) NOT NULL, `name` VARCHAR(255) NOT NULL, `answer` LONGTEXT NOT NULL, `faq_question_id` INT NOT NULL, PRIMARY KEY (`id`)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE UNIQUE INDEX `UNIQ_3824862757C67C95` ON `faq_question_translation` (`faq_question_id`, `locale`)');
        $this->addSql('ALTER TABLE `category_service_suggestion` ADD `name` VARCHAR(120) NOT NULL');
        $this->addSql('DROP INDEX `UNIQ_85DE2D30E2E2D1FF` ON `faq_category`');
        $this->addSql('ALTER TABLE `faq_category` DROP `name`');
        $this->addSql('ALTER TABLE `faq_category` DROP `slug`');
        $this->addSql('ALTER TABLE `faq_question` DROP `question`');
        $this->addSql('ALTER TABLE `faq_question` DROP `answer`');
        $this->addSql('ALTER TABLE `faq_category_translation` ADD CONSTRAINT `FK_2C450EA681C1DA76` FOREIGN KEY (`category_id`) REFERENCES `faq_category` (`id`) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `faq_question_translation` ADD CONSTRAINT `FK_2CF7A3E5BE5276A7` FOREIGN KEY (`faq_question_id`) REFERENCES `faq_question` (`id`) ON DELETE CASCADE');
    }

    public function down(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('ALTER TABLE `faq_category_translation` DROP FOREIGN KEY `FK_2C450EA681C1DA76`');
        $this->addSql('ALTER TABLE `faq_question_translation` DROP FOREIGN KEY `FK_2CF7A3E5BE5276A7`');
        $this->addSql('DROP TABLE `faq_category_translation`');
        $this->addSql('DROP TABLE `faq_question_translation`');
        $this->addSql('CREATE TABLE `category_service_suggestion_translation` (`id` INT NOT NULL AUTO_INCREMENT, `locale` VARCHAR(5) NOT NULL, `name` VARCHAR(120) NOT NULL, `suggestion_id` INT NOT NULL, PRIMARY KEY (`id`)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE INDEX `IDX_38F00F467F1A1F8D` ON `category_service_suggestion_translation` (`locale`, `name`)');
        $this->addSql('CREATE UNIQUE INDEX `UNIQ_A544382A5C1613C7` ON `category_service_suggestion_translation` (`suggestion_id`, `locale`)');
        $this->addSql('ALTER TABLE `category_service_suggestion` DROP `name`');
        $this->addSql('ALTER TABLE `faq_category` ADD `name` VARCHAR(80) NOT NULL');
        $this->addSql('ALTER TABLE `faq_category` ADD `slug` VARCHAR(140) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX `UNIQ_85DE2D30E2E2D1FF` ON `faq_category` (`slug`)');
        $this->addSql('ALTER TABLE `faq_question` ADD `question` VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE `faq_question` ADD `answer` LONGTEXT NOT NULL');
        $this->addSql('ALTER TABLE `category_service_suggestion_translation` ADD CONSTRAINT `FK_961C0B691218F34D` FOREIGN KEY (`suggestion_id`) REFERENCES `category_service_suggestion` (`id`) ON DELETE CASCADE');
    }
}

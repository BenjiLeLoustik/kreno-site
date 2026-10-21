<?php

declare(strict_types=1);

namespace Migrations;

use NeoPHP\Package\Orm\Contract\AbstractMigration;

class Migration_01a15aac56cc292a extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('CREATE TABLE `category_translation` (`id` INT NOT NULL AUTO_INCREMENT, `locale` VARCHAR(5) NOT NULL, `name` VARCHAR(255) NOT NULL, `slug` VARCHAR(140) NOT NULL, `description` LONGTEXT DEFAULT NULL, `category_id` INT NOT NULL, PRIMARY KEY (`id`)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE UNIQUE INDEX `UNIQ_13E238579A1217D4` ON `category_translation` (`locale`, `slug`)');
        $this->addSql('CREATE INDEX `IDX_1931CF5BCDB3AB44` ON `category_translation` (`category_id`, `locale`)');
        $this->addSql('DROP INDEX `UNIQ_5D60D114D0DD9940` ON `category`');
        $this->addSql('ALTER TABLE `category` DROP `name`');
        $this->addSql('ALTER TABLE `category` DROP `description`');
        $this->addSql('ALTER TABLE `category` DROP `slug`');
        $this->addSql('ALTER TABLE `category_translation` ADD CONSTRAINT `FK_2D84C78F25427562` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`)');
    }

    public function down(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('ALTER TABLE `category_translation` DROP FOREIGN KEY `FK_2D84C78F25427562`');
        $this->addSql('DROP TABLE `category_translation`');
        $this->addSql('ALTER TABLE `category` ADD `name` VARCHAR(80) NOT NULL');
        $this->addSql('ALTER TABLE `category` ADD `description` LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE `category` ADD `slug` VARCHAR(140) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX `UNIQ_5D60D114D0DD9940` ON `category` (`slug`)');
    }
}

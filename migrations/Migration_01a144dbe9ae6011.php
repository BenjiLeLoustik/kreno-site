<?php

declare(strict_types=1);

namespace Migrations;

use NeoPHP\Package\Orm\Contract\AbstractMigration;

class Migration_01a144dbe9ae6011 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('CREATE TABLE `category_service_suggestion` (`id` INT NOT NULL AUTO_INCREMENT, `created_at` DATETIME NOT NULL, `updated_at` DATETIME NOT NULL, `name` VARCHAR(120) NOT NULL, `duration` SMALLINT NOT NULL, `price` INT NOT NULL, `position` INT NOT NULL, `category_id` INT NOT NULL, PRIMARY KEY (`id`)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE INDEX `IDX_3193195957EAF19F` ON `category_service_suggestion` (`category_id`, `position`)');
        $this->addSql('ALTER TABLE `category_service_suggestion` ADD CONSTRAINT `FK_542CFA8A5A0D86D0` FOREIGN KEY (`category_id`) REFERENCES `category` (`id`) ON DELETE CASCADE');
    }

    public function down(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('ALTER TABLE `category_service_suggestion` DROP FOREIGN KEY `FK_542CFA8A5A0D86D0`');
        $this->addSql('DROP TABLE `category_service_suggestion`');
    }
}

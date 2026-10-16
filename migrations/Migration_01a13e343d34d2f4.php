<?php

declare(strict_types=1);

namespace Migrations;

use NeoPHP\Package\Orm\Contract\AbstractMigration;

class Migration_01a13e343d34d2f4 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('CREATE TABLE `company_price_notice` (`id` INT NOT NULL AUTO_INCREMENT, `created_at` DATETIME NOT NULL, `updated_at` DATETIME NOT NULL, `current_price` INT NOT NULL, `new_price` INT NOT NULL, `effective_at` DATETIME NOT NULL, `subscription_id` INT NOT NULL, `website_id` INT NOT NULL, PRIMARY KEY (`id`)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE UNIQUE INDEX `UNIQ_101F9748F16AB88D` ON `company_price_notice` (`subscription_id`, `website_id`)');
        $this->addSql('CREATE INDEX `IDX_AA5D057E2E1DA0DD` ON `company_price_notice` (`effective_at`)');
        $this->addSql('CREATE INDEX `IDX_6467F6CDACFDB7AE` ON `company_price_notice` (`website_id`)');
        $this->addSql('ALTER TABLE `company_price_notice` ADD CONSTRAINT `FK_3EA4D49F4405DAFD` FOREIGN KEY (`subscription_id`) REFERENCES `company_subscription` (`id`) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `company_price_notice` ADD CONSTRAINT `FK_6467F6CDACFDB7AE` FOREIGN KEY (`website_id`) REFERENCES `website_config` (`id`) ON DELETE CASCADE');
    }

    public function down(): void
    {
        $this->abortIf($this->getPlatformName() !== 'mysql', 'This migration was generated for mysql.');

        $this->addSql('ALTER TABLE `company_price_notice` DROP FOREIGN KEY `FK_3EA4D49F4405DAFD`');
        $this->addSql('ALTER TABLE `company_price_notice` DROP FOREIGN KEY `FK_6467F6CDACFDB7AE`');
        $this->addSql('DROP TABLE `company_price_notice`');
    }
}

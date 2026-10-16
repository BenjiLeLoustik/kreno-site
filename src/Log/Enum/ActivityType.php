<?php

namespace App\Log\Enum;

enum ActivityType: string
{
    case MODERATION = 'moderation';
    case SANCTION = 'sanction';
    case RULES = 'rules';
    case PRICING = 'pricing';
    case IDENTITY = 'identity';
    case VERSION = 'version';
    case BILLING = 'billing';
    case CATALOG = 'catalog';
    case TEAM = 'team';

    public function label(): string
    {
        return match ($this) {
            self::MODERATION => 'Modération',
            self::SANCTION => 'Sanction',
            self::RULES => 'Règles',
            self::PRICING => 'Tarifs',
            self::IDENTITY => 'Identité',
            self::VERSION => 'Version',
            self::BILLING => 'Facturation',
            self::CATALOG => 'Catalogue',
            self::TEAM => 'Équipe',
        };
    }
}
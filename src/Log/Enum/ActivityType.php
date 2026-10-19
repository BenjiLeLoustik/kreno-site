<?php

namespace App\Log\Enum;

use App\Shared\Helper\Translation;

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
    case FAQ = 'faq';

    public function label(): string
    {
        return match ($this) {
            self::MODERATION => Translation::translate('Modération'),
            self::SANCTION => Translation::translate('Sanction'),
            self::RULES => Translation::translate('Règles'),
            self::PRICING => Translation::translate('Tarifs'),
            self::IDENTITY => Translation::translate('Identité'),
            self::VERSION => Translation::translate('Version'),
            self::BILLING => Translation::translate('Facturation'),
            self::CATALOG => Translation::translate('Catalogue'),
            self::TEAM => Translation::translate('Équipe'),
            self::FAQ => Translation::translate('FAQ'),
        };
    }
}
<?php

namespace App\Website\Enum;

enum InstallIdentityField: string
{
    case SITE_NAME = 'siteName';
    case DOMAIN = 'domain';
    case TAGLINE = 'tagline';
    case SITE_DESCRIPTION = 'siteDescription';
    case SEO_TITLE = 'seoTitle';
    case CONTACT_EMAIL = 'contactEmail';

    public static function defaults(): array
    {
        $defaults = [];

        foreach (self::cases() as $field) {
            $defaults[$field->value] = $field->defaultValue();
        }

        return $defaults;
    }

    public function defaultValue(): string
    {
        return match ($this) {
            self::SITE_NAME => WebsiteDefault::SITE_NAME->text(),
            self::DOMAIN => 'kreno.fr',
            self::TAGLINE => WebsiteDefault::TAGLINE->text(),
            self::SITE_DESCRIPTION => WebsiteDefault::SITE_DESCRIPTION->text(),
            self::SEO_TITLE => 'Kréno · Les créneaux libérés près de chez toi',
            self::CONTACT_EMAIL => 'bonjour@kreno.fr',
        };
    }
}
<?php

namespace App\Website\Enum;

use App\Shared\Helper\Translation;

enum IdentityField: string
{
    case NAME = 'name';
    case DESCRIPTION = 'description';
    case SITE_NAME = 'siteName';
    case DOMAIN = 'domain';
    case TAGLINE = 'tagline';
    case SITE_DESCRIPTION = 'siteDescription';
    case SEO_TITLE = 'seoTitle';
    case CONTACT_EMAIL = 'contactEmail';

    public function label(): string
    {
        return match ($this) {
            self::NAME => Translation::translate('Nom de la version'),
            self::DESCRIPTION => Translation::translate('Note'),
            self::SITE_NAME => Translation::translate('Nom du site'),
            self::DOMAIN => Translation::translate('Domaine'),
            self::TAGLINE => Translation::translate('Accroche'),
            self::SITE_DESCRIPTION => Translation::translate('Description'),
            self::SEO_TITLE => Translation::translate('Titre SEO'),
            self::CONTACT_EMAIL => Translation::translate('E-mail de contact'),
        };
    }

    public function getter(): string
    {
        return 'get' . ucfirst($this->value);
    }

    public function setter(): string
    {
        return 'set' . ucfirst($this->value);
    }
}
<?php

namespace App\Website\Enum;

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
            self::NAME => 'Nom de la version',
            self::DESCRIPTION => 'Note',
            self::SITE_NAME => 'Nom du site',
            self::DOMAIN => 'Domaine',
            self::TAGLINE => 'Accroche',
            self::SITE_DESCRIPTION => 'Description',
            self::SEO_TITLE => 'Titre SEO',
            self::CONTACT_EMAIL => 'E-mail de contact',
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
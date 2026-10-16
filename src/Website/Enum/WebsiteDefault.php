<?php

namespace App\Website\Enum;

enum WebsiteDefault
{
    case SITE_NAME;
    case TAGLINE;
    case SITE_DESCRIPTION;

    public function text(): string
    {
        return match ($this) {
            self::SITE_NAME => 'Kréno',
            self::TAGLINE => 'Un créneau vient de se libérer. Il est pour toi.',
            self::SITE_DESCRIPTION => 'Les créneaux libérés de ton quartier, à prix réduit. Tu réserves en deux clics, tu paies sur place.',
        };
    }
}
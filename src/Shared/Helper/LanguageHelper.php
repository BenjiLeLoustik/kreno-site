<?php

namespace App\Shared\Helper;

use Locale;

class LanguageHelper
{
    public static function label(string $locale): string
    {
        if (!class_exists(Locale::class)) {
            return strtoupper($locale);
        }

        $label = Locale::getDisplayName($locale, $locale);

        if ($label === '' || $label === $locale) {
            return strtoupper($locale);
        }

        return mb_strtoupper(mb_substr($label, 0, 1)) . mb_substr($label, 1);
    }
}
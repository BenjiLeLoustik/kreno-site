<?php

namespace App\Shared\Helper;

use NeoPHP\Package\Translation\TranslationManagerInterface;

class Translation
{
    private static ?self $instance = null;

    public function __construct(
        private TranslationManagerInterface $translator,
    ) {
    }

    public static function boot(self $translation): void
    {
        self::$instance = $translation;
    }

    public static function translate(string $message, array $parameters = [], ?string $domain = null): string
    {
        if (self::$instance === null) {
            return self::interpolate($message, $parameters);
        }

        return self::$instance->translator->translate($message, $parameters, $domain);
    }

    private static function interpolate(string $message, array $parameters = []): string
    {
        $replacements = [];

        foreach ($parameters as $key => $value) {
            $replacements['{' . $key . '}'] = (string) $value;
        }

        return strtr($message, $replacements);
    }
}
<?php

namespace App\Shared\Service;

use App\Shared\Exception\LocaleNotFoundException;
use NeoPHP\Package\Translation\TranslationManagerInterface;

class LocaleService
{
    public function __construct(
        private TranslationManagerInterface $translator,
    ) {
    }

    public function enabled(string $locale): string
    {
        $matched = $this->translator->matchLocale($locale);

        if ($matched === null) {
            throw new LocaleNotFoundException($locale);
        }

        return $matched;
    }
}
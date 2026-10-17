<?php

namespace App\Shared\Helper\View;

use App\Shared\Helper\LanguageHelper;
use NeoPHP\Component\View\Contract\ViewFunctionInterface;
use NeoPHP\Package\Translation\TranslationManagerInterface;

class LocaleSwitcherViewHelper implements ViewFunctionInterface
{
    public function __construct(
        private TranslationManagerInterface $translator,
    ) {
    }

    public function getName(): string
    {
        return 'locale_switcher';
    }

    public function __invoke(): array
    {
        $current = $this->translator->getLocale();
        $items = [];

        foreach ($this->translator->getLocales() as $locale) {
            $items[] = $this->item($locale, $current);
        }

        return [
            'current' => ['code' => $current, 'label' => LanguageHelper::label($current)],
            'items' => $items,
        ];
    }

    private function item(string $locale, string $current): array
    {
        return ['code' => $locale, 'label' => LanguageHelper::label($locale), 'current' => $locale === $current];
    }
}
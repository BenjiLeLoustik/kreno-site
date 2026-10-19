<?php

namespace App\Shared\Helper\View;

use DateTimeImmutable;
use DateTimeInterface;
use IntlDateFormatter;
use NeoPHP\Component\View\Contract\ViewFilterInterface;
use NeoPHP\Package\Translation\TranslationManagerInterface;

class FormatDateViewHelper implements ViewFilterInterface
{
    private const FORMATS = [
        'none' => IntlDateFormatter::NONE,
        'short' => IntlDateFormatter::SHORT,
        'medium' => IntlDateFormatter::MEDIUM,
        'long' => IntlDateFormatter::LONG,
        'full' => IntlDateFormatter::FULL,
    ];

    public function __construct(
        private TranslationManagerInterface $translator,
    ) {
    }

    public function getName(): string
    {
        return 'format_date';
    }

    public function __invoke(
        DateTimeInterface|string|null $date,
        string $dateFormat = 'medium',
        string $pattern = '',
        ?string $locale = null,
    ): string {
        $value = $this->toDate($date);

        if ($value === null) {
            return '';
        }

        $formatted = $this->formatter($dateFormat, $pattern, $locale, $value)->format($value);

        if ($formatted === false) {
            return '';
        }

        return $formatted;
    }

    private function toDate(DateTimeInterface|string|null $date): ?DateTimeInterface
    {
        if ($date instanceof DateTimeInterface) {
            return $date;
        }

        if ($date === null || $date === '') {
            return null;
        }

        return new DateTimeImmutable($date);
    }

    private function formatter(
        string $dateFormat,
        string $pattern,
        ?string $locale,
        DateTimeInterface $date,
    ): IntlDateFormatter {
        return new IntlDateFormatter(
            $locale ?? $this->translator->getLocale(),
            self::FORMATS[$dateFormat] ?? IntlDateFormatter::MEDIUM,
            IntlDateFormatter::NONE,
            $date->getTimezone(),
            null,
            $this->pattern($pattern),
        );
    }

    private function pattern(string $pattern): ?string
    {
        if ($pattern === '') {
            return null;
        }

        return $pattern;
    }
}
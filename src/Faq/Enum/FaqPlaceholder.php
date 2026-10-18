<?php

namespace App\Faq\Enum;

use App\Shared\Helper\Translation;

enum FaqPlaceholder: string
{
    case MIN_DISCOUNT = 'minDiscount';
    case MAX_DISCOUNT = 'maxDiscount';
    case CANCELLATION_DELAY = 'cancellationDelay';
    case TOLERATED_NO_SHOWS = 'toleratedNoShows';
    case SUSPENSION_DAYS = 'suspensionDays';
    case REPORT_WINDOW_HOURS = 'reportWindowHours';

    public static function replace(string $text, array $rules): string
    {
        $pairs = [];

        foreach (self::cases() as $placeholder) {
            $pairs[$placeholder->token()] = (string) ($rules[$placeholder->value] ?? '');
        }

        return strtr($text, $pairs);
    }

    public static function choices(): array
    {
        $choices = [];

        foreach (self::cases() as $placeholder) {
            $choices[] = [
                'token' => $placeholder->token(),
                'label' => $placeholder->label()
            ];
        }

        return $choices;
    }

    public function token(): string
    {
        return '{' . $this->value . '}';
    }

    public function label(): string
    {
        return match ($this) {
            self::MIN_DISCOUNT => Translation::translate('Remise minimale (%)'),
            self::MAX_DISCOUNT => Translation::translate('Remise maximale (%)'),
            self::CANCELLATION_DELAY => Translation::translate('Délai d\'annulation gratuite'),
            self::TOLERATED_NO_SHOWS => Translation::translate('Absences tolérées'),
            self::SUSPENSION_DAYS => Translation::translate('Jours de suspension'),
            self::REPORT_WINDOW_HOURS => Translation::translate('Délai de signalement (h)'),
        };
    }
}
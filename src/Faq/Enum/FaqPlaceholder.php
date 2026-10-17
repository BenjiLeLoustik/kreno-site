<?php

namespace App\Faq\Enum;

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
            $choices[] = ['token' => $placeholder->token(), 'label' => $placeholder->label()];
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
            self::MIN_DISCOUNT => 'Remise minimale (%)',
            self::MAX_DISCOUNT => 'Remise maximale (%)',
            self::CANCELLATION_DELAY => 'Délai d\'annulation gratuite',
            self::TOLERATED_NO_SHOWS => 'Absences tolérées',
            self::SUSPENSION_DAYS => 'Jours de suspension',
            self::REPORT_WINDOW_HOURS => 'Délai de signalement (h)',
        };
    }
}
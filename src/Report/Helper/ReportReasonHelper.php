<?php

namespace App\Report\Helper;

use App\Report\Enum\ReportDirection;
use App\Report\Enum\ReportReason;

class ReportReasonHelper
{
    public const CUSTOMER_HINTS = [
        'price_mismatch' => 'Le prix demandé n’est pas celui affiché.',
        'closed' => 'Personne à l’adresse au moment du rendez-vous.',
        'service_mismatch' => 'Ce n’est pas la prestation réservée.',
        'last_minute_cancellation' => 'L’établissement a annulé au dernier moment.',
        'behavior' => 'Propos ou attitude déplacés.',
        'other' => 'Un autre problème avec cette réservation.',
    ];

    public const ESTABLISHMENT_REASONS = ['no_show', 'late', 'behavior', 'other'];

    public const HIGH_PRIORITY = ['closed', 'behavior'];

    public const LOW_PRIORITY = ['other'];

    public static function allowed(ReportDirection $direction): array
    {
        $values = $direction === ReportDirection::FROM_CUSTOMER ? array_keys(self::CUSTOMER_HINTS) : self::ESTABLISHMENT_REASONS;

        return array_map(static fn (string $value): ReportReason => ReportReason::from($value), $values);
    }

    public static function isAllowed(ReportDirection $direction, ReportReason $reason): bool
    {
        return in_array($reason, self::allowed($direction), true);
    }

    public static function choices(ReportDirection $direction, ?string $selected = null): array
    {
        return array_map(static fn (ReportReason $reason): array => [
            'key' => $reason->value,
            'label' => $reason->label(),
            'hint' => self::CUSTOMER_HINTS[$reason->value] ?? null,
            'selected' => $reason->value === $selected,
        ], self::allowed($direction));
    }

    public static function priority(ReportReason $reason, int $similar = 0): string
    {
        if (in_array($reason->value, self::HIGH_PRIORITY, true) || $similar >= 2) {
            return 'high';
        }

        return in_array($reason->value, self::LOW_PRIORITY, true) ? 'low' : 'mid';
    }
}
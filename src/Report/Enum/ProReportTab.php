<?php

namespace App\Report\Enum;

enum ProReportTab: string
{
    case MINE = 'mine';
    case RECEIVED = 'received';

    public static function resolve(string $tab, bool $canReport, bool $canSeeReceived): self
    {
        if (!$canReport) {
            return self::RECEIVED;
        }

        if ($tab === self::RECEIVED->value && $canSeeReceived) {
            return self::RECEIVED;
        }

        return self::MINE;
    }

    public function label(): string
    {
        return match ($this) {
            self::MINE => 'Mes signalements',
            self::RECEIVED => 'Concernant l’établissement',
        };
    }
}
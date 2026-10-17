<?php

namespace App\Establishment\Enum;

enum AdminEstablishmentFilter: string
{
    case ALL = 'all';
    case ONLINE = 'online';
    case SUSPENDED = 'suspended';

    public static function of(string $filter): self
    {
        return self::tryFrom($filter) ?? self::ALL;
    }

    public static function choices(): array
    {
        $choices = [];

        foreach (self::cases() as $filter) {
            $choices[] = ['key' => $filter->value, 'label' => $filter->label()];
        }

        return $choices;
    }

    public function label(): string
    {
        return match ($this) {
            self::ALL => 'Tous',
            self::ONLINE => 'En ligne',
            self::SUSPENDED => 'Suspendus',
        };
    }

    public function statuses(): array
    {
        return match ($this) {
            self::ALL => [EstablishmentStatus::ONLINE, EstablishmentStatus::PAUSED],
            self::ONLINE => [EstablishmentStatus::ONLINE],
            self::SUSPENDED => [EstablishmentStatus::PAUSED],
        };
    }
}
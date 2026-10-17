<?php

namespace App\Website\Enum;

enum ProLandingStep
{
    case CANCELLATION;
    case DISCOUNT;
    case WELCOME;

    public static function all(array $rules): array
    {
        $steps = [];

        foreach (self::cases() as $step) {
            $steps[] = ['title' => $step->title(), 'text' => $step->text($rules)];
        }

        return $steps;
    }

    public function title(): string
    {
        return match ($this) {
            self::CANCELLATION => 'Un rendez-vous s’annule',
            self::DISCOUNT => 'Choisissez la remise',
            self::WELCOME => 'Recevez le client',
        };
    }

    public function text(array $rules): string
    {
        return sprintf($this->pattern(), $rules['minDiscount'], $rules['maxDiscount']);
    }

    private function pattern(): string
    {
        return match ($this) {
            self::CANCELLATION => 'Ouvrez votre espace pro et choisissez l’horaire libéré dans votre journée.',
            self::DISCOUNT => 'De %d à %d %%. Le créneau part en ligne avec une heure d’expiration.',
            self::WELCOME => 'Vous êtes prévenu dès la réservation. Le client paie sur place.',
        };
    }
}
<?php

namespace App\Website\Enum;

use App\Shared\Helper\Translation;

enum ProLandingStep
{
    case CANCELLATION;
    case DISCOUNT;
    case WELCOME;

    public static function all(array $rules): array
    {
        $steps = [];

        foreach (self::cases() as $step) {
            $steps[] = [
                'title' => $step->title(),
                'text' => $step->text($rules)
            ];
        }

        return $steps;
    }

    public function title(): string
    {
        return match ($this) {
            self::CANCELLATION => Translation::translate('Un rendez-vous s’annule'),
            self::DISCOUNT => Translation::translate('Choisissez la remise'),
            self::WELCOME => Translation::translate('Recevez le client'),
        };
    }

    public function text(array $rules): string
    {
        return sprintf($this->pattern(), $rules['minDiscount'], $rules['maxDiscount']);
    }

    private function pattern(): string
    {
        return match ($this) {
            self::CANCELLATION => Translation::translate('Ouvrez votre espace pro et choisissez l’horaire libéré dans votre journée.'),
            self::DISCOUNT => Translation::translate('De %d à %d %%. Le créneau part en ligne avec une heure d’expiration.'),
            self::WELCOME => Translation::translate('Vous êtes prévenu dès la réservation. Le client paie sur place.'),
        };
    }
}
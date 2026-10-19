<?php

namespace App\Company\Enum;

use App\Shared\Helper\Translation;

enum ProSignupStep: string
{
    case ACCOUNT = 'account';
    case VERIFY = 'verify';
    case ESTABLISHMENT = 'establishment';
    case SERVICES = 'services';
    case PAGE = 'page';
    case PLAN = 'plan';
    case PAYMENT = 'payment';
    case PENDING = 'pending';
    case DONE = 'done';

    public const PATTERN = 'account|verify|establishment|services|page|plan|payment|pending';

    public static function ordered(): array
    {
        return array_values(array_filter(self::cases(), static fn (self $step): bool => $step !== self::DONE));
    }

    public static function tracked(): array
    {
        return array_values(array_filter(self::ordered(), static fn (self $step): bool => $step !== self::PENDING));
    }

    public function position(): int
    {
        return (int) array_search($this, self::ordered(), true);
    }

    public function isLocked(): bool
    {
        return $this === self::ACCOUNT || $this === self::VERIFY;
    }

    public function template(): string
    {
        return sprintf('pro/signup/%s.html.twig', $this->value);
    }

    public function label(): string
    {
        return match ($this) {
            self::ACCOUNT => Translation::translate('Votre compte'),
            self::VERIFY => Translation::translate('Vérification'),
            self::ESTABLISHMENT => Translation::translate('Établissement'),
            self::SERVICES => Translation::translate('Prestations'),
            self::PAGE => Translation::translate('Page publique'),
            self::PLAN => Translation::translate('Formule'),
            self::PAYMENT => Translation::translate('Paiement'),
            self::PENDING,
            self::DONE => '',
        };
    }

    public function sub(): string
    {
        return match ($this) {
            self::ACCOUNT => Translation::translate('Identité et connexion'),
            self::VERIFY => Translation::translate('Code reçu par e-mail'),
            self::ESTABLISHMENT => Translation::translate('SIRET et adresse'),
            self::SERVICES => Translation::translate('Ce que vous proposez'),
            self::PAGE => Translation::translate('Photos, description, horaires'),
            self::PLAN => Translation::translate('Choix de l\'abonnement'),
            self::PAYMENT => Translation::translate('Carte ou prélèvement'),
            self::PENDING,
            self::DONE => '',
        };
    }
}
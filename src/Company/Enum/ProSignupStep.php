<?php

namespace App\Company\Enum;

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
            self::ACCOUNT => 'Votre compte',
            self::VERIFY => 'Vérification',
            self::ESTABLISHMENT => 'Établissement',
            self::SERVICES => 'Prestations',
            self::PAGE => 'Page publique',
            self::PLAN => 'Formule',
            self::PAYMENT => 'Paiement',
            self::PENDING, self::DONE => '',
        };
    }

    public function sub(): string
    {
        return match ($this) {
            self::ACCOUNT => 'Identité et connexion',
            self::VERIFY => 'Code reçu par e-mail',
            self::ESTABLISHMENT => 'SIRET et adresse',
            self::SERVICES => 'Ce que vous proposez',
            self::PAGE => 'Photos, description, horaires',
            self::PLAN => 'Choix de l\'abonnement',
            self::PAYMENT => 'Carte ou prélèvement',
            self::PENDING, self::DONE => '',
        };
    }
}
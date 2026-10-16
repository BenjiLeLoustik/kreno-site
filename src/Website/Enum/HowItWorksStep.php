<?php

namespace App\Website\Enum;

enum HowItWorksStep
{
    case CLIENT_CATEGORIES;
    case CLIENT_ALERT;
    case CLIENT_BOOKING;
    case CLIENT_PAYMENT;
    case PRO_CANCELLATION;
    case PRO_DISCOUNT;
    case PRO_ALERT;
    case PRO_WELCOME;
    case RULE_PAYMENT;
    case RULE_CANCELLATION;
    case RULE_NO_SHOW;
    case RULE_VERIFIED;

    public static function grouped(array $rules): array
    {
        $groups = [];

        foreach (self::cases() as $step) {
            $groups[$step->group()][] = ['title' => $step->title(), 'text' => $step->text($rules)];
        }

        return $groups;
    }

    public function group(): string
    {
        return match ($this) {
            self::CLIENT_CATEGORIES, self::CLIENT_ALERT, self::CLIENT_BOOKING, self::CLIENT_PAYMENT => 'client',
            self::PRO_CANCELLATION, self::PRO_DISCOUNT, self::PRO_ALERT, self::PRO_WELCOME => 'pro',
            self::RULE_PAYMENT, self::RULE_CANCELLATION, self::RULE_NO_SHOW, self::RULE_VERIFIED => 'rules',
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::CLIENT_CATEGORIES => 'Choisissez vos catégories',
            self::CLIENT_ALERT => 'Recevez une alerte',
            self::CLIENT_BOOKING => 'Réservez en deux clics',
            self::CLIENT_PAYMENT => 'Payez sur place',
            self::PRO_CANCELLATION => 'Un client annule',
            self::PRO_DISCOUNT => 'Vous fixez la remise',
            self::PRO_ALERT => 'Le quartier est prévenu',
            self::PRO_WELCOME => 'Vous accueillez le client',
            self::RULE_PAYMENT => 'Paiement sur place',
            self::RULE_CANCELLATION => 'Annulation gratuite',
            self::RULE_NO_SHOW => 'Absence non prévenue',
            self::RULE_VERIFIED => 'Pros vérifiés',
        };
    }

    public function text(array $rules): string
    {
        return sprintf($this->pattern(), ...$this->values($rules));
    }

    private function pattern(): string
    {
        return match ($this) {
            self::CLIENT_CATEGORIES => 'Coiffure, esthétique, ostéo, garage… et votre quartier.',
            self::CLIENT_ALERT => 'Dès qu’un pro proche libère un créneau qui vous correspond.',
            self::CLIENT_BOOKING => 'Le créneau est bloqué à votre nom immédiatement.',
            self::CLIENT_PAYMENT => 'Au prix réduit affiché, comme un rendez-vous normal.',
            self::PRO_CANCELLATION => 'Le créneau vide est publié depuis l’espace pro en quelques secondes.',
            self::PRO_DISCOUNT => 'De %d à %d %%, et l’heure à laquelle l’offre disparaît.',
            self::PRO_ALERT => 'Les clients qui suivent votre catégorie reçoivent une alerte.',
            self::PRO_WELCOME => 'Vous êtes prévenu dès la réservation. Rien à encaisser en ligne.',
            self::RULE_PAYMENT => 'Aucun paiement en ligne pour les clients.',
            self::RULE_CANCELLATION => 'Jusqu’à %s avant le rendez-vous.',
            self::RULE_NO_SHOW => 'Au-delà de %d, les réservations sont suspendues %d jours.',
            self::RULE_VERIFIED => 'Chaque établissement est contrôlé avant sa mise en ligne.',
        };
    }

    private function values(array $rules): array
    {
        return match ($this) {
            self::PRO_DISCOUNT => [$rules['minDiscount'], $rules['maxDiscount']],
            self::RULE_CANCELLATION => [$rules['cancellationDelay']],
            self::RULE_NO_SHOW => [$rules['toleratedNoShows'], $rules['suspensionDays']],
            default => [],
        };
    }
}
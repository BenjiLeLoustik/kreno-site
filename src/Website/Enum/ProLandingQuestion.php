<?php

namespace App\Website\Enum;

enum ProLandingQuestion
{
    case NO_SHOW;
    case DISCOUNT;
    case PAYMENT;
    case WITHDRAW;
    case CANCELLATION;

    public static function all(array $rules): array
    {
        $questions = [];

        foreach (self::cases() as $question) {
            $questions[] = ['question' => $question->question(), 'answer' => $question->answer($rules)];
        }

        return $questions;
    }

    public function question(): string
    {
        return match ($this) {
            self::NO_SHOW => 'Et si le client ne vient pas ?',
            self::DISCOUNT => 'Est-ce que je choisis la remise ?',
            self::PAYMENT => 'Comment sont payés les clients ?',
            self::WITHDRAW => 'Puis-je retirer un créneau publié ?',
            self::CANCELLATION => 'Et si le client annule ?',
        };
    }

    public function answer(array $rules): string
    {
        return sprintf($this->pattern(), ...$this->values($rules));
    }

    private function pattern(): string
    {
        return match ($this) {
            self::NO_SHOW => 'Le client s’engage en réservant. Après %d absences non prévenues, '
                . 'son compte est suspendu des réservations pendant %d jours.',
            self::DISCOUNT => 'Oui, pour chaque créneau. Vous pouvez aussi enregistrer une remise par défaut '
                . 'par prestation.',
            self::PAYMENT => 'Sur place, comme d’habitude. Nous ne touchons pas à votre encaissement.',
            self::WITHDRAW => 'À tout moment tant qu’il n’est pas réservé, depuis Mes créneaux.',
            self::CANCELLATION => 'L’annulation est gratuite jusqu’à %s avant le rendez-vous. '
                . 'Le créneau peut ensuite être republié en un clic.',
        };
    }

    private function values(array $rules): array
    {
        return match ($this) {
            self::NO_SHOW => [$rules['toleratedNoShows'], $rules['suspensionDays']],
            self::CANCELLATION => [$rules['cancellationDelay']],
            default => [],
        };
    }
}
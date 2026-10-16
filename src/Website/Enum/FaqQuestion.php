<?php

namespace App\Website\Enum;

enum FaqQuestion
{
    case PAY_ONLINE;
    case DISCOUNTS;
    case CANCEL;
    case PRO_CANCEL;
    case ALERTS;
    case NO_SHOW;
    case DATA;
    case DELETE_ACCOUNT;
    case WRONG_PRICE;
    case CLOSED;
    case PUBLISH;

    public function section(): FaqSection
    {
        return match ($this) {
            self::PAY_ONLINE, self::DISCOUNTS, self::CANCEL, self::PRO_CANCEL => FaqSection::BOOKING,
            self::ALERTS, self::NO_SHOW, self::DATA, self::DELETE_ACCOUNT => FaqSection::ACCOUNT,
            self::WRONG_PRICE, self::CLOSED => FaqSection::REPORT,
            self::PUBLISH => FaqSection::PRO,
        };
    }

    public function question(): string
    {
        return match ($this) {
            self::PAY_ONLINE => 'Dois-je payer en ligne ?',
            self::DISCOUNTS => 'Quelles remises sont proposées ?',
            self::CANCEL => 'Comment annuler ?',
            self::PRO_CANCEL => 'Le professionnel peut-il annuler ?',
            self::ALERTS => 'Comment être prévenu des nouveaux créneaux ?',
            self::NO_SHOW => 'Que se passe-t-il si je ne viens pas ?',
            self::DATA => 'Comment récupérer mes données ?',
            self::DELETE_ACCOUNT => 'Comment supprimer mon compte ?',
            self::WRONG_PRICE => 'Le prix n’est pas celui affiché',
            self::CLOSED => 'L’établissement était fermé',
            self::PUBLISH => 'Comment publier mes créneaux ?',
        };
    }

    public function answer(array $rules): string
    {
        return sprintf($this->pattern(), ...$this->values($rules));
    }

    private function pattern(): string
    {
        return match ($this) {
            self::PAY_ONLINE => 'Non. Vous réglez le prix réduit directement chez le professionnel, '
                . 'le jour du rendez-vous.',
            self::DISCOUNTS => 'Les professionnels publient leurs créneaux libérés avec une remise de %d à %d %%.',
            self::CANCEL => 'Depuis Mon compte, dans Mes réservations, jusqu’à %s avant le rendez-vous et sans frais.',
            self::PRO_CANCEL => 'Oui en cas d’imprévu. Vous êtes prévenu par e-mail '
                . 'et pouvez réserver un autre créneau.',
            self::ALERTS => 'Créez une alerte dans Mon compte, Mes alertes : catégories, distance et horaires.',
            self::NO_SHOW => 'Au-delà de %d absence%s non prévenue%s, '
                . 'les réservations sont suspendues pendant %d jours.',
            self::DATA => 'Dans Mon compte, Réglages, rubrique Mes données : le téléchargement est immédiat.',
            self::DELETE_ACCOUNT => 'Dans Mon compte, Réglages, en bas de page. La suppression est définitive.',
            self::WRONG_PRICE => 'Signalez-le depuis le détail de la réservation dans les %d h. '
                . 'Nous contactons le professionnel.',
            self::CLOSED => 'Signalez-le depuis le détail de la réservation dans les %d h. '
                . 'Nous vérifions avec le professionnel.',
            self::PUBLISH => 'Créez votre espace pro depuis la page Pour les pros, '
                . 'puis publiez un créneau libéré en quelques secondes.',
        };
    }

    private function values(array $rules): array
    {
        return match ($this) {
            self::DISCOUNTS => [$rules['minDiscount'], $rules['maxDiscount']],
            self::CANCEL => [$rules['cancellationDelay']],
            self::NO_SHOW => $this->noShowValues($rules['toleratedNoShows'], $rules['suspensionDays']),
            self::WRONG_PRICE, self::CLOSED => [$rules['reportWindowHours']],
            default => [],
        };
    }

    private function noShowValues(int $tolerated, int $days): array
    {
        $plural = $this->plural($tolerated);

        return [$tolerated, $plural, $plural, $days];
    }

    private function plural(int $count): string
    {
        if ($count > 1) {
            return 's';
        }

        return '';
    }
}
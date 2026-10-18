<?php

namespace App\Billing\Enum;

use App\Shared\Helper\Translation;

enum PublicationBlock: string
{
    case NO_PLAN = 'no_plan';
    case PAUSED = 'paused';
    case SUSPENDED = 'suspended';

    public function message(): string
    {
        return match ($this) {
            self::NO_PLAN => Translation::translate('Choisissez une formule pour publier des créneaux.'),
            self::PAUSED => Translation::translate('Votre abonnement est en pause : la publication de créneaux reprendra à la fin de la pause.'),
            self::SUSPENDED => Translation::translate('Un paiement est en attente depuis trop longtemps : réglez la facture pour publier à nouveau.'),
        };
    }
}
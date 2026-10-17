<?php

namespace App\Billing\Enum;

enum BillingFlash
{
    case INFO_SAVED;
    case METHOD_SAVED;
    case METHOD_DEFAULT;
    case METHOD_DELETED;
    case INVOICE_PAID;

    public function text(): string
    {
        return match ($this) {
            self::INFO_SAVED => 'Informations de facturation enregistrées.',
            self::METHOD_SAVED => 'Moyen de paiement enregistré.',
            self::METHOD_DEFAULT => 'Moyen de paiement par défaut modifié.',
            self::METHOD_DELETED => 'Moyen de paiement supprimé.',
            self::INVOICE_PAID => 'Facture réglée.',
        };
    }
}
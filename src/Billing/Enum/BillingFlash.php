<?php

namespace App\Billing\Enum;

use App\Shared\Helper\Translation;

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
            self::INFO_SAVED => Translation::translate('Informations de facturation enregistrées.'),
            self::METHOD_SAVED => Translation::translate('Moyen de paiement enregistré.'),
            self::METHOD_DEFAULT => Translation::translate('Moyen de paiement par défaut modifié.'),
            self::METHOD_DELETED => Translation::translate('Moyen de paiement supprimé.'),
            self::INVOICE_PAID => Translation::translate('Facture réglée.'),
        };
    }
}
<?php

namespace App\Billing\Enum;

use App\Shared\Helper\Translation;

enum AdminBillingFlash
{
    case RETRY_SUCCEEDED;
    case RETRY_RELAUNCHED;
    case GRACE_GRANTED;
    case SUBSCRIPTION_CHANGED;

    public static function retry(PaymentStatus $status): self
    {
        if ($status === PaymentStatus::SUCCEEDED) {
            return self::RETRY_SUCCEEDED;
        }

        return self::RETRY_RELAUNCHED;
    }

    public function type(): string
    {
        return match ($this) {
            self::RETRY_RELAUNCHED => 'warning',
            self::RETRY_SUCCEEDED, self::GRACE_GRANTED, self::SUBSCRIPTION_CHANGED => 'success',
        };
    }

    public function text(string|int|null ...$values): string
    {
        return sprintf($this->pattern(), ...$values);
    }

    private function pattern(): string
    {
        return match ($this) {
            self::RETRY_SUCCEEDED => Translation::translate('Prélèvement réussi. La facture est réglée.'),
            self::RETRY_RELAUNCHED => Translation::translate('Prélèvement relancé : %s.'),
            self::GRACE_GRANTED => Translation::translate('Délai de %d jours accordé à %s.'),
            self::SUBSCRIPTION_CHANGED => Translation::translate('Abonnement modifié. L\'entreprise a été prévenue par e-mail et l\'action est notée dans le journal.'),
        };
    }
}
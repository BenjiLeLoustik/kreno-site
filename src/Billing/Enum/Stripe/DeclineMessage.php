<?php

namespace App\Billing\Enum\Stripe;

enum DeclineMessage: string
{
    case INSUFFICIENT_FUNDS = 'insufficient_funds';
    case EXPIRED_CARD = 'expired_card';
    case CARD_DECLINED = 'card_declined';
    case AUTHENTICATION_REQUIRED = 'authentication_required';
    case INCORRECT_CVC = 'incorrect_cvc';
    case PROCESSING_ERROR = 'processing_error';
    case CARD_VELOCITY_EXCEEDED = 'card_velocity_exceeded';

    public static function fromCode(?string $code): self
    {
        return self::tryFrom((string) $code) ?? self::CARD_DECLINED;
    }

    public function message(): string
    {
        return match ($this) {
            self::INSUFFICIENT_FUNDS => 'provision insuffisante',
            self::EXPIRED_CARD => 'carte expirée',
            self::CARD_DECLINED => 'paiement refusé par la banque',
            self::AUTHENTICATION_REQUIRED => 'authentification 3D Secure non validée',
            self::INCORRECT_CVC => 'code de sécurité incorrect',
            self::PROCESSING_ERROR => 'erreur de traitement chez la banque',
            self::CARD_VELOCITY_EXCEEDED => 'plafond de paiement atteint',
        };
    }
}

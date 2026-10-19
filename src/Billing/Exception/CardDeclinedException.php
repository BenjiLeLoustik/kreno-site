<?php

namespace App\Billing\Exception;

use App\Billing\Enum\Stripe\DeclineMessage;
use App\Shared\Exception\BusinessRuleException;

class CardDeclinedException extends BusinessRuleException
{
    public function __construct(?string $code = null)
    {
        parent::__construct(DeclineMessage::fromCode($code)->message());
    }
}
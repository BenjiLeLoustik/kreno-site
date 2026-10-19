<?php

namespace App\Booking\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;
use DateTimeImmutable;

class AccountSuspendedException extends BusinessRuleException
{
    public function __construct(?DateTimeImmutable $until)
    {
        parent::__construct(
            Translation::translate('Ton compte est suspendu jusqu\'au {date} : tu ne peux pas réserver d\'ici là.'),
            [
                'date' => $until?->format('d/m/Y') ?? '-'
            ],
        );
    }
}
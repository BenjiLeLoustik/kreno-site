<?php

namespace App\Billing\Exception;

use App\Billing\Enum\PublicationBlock;
use App\Shared\Exception\BusinessRuleException;

class PublicationBlockedException extends BusinessRuleException
{
    public function __construct(PublicationBlock $block)
    {
        parent::__construct($block->message());
    }
}
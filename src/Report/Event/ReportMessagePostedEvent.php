<?php

namespace App\Report\Event;

use App\Entity\Report\ReportMessage;

class ReportMessagePostedEvent
{
    public function __construct(
        private ReportMessage $message,
    ) {
    }

    public function getMessage(): ReportMessage
    {
        return $this->message;
    }
}
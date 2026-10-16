<?php

namespace App\Booking\Task;

use App\Booking\Service\AttendanceService;
use DateTimeImmutable;
use NeoPHP\Package\Scheduler\Attribute\AsScheduledTask;

#[AsScheduledTask(
    cron: '0 * * * *',
    name: 'booking-close-finished',
    withoutOverlapping: true,
    description: 'Marque comme honorées les réservations terminées depuis 48 h sans absence signalée'
)]
class CloseFinishedBookingsTask
{
    public function __invoke(AttendanceService $attendance): string
    {
        return sprintf(
            '%d réservation(s) clôturée(s)',
            $attendance->closeFinished(new DateTimeImmutable())
        );
    }
}
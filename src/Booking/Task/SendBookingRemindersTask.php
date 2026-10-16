<?php

namespace App\Booking\Task;

use App\Booking\Service\ReminderService;
use DateTimeImmutable;
use NeoPHP\Package\Scheduler\Attribute\AsScheduledTask;

#[AsScheduledTask(cron: '*/5 * * * *', name: 'booking-send-reminders', withoutOverlapping: true, description: 'Déclenche le rappel des rendez-vous qui commencent dans une heure')]
class SendBookingRemindersTask
{
    public function __invoke(ReminderService $reminders): string
    {
        return sprintf('%d rappel(s) déclenché(s)', $reminders->sendDue(new DateTimeImmutable()));
    }
}
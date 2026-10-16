<?php

namespace App\Establishment\Task;

use App\Establishment\Service\SlotPublicationService;
use DateTimeImmutable;
use NeoPHP\Package\Scheduler\Attribute\AsScheduledTask;

#[AsScheduledTask(cron: '* * * * *', name: 'establishment-expire-slots', withoutOverlapping: true, description: 'Passe en expiré les créneaux non réservés dont l\'heure de retrait est atteinte')]
class ExpireSlotsTask
{
    public function __invoke(SlotPublicationService $publication): string
    {
        return sprintf('%d créneau(x) expiré(s)', $publication->expireDue(new DateTimeImmutable()));
    }
}
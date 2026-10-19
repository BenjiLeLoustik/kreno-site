<?php

namespace App\User\Task;

use App\User\Service\UserTokenService;
use NeoPHP\Package\Scheduler\Attribute\AsScheduledTask;

#[AsScheduledTask(
    cron: '30 3 * * *',
    name: 'user-purge-tokens',
    withoutOverlapping: true,
    description: 'Supprime les liens de vérification et de réinitialisation expirés'
)]
class PurgeExpiredTokensTask
{
    public function __invoke(UserTokenService $tokens): string
    {
        return sprintf(
            '%d lien(s) expiré(s) supprimé(s)',
            $tokens->purgeExpired()
        );
    }
}
<?php

namespace App\User\Listener;

use App\Entity\User\User;
use DateTimeImmutable;
use NeoPHP\Component\Event\Attribute\AsListener;
use NeoPHP\Package\Orm\OrmManagerInterface;
use NeoPHP\Package\Security\Event\LoginSuccessEvent;

class LoginSuccessListener
{
    public function __construct(
        private OrmManagerInterface $orm
    ) {
    }

    #[AsListener(LoginSuccessEvent::class)]
    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        $user->setLastLoginAt(new DateTimeImmutable());
        $this->orm->flush();
    }
}
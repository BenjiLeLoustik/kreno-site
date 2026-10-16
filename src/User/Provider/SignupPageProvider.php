<?php

namespace App\User\Provider;

use NeoPHP\Component\Session\SessionManagerInterface;

class SignupPageProvider
{
    public const OLD_INPUT = 'app.signup.old';

    public function __construct(
        private SessionManagerInterface $session,
    ) {
    }

    public function form(): array
    {
        return [
            'form' => $this->pullOld() + ['firstName' => null, 'lastName' => null, 'email' => null, 'phone' => null],
        ];
    }

    public function remember(array $old): void
    {
        $this->session->set(self::OLD_INPUT, $old);
    }

    private function pullOld(): array
    {
        $old = $this->session->get(self::OLD_INPUT);
        $this->session->remove(self::OLD_INPUT);

        if (!is_array($old)) {
            return [];
        }

        return $old;
    }
}
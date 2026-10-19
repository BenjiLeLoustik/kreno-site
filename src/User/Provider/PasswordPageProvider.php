<?php

namespace App\User\Provider;

use App\Entity\User\User;
use App\Shared\Exception\BusinessRuleException;
use App\User\Enum\PasswordSpace;
use App\User\Service\PasswordService;
use NeoPHP\Component\Http\Request\Request;

class PasswordPageProvider
{
    public function __construct(
        private PasswordService $passwords,
    ) {
    }

    public function forgot(Request $request, PasswordSpace $space, ?User $user): array
    {
        return [
            'pro' => $space->isPro(),
            'sent' => $request->query->getBoolean('sent'),
            'email' => $request->query->getString('email') ?: $user?->getEmail(),
        ];
    }

    public function reset(string $token, PasswordSpace $space): array
    {
        $email = $this->email($token);

        return [
            'pro' => $space->isPro(),
            'token' => $token,
            'valid' => $email !== null,
            'email' => $email,
        ];
    }

    private function email(string $token): ?string
    {
        try {
            return $this->passwords->checkResetToken($token)->getEmail();
        } catch (BusinessRuleException) {
            return null;
        }
    }
}
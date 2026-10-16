<?php

namespace App\User\Presenter;

use App\Entity\User\User;
use App\Establishment\Helper\CalendarHelper;
use App\User\Helper\StaffRoleHelper;
use App\User\Helper\UserNameHelper;

class StaffPresenter
{
    public function present(User $user, ?User $viewer = null): array
    {
        $role = StaffRoleHelper::role($user);

        return [
            'id' => $user->getId(),
            'initials' => UserNameHelper::initials($user),
            'name' => UserNameHelper::shortName($user),
            'email' => $user->getEmail(),
            'role' => $role?->value,
            'roleLabel' => $role?->label(),
            'status' => $user->getStatus()->value,
            'pending' => $user->getLastLoginAt() === null,
            'lastLogin' => $user->getLastLoginAt() !== null ? CalendarHelper::relative($user->getLastLoginAt()) : null,
            'isMe' => $viewer !== null && $viewer === $user,
        ];
    }

    public function presentAll(array $users, ?User $viewer = null): array
    {
        return array_map(fn (User $user): array => $this->present($user, $viewer), $users);
    }
}
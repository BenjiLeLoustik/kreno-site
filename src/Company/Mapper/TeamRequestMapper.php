<?php

namespace App\Company\Mapper;

use App\Company\Dto\InvitationInput;
use App\Company\Dto\MemberRoleInput;
use NeoPHP\Component\Http\Request\Request;

class TeamRequestMapper
{
    public const ADMIN_ROLE = 'admin';

    public const ALL_ESTABLISHMENTS = 'all';

    public static function filter(Request $request): string
    {
        return $request->query->getString('establishment') ?: self::ALL_ESTABLISHMENTS;
    }

    public static function invitation(InvitationInput $input, Request $request): InvitationInput
    {
        $input->canManageBookings = $request->request->getBoolean('canManageBookings');
        $input->establishmentId = self::establishmentId($input->role, $request);

        return $input;
    }

    public static function role(MemberRoleInput $input, Request $request): MemberRoleInput
    {
        $input->canManageBookings = $request->request->getBoolean('canManageBookings');
        $input->establishmentId = self::establishmentId($input->role, $request);

        return $input;
    }

    private static function establishmentId(?string $role, Request $request): ?int
    {
        if ($role === self::ADMIN_ROLE) {
            return null;
        }

        return $request->request->getInt('establishmentId') ?: null;
    }
}
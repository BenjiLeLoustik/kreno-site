<?php

namespace App\Establishment\Provider;

use App\Company\Security\Permission;
use App\Company\Security\PermissionResolver;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Company\CompanyMember;
use App\Establishment\Helper\CalendarHelper;

class AgendaPageProvider
{
    public function __construct(
        private AgendaProvider $agenda,
        private PermissionResolver $resolver,
    ) {
    }

    public function index(CompanyMember $member, CompanyEstablishment $establishment, ?string $week): array
    {
        return [
            'agenda' => $this->agenda->week($establishment, CalendarHelper::parseWeek($week)),
            'canPublish' => $this->resolver->memberCan($member, Permission::SLOTS_MANAGE, $establishment),
            'canManageBookings' => $this->resolver->memberCan($member, Permission::BOOKINGS_MANAGE, $establishment),
        ];
    }
}
<?php

namespace App\Company\Security;

class Permission
{
    public const COMPANY_EDIT = 'company.edit';
    public const COMPANY_BILLING = 'company.billing';
    public const ESTABLISHMENT_CREATE = 'establishment.create';
    public const ESTABLISHMENT_EDIT = 'establishment.edit';
    public const MEMBERS_MANAGE = 'members.manage';
    public const SERVICES_MANAGE = 'services.manage';
    public const SLOTS_MANAGE = 'slots.manage';
    public const BOOKINGS_MANAGE = 'bookings.manage';
    public const PLANNING_VIEW = 'planning.view';

    public const ALL = [
        self::COMPANY_EDIT,
        self::COMPANY_BILLING,
        self::ESTABLISHMENT_CREATE,
        self::ESTABLISHMENT_EDIT,
        self::MEMBERS_MANAGE,
        self::SERVICES_MANAGE,
        self::SLOTS_MANAGE,
        self::BOOKINGS_MANAGE,
        self::PLANNING_VIEW,
    ];

    public const ESTABLISHMENT_ROLES = [
        'manager' => [
            self::ESTABLISHMENT_EDIT,
            self::MEMBERS_MANAGE,
            self::SERVICES_MANAGE,
            self::SLOTS_MANAGE,
            self::BOOKINGS_MANAGE,
            self::PLANNING_VIEW
        ],
        'planner' => [
            self::SERVICES_MANAGE,
            self::SLOTS_MANAGE,
            self::PLANNING_VIEW
        ],
        'receptionist' => [
            self::BOOKINGS_MANAGE,
            self::PLANNING_VIEW
        ],
        'employee' => [
            self::PLANNING_VIEW
        ],
    ];

    public const ON_DEMAND = [
        'planner' => self::BOOKINGS_MANAGE,
        'employee' => self::BOOKINGS_MANAGE
    ];
}
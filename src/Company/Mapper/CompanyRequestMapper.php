<?php

namespace App\Company\Mapper;

use App\Company\Dto\CompanyInput;
use App\Company\Dto\EstablishmentInput;
use NeoPHP\Component\Http\Request\Request;

class CompanyRequestMapper
{
    public static function company(CompanyInput $input, Request $request): CompanyInput
    {
        $input->bookingEmailNotifications = $request->request->getBoolean('bookingEmailNotifications');
        $input->monthlyTips = $request->request->getBoolean('monthlyTips');

        return $input;
    }

    public static function establishmentSettings(EstablishmentInput $input): EstablishmentInput
    {
        $input->managerMemberId = null;
        $input->siret = null;

        return $input;
    }

    public static function signupEstablishment(EstablishmentInput $input): EstablishmentInput
    {
        $input->managerMemberId = null;

        return $input;
    }
}
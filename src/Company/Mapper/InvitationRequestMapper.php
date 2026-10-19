<?php

namespace App\Company\Mapper;

use App\Company\Dto\AcceptInvitationInput;
use NeoPHP\Component\Http\Request\Request;

class InvitationRequestMapper
{
    public static function accept(Request $request): AcceptInvitationInput
    {
        $input = new AcceptInvitationInput();
        $input->firstName = trim($request->request->getString('firstName')) ?: null;
        $input->lastName = trim($request->request->getString('lastName')) ?: null;
        $input->password = $request->request->getString('password') ?: null;

        return $input;
    }
}
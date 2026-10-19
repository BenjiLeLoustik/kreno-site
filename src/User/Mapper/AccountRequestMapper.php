<?php

namespace App\User\Mapper;

use App\User\Dto\ChangePasswordInput;
use App\User\Dto\DeleteAccountInput;
use App\User\Dto\ProfileInput;
use App\User\Dto\SettingsInput;
use NeoPHP\Component\Http\Request\Request;

class AccountRequestMapper
{
    public static function profile(Request $request): ProfileInput
    {
        $input = new ProfileInput();
        $input->firstName = trim($request->request->getString('firstName'));
        $input->lastName = trim($request->request->getString('lastName'));
        $input->phone = trim($request->request->getString('phone'));
        $input->email = trim($request->request->getString('email'));

        return $input;
    }

    public static function password(Request $request): ChangePasswordInput
    {
        $input = new ChangePasswordInput();
        $input->currentPassword = $request->request->getString('currentPassword');
        $input->newPassword = $request->request->getString('newPassword');
        $input->newPasswordConfirmation = $request->request->getString('confirmPassword');

        return $input;
    }

    public static function settings(Request $request): SettingsInput
    {
        $input = new SettingsInput();
        $input->emailNotifications = $request->request->getBoolean('emailNotifications');

        return $input;
    }

    public static function deletion(Request $request): DeleteAccountInput
    {
        $input = new DeleteAccountInput();
        $input->confirmation = $request->request->getString('confirmation');

        return $input;
    }
}
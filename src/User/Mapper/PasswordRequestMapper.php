<?php

namespace App\User\Mapper;

use App\User\Dto\ForgotPasswordInput;
use App\User\Dto\ResetPasswordInput;
use NeoPHP\Component\Http\Request\Request;

class PasswordRequestMapper
{
    public static function forgot(Request $request): ForgotPasswordInput
    {
        $input = new ForgotPasswordInput();
        $input->email = mb_strtolower(trim($request->request->getString('email')));

        return $input;
    }

    public static function reset(Request $request): ResetPasswordInput
    {
        $input = new ResetPasswordInput();
        $input->password = $request->request->getString('password');
        $input->passwordConfirmation = $request->request->getString('passwordConfirmation');

        return $input;
    }
}
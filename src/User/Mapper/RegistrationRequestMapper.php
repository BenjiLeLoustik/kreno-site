<?php

namespace App\User\Mapper;

use App\User\Dto\RegistrationInput;
use NeoPHP\Component\Http\Request\Request;

class RegistrationRequestMapper
{
    public static function registration(Request $request): RegistrationInput
    {
        $input = new RegistrationInput();
        $input->firstName = trim($request->request->getString('firstName'));
        $input->lastName = trim($request->request->getString('lastName')) ?: null;
        $input->email = trim($request->request->getString('email'));
        $input->phone = trim($request->request->getString('phone')) ?: null;
        $input->password = $request->request->getString('password');
        $input->acceptTerms = $request->request->getBoolean('acceptTerms');

        return $input;
    }

    public static function old(RegistrationInput $input): array
    {
        return [
            'firstName' => $input->firstName,
            'lastName' => $input->lastName,
            'email' => $input->email,
            'phone' => $input->phone,
        ];
    }
}
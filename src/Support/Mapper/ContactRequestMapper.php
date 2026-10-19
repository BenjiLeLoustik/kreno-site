<?php

namespace App\Support\Mapper;

use App\Support\Dto\ContactInput;
use App\Support\Dto\ContactReplyInput;
use NeoPHP\Component\Http\Request\Request;

class ContactRequestMapper
{
    public static function reply(Request $request): ContactReplyInput
    {
        $input = new ContactReplyInput();
        $input->body = trim($request->request->getString('body')) ?: null;

        return $input;
    }

    public static function contact(Request $request): ContactInput
    {
        $input = new ContactInput();
        $input->topic = $request->request->getString('topic') ?: null;
        $input->reference = strtoupper(trim($request->request->getString('reference'))) ?: null;
        $input->email = trim($request->request->getString('email')) ?: null;
        $input->message = trim($request->request->getString('message')) ?: null;

        return $input;
    }

    public static function old(ContactInput $input): array
    {
        return [
            'topic' => $input->topic,
            'reference' => $input->reference,
            'email' => $input->email,
            'message' => $input->message,
        ];
    }
}
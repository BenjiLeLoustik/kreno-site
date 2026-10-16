<?php

namespace App\Shared\Helper;

class ValidationPattern
{
    public const PASSWORD = '/^(?=.*[A-Za-z])(?=.*\d).{12,}$/';

    public const PASSWORD_MESSAGE = 'Le mot de passe doit faire au moins 12 caractères, avec des lettres et des chiffres.';

    public const PHONE = '/^\+?[0-9 .-]{10,20}$/';

    public const TIME = '/^([01]\d|2[0-3]):[0-5]\d$/';

    public const BOOKING_REFERENCE = '/^KR-\d{6}$/';
}
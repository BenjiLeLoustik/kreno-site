<?php

namespace App\User\Dto;

use NeoPHP\Component\Validator\Constraint as Assert;

class DeleteAccountInput
{
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^\s*supprimer\s*$/i', message: 'Tapez SUPPRIMER pour confirmer.')]
    public string $confirmation = '';
}
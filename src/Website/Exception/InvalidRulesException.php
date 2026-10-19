<?php

namespace App\Website\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;
use App\Website\Enum\RuleError;

class InvalidRulesException extends BusinessRuleException
{
    public function __construct(array $errors)
    {
        parent::__construct(
            Translation::translate('Règles invalides : {errors}.', ['errors' => $this->join($errors)])
        );
    }

    private function join(array $errors): string
    {
        $messages = array_map(static fn (RuleError $error): string => $error->message(), $errors);

        return array_reduce(
            array_slice($messages, 1),
            static fn (string $previous, string $next): string => Translation::translate(
                '{previous} ; {next}',
                ['previous' => $previous, 'next' => $next],
            ),
            $messages[0] ?? '',
        );
    }
}
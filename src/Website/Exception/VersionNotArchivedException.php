<?php

namespace App\Website\Exception;

use App\Shared\Exception\BusinessRuleException;
use App\Shared\Helper\Translation;

class VersionNotArchivedException extends BusinessRuleException
{
    public function __construct(string $code, string $status)
    {
        parent::__construct(
            Translation::translate('Seule une version archivée peut être restaurée. La {code} est {status}.'),
            [
                'code' => $code,
                'status' => $status
            ],
        );
    }
}
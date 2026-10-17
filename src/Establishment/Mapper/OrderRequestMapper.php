<?php

namespace App\Establishment\Mapper;

use App\Establishment\Dto\OrderInput;

class OrderRequestMapper
{
    public static function ids(OrderInput $input): array
    {
        return array_map('intval', $input->ids);
    }
}
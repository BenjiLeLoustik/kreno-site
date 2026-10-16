<?php

namespace App\Booking\Mapper;

use App\Booking\Dto\BookingInput;
use App\Booking\Dto\CancellationInput;
use NeoPHP\Component\Http\Request\Request;

class BookingRequestMapper
{
    public static function booking(Request $request): BookingInput
    {
        $input = new BookingInput();
        $input->firstName = self::text($request, 'firstName');
        $input->phone = self::text($request, 'phone');
        $input->email = self::text($request, 'email');
        $input->note = self::text($request, 'note');

        return $input;
    }

    public static function cancellation(Request $request): CancellationInput
    {
        $input = new CancellationInput();
        $input->reason = self::text($request, 'reason');

        return $input;
    }

    public static function oldBooking(int $slotId, BookingInput $input): array
    {
        return [
            'slot' => $slotId,
            'values' => [
                'firstName' => $input->firstName,
                'phone' => $input->phone,
                'email' => $input->email,
                'note' => $input->note,
            ],
        ];
    }

    private static function text(Request $request, string $field): ?string
    {
        return trim($request->request->getString($field)) ?: null;
    }
}
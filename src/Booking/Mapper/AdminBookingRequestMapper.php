<?php

namespace App\Booking\Mapper;

use App\Booking\Enum\AdminBookingTab;
use NeoPHP\Component\Http\Request\Request;

class AdminBookingRequestMapper
{
    public static function tab(Request $request): AdminBookingTab
    {
        return AdminBookingTab::of($request->query->getString('tab', AdminBookingTab::ALL->value));
    }

    public static function search(Request $request): string
    {
        return trim($request->query->getString('q'));
    }

    public static function page(Request $request): int
    {
        return max(1, $request->query->getInt('page', 1));
    }
}
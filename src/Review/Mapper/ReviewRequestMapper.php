<?php

namespace App\Review\Mapper;

use App\Review\Enum\AdminReviewFilter;
use App\Review\Enum\ProReviewTab;
use NeoPHP\Component\Http\Request\Request;

class ReviewRequestMapper
{
    public static function proTab(Request $request): ProReviewTab
    {
        return ProReviewTab::of($request->query->getString('tab'));
    }

    public static function adminFilter(Request $request): AdminReviewFilter
    {
        return AdminReviewFilter::of($request->query->getString('status'));
    }

    public static function proReturn(Request $request): array
    {
        $tab = ProReviewTab::of($request->request->getString('tab'));

        if ($tab === ProReviewTab::ALL) {
            return [];
        }

        return [
            'tab' => $tab->value
        ];
    }

    public static function adminReturn(Request $request, int $id): array
    {
        return [
            'id' => $id,
            'status' => AdminReviewFilter::of($request->request->getString('status'))->value
        ];
    }
}
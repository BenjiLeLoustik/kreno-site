<?php

namespace App\Report\Mapper;

use App\Report\Dto\CustomerReportInput;
use App\Report\Dto\ReportMessageInput;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Request\UploadedFile;

class ReportRequestMapper
{
    public static function customer(Request $request): CustomerReportInput
    {
        $input = new CustomerReportInput();
        $input->booking = trim($request->request->getString('booking')) ?: null;
        $input->reason = $request->request->getString('reason') ?: null;
        $input->description = trim($request->request->getString('description')) ?: null;

        return $input;
    }

    public static function attachment(Request $request): ?UploadedFile
    {
        $file = $request->files->get('attachment');

        if (!$file instanceof UploadedFile || !$file->isValid()) {
            return null;
        }

        return $file;
    }

    public static function message(Request $request): ReportMessageInput
    {
        $input = new ReportMessageInput();
        $input->body = trim($request->request->getString('body')) ?: null;

        return $input;
    }

    public static function selectedBooking(Request $request): ?string
    {
        return $request->query->getString('booking') ?: null;
    }

    public static function old(CustomerReportInput $input): array
    {
        return [
            'booking' => $input->booking,
            'reason' => $input->reason,
            'description' => $input->description,
        ];
    }
}
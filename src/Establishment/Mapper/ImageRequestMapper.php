<?php

namespace App\Establishment\Mapper;

use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Request\UploadedFile;

class ImageRequestMapper
{
    public static function file(Request $request): ?UploadedFile
    {
        $file = $request->files->get('image');

        if (!$file instanceof UploadedFile) {
            return null;
        }

        return $file;
    }

    public static function alt(Request $request): string
    {
        return $request->request->getString('alt');
    }
}
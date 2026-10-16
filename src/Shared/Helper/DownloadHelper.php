<?php

namespace App\Shared\Helper;

use NeoPHP\Component\Http\Response\Response;

class DownloadHelper
{
    public const MIME_TYPES = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'zip' => 'application/zip',
        'csv' => 'text/csv; charset=UTF-8',
    ];

    public static function response(string $content, string $fileName, bool $inline = false): Response
    {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $fallback = preg_replace('/[^A-Za-z0-9._-]/', '_', $fileName) ?? 'fichier';

        return new Response($content, 200, [
            'Content-Type' => self::MIME_TYPES[$extension] ?? 'application/octet-stream',
            'Content-Length' => (string) strlen($content),
            'Content-Disposition' => sprintf('%s; filename="%s"; filename*=UTF-8\'\'%s', $inline ? 'inline' : 'attachment', $fallback, rawurlencode($fileName)),
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
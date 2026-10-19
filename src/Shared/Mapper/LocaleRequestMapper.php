<?php

namespace App\Shared\Mapper;

use NeoPHP\Component\Http\Request\Request;

class LocaleRequestMapper
{
    public const FALLBACK = '/';

    public static function target(Request $request): string
    {
        $referer = (string) $request->headers->get('Referer');
        $parts = parse_url($referer);

        if ($parts === false || ($parts['host'] ?? null) !== $request->getHost()) {
            return self::FALLBACK;
        }

        return self::path($parts);
    }

    private static function path(array $parts): string
    {
        $path = $parts['path'] ?? self::FALLBACK;

        if (!isset($parts['query'])) {
            return $path;
        }

        return $path . '?' . $parts['query'];
    }
}
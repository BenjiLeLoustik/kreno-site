<?php

namespace App\Log\Helper;

use Throwable;

class AppLogHelper
{
    public const TRACE_LENGTH = 20000;

    public static function fingerprint(Throwable $throwable, string $root = ''): string
    {
        return sha1(implode('|', [
            $throwable::class,
            self::file($throwable->getFile(), $root),
            $throwable->getLine(),
            self::normalize($throwable->getMessage()),
        ]));
    }

    public static function normalize(string $message): string
    {
        return (string) preg_replace(['/"[^"]*"/', "/'[^']*'/", '/\d+/'], ['"?"', "'?'", '?'], $message);
    }

    public static function file(?string $file, string $root = ''): ?string
    {
        if ($file === null || $file === '') {
            return null;
        }

        $file = str_replace('\\', '/', $file);
        $root = rtrim(str_replace('\\', '/', $root), '/');

        if ($root !== '' && str_starts_with($file, $root . '/')) {
            $file = substr($file, strlen($root) + 1);
        }

        return mb_strlen($file) > 255 ? '…' . mb_substr($file, -254) : $file;
    }

    public static function path(string $path): string
    {
        return mb_substr((string) preg_replace('#/[A-Za-z0-9_\-]{24,}#', '/{token}', $path), 0, 500);
    }

    public static function trace(Throwable $throwable, string $root = ''): string
    {
        $parts = [$throwable->getTraceAsString()];
        $previous = $throwable->getPrevious();

        while ($previous !== null) {
            $parts[] = sprintf("Cause : %s: %s\n%s:%d\n%s", $previous::class, $previous->getMessage(), self::file($previous->getFile(), $root), $previous->getLine(), $previous->getTraceAsString());
            $previous = $previous->getPrevious();
        }

        $trace = implode("\n\n", $parts);

        if ($root !== '') {
            $trace = str_replace([rtrim($root, '/\\') . DIRECTORY_SEPARATOR, str_replace('\\', '/', rtrim($root, '/\\')) . '/'], '', $trace);
        }

        return mb_substr($trace, 0, self::TRACE_LENGTH);
    }
}
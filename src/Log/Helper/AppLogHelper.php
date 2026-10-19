<?php

namespace App\Log\Helper;

use Throwable;

class AppLogHelper
{
    public const TRACE_LENGTH = 20000;

    public const FILE_LENGTH = 255;

    public const PATH_LENGTH = 500;

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

        if (mb_strlen($file) > self::FILE_LENGTH) {
            return '…' . mb_substr($file, 1 - self::FILE_LENGTH);
        }

        return $file;
    }

    public static function path(string $path): string
    {
        $path = (string) preg_replace('#/[A-Za-z0-9_\-]{24,}#', '/{token}', $path);

        return mb_substr($path, 0, self::PATH_LENGTH);
    }

    public static function trace(Throwable $throwable, string $root = ''): string
    {
        $parts = [$throwable->getTraceAsString()];
        $previous = $throwable->getPrevious();

        while ($previous !== null) {
            $parts[] = self::cause($previous, $root);
            $previous = $previous->getPrevious();
        }

        $trace = self::stripRoot(implode("\n\n", $parts), $root);

        return mb_substr($trace, 0, self::TRACE_LENGTH);
    }

    private static function cause(Throwable $previous, string $root): string
    {
        return sprintf(
            "Cause : %s: %s\n%s:%d\n%s",
            $previous::class,
            $previous->getMessage(),
            self::file($previous->getFile(), $root),
            $previous->getLine(),
            $previous->getTraceAsString(),
        );
    }

    private static function stripRoot(string $trace, string $root): string
    {
        if ($root === '') {
            return $trace;
        }

        $root = rtrim($root, '/\\');

        return str_replace(
            [
                $root . DIRECTORY_SEPARATOR,
                str_replace('\\', '/', $root) . '/'
            ],
            '',
            $trace
        );
    }
}
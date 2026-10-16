<?php

namespace App\Shared\Helper;

class StringHelper
{
    public static function slugify(string $value): string
    {
        $value = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $value) ?: strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value);

        return trim($value, '-');
    }

    public static function uniqueSlug(string $value, callable $exists): string
    {
        $base = self::slugify($value) ?: 'element';
        $slug = $base;
        $suffix = 2;

        while ($exists($slug)) {
            $slug = $base . '-' . $suffix++;
        }

        return $slug;
    }
}
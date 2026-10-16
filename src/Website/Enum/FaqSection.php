<?php

namespace App\Website\Enum;

enum FaqSection: string
{
    case BOOKING = 'booking';
    case ACCOUNT = 'account';
    case REPORT = 'report';
    case PRO = 'pro';

    public static function all(array $rules): array
    {
        $sections = [];

        foreach (self::cases() as $section) {
            $sections[] = ['key' => $section->value, 'label' => $section->label(), 'items' => $section->items($rules)];
        }

        return $sections;
    }

    public function label(): string
    {
        return match ($this) {
            self::BOOKING => 'Réserver',
            self::ACCOUNT => 'Mon compte',
            self::REPORT => 'Problème sur place',
            self::PRO => 'Professionnels',
        };
    }

    public function items(array $rules): array
    {
        $items = [];

        foreach (FaqQuestion::cases() as $question) {
            if ($question->section() === $this) {
                $items[] = ['question' => $question->question(), 'answer' => $question->answer($rules)];
            }
        }

        return $items;
    }
}
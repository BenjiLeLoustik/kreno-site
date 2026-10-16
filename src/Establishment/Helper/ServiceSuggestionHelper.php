<?php

namespace App\Establishment\Helper;

class ServiceSuggestionHelper
{
    public const DURATIONS = [15, 30, 45, 60, 90, 120];

    public const SUGGESTIONS = [
        'coiffure' => [
            ['name' => 'Coupe + brushing', 'duration' => 45, 'price' => 3800],
            ['name' => 'Coupe homme', 'duration' => 30, 'price' => 2500],
            ['name' => 'Brushing', 'duration' => 30, 'price' => 2200],
            ['name' => 'Couleur', 'duration' => 90, 'price' => 6500],
            ['name' => 'Balayage', 'duration' => 120, 'price' => 9500],
        ],
        'barbier' => [
            ['name' => 'Taille de barbe', 'duration' => 30, 'price' => 2200],
            ['name' => 'Coupe homme', 'duration' => 30, 'price' => 2500],
            ['name' => 'Coupe + barbe', 'duration' => 45, 'price' => 4000],
            ['name' => 'Rasage à l\'ancienne', 'duration' => 30, 'price' => 3000],
        ],
        'esthetique' => [
            ['name' => 'Soin visage', 'duration' => 30, 'price' => 4500],
            ['name' => 'Manucure', 'duration' => 45, 'price' => 3500],
            ['name' => 'Épilation jambes', 'duration' => 30, 'price' => 3000],
            ['name' => 'Pose de vernis semi-permanent', 'duration' => 60, 'price' => 4000],
        ],
        'osteo-et-massage' => [
            ['name' => 'Séance adulte', 'duration' => 60, 'price' => 6000],
            ['name' => 'Massage relaxant', 'duration' => 60, 'price' => 7000],
            ['name' => 'Massage dos', 'duration' => 30, 'price' => 4000],
        ],
        'garage' => [
            ['name' => 'Vidange', 'duration' => 30, 'price' => 8900],
            ['name' => 'Diagnostic', 'duration' => 45, 'price' => 5900],
            ['name' => 'Changement de plaquettes', 'duration' => 60, 'price' => 12000],
            ['name' => 'Géométrie', 'duration' => 45, 'price' => 7900],
        ],
    ];

    public static function for(?string $categorySlug): array
    {
        return self::SUGGESTIONS[$categorySlug ?? ''] ?? [];
    }
}
<?php

namespace App\Website\Presenter;

use App\Entity\Website\WebsiteRule;
use App\Shared\Helper\DurationHelper;
use App\Website\Service\WebsiteRuleService;

final class WebsiteRulePresenter
{
    private const GROUPS = [
        'booking' => ['Réservations', ['freeCancellationMinutes', 'bookingLeadMinutes', 'maxUpcomingBookingsPerUser']],
        'absence' => ['Absences', ['toleratedNoShows', 'noShowWindowDays', 'suspensionDays']],
        'discount' => ['Remises', ['minDiscount', 'maxDiscount', 'defaultDiscount']],
        'slot' => ['Créneaux', ['publicationWindowDays', 'autoRemovalMinutes']],
    ];

    public function __construct(
        private WebsiteRuleService $rules
    ) {
    }

    public function present(?WebsiteRule $rule, ?WebsiteRule $liveRule): array
    {
        if ($rule === null) {
            return ['groups' => [], 'differences' => [], 'userSummary' => null];
        }

        $values = $this->rules->toArray($rule);
        $liveValues = $liveRule !== null && $liveRule !== $rule ? $this->rules->toArray($liveRule) : null;
        $groups = [];
        $differences = [];

        foreach (self::GROUPS as $key => [$title, $fields]) {
            $items = [];

            foreach ($fields as $field) {
                [$label, $unit] = WebsiteRuleService::LABELS[$field];
                $changed = $liveValues !== null && $liveValues[$field] !== $values[$field];

                if ($changed) {
                    $differences[] = $field;
                }

                $items[] = [
                    'key' => $field,
                    'label' => $label,
                    'unit' => $unit,
                    'value' => $values[$field],
                    'liveValue' => $liveValues[$field] ?? null,
                    'changed' => $changed,
                ];
            }

            $groups[] = ['key' => $key, 'title' => $title, 'rules' => $items];
        }

        return [
            'groups' => $groups,
            'differences' => $differences,
            'userSummary' => sprintf(
                'Annulation gratuite jusqu\'à %s avant le rendez-vous. Après %d absences non prévenues en %d jours, ton compte est suspendu %d jours.',
                DurationHelper::format($values['freeCancellationMinutes']),
                $values['toleratedNoShows'],
                $values['noShowWindowDays'],
                $values['suspensionDays'],
            ),
        ];
    }
}
<?php

namespace App\Website\Presenter;

use App\Entity\Website\WebsiteRule;
use App\Shared\Helper\DurationHelper;
use App\Website\Enum\RuleField;
use App\Website\Enum\RuleGroup;
use App\Website\Enum\WebsiteText;
use App\Website\Service\WebsiteRuleService;

class WebsiteRulePresenter
{
    public function __construct(
        private WebsiteRuleService $rules,
    ) {
    }

    public function present(?WebsiteRule $rule, ?WebsiteRule $liveRule): array
    {
        if ($rule === null) {
            return [
                'groups' => [],
                'differences' => [],
                'userSummary' => null
            ];
        }

        $values = $this->rules->toArray($rule);
        $liveValues = $this->liveValues($rule, $liveRule);
        $groups = [];
        $differences = [];

        foreach (RuleGroup::cases() as $group) {
            $items = $this->items($group, $values, $liveValues);

            foreach ($items as $item) {
                if ($item['changed']) {
                    $differences[] = $item['key'];
                }
            }

            $groups[] = [
                'key' => $group->value,
                'title' => $group->label(),
                'rules' => $items
            ];
        }

        return [
            'groups' => $groups,
            'differences' => $differences,
            'userSummary' => $this->userSummary($values),
        ];
    }

    private function liveValues(WebsiteRule $rule, ?WebsiteRule $liveRule): ?array
    {
        if ($liveRule === null || $liveRule === $rule) {
            return null;
        }

        return $this->rules->toArray($liveRule);
    }

    private function items(RuleGroup $group, array $values, ?array $liveValues): array
    {
        $items = [];

        foreach ($group->fields() as $key) {
            $field = RuleField::from($key);

            $items[] = [
                'key' => $key,
                'label' => $field->label(),
                'unit' => $field->unit(),
                'value' => $values[$key],
                'liveValue' => $liveValues[$key] ?? null,
                'changed' => $liveValues !== null && $liveValues[$key] !== $values[$key],
            ];
        }

        return $items;
    }

    private function userSummary(array $values): string
    {
        return WebsiteText::RULES_SUMMARY->text(
            DurationHelper::format($values['freeCancellationMinutes']),
            $values['toleratedNoShows'],
            $values['noShowWindowDays'],
            $values['suspensionDays'],
        );
    }
}
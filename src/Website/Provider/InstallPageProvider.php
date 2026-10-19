<?php

namespace App\Website\Provider;

use App\Website\Enum\InstallIdentityField;
use App\Website\Enum\InstallPlan;
use App\Website\Enum\InstallRuleField;
use NeoPHP\Component\Session\SessionManagerInterface;

class InstallPageProvider
{
    public const OLD_INPUT = 'app.install.old';

    public function __construct(
        private SessionManagerInterface $session,
    ) {
    }

    public function form(bool $needsAdmin): array
    {
        $old = $this->pullOld();

        return [
            'needsAdmin' => $needsAdmin,
            'identity' => ($old['identity'] ?? []) + InstallIdentityField::defaults(),
            'rules' => $this->rules($old['rules'] ?? []),
            'plans' => $this->plans($old['plans'] ?? []),
            'admin' => ($old['admin'] ?? []) + ['firstName' => null, 'email' => null],
        ];
    }

    public function remember(array $old): void
    {
        $this->session->set(self::OLD_INPUT, $old);
    }

    private function pullOld(): array
    {
        $old = $this->session->get(self::OLD_INPUT);
        $this->session->remove(self::OLD_INPUT);

        if (!is_array($old)) {
            return [];
        }

        return $old;
    }

    private function rules(array $old): array
    {
        $rules = [];

        foreach (InstallRuleField::cases() as $field) {
            $rules[] = [
                'label' => $field->label(),
                'unit' => $field->unit(),
                'value' => $field->defaultValue(),
                'key' => $field->value,
                'current' => $old[$field->value] ?? $field->defaultValue(),
            ];
        }

        return $rules;
    }

    private function plans(array $old): array
    {
        $plans = [];

        foreach (InstallPlan::cases() as $index => $plan) {
            $plans[] = array_merge($plan->defaults(), $old[$index] ?? []);
        }

        return $plans;
    }
}
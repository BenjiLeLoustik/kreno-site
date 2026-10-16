<?php

namespace App\Website\Mapper;

use App\Website\Dto\WebsiteIdentityInput;
use App\Website\Dto\WebsitePlanInput;
use App\Website\Dto\WebsiteRuleInput;
use App\Website\Enum\InstallIdentityField;
use App\Website\Enum\InstallRuleField;
use App\Website\Enum\PlanBilling;
use App\Website\Service\WebsiteInstallService;
use NeoPHP\Component\Http\Request\Request;

class InstallRequestMapper
{
    public static function section(Request $request, string $name): array
    {
        $values = $request->request->get($name);

        if (!is_array($values)) {
            return [];
        }

        return $values;
    }

    public static function planValues(Request $request): array
    {
        return array_values(array_filter($request->request->get('plans') ?? [], 'is_array'));
    }

    public static function identity(array $values): WebsiteIdentityInput
    {
        $input = new WebsiteIdentityInput();

        foreach (InstallIdentityField::cases() as $field) {
            $input->{$field->value} = self::text($values[$field->value] ?? null);
        }

        return $input;
    }

    public static function rules(array $values): WebsiteRuleInput
    {
        $input = new WebsiteRuleInput();

        foreach (InstallRuleField::cases() as $field) {
            $input->{$field->value} = self::integer($values[$field->value] ?? null);
        }

        return $input;
    }

    public static function plans(array $planValues): array
    {
        $plans = [];

        foreach ($planValues as $values) {
            $plan = self::plan($values);

            if ($plan->active || ($plan->code !== null && $plan->name !== null)) {
                $plans[] = $plan;
            }
        }

        return $plans;
    }

    public static function admin(array $values): array
    {
        return [
            'email' => trim((string) ($values['email'] ?? '')),
            'firstName' => trim((string) ($values['firstName'] ?? '')),
            'password' => (string) ($values['password'] ?? ''),
        ];
    }

    public static function old(array $identity, array $rules, array $plans, array $admin): array
    {
        unset($admin['password']);

        return ['identity' => $identity, 'rules' => $rules, 'plans' => $plans, 'admin' => $admin];
    }

    private static function plan(array $values): WebsitePlanInput
    {
        $input = new WebsitePlanInput();
        $input->billing = PlanBilling::tryFrom((string) ($values['billing'] ?? '')) ?? PlanBilling::PER_BOOKING;
        $input->active = (bool) ($values['active'] ?? false);
        $input->code = self::text($values['code'] ?? null);
        $input->name = self::text($values['name'] ?? null);
        $input->description = self::text($values['description'] ?? null);
        $input->price = self::cents($values['price'] ?? null) ?? 0;
        $input->yearlyPrice = self::cents($values['yearlyPrice'] ?? null);
        $input->vatRate = WebsiteInstallService::DEFAULT_VAT_RATE;
        $input->trialDays = (int) ($values['trialDays'] ?? 0);
        $input->commitmentMonths = 0;
        $input->maxEstablishments = self::integer($values['maxEstablishments'] ?? null);
        $input->unlimitedEstablishments = $input->maxEstablishments === null;
        $input->commissionFree = (bool) ($values['commissionFree'] ?? false);
        $input->featured = (bool) ($values['featured'] ?? false);
        $input->advancedStats = (bool) ($values['advancedStats'] ?? false);

        return $input;
    }

    private static function text(mixed $value): ?string
    {
        return trim((string) $value) ?: null;
    }

    private static function integer(mixed $value): ?int
    {
        if (!is_numeric($value)) {
            return null;
        }

        return (int) $value;
    }

    private static function cents(mixed $value): ?int
    {
        $value = str_replace([' ', ','], ['', '.'], (string) $value);

        if (!is_numeric($value)) {
            return null;
        }

        return (int) round((float) $value * 100);
    }
}
<?php

namespace App\Company\Mapper;

use App\Billing\Dto\SignupPaymentInput;
use App\Billing\Mapper\BillingRequestMapper;
use App\Company\Dto\ProAccountInput;
use App\Company\Dto\VerificationCodeInput;
use App\Company\Enum\CompanyKind;
use App\Entity\User\User;
use App\Establishment\Dto\PageInput;
use App\Establishment\Mapper\OpeningHoursRequestMapper;
use NeoPHP\Component\Http\Request\Request;

class ProSignupRequestMapper
{
    public static function account(Request $request, ?User $user): ProAccountInput
    {
        $input = new ProAccountInput();
        $input->kind = CompanyKind::tryFrom($request->request->getString('kind')) ?? CompanyKind::SOLO;
        $input->firstName = trim($request->request->getString('firstName'));
        $input->lastName = trim($request->request->getString('lastName'));
        $input->phone = trim($request->request->getString('phone'));
        $input->email = $user?->getEmail() ?? trim($request->request->getString('email'));
        $input->password = self::password($request, $user);

        return $input;
    }

    public static function accountOld(ProAccountInput $input): array
    {
        return [
            'kind' => $input->kind->value,
            'firstName' => $input->firstName,
            'lastName' => $input->lastName,
            'email' => $input->email,
            'phone' => $input->phone,
        ];
    }

    public static function code(VerificationCodeInput $input): VerificationCodeInput
    {
        $input->code = preg_replace('/\D/', '', $input->code) ?? '';

        return $input;
    }

    public static function scalars(Request $request): array
    {
        return array_map(self::scalar(...), $request->request->all());
    }

    public static function page(Request $request): PageInput
    {
        $input = new PageInput();
        $input->description = trim($request->request->getString('description')) ?: null;
        $input->hours = OpeningHoursRequestMapper::hours($request->request->get('hours'));

        return $input;
    }

    public static function payment(Request $request): SignupPaymentInput
    {
        $input = new SignupPaymentInput();
        $input->billing = BillingRequestMapper::infoFromValues(self::billing($request));
        $input->setupReference = trim($request->request->getString('setupReference')) ?: null;
        $input->mandateAccepted = $request->request->getBoolean('mandateAccepted');
        $input->acceptTerms = $request->request->getBoolean('acceptTerms');
        $input->promoCode = trim($request->request->getString('promoCode')) ?: null;

        return $input;
    }

    public static function paymentOld(Request $request, SignupPaymentInput $input): array
    {
        return [
            'billing' => self::billing($request),
            'promoCode' => $input->promoCode
        ];
    }

    private static function billing(Request $request): array
    {
        $billing = $request->request->get('billing');

        if (!is_array($billing)) {
            return [];
        }

        return $billing;
    }

    private static function password(Request $request, ?User $user): string
    {
        if ($user !== null) {
            return 'Compte' . bin2hex(random_bytes(6)) . '1';
        }

        return $request->request->getString('password');
    }

    private static function scalar(mixed $value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }

        return (string) $value;
    }
}
<?php

namespace App\Company\Provider;

use App\Company\Enum\SignupRecapLine;
use App\Company\Enum\SignupRecapText;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\User\User;
use App\Repository\Company\CompanySubscriptionRepository;

class ProSignupRecapProvider
{
    public function __construct(
        private CompanySubscriptionRepository $subscriptions,
    ) {
    }

    public function recap(User $user, CompanyEstablishment $establishment): array
    {
        $account = trim($user->getFirstName() . ' ' . $user->getLastName());

        return [
            SignupRecapLine::ACCOUNT->row(SignupRecapText::PAIR->text($account, $user->getEmail())),
            SignupRecapLine::ESTABLISHMENT->row(
                SignupRecapText::PAIR->text($establishment->getName(), $establishment->getLocality()),
            ),
            SignupRecapLine::SIRET->row($establishment->getSiret()),
            SignupRecapLine::SERVICES->row($this->services($establishment)),
            SignupRecapLine::PAGE->row($this->page($establishment)),
            SignupRecapLine::PLAN->row($this->plan($establishment)),
        ];
    }

    private function services(CompanyEstablishment $establishment): string
    {
        $count = $establishment->getServices()->count();

        return SignupRecapText::SERVICES->text($count, $this->plural($count), $this->discount($establishment));
    }

    private function discount(CompanyEstablishment $establishment): string
    {
        if ($establishment->getDefaultDiscount() === null) {
            return '';
        }

        return SignupRecapText::DISCOUNT->text($establishment->getDefaultDiscount());
    }

    private function page(CompanyEstablishment $establishment): string
    {
        $images = $establishment->getImages()->count();
        $days = $this->openDays($establishment);

        return SignupRecapText::PAGE->text($images, $this->plural($images), $days, $this->plural($days));
    }

    private function openDays(CompanyEstablishment $establishment): int
    {
        $days = [];

        foreach ($establishment->getOpeningHours() as $hour) {
            $days[$hour->getWeekday()->value] = true;
        }

        return count($days);
    }

    private function plan(CompanyEstablishment $establishment): ?string
    {
        $company = $establishment->getCompany();

        if ($company === null) {
            return null;
        }

        return $this->subscriptions->findPending($company)?->getPlan()?->getName();
    }

    private function plural(int $count): string
    {
        if ($count > 1) {
            return 's';
        }

        return '';
    }
}
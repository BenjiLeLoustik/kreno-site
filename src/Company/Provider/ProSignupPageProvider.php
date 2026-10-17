<?php

namespace App\Company\Provider;

use App\Billing\Enum\BillingPeriod;
use App\Catalog\Presenter\CategoryPresenter;
use App\Catalog\Presenter\CityPresenter;
use App\Company\Enum\ProSignupStep;
use App\Company\Enum\SignupKind;
use App\Company\Service\ProSignupFlow;
use App\Company\Service\ProSignupService;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\User\User;
use App\Establishment\Helper\ServiceSuggestionHelper;
use App\Establishment\Presenter\EstablishmentPagePresenter;
use App\Repository\CategoryRepository;
use App\Repository\CityRepository;
use App\Shared\Exception\BusinessRuleException;
use App\Website\Provider\WebsiteProvider;
use NeoPHP\Component\Session\SessionManagerInterface;

class ProSignupPageProvider
{
    public const OLD_INPUT = 'pro.signup.old';

    public const RANGES_PER_DAY = 2;

    public const EMPTY_SERVICE_ROWS = 3;

    public const HIDDEN_FIELDS = ['password', '_token', '_csrf_token'];

    public const EMPTY_ESTABLISHMENT = [
        'name' => null,
        'siret' => null,
        'categorySlug' => null,
        'citySlug' => null,
        'addressLine1' => null,
        'addressLine2' => null,
        'postalCode' => null,
        'locality' => null,
        'latitude' => null,
        'longitude' => null,
        'phone' => null,
    ];

    public function __construct(
        private ProSignupFlow $flow,
        private ProSignupService $signup,
        private ProSignupPlanProvider $plans,
        private ProSignupRecapProvider $recaps,
        private SessionManagerInterface $session,
        private WebsiteProvider $website,
        private CityRepository $cities,
        private CategoryRepository $categories,
        private CityPresenter $cityPresenter,
        private CategoryPresenter $categoryPresenter,
        private EstablishmentPagePresenter $pagePresenter,
    ) {
    }

    public function mustRedirect(ProSignupStep $step, ProSignupStep $current): bool
    {
        if ($step->position() > $current->position()) {
            return true;
        }

        if ($current === ProSignupStep::PENDING && $step !== ProSignupStep::PENDING) {
            return true;
        }

        return $step !== $current && $step->isLocked();
    }

    public function show(ProSignupStep $step, ProSignupStep $current, ?User $user, ?BillingPeriod $period): array
    {
        $old = $this->pullOld($step);

        return ['signup' => $this->progress($step, $current, $user)] + $this->data($step, $user, $old, $period);
    }

    public function remember(ProSignupStep $step, array $values): void
    {
        foreach (self::HIDDEN_FIELDS as $field) {
            unset($values[$field]);
        }

        $this->session->set(self::OLD_INPUT, ['step' => $step->value, 'values' => $values]);
    }

    public function siret(string $siret): array
    {
        try {
            return ['found' => true, 'establishment' => $this->signup->lookupSiret($siret)->toArray()];
        } catch (BusinessRuleException $exception) {
            return ['found' => false, 'error' => $exception->getMessage()];
        }
    }

    private function pullOld(ProSignupStep $step): array
    {
        $old = $this->session->get(self::OLD_INPUT);
        $this->session->remove(self::OLD_INPUT);

        if (!is_array($old) || ($old['step'] ?? null) !== $step->value) {
            return [];
        }

        return $old['values'];
    }

    private function progress(ProSignupStep $step, ProSignupStep $current, ?User $user): array
    {
        $position = $step->position();
        $reached = $current->position();
        $tracked = ProSignupStep::tracked();
        $steps = [];

        foreach ($tracked as $index => $item) {
            $steps[] = [
                'label' => $item->label(),
                'sub' => $item->sub(),
                'key' => $item->value,
                'number' => $index + 1,
                'state' => $this->state($item, $step, $index, $reached),
                'reachable' => $index <= $reached && !$item->isLocked() && $current !== ProSignupStep::PENDING,
            ];
        }

        return [
            'step' => $step->value,
            'steps' => $steps,
            'number' => min($position + 1, count($tracked)),
            'total' => count($tracked),
            'previous' => $this->previous($step),
            'loggedIn' => $user !== null,
        ];
    }

    private function state(ProSignupStep $item, ProSignupStep $step, int $index, int $reached): string
    {
        if ($item === $step) {
            return 'current';
        }

        if ($index < $reached) {
            return 'done';
        }

        return 'todo';
    }

    private function previous(ProSignupStep $step): ?string
    {
        $position = $step->position();

        if ($position === 0 || $step === ProSignupStep::PENDING) {
            return null;
        }

        $previous = ProSignupStep::ordered()[$position - 1];

        if ($previous->isLocked()) {
            return null;
        }

        return $previous->value;
    }

    private function data(ProSignupStep $step, ?User $user, array $old, ?BillingPeriod $period): array
    {
        return match ($step) {
            ProSignupStep::ACCOUNT => $this->account($user, $old),
            ProSignupStep::VERIFY => ['email' => $user?->getEmail()],
            ProSignupStep::ESTABLISHMENT => $this->establishment($user, $old),
            ProSignupStep::SERVICES => $this->services($user),
            ProSignupStep::PAGE => $this->page($user),
            ProSignupStep::PLAN => $this->plan($user, $period),
            ProSignupStep::PAYMENT => $this->payment($user, $old),
            ProSignupStep::PENDING, ProSignupStep::DONE => $this->pending($user),
        };
    }

    private function account(?User $user, array $old): array
    {
        return [
            'existing' => $user !== null,
            'kinds' => SignupKind::choices(),
            'form' => $old + [
                    'kind' => SignupKind::SOLO->value,
                    'firstName' => $user?->getFirstName(),
                    'lastName' => $user?->getLastName(),
                    'email' => $user?->getEmail(),
                    'phone' => $user?->getPhone(),
                ],
        ];
    }

    private function establishment(?User $user, array $old): array
    {
        return [
            'form' => $old + $this->establishmentForm($this->flow->draft($user)),
            'categories' => $this->categoryPresenter->summaries($this->categories->findVisible()),
            'cities' => $this->cityPresenter->summaries($this->cities->findAvailableForPros()),
        ];
    }

    private function establishmentForm(?CompanyEstablishment $establishment): array
    {
        if ($establishment === null) {
            return self::EMPTY_ESTABLISHMENT;
        }

        return $this->pagePresenter->settings($establishment)['form'] + ['siret' => $establishment->getSiret()];
    }

    private function services(?User $user): array
    {
        $establishment = $this->flow->establishment($this->flow->member($user));
        $discount = $this->website->getRule()->getDefaultDiscount();

        return [
            'services' => $this->pagePresenter->signupServices($establishment, $discount),
            'emptyRows' => self::EMPTY_SERVICE_ROWS,
            'durations' => ServiceSuggestionHelper::DURATIONS,
        ];
    }

    private function page(?User $user): array
    {
        $establishment = $this->flow->establishment($this->flow->member($user));

        return [
            'page' => $this->pagePresenter->signupPage($establishment),
            'rangesPerDay' => self::RANGES_PER_DAY,
        ];
    }

    private function plan(?User $user, ?BillingPeriod $period): array
    {
        $company = $this->flow->company($this->flow->member($user));

        return ['plan' => $this->plans->plan($company, $user, $period)];
    }

    private function payment(?User $user, array $old): array
    {
        $company = $this->flow->company($this->flow->member($user));

        return [
            'plan' => $this->plans->plan($company, $user),
            'old' => $old + ['billing' => [], 'promoCode' => null],
            'recap' => $this->recap($user),
        ];
    }

    private function pending(?User $user): array
    {
        $establishment = $this->flow->draft($user);

        return [
            'firstName' => $user?->getFirstName(),
            'email' => $user?->getEmail(),
            'establishmentName' => $establishment?->getName(),
            'statusLabel' => $establishment?->getStatus()->label(),
            'recap' => $this->recap($user),
            'companyName' => $establishment?->getCompany()?->getName(),
        ];
    }

    private function recap(?User $user): ?array
    {
        $establishment = $this->flow->draft($user);

        if ($user === null || $establishment === null) {
            return null;
        }

        return $this->recaps->recap($user, $establishment);
    }
}
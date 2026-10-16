<?php

namespace App\Controller\Pro;

use App\Billing\Dto\BillingInfoInput;
use App\Billing\Dto\PaymentSetupInput;
use App\Billing\Dto\PlanChoiceInput;
use App\Billing\Dto\PromoCheckInput;
use App\Billing\Dto\SignupPaymentInput;
use App\Billing\Enum\BillingPeriod;
use App\Billing\Enum\PaymentMethodType;
use App\Billing\Exception\PaymentGatewayException;
use App\Billing\Gateway\PaymentGatewayInterface;
use App\Billing\Presenter\PlanPresenter;
use App\Billing\Service\BillingInfoService;
use App\Billing\Service\PaymentMethodService;
use App\Billing\Service\PlanCatalog;
use App\Billing\Service\PriceCalculator;
use App\Billing\Service\PromoCodeService;
use App\Billing\Service\SignupBillingService;
use App\Billing\Service\SubscriptionService;
use App\Catalog\Presenter\CategoryPresenter;
use App\Catalog\Presenter\CityPresenter;
use App\Company\Dto\EstablishmentInput;
use App\Company\Dto\ProAccountInput;
use App\Company\Dto\VerificationCodeInput;
use App\Company\Enum\CompanyKind;
use App\Company\Enum\CompanyRole;
use App\Company\Provider\CurrentCompanyProvider;
use App\Company\Security\PermissionResolver;
use App\Company\Service\ProSignupService;
use App\Entity\Company\Company;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Company\CompanyMember;
use App\Entity\Establishment\EstablishmentOpeningHour;
use App\Entity\User\User;
use App\Establishment\Dto\OpeningHourInput;
use App\Establishment\Dto\PageInput;
use App\Establishment\Dto\ServiceInput;
use App\Establishment\Dto\ServicesInput;
use App\Establishment\Enum\EstablishmentStatus;
use App\Establishment\Helper\ServiceSuggestionHelper;
use App\Establishment\Presenter\EstablishmentPagePresenter;
use App\Establishment\Service\ImageService;
use App\Establishment\Service\PageService;
use App\Establishment\Service\ServiceCatalogService;
use App\Repository\CategoryRepository;
use App\Repository\CityRepository;
use App\Repository\Company\CompanyPaymentMethodRepository;
use App\Repository\Company\CompanySubscriptionRepository;
use App\Repository\Establishment\EstablishmentImageRepository;
use App\Shared\Exception\BusinessRuleException;
use App\Website\Enum\PlanBilling;
use App\Website\Provider\WebsiteProvider;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Request\UploadedFile;
use NeoPHP\Component\Http\Response\JsonResponse;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Serializer\Attribute\MapRequestPayload;
use NeoPHP\Component\Session\SessionManagerInterface;

class SignupController extends AbstractProController
{
    public const STEPS = [
        'account' => ['label' => 'Votre compte', 'sub' => 'Identité et connexion'],
        'verify' => ['label' => 'Vérification', 'sub' => 'Code reçu par e-mail'],
        'establishment' => ['label' => 'Établissement', 'sub' => 'SIRET et adresse'],
        'services' => ['label' => 'Prestations', 'sub' => 'Ce que vous proposez'],
        'page' => ['label' => 'Page publique', 'sub' => 'Photos, description, horaires'],
        'plan' => ['label' => 'Formule', 'sub' => 'Choix de l\'abonnement'],
        'payment' => ['label' => 'Paiement', 'sub' => 'Carte ou prélèvement'],
    ];

    public const KINDS = [
        ['value' => 'solo', 'title' => 'Je travaille seul·e', 'text' => 'Indépendant, auto-entrepreneur'],
        ['value' => 'team', 'title' => 'J’ai une équipe', 'text' => 'Salon, cabinet, atelier avec employés'],
    ];

    public const ORDER = ['account', 'verify', 'establishment', 'services', 'page', 'plan', 'payment', 'pending'];

    public const LOCKED = ['account', 'verify'];

    public const PAGE_SKIPPED = 'pro.signup.page_skipped';

    public const OLD_INPUT = 'pro.signup.old';

    public const RANGES_PER_DAY = 2;

    public const EMPTY_SERVICE_ROWS = 3;

    public function __construct(
        CurrentCompanyProvider $context,
        PermissionResolver $resolver,
        private ProSignupService $signup,
        private ServiceCatalogService $catalog,
        private PageService $pages,
        private ImageService $imageService,
        private WebsiteProvider $website,
        private SessionManagerInterface $session,
        private CityRepository $cities,
        private CategoryRepository $categories,
        private EstablishmentImageRepository $images,
        private CityPresenter $cityPresenter,
        private CategoryPresenter $categoryPresenter,
        private EstablishmentPagePresenter $pagePresenter,
        private PaymentGatewayInterface $gateway,
        private SubscriptionService $subscriptionService,
        private SignupBillingService $signupBilling,
        private PaymentMethodService $methodService,
        private BillingInfoService $billingInfo,
        private PlanCatalog $planCatalog,
        private PriceCalculator $prices,
        private PromoCodeService $promos,
        private CompanySubscriptionRepository $subscriptions,
        private CompanyPaymentMethodRepository $methods,
        private PlanPresenter $planPresenter,
    ) {
        parent::__construct($context, $resolver);
    }

    #[Route('/pro/signup', name: 'pro_signup', methods: ['GET'])]
    public function start(): RedirectResponse
    {
        $step = $this->currentStep();

        return $step === 'done' ? $this->redirectToRoute('pro_dashboard') : $this->redirectToRoute('pro_signup_step', ['step' => $step]);
    }

    #[Route('/pro/signup/{step}', name: 'pro_signup_step', methods: ['GET'], requirements: ['step' => 'account|verify|establishment|services|page|plan|payment|pending'])]
    public function show(Request $request, string $step): Response
    {
        $current = $this->currentStep();

        if ($current === 'done') {
            return $this->redirectToRoute('pro_dashboard');
        }

        $position = array_search($step, self::ORDER, true);
        $reached = array_search($current, self::ORDER, true);

        if ($position > $reached || ($current === 'pending' && $step !== 'pending') || ($step !== $current && in_array($step, self::LOCKED, true))) {
            return $this->redirectToRoute('pro_signup_step', ['step' => $current]);
        }

        $old = $this->session->get(self::OLD_INPUT);
        $this->session->remove(self::OLD_INPUT);
        $old = is_array($old) && ($old['step'] ?? null) === $step ? $old['values'] : [];

        return $this->render(sprintf('pro/signup/%s.html.twig', $step), [
                'signup' => $this->progress($step, $current),
            ] + $this->stepData($step, $request, $old));
    }

    #[Csrf('pro')]
    #[Route('/pro/signup/account', name: 'pro_signup_account', methods: ['POST'])]
    public function account(Request $request): RedirectResponse
    {
        $user = $this->user();
        $input = new ProAccountInput();
        $input->kind = CompanyKind::tryFrom($request->request->getString('kind')) ?? CompanyKind::SOLO;
        $input->firstName = trim($request->request->getString('firstName'));
        $input->lastName = trim($request->request->getString('lastName'));
        $input->phone = trim($request->request->getString('phone'));
        $input->email = $user?->getEmail() ?? trim($request->request->getString('email'));
        $input->password = $user !== null ? 'Compte' . bin2hex(random_bytes(6)) . '1' : $request->request->getString('password');
        $created = null;

        if (!$this->invalid($input)) {
            $this->attempt(function () use ($input, $user, &$created): void {
                $created = $this->signup->createAccount($input, $user);
            });
        }

        if (!$created instanceof User) {
            $this->remember('account', ['kind' => $input->kind->value, 'firstName' => $input->firstName, 'lastName' => $input->lastName, 'email' => $input->email, 'phone' => $input->phone]);

            return $this->redirectToRoute('pro_signup_step', ['step' => 'account']);
        }

        if ($user === null) {
            $this->loginUser($created);
        }

        $this->addFlash('success', sprintf('Un code de vérification a été envoyé à %s.', $created->getEmail()));

        return $this->redirectToRoute('pro_signup_step', ['step' => 'verify']);
    }

    #[Csrf('pro')]
    #[Route('/pro/signup/verify', name: 'pro_signup_verify', methods: ['POST'])]
    public function verify(#[MapRequestPayload(validate: false)] VerificationCodeInput $input): RedirectResponse
    {
        $input->code = preg_replace('/\D/', '', $input->code) ?? '';

        if (!$this->invalid($input) && $this->attempt(fn () => $this->signup->verifyCode($this->currentUser(), $input->code), 'Adresse e-mail vérifiée.')) {
            return $this->redirectToRoute('pro_signup_step', ['step' => 'establishment']);
        }

        return $this->redirectToRoute('pro_signup_step', ['step' => 'verify']);
    }

    #[Csrf('pro')]
    #[Route('/pro/signup/verify/resend', name: 'pro_signup_verify_resend', methods: ['POST'])]
    public function resend(): RedirectResponse
    {
        $this->attempt(fn () => $this->signup->resendCode($this->currentUser(), $this->currentCompany()), 'Un nouveau code vient de vous être envoyé.');

        return $this->redirectToRoute('pro_signup_step', ['step' => 'verify']);
    }

    #[Route('/pro/signup/siret/{siret}', name: 'pro_signup_siret', methods: ['GET'], requirements: ['siret' => '[\d ]{14,17}'])]
    public function siret(string $siret): JsonResponse
    {
        $this->currentMember();

        try {
            return $this->json(['found' => true, 'establishment' => $this->signup->lookupSiret($siret)->toArray()]);
        } catch (BusinessRuleException $exception) {
            return $this->json(['found' => false, 'error' => $exception->getMessage()], 422);
        }
    }

    #[Csrf('pro')]
    #[Route('/pro/signup/establishment', name: 'pro_signup_establishment', methods: ['POST'])]
    public function establishment(Request $request, #[MapRequestPayload(validate: false)] EstablishmentInput $input): RedirectResponse
    {
        $member = $this->signupOwner();
        $input->managerMemberId = null;

        if (!$this->invalid($input) && $this->attempt(fn () => $this->signup->attachEstablishment($member, $input))) {
            return $this->redirectToRoute('pro_signup_step', ['step' => 'services']);
        }

        $this->remember('establishment', array_map(static fn (mixed $value): mixed => is_scalar($value) ? (string) $value : null, $request->request->all()));

        return $this->redirectToRoute('pro_signup_step', ['step' => 'establishment']);
    }

    #[Csrf('pro')]
    #[Route('/pro/signup/services', name: 'pro_signup_services', methods: ['POST'])]
    public function services(Request $request): RedirectResponse
    {
        $establishment = $this->signupEstablishment();
        $input = new ServicesInput();
        $rows = $request->request->get('services');
        $discount = $request->request->getString('defaultDiscount');
        $input->defaultDiscount = $discount !== '' ? (int) $discount : null;

        foreach (is_array($rows) ? $rows : [] as $row) {
            if (!is_array($row) || trim((string) ($row['name'] ?? '')) === '') {
                continue;
            }

            $price = str_replace([' ', ','], ['', '.'], (string) ($row['price'] ?? ''));
            $service = new ServiceInput();
            $service->name = trim((string) $row['name']);
            $service->price = is_numeric($price) ? (int) round((float) $price * 100) : null;
            $service->duration = (int) ($row['duration'] ?? 0) ?: null;
            $service->active = true;
            $input->services[] = $service;
        }

        if (!$this->invalid($input) && $this->attempt(fn () => $this->catalog->replaceAll($establishment, $input))) {
            return $this->redirectToRoute('pro_signup_step', ['step' => 'page']);
        }

        return $this->redirectToRoute('pro_signup_step', ['step' => 'services']);
    }

    #[Csrf('pro')]
    #[Route('/pro/signup/page', name: 'pro_signup_page', methods: ['POST'])]
    public function page(Request $request): RedirectResponse
    {
        $establishment = $this->signupEstablishment();
        $input = new PageInput();
        $input->description = trim($request->request->getString('description')) ?: null;
        $rows = $request->request->get('hours');

        foreach (is_array($rows) ? $rows : [] as $row) {
            if (!is_array($row) || ($row['opensAt'] ?? '') === '' || ($row['closesAt'] ?? '') === '') {
                continue;
            }

            $hour = new OpeningHourInput();
            $hour->weekday = (int) ($row['weekday'] ?? 0);
            $hour->opensAt = (string) $row['opensAt'];
            $hour->closesAt = (string) $row['closesAt'];
            $input->hours[] = $hour;
        }

        if (!$this->invalid($input) && $this->attempt(fn () => $this->pages->update($establishment, $input))) {
            $this->session->remove(self::PAGE_SKIPPED);

            return $this->redirectToRoute('pro_signup_step', ['step' => 'plan']);
        }

        return $this->redirectToRoute('pro_signup_step', ['step' => 'page']);
    }

    #[Csrf('pro')]
    #[Route('/pro/signup/page/skip', name: 'pro_signup_page_skip', methods: ['POST'])]
    public function skipPage(): RedirectResponse
    {
        $this->signupEstablishment();
        $this->session->set(self::PAGE_SKIPPED, true);

        return $this->redirectToRoute('pro_signup_step', ['step' => 'plan']);
    }

    #[Csrf('pro')]
    #[Route('/pro/signup/page/images', name: 'pro_signup_image', methods: ['POST'])]
    public function uploadImage(Request $request): RedirectResponse
    {
        $establishment = $this->signupEstablishment();
        $file = $request->files->get('image');

        if (!$file instanceof UploadedFile) {
            $this->addFlash('error', 'Choisissez une photo à envoyer.');
        } else {
            $this->attempt(fn () => $this->imageService->upload($establishment, $file, $request->request->getString('alt')), 'Photo ajoutée.');
        }

        return $this->redirect($this->generateUrl('pro_signup_step', ['step' => 'page']) . '#photos');
    }

    #[Csrf('pro')]
    #[Route('/pro/signup/page/images/{id}/delete', name: 'pro_signup_image_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function deleteImage(int $id): RedirectResponse
    {
        $establishment = $this->signupEstablishment();
        $image = $this->images->findOneInEstablishment($establishment, $id) ?? throw $this->createNotFoundException('Photo introuvable.');
        $this->attempt(fn () => $this->imageService->delete($image), 'Photo supprimée.');

        return $this->redirect($this->generateUrl('pro_signup_step', ['step' => 'page']) . '#photos');
    }

    #[Csrf('pro')]
    #[Route('/pro/signup/plan', name: 'pro_signup_plan', methods: ['POST'])]
    public function plan(#[MapRequestPayload(validate: false)] PlanChoiceInput $input): RedirectResponse
    {
        $company = $this->signupOwner()->getCompany() ?? throw $this->createAccessDeniedException();
        $input->paymentMethodId = null;

        if (!$this->invalid($input) && $this->attempt(fn () => $this->subscriptionService->choose($company, $this->currentUser(), $input))) {
            return $this->redirectToRoute('pro_signup_step', ['step' => 'payment']);
        }

        return $this->redirectToRoute('pro_signup_step', ['step' => 'plan', 'period' => $input->period]);
    }

    #[Csrf('pro')]
    #[Route('/pro/signup/promo', name: 'pro_signup_promo', methods: ['POST'])]
    public function promo(#[MapRequestPayload(validate: false)] PromoCheckInput $input): JsonResponse
    {
        $this->signupOwner();
        $error = $this->validate($input)->first();

        if ($error !== null) {
            return $this->json(['error' => $error], 422);
        }

        try {
            $plan = $this->planCatalog->get((string) $input->plan);
            $promo = $this->promos->resolve($input->code, $plan);
        } catch (BusinessRuleException $exception) {
            return $this->json(['error' => $exception->getMessage()], 422);
        }

        if ($promo === null) {
            return $this->json(['error' => 'Ce code promo n\'est pas valable pour cette formule.'], 422);
        }

        return $this->json([
            'promo' => ['code' => $promo->getCode(), 'label' => $this->promos->describe($promo)],
            'quote' => $this->prices->quote($plan, BillingPeriod::from($input->period), $promo),
        ]);
    }

    #[Csrf('pro')]
    #[Route('/pro/signup/payment/setup', name: 'pro_signup_payment_setup', methods: ['POST'])]
    public function paymentSetup(#[MapRequestPayload(validate: false)] PaymentSetupInput $input): JsonResponse
    {
        $company = $this->signupOwner()->getCompany() ?? throw $this->createAccessDeniedException();

        try {
            $setup = $this->methodService->startSetup($company, PaymentMethodType::tryFrom($input->type) ?? PaymentMethodType::CARD);
        } catch (BusinessRuleException|PaymentGatewayException $exception) {
            return $this->json(['error' => $exception->getMessage()], 422);
        }

        return $this->json(['setup' => $setup->toArray(), 'gatewayKey' => $this->gateway->getPublicKey()]);
    }

    #[Csrf('pro')]
    #[Route('/pro/signup/payment', name: 'pro_signup_payment', methods: ['POST'])]
    public function payment(Request $request): RedirectResponse
    {
        $member = $this->signupOwner();
        $billing = $request->request->get('billing');
        $billing = is_array($billing) ? $billing : [];
        $input = new SignupPaymentInput();
        $input->billing = new BillingInfoInput();

        foreach (['billingName', 'billingEmail', 'vatNumber', 'addressLine1', 'addressLine2', 'postalCode', 'locality'] as $field) {
            $value = trim((string) ($billing[$field] ?? ''));
            $input->billing->{$field} = $value !== '' ? $value : null;
        }

        $input->billing->vatNumber = $input->billing->vatNumber !== null ? strtoupper(str_replace(' ', '', $input->billing->vatNumber)) : null;
        $input->setupReference = trim($request->request->getString('setupReference')) ?: null;
        $input->mandateAccepted = $request->request->getBoolean('mandateAccepted');
        $input->acceptTerms = $request->request->getBoolean('acceptTerms');
        $input->promoCode = trim($request->request->getString('promoCode')) ?: null;

        if (!$this->invalid($input) && $this->attempt(fn () => $this->signupBilling->complete($member, $input))) {
            return $this->redirectToRoute('pro_signup_step', ['step' => 'pending']);
        }

        $this->remember('payment', ['billing' => $billing, 'promoCode' => $input->promoCode]);

        return $this->redirectToRoute('pro_signup_step', ['step' => 'payment']);
    }

    private function stepData(string $step, Request $request, array $old): array
    {
        $user = $this->user();
        $establishment = $this->draft();
        $company = $establishment?->getCompany();

        return match ($step) {
            'account' => [
                'existing' => $user !== null,
                'kinds' => self::KINDS,
                'form' => $old + [
                        'kind' => 'solo',
                        'firstName' => $user?->getFirstName(),
                        'lastName' => $user?->getLastName(),
                        'email' => $user?->getEmail(),
                        'phone' => $user?->getPhone(),
                    ],
            ],
            'verify' => ['email' => $user?->getEmail()],
            'establishment' => [
                'form' => $old + ($establishment !== null ? $this->pagePresenter->settings($establishment)['form'] + ['siret' => $establishment->getSiret()] : [
                        'name' => null, 'siret' => null, 'categorySlug' => null, 'citySlug' => null, 'addressLine1' => null, 'addressLine2' => null,
                        'postalCode' => null, 'locality' => null, 'latitude' => null, 'longitude' => null, 'phone' => null,
                    ]),
                'categories' => $this->categoryPresenter->summaries($this->categories->findVisible()),
                'cities' => $this->cityPresenter->summaries($this->cities->findAvailableForPros()),
            ],
            'services' => [
                'services' => $this->pagePresenter->signupServices($this->signupEstablishment(), $this->website->getRule()->getDefaultDiscount()),
                'emptyRows' => self::EMPTY_SERVICE_ROWS,
                'durations' => ServiceSuggestionHelper::DURATIONS,
            ],
            'page' => [
                'page' => $this->pagePresenter->signupPage($this->signupEstablishment()),
                'rangesPerDay' => self::RANGES_PER_DAY,
            ],
            'plan' => ['plan' => $this->planData($this->signupCompany(), BillingPeriod::tryFrom($request->query->getString('period')))],
            'payment' => [
                'plan' => $this->planData($this->signupCompany()),
                'old' => $old + ['billing' => [], 'promoCode' => null],
                'recap' => $user !== null && $establishment !== null ? $this->recap($user, $establishment) : null,
            ],
            'pending' => [
                'firstName' => $user?->getFirstName(),
                'email' => $user?->getEmail(),
                'establishmentName' => $establishment?->getName(),
                'statusLabel' => $establishment?->getStatus()->label(),
                'recap' => $user !== null && $establishment !== null ? $this->recap($user, $establishment) : null,
                'companyName' => $company?->getName(),
            ],
        };
    }

    private function progress(string $step, string $current): array
    {
        $position = array_search($step, self::ORDER, true);
        $reached = array_search($current, self::ORDER, true);
        $steps = [];
        $index = 0;

        foreach (self::STEPS as $key => $definition) {
            $steps[] = $definition + [
                    'key' => $key,
                    'number' => $index + 1,
                    'state' => $key === $step ? 'current' : ($index < $reached ? 'done' : 'todo'),
                    'reachable' => $index <= $reached && !in_array($key, self::LOCKED, true) && $current !== 'pending',
                ];
            ++$index;
        }

        return [
            'step' => $step,
            'steps' => $steps,
            'number' => min($position + 1, count(self::STEPS)),
            'total' => count(self::STEPS),
            'previous' => $position > 0 && !in_array(self::ORDER[$position - 1], self::LOCKED, true) && $step !== 'pending' ? self::ORDER[$position - 1] : null,
            'loggedIn' => $this->user() !== null,
        ];
    }

    private function planData(Company $company, ?BillingPeriod $period = null): array
    {
        $pending = $this->subscriptions->findPending($company);
        $period ??= $pending?->getPeriod() ?? BillingPeriod::MONTHLY;
        $plans = $this->planCatalog->plans();
        $plan = $pending?->getPlan();
        $config = $company->getConfig();

        return [
            'period' => $period->value,
            'periods' => $this->planPresenter->periods($plans, $period),
            'plans' => $this->planPresenter->offers($plans, $period, null, $plan?->getCode()),
            'selected' => $plan === null ? null : [
                'code' => $plan->getCode(),
                'name' => $plan->getName(),
                'period' => $pending->getPeriod()->value,
                'perBooking' => $plan->getBilling() === PlanBilling::PER_BOOKING,
                'quote' => $this->prices->quote($plan, $pending->getPeriod(), $pending->getPromoCode()),
                'promo' => $pending->getPromoCode() !== null ? ['code' => $pending->getPromoCode()->getCode(), 'label' => $this->promos->describe($pending->getPromoCode())] : null,
            ],
            'establishmentsCount' => $company->getEstablishments()->count(),
            'billing' => [
                'billingName' => $this->billingInfo->billingName($company),
                'billingEmail' => $this->billingInfo->billingEmail($company) ?? $this->currentUser()->getEmail(),
                'vatNumber' => $company->getVatNumber(),
                'addressLine1' => $config?->getAddressLine1() ?? $company->getAddressLine1(),
                'addressLine2' => $config?->getAddressLine2() ?? $company->getAddressLine2(),
                'postalCode' => $config?->getPostalCode() ?? $company->getPostalCode(),
                'locality' => $config?->getLocality() ?? $company->getLocality(),
            ],
            'hasPaymentMethod' => $this->methods->findForCompany($company) !== [],
            'gatewayKey' => $this->gateway->getPublicKey(),
        ];
    }

    private function recap(User $user, CompanyEstablishment $establishment): array
    {
        $services = $establishment->getServices()->count();
        $days = count(array_unique(array_map(static fn (EstablishmentOpeningHour $hour): int => $hour->getWeekday()->value, $establishment->getOpeningHours()->toArray())));
        $images = $establishment->getImages()->count();
        $pending = $establishment->getCompany() !== null ? $this->subscriptions->findPending($establishment->getCompany()) : null;

        return [
            ['key' => 'account', 'label' => 'Compte', 'value' => sprintf('%s · %s', trim($user->getFirstName() . ' ' . $user->getLastName()), $user->getEmail())],
            ['key' => 'establishment', 'label' => 'Établissement', 'value' => sprintf('%s · %s', $establishment->getName(), $establishment->getLocality())],
            ['key' => 'establishment', 'label' => 'SIRET', 'value' => $establishment->getSiret()],
            ['key' => 'services', 'label' => 'Prestations', 'value' => sprintf('%d prestation%s%s', $services, $services > 1 ? 's' : '', $establishment->getDefaultDiscount() !== null ? sprintf(' · remise −%d %%', $establishment->getDefaultDiscount()) : '')],
            ['key' => 'page', 'label' => 'Page publique', 'value' => sprintf('%d photo%s · %d jour%s d\'ouverture', $images, $images > 1 ? 's' : '', $days, $days > 1 ? 's' : '')],
            ['key' => 'plan', 'label' => 'Formule', 'value' => $pending?->getPlan()?->getName()],
        ];
    }

    private function currentStep(): string
    {
        $user = $this->user();

        if ($user === null || $this->context->getMembership($user) === null) {
            return 'account';
        }

        if ($user->getEmailVerifiedAt() === null) {
            return 'verify';
        }

        $establishment = $this->draft();

        if ($establishment === null) {
            return 'establishment';
        }

        if ($establishment->getServices()->isEmpty()) {
            return 'services';
        }

        if ($establishment->getOpeningHours()->isEmpty() && $this->session->get(self::PAGE_SKIPPED) !== true) {
            return 'page';
        }

        $company = $establishment->getCompany();

        if ($company === null || ($this->subscriptions->findPending($company) === null && $this->subscriptions->findCurrent($company) === null)) {
            return 'plan';
        }

        if ($this->methods->findForCompany($company) === [] || in_array($establishment->getStatus(), [EstablishmentStatus::DRAFT, EstablishmentStatus::REJECTED], true)) {
            return 'payment';
        }

        return $establishment->getStatus() === EstablishmentStatus::PENDING_REVIEW ? 'pending' : 'done';
    }

    private function remember(string $step, array $values): void
    {
        unset($values['password'], $values['_token'], $values['_csrf_token']);
        $this->session->set(self::OLD_INPUT, ['step' => $step, 'values' => $values]);
    }

    private function user(): ?User
    {
        $user = $this->getUser();

        return $user instanceof User ? $user : null;
    }

    private function draft(): ?CompanyEstablishment
    {
        $user = $this->user();
        $establishment = $user !== null ? $this->context->getMembership($user)?->getCompany()?->getEstablishments()->first() : null;

        return $establishment instanceof CompanyEstablishment ? $establishment : null;
    }

    private function signupOwner(): CompanyMember
    {
        $member = $this->currentMember();

        if ($member->getRole() !== CompanyRole::OWNER) {
            throw $this->createAccessDeniedException();
        }

        return $member;
    }

    private function signupCompany(): Company
    {
        return $this->signupOwner()->getCompany() ?? throw $this->createAccessDeniedException();
    }

    private function signupEstablishment(): CompanyEstablishment
    {
        $this->signupOwner();

        return $this->draft() ?? throw $this->createNotFoundException('Renseignez d\'abord votre établissement.');
    }
}
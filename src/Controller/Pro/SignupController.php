<?php

namespace App\Controller\Pro;

use App\Billing\Dto\PaymentSetupInput;
use App\Billing\Dto\PlanChoiceInput;
use App\Billing\Dto\PromoCheckInput;
use App\Billing\Mapper\BillingRequestMapper;
use App\Billing\Mapper\SubscriptionRequestMapper;
use App\Billing\Provider\PaymentSetupProvider;
use App\Billing\Provider\PromoQuoteProvider;
use App\Billing\Service\SignupBillingService;
use App\Billing\Service\SubscriptionService;
use App\Company\Dto\EstablishmentInput;
use App\Company\Dto\VerificationCodeInput;
use App\Company\Enum\ProSignupStep;
use App\Company\Enum\SignupFlash;
use App\Company\Mapper\CompanyRequestMapper;
use App\Company\Mapper\ProSignupRequestMapper;
use App\Company\Provider\CurrentCompanyProvider;
use App\Company\Provider\ProContextProvider;
use App\Company\Provider\ProSignupPageProvider;
use App\Company\Security\PermissionResolver;
use App\Company\Service\ProSignupFlow;
use App\Company\Service\ProSignupService;
use App\Entity\User\User;
use App\Establishment\Enum\ImageFlash;
use App\Establishment\Mapper\ImageRequestMapper;
use App\Establishment\Mapper\ServiceRequestMapper;
use App\Establishment\Provider\EstablishmentImageProvider;
use App\Establishment\Service\ImageService;
use App\Establishment\Service\ImageUploadService;
use App\Establishment\Service\PageService;
use App\Establishment\Service\ServiceCatalogService;
use App\Shared\Helper\JsonStatusHelper;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\JsonResponse;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Serializer\Attribute\MapRequestPayload;

class SignupController extends AbstractProController
{
    public function __construct(
        CurrentCompanyProvider $context,
        PermissionResolver $resolver,
        ProContextProvider $pro,
        private ProSignupService $signup,
        private ProSignupFlow $flow,
        private ProSignupPageProvider $pages,
        private ServiceCatalogService $catalog,
        private PageService $publicPages,
        private ImageService $imageService,
        private ImageUploadService $uploads,
        private EstablishmentImageProvider $images,
        private SubscriptionService $subscriptionService,
        private SignupBillingService $signupBilling,
        private PaymentSetupProvider $setups,
        private PromoQuoteProvider $promoQuotes,
    ) {
        parent::__construct($context, $resolver, $pro);
    }

    #[Route('/pro/signup', name: 'pro_signup', methods: ['GET'])]
    public function start(): RedirectResponse
    {
        $step = $this->flow->currentStep($this->optionalUser());

        if ($step === ProSignupStep::DONE) {
            return $this->redirectToRoute('pro_dashboard');
        }

        return $this->redirectToRoute('pro_signup_step', ['step' => $step->value]);
    }

    #[Route(
        '/pro/signup/{step}',
        name: 'pro_signup_step',
        methods: ['GET'],
        requirements: ['step' => ProSignupStep::PATTERN],
    )]
    public function show(Request $request, string $step): Response
    {
        $user = $this->optionalUser();
        $requested = ProSignupStep::from($step);
        $current = $this->flow->currentStep($user);

        if ($current === ProSignupStep::DONE) {
            return $this->redirectToRoute('pro_dashboard');
        }

        if ($this->pages->mustRedirect($requested, $current)) {
            return $this->redirectToRoute('pro_signup_step', ['step' => $current->value]);
        }

        $page = $this->pages->show($requested, $current, $user, SubscriptionRequestMapper::period($request));

        return $this->render($requested->template(), $page);
    }

    #[Csrf('pro')]
    #[Route('/pro/signup/account', name: 'pro_signup_account', methods: ['POST'])]
    public function account(Request $request): RedirectResponse
    {
        $user = $this->optionalUser();
        $input = ProSignupRequestMapper::account($request, $user);
        $created = null;

        if (!$this->invalid($input)) {
            $this->attempt(function () use ($input, $user, &$created): void {
                $created = $this->signup->createAccount($input, $user);
            });
        }

        if (!$created instanceof User) {
            $this->pages->remember(ProSignupStep::ACCOUNT, ProSignupRequestMapper::accountOld($input));

            return $this->redirectToRoute('pro_signup_step', ['step' => ProSignupStep::ACCOUNT->value]);
        }

        if ($user === null) {
            $this->loginUser($created);
        }

        $this->addFlash('success', SignupFlash::CODE_SENT->text($created->getEmail()));

        return $this->redirectToRoute('pro_signup_step', ['step' => ProSignupStep::VERIFY->value]);
    }

    #[Csrf('pro')]
    #[Route('/pro/signup/verify', name: 'pro_signup_verify', methods: ['POST'])]
    public function verify(#[MapRequestPayload(validate: false)] VerificationCodeInput $input): RedirectResponse
    {
        $input = ProSignupRequestMapper::code($input);

        if (!$this->invalid($input) && $this->attempt(
                fn () => $this->signup->verifyCode($this->currentUser(), $input->code),
                SignupFlash::EMAIL_VERIFIED->text(),
            )) {
            return $this->redirectToRoute('pro_signup_step', ['step' => ProSignupStep::ESTABLISHMENT->value]);
        }

        return $this->redirectToRoute('pro_signup_step', ['step' => ProSignupStep::VERIFY->value]);
    }

    #[Csrf('pro')]
    #[Route('/pro/signup/verify/resend', name: 'pro_signup_verify_resend', methods: ['POST'])]
    public function resend(): RedirectResponse
    {
        $this->attempt(
            fn () => $this->signup->resendCode($this->currentUser(), $this->currentCompany()),
            SignupFlash::CODE_RESENT->text(),
        );

        return $this->redirectToRoute('pro_signup_step', ['step' => ProSignupStep::VERIFY->value]);
    }

    #[Route(
        '/pro/signup/siret/{siret}',
        name: 'pro_signup_siret',
        methods: ['GET'],
        requirements: ['siret' => '[\d ]{14,17}'],
    )]
    public function siret(string $siret): JsonResponse
    {
        $this->currentMember();
        $body = $this->pages->siret($siret);

        return $this->json($body, JsonStatusHelper::of($body));
    }

    #[Csrf('pro')]
    #[Route('/pro/signup/establishment', name: 'pro_signup_establishment', methods: ['POST'])]
    public function establishment(
        Request $request,
        #[MapRequestPayload(validate: false)] EstablishmentInput $input,
    ): RedirectResponse {
        $member = $this->flow->owner($this->currentMember());
        $input = CompanyRequestMapper::signupEstablishment($input);

        if (!$this->invalid($input) && $this->attempt(fn () => $this->signup->attachEstablishment($member, $input))) {
            return $this->redirectToRoute('pro_signup_step', ['step' => ProSignupStep::SERVICES->value]);
        }

        $this->pages->remember(ProSignupStep::ESTABLISHMENT, ProSignupRequestMapper::scalars($request));

        return $this->redirectToRoute('pro_signup_step', ['step' => ProSignupStep::ESTABLISHMENT->value]);
    }

    #[Csrf('pro')]
    #[Route('/pro/signup/services', name: 'pro_signup_services', methods: ['POST'])]
    public function services(Request $request): RedirectResponse
    {
        $establishment = $this->flow->establishment($this->currentMember());
        $input = ServiceRequestMapper::services($request);

        if (!$this->invalid($input) && $this->attempt(fn () => $this->catalog->replaceAll($establishment, $input))) {
            return $this->redirectToRoute('pro_signup_step', ['step' => ProSignupStep::PAGE->value]);
        }

        return $this->redirectToRoute('pro_signup_step', ['step' => ProSignupStep::SERVICES->value]);
    }

    #[Csrf('pro')]
    #[Route('/pro/signup/page', name: 'pro_signup_page', methods: ['POST'])]
    public function page(Request $request): RedirectResponse
    {
        $establishment = $this->flow->establishment($this->currentMember());
        $input = ProSignupRequestMapper::page($request);

        if (!$this->invalid($input) && $this->attempt(fn () => $this->publicPages->update($establishment, $input))) {
            $this->flow->pageCompleted();

            return $this->redirectToRoute('pro_signup_step', ['step' => ProSignupStep::PLAN->value]);
        }

        return $this->redirectToRoute('pro_signup_step', ['step' => ProSignupStep::PAGE->value]);
    }

    #[Csrf('pro')]
    #[Route('/pro/signup/page/skip', name: 'pro_signup_page_skip', methods: ['POST'])]
    public function skipPage(): RedirectResponse
    {
        $this->flow->skipPage($this->currentMember());

        return $this->redirectToRoute('pro_signup_step', ['step' => ProSignupStep::PLAN->value]);
    }

    #[Csrf('pro')]
    #[Route('/pro/signup/page/images', name: 'pro_signup_image', methods: ['POST'])]
    public function uploadImage(Request $request): RedirectResponse
    {
        $establishment = $this->flow->establishment($this->currentMember());
        $file = ImageRequestMapper::file($request);
        $alt = ImageRequestMapper::alt($request);
        $this->attempt(fn () => $this->uploads->upload($establishment, $file, $alt), ImageFlash::ADDED->text());

        $url = $this->generateUrl('pro_signup_step', ['step' => ProSignupStep::PAGE->value]);

        return $this->redirect($url . '#photos');
    }

    #[Csrf('pro')]
    #[Route(
        '/pro/signup/page/images/{id}/delete',
        name: 'pro_signup_image_delete',
        methods: ['POST'],
        requirements: ['id' => '\d+'],
    )]
    public function deleteImage(int $id): RedirectResponse
    {
        $image = $this->images->owned($this->flow->establishment($this->currentMember()), $id);
        $this->attempt(fn () => $this->imageService->delete($image), ImageFlash::DELETED->text());

        $url = $this->generateUrl('pro_signup_step', ['step' => ProSignupStep::PAGE->value]);

        return $this->redirect($url . '#photos');
    }

    #[Csrf('pro')]
    #[Route('/pro/signup/plan', name: 'pro_signup_plan', methods: ['POST'])]
    public function plan(#[MapRequestPayload(validate: false)] PlanChoiceInput $input): RedirectResponse
    {
        $company = $this->flow->company($this->currentMember());
        $input = SubscriptionRequestMapper::signupPlan($input);

        if (!$this->invalid($input) && $this->attempt(
                fn () => $this->subscriptionService->choose($company, $this->currentUser(), $input),
            )) {
            return $this->redirectToRoute('pro_signup_step', ['step' => ProSignupStep::PAYMENT->value]);
        }

        return $this->redirectToRoute('pro_signup_step', [
            'step' => ProSignupStep::PLAN->value,
            'period' => $input->period,
        ]);
    }

    #[Csrf('pro')]
    #[Route('/pro/signup/promo', name: 'pro_signup_promo', methods: ['POST'])]
    public function promo(#[MapRequestPayload(validate: false)] PromoCheckInput $input): JsonResponse
    {
        $this->flow->owner($this->currentMember());
        $error = $this->validate($input)->first();

        if ($error !== null) {
            return $this->json(['error' => $error], JsonStatusHelper::UNPROCESSABLE);
        }

        $body = $this->promoQuotes->quote($input);

        return $this->json($body, JsonStatusHelper::of($body));
    }

    #[Csrf('pro')]
    #[Route('/pro/signup/payment/setup', name: 'pro_signup_payment_setup', methods: ['POST'])]
    public function paymentSetup(#[MapRequestPayload(validate: false)] PaymentSetupInput $input): JsonResponse
    {
        $company = $this->flow->company($this->currentMember());
        $body = $this->setups->start($company, BillingRequestMapper::setupType($input));

        return $this->json($body, JsonStatusHelper::of($body));
    }

    #[Csrf('pro')]
    #[Route('/pro/signup/payment', name: 'pro_signup_payment', methods: ['POST'])]
    public function payment(Request $request): RedirectResponse
    {
        $member = $this->flow->owner($this->currentMember());
        $input = ProSignupRequestMapper::payment($request);

        if (!$this->invalid($input) && $this->attempt(fn () => $this->signupBilling->complete($member, $input))) {
            return $this->redirectToRoute('pro_signup_step', ['step' => ProSignupStep::PENDING->value]);
        }

        $this->pages->remember(ProSignupStep::PAYMENT, ProSignupRequestMapper::paymentOld($request, $input));

        return $this->redirectToRoute('pro_signup_step', ['step' => ProSignupStep::PAYMENT->value]);
    }
}
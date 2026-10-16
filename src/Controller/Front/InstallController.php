<?php

namespace App\Controller\Front;

use App\Entity\User\User;
use App\Shared\Exception\BusinessRuleException;
use App\Website\Dto\WebsiteIdentityInput;
use App\Website\Dto\WebsitePlanInput;
use App\Website\Dto\WebsiteRuleInput;
use App\Website\Enum\PlanBilling;
use App\Website\Service\WebsiteInstallService;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Session\SessionManagerInterface;

class InstallController extends AbstractFrontController
{
    public const OLD_INPUT = 'app.install.old';

    public const PASSWORD_PATTERN = '/^(?=.*[A-Za-z])(?=.*\d).{12,}$/';

    public const IDENTITY = [
        'siteName' => 'Kréno',
        'domain' => 'kreno.fr',
        'tagline' => 'Un créneau vient de se libérer. Il est pour toi.',
        'siteDescription' => 'Les créneaux libérés de ton quartier, à prix réduit. Tu réserves en deux clics, tu paies sur place.',
        'seoTitle' => 'Kréno · Les créneaux libérés près de chez toi',
        'contactEmail' => 'bonjour@kreno.fr',
    ];

    public const RULES = [
        'freeCancellationMinutes' => ['label' => 'Annulation gratuite jusqu\'à', 'unit' => 'min avant', 'value' => 60],
        'bookingLeadMinutes' => ['label' => 'Réservation possible jusqu\'à', 'unit' => 'min avant', 'value' => 15],
        'autoRemovalMinutes' => ['label' => 'Retrait automatique du créneau', 'unit' => 'min avant', 'value' => 10],
        'maxUpcomingBookingsPerUser' => ['label' => 'Réservations à venir par client', 'unit' => 'maximum', 'value' => 2],
        'toleratedNoShows' => ['label' => 'Absences tolérées', 'unit' => 'absences', 'value' => 2],
        'noShowWindowDays' => ['label' => 'Sur une période de', 'unit' => 'jours', 'value' => 90],
        'suspensionDays' => ['label' => 'Durée de suspension', 'unit' => 'jours', 'value' => 30],
        'minDiscount' => ['label' => 'Remise minimale', 'unit' => '%', 'value' => 10],
        'maxDiscount' => ['label' => 'Remise maximale', 'unit' => '%', 'value' => 50],
        'defaultDiscount' => ['label' => 'Remise proposée par défaut', 'unit' => '%', 'value' => 30],
        'publicationWindowDays' => ['label' => 'Publication jusqu\'à', 'unit' => 'jours à l\'avance', 'value' => 7],
    ];

    public const PLANS = [
        [
            'billing' => 'per_booking', 'active' => true, 'code' => 'reservation', 'name' => 'À la réservation',
            'description' => 'Vous ne payez que les créneaux réservés.', 'price' => '2,90', 'yearlyPrice' => null,
            'trialDays' => 0, 'maxEstablishments' => null, 'commissionFree' => false, 'featured' => false, 'advancedStats' => false,
        ],
        [
            'billing' => 'subscription', 'active' => true, 'code' => 'abonnement', 'name' => 'Abonnement',
            'description' => 'Aucune commission, mise en avant et statistiques détaillées.', 'price' => '39,00', 'yearlyPrice' => '390,00',
            'trialDays' => 14, 'maxEstablishments' => 3, 'commissionFree' => true, 'featured' => true, 'advancedStats' => true,
        ],
    ];

    public function __construct(
        private WebsiteInstallService $installer,
        private SessionManagerInterface $session,
    ) {
    }

    #[Route('/install', name: 'app_install', methods: ['GET'])]
    public function form(): Response
    {
        if ($this->installer->isInstalled()) {
            return $this->redirectToRoute('home_index');
        }

        $needsAdmin = !$this->installer->hasAdmin();

        if (!$needsAdmin && !$this->isGranted('ROLE_ADMIN')) {
            return $this->render('install/login.html.twig', [
                'loggedIn' => $this->optionalUser() !== null,
            ]);
        }

        $old = $this->session->get(self::OLD_INPUT);
        $this->session->remove(self::OLD_INPUT);
        $old = is_array($old) ? $old : [];

        return $this->render('install/index.html.twig', [
            'needsAdmin' => $needsAdmin,
            'identity' => ($old['identity'] ?? []) + self::IDENTITY,
            'rules' => array_map(static fn (array $rule, string $key): array => $rule + ['key' => $key, 'current' => $old['rules'][$key] ?? $rule['value']], self::RULES, array_keys(self::RULES)),
            'plans' => array_map(static fn (array $plan, int $index): array => array_merge($plan, $old['plans'][$index] ?? []), self::PLANS, array_keys(self::PLANS)),
            'admin' => ($old['admin'] ?? []) + ['firstName' => null, 'email' => null],
        ]);
    }

    #[Csrf('install')]
    #[Route('/install', name: 'app_install_submit', methods: ['POST'])]
    public function install(Request $request): RedirectResponse
    {
        if ($this->installer->isInstalled()) {
            return $this->redirectToRoute('home_index');
        }

        $needsAdmin = !$this->installer->hasAdmin();

        if (!$needsAdmin && !$this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_install');
        }

        $identityValues = $this->section($request, 'identity');
        $ruleValues = $this->section($request, 'rules');
        $adminValues = $this->section($request, 'admin');
        $planValues = array_values(array_filter($request->request->get('plans') ?? [], 'is_array'));

        $identity = new WebsiteIdentityInput();

        foreach (array_keys(self::IDENTITY) as $field) {
            $identity->{$field} = trim((string) ($identityValues[$field] ?? '')) ?: null;
        }

        $rules = new WebsiteRuleInput();

        foreach (array_keys(self::RULES) as $field) {
            $rules->{$field} = is_numeric($ruleValues[$field] ?? null) ? (int) $ruleValues[$field] : null;
        }

        $plans = array_values(array_filter(
            array_map(fn (array $values): WebsitePlanInput => $this->plan($values), $planValues),
            static fn (WebsitePlanInput $plan): bool => $plan->active || ($plan->code !== null && $plan->name !== null),
        ));
        $actor = $this->optionalUser();

        $done = !$this->invalid($identity) && !$this->invalid($rules) && !$this->invalidPlans($plans) && $this->attempt(function () use ($identity, $rules, $plans, $needsAdmin, $adminValues, &$actor): void {
                if ($identity->siteName === null || $identity->domain === null || $identity->tagline === null || $identity->siteDescription === null || $identity->contactEmail === null) {
                    throw new BusinessRuleException('Renseigne le nom du site, le domaine, l\'accroche, la description et l\'e-mail de contact.');
                }

                $this->installer->assertInstallable($rules, $plans);

                if ($needsAdmin) {
                    $actor = $this->createAdmin($adminValues);
                }

                $this->installer->install($identity, $rules, $plans, $actor);
            }, 'Installation terminée : la première version du site est en ligne.');

        if (!$done) {
            unset($adminValues['password']);
            $this->session->set(self::OLD_INPUT, ['identity' => $identityValues, 'rules' => $ruleValues, 'plans' => $planValues, 'admin' => $adminValues]);

            return $this->redirectToRoute('app_install');
        }

        if ($needsAdmin && $actor instanceof User) {
            $this->loginUser($actor);
        }

        return $this->redirectToRoute('admin_dashboard');
    }

    private function createAdmin(array $values): User
    {
        $email = trim((string) ($values['email'] ?? ''));
        $firstName = trim((string) ($values['firstName'] ?? ''));
        $password = (string) ($values['password'] ?? '');

        if ($firstName === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw new BusinessRuleException('Indique le prénom et une adresse e-mail valide pour le compte administrateur.');
        }

        if (preg_match(self::PASSWORD_PATTERN, $password) !== 1) {
            throw new BusinessRuleException('Le mot de passe administrateur doit faire au moins 12 caractères, avec des lettres et des chiffres.');
        }

        return $this->installer->createAdmin($email, $firstName, $password);
    }

    private function plan(array $values): WebsitePlanInput
    {
        $input = new WebsitePlanInput();
        $input->billing = PlanBilling::tryFrom((string) ($values['billing'] ?? '')) ?? PlanBilling::PER_BOOKING;
        $input->active = (bool) ($values['active'] ?? false);
        $input->code = trim((string) ($values['code'] ?? '')) ?: null;
        $input->name = trim((string) ($values['name'] ?? '')) ?: null;
        $input->description = trim((string) ($values['description'] ?? '')) ?: null;
        $input->price = $this->cents($values['price'] ?? null) ?? 0;
        $input->yearlyPrice = $this->cents($values['yearlyPrice'] ?? null);
        $input->vatRate = 20;
        $input->trialDays = (int) ($values['trialDays'] ?? 0);
        $input->commitmentMonths = 0;
        $input->maxEstablishments = is_numeric($values['maxEstablishments'] ?? null) ? (int) $values['maxEstablishments'] : null;
        $input->unlimitedEstablishments = $input->maxEstablishments === null;
        $input->commissionFree = (bool) ($values['commissionFree'] ?? false);
        $input->featured = (bool) ($values['featured'] ?? false);
        $input->advancedStats = (bool) ($values['advancedStats'] ?? false);

        return $input;
    }

    private function invalidPlans(array $plans): bool
    {
        foreach ($plans as $plan) {
            if ($plan->active && ($plan->code === null || $plan->name === null)) {
                $this->addFlash('error', 'Chaque formule active doit avoir un code et un nom.');

                return true;
            }

            if ($this->invalid($plan)) {
                return true;
            }
        }

        return false;
    }

    private function cents(mixed $value): ?int
    {
        $value = str_replace([' ', ','], ['', '.'], (string) $value);

        return is_numeric($value) ? (int) round((float) $value * 100) : null;
    }

    private function section(Request $request, string $name): array
    {
        $values = $request->request->get($name);

        return is_array($values) ? $values : [];
    }
}
<?php

namespace App\Controller\Pro;

use App\Billing\Enum\BillingPeriod;
use App\Billing\Presenter\PlanPresenter;
use App\Billing\Service\PlanCatalog;
use App\Company\Provider\CurrentCompanyProvider;
use App\Entity\User\User;
use App\Website\Provider\WebsiteProvider;
use NeoPHP\Component\Controller\Contract\AbstractController;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;

class LandingController extends AbstractController
{
    public const STEPS = [
        ['title' => 'Un rendez-vous s’annule', 'text' => 'Ouvrez votre espace pro et choisissez l’horaire libéré dans votre journée.'],
        ['title' => 'Choisissez la remise', 'text' => 'De %d à %d %%. Le créneau part en ligne avec une heure d’expiration.'],
        ['title' => 'Recevez le client', 'text' => 'Vous êtes prévenu dès la réservation. Le client paie sur place.'],
    ];

    public const FAQ = [
        ['question' => 'Et si le client ne vient pas ?', 'answer' => 'Le client s’engage en réservant. Après %d absences non prévenues, son compte est suspendu des réservations pendant %d jours.'],
        ['question' => 'Est-ce que je choisis la remise ?', 'answer' => 'Oui, pour chaque créneau. Vous pouvez aussi enregistrer une remise par défaut par prestation.'],
        ['question' => 'Comment sont payés les clients ?', 'answer' => 'Sur place, comme d’habitude. Nous ne touchons pas à votre encaissement.'],
        ['question' => 'Puis-je retirer un créneau publié ?', 'answer' => 'À tout moment tant qu’il n’est pas réservé, depuis Mes créneaux.'],
        ['question' => 'Et si le client annule ?', 'answer' => 'L’annulation est gratuite jusqu’à %s avant le rendez-vous. Le créneau peut ensuite être republié en un clic.'],
    ];

    public function __construct(
        private PlanCatalog $planCatalog,
        private PlanPresenter $planPresenter,
        private WebsiteProvider $website,
        private CurrentCompanyProvider $context,
    ) {
    }

    #[Route('/pros', name: 'pro_landing', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $user = $this->getUser();
        $rule = $this->website->getRule();
        $plans = $this->planCatalog->plans();
        $period = BillingPeriod::tryFrom($request->query->getString('period')) ?? BillingPeriod::MONTHLY;
        $minutes = $rule->getFreeCancellationMinutes();

        return $this->render('pro/landing/index.html.twig', [
            'period' => $period->value,
            'plans' => $this->planPresenter->offers($plans, $period),
            'periods' => $this->planPresenter->periods($plans, $period),
            'steps' => array_map(static fn (array $step): array => ['title' => $step['title'], 'text' => sprintf($step['text'], $rule->getMinDiscount(), $rule->getMaxDiscount())], self::STEPS),
            'faq' => [
                ['question' => self::FAQ[0]['question'], 'answer' => sprintf(self::FAQ[0]['answer'], $rule->getToleratedNoShows(), $rule->getSuspensionDays())],
                self::FAQ[1],
                self::FAQ[2],
                self::FAQ[3],
                ['question' => self::FAQ[4]['question'], 'answer' => sprintf(self::FAQ[4]['answer'], $minutes >= 60 && $minutes % 60 === 0 ? sprintf('%d h', $minutes / 60) : sprintf('%d min', $minutes))],
            ],
            'isPro' => $user instanceof User && $this->context->getMembership($user) !== null,
        ]);
    }
}
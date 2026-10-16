<?php

namespace App\Controller\Front;

use App\Entity\AppContact;
use App\Report\Service\ReportService;
use App\Support\Dto\ContactInput;
use App\Support\Service\ContactService;
use App\Website\Provider\WebsiteProvider;
use NeoPHP\Component\Container\Attribute\Autowire;
use NeoPHP\Component\Csrf\Attribute\Csrf;
use NeoPHP\Component\Http\Request\Request;
use NeoPHP\Component\Http\Response\RedirectResponse;
use NeoPHP\Component\Http\Response\Response;
use NeoPHP\Component\Routing\Attribute\Route;
use NeoPHP\Component\Session\SessionManagerInterface;

class PageController extends AbstractFrontController
{
    public const LEGAL_DOCUMENTS = [
        'cgu' => 'Conditions d’utilisation',
        'privacy' => 'Confidentialité',
        'mentions' => 'Mentions légales',
        'cgv' => 'Conditions pros',
    ];

    public const LEGAL_UPDATED_AT = '16 octobre 2026';

    public const OLD_CONTACT = 'page.help.old';

    public const CONTACT_SENT = 'page.help.sent';

    public function __construct(
        private WebsiteProvider $website,
        private ContactService $contact,
        private SessionManagerInterface $session,
        #[Autowire(config: 'packages.billing.issuer')] private array $issuer = [],
        #[Autowire(config: 'packages.mail.supportAddress')] private string $supportAddress = 'aide@kreno.fr',
    ) {
    }

    #[Route('/how-it-works', name: 'page_how_it_works', methods: ['GET'])]
    public function howItWorks(): Response
    {
        $rules = $this->rules();
        $minutes = $rules['freeCancellationMinutes'];

        return $this->render('front/page/how_it_works.html.twig', [
            'client' => [
                ['title' => 'Choisissez vos catégories', 'text' => 'Coiffure, esthétique, ostéo, garage… et votre quartier.'],
                ['title' => 'Recevez une alerte', 'text' => 'Dès qu’un pro proche libère un créneau qui vous correspond.'],
                ['title' => 'Réservez en deux clics', 'text' => 'Le créneau est bloqué à votre nom immédiatement.'],
                ['title' => 'Payez sur place', 'text' => 'Au prix réduit affiché, comme un rendez-vous normal.'],
            ],
            'pro' => [
                ['title' => 'Un client annule', 'text' => 'Le créneau vide est publié depuis l’espace pro en quelques secondes.'],
                ['title' => 'Vous fixez la remise', 'text' => sprintf('De %d à %d %%, et l’heure à laquelle l’offre disparaît.', $rules['minDiscount'], $rules['maxDiscount'])],
                ['title' => 'Le quartier est prévenu', 'text' => 'Les clients qui suivent votre catégorie reçoivent une alerte.'],
                ['title' => 'Vous accueillez le client', 'text' => 'Vous êtes prévenu dès la réservation. Rien à encaisser en ligne.'],
            ],
            'rules' => [
                ['title' => 'Paiement sur place', 'text' => 'Aucun paiement en ligne pour les clients.'],
                ['title' => 'Annulation gratuite', 'text' => sprintf('Jusqu’à %s avant le rendez-vous.', $minutes >= 60 && $minutes % 60 === 0 ? sprintf('%d h', $minutes / 60) : sprintf('%d min', $minutes))],
                ['title' => 'Absence non prévenue', 'text' => sprintf('Au-delà de %d, les réservations sont suspendues %d jours.', $rules['toleratedNoShows'], $rules['suspensionDays'])],
                ['title' => 'Pros vérifiés', 'text' => 'Chaque établissement est contrôlé avant sa mise en ligne.'],
            ],
        ]);
    }

    #[Route('/help', name: 'page_help', methods: ['GET'])]
    public function help(): Response
    {
        $old = $this->session->get(self::OLD_CONTACT);
        $sent = $this->session->get(self::CONTACT_SENT);
        $this->session->remove(self::OLD_CONTACT);
        $this->session->remove(self::CONTACT_SENT);
        $old = is_array($old) ? $old : [];
        $rules = $this->rules();

        return $this->render('front/page/help.html.twig', [
            'faq' => $this->faq($rules),
            'topics' => array_map(static fn (string $key, string $label): array => ['value' => $key, 'label' => $label], array_keys(ContactService::TOPICS), ContactService::TOPICS),
            'form' => [
                'topic' => $old['topic'] ?? null,
                'reference' => $old['reference'] ?? null,
                'email' => $old['email'] ?? $this->optionalUser()?->getEmail(),
                'message' => $old['message'] ?? null,
            ],
            'sent' => is_string($sent) ? $sent : null,
            'supportEmail' => $this->supportAddress,
        ]);
    }

    #[Csrf('help')]
    #[Route('/help', name: 'page_help_submit', methods: ['POST'])]
    public function helpSubmit(Request $request): RedirectResponse
    {
        $input = new ContactInput();
        $input->topic = $request->request->getString('topic') ?: null;
        $input->reference = strtoupper(trim($request->request->getString('reference'))) ?: null;
        $input->email = trim($request->request->getString('email')) ?: null;
        $input->message = trim($request->request->getString('message')) ?: null;

        $contact = null;

        if (!$this->invalid($input)) {
            $this->attempt(function () use ($input, &$contact): void {
                $contact = $this->contact->send($input, $this->optionalUser());
            });
        }

        if ($contact instanceof AppContact) {
            $this->session->set(self::CONTACT_SENT, $contact->getToken());
        } else {
            $this->session->set(self::OLD_CONTACT, ['topic' => $input->topic, 'reference' => $input->reference, 'email' => $input->email, 'message' => $input->message]);
        }

        return $this->redirect($this->generateUrl('page_help') . '#contact');
    }

    #[Route('/legal/{document}', name: 'page_legal', methods: ['GET'], requirements: ['document' => 'cgu|privacy|mentions|cgv'], defaults: ['document' => 'cgu'])]
    public function legal(string $document = 'cgu'): Response
    {
        $rules = $this->rules();
        $minutes = $rules['freeCancellationMinutes'];

        return $this->render('front/page/legal.html.twig', [
            'document' => $document,
            'title' => self::LEGAL_DOCUMENTS[$document],
            'updatedAt' => self::LEGAL_UPDATED_AT,
            'documents' => array_map(static fn (string $key, string $label): array => ['key' => $key, 'label' => $label, 'selected' => $key === $document], array_keys(self::LEGAL_DOCUMENTS), self::LEGAL_DOCUMENTS),
            'issuer' => $this->issuer + ['name' => null, 'address' => null, 'siren' => null, 'vatNumber' => null],
            'rules' => $rules + ['cancellationDelay' => $minutes >= 60 && $minutes % 60 === 0 ? sprintf('%d h', $minutes / 60) : sprintf('%d min', $minutes)],
            'supportEmail' => $this->supportAddress,
        ]);
    }

    private function faq(array $rules): array
    {
        $minutes = $rules['freeCancellationMinutes'];
        $delay = $minutes >= 60 && $minutes % 60 === 0 ? sprintf('%d h', $minutes / 60) : sprintf('%d min', $minutes);

        return [
            ['key' => 'booking', 'label' => 'Réserver', 'items' => [
                ['question' => 'Dois-je payer en ligne ?', 'answer' => 'Non. Vous réglez le prix réduit directement chez le professionnel, le jour du rendez-vous.'],
                ['question' => 'Quelles remises sont proposées ?', 'answer' => sprintf('Les professionnels publient leurs créneaux libérés avec une remise de %d à %d %%.', $rules['minDiscount'], $rules['maxDiscount'])],
                ['question' => 'Comment annuler ?', 'answer' => sprintf('Depuis Mon compte, dans Mes réservations, jusqu’à %s avant le rendez-vous et sans frais.', $delay)],
                ['question' => 'Le professionnel peut-il annuler ?', 'answer' => 'Oui en cas d’imprévu. Vous êtes prévenu par e-mail et pouvez réserver un autre créneau.'],
            ]],
            ['key' => 'account', 'label' => 'Mon compte', 'items' => [
                ['question' => 'Comment être prévenu des nouveaux créneaux ?', 'answer' => 'Créez une alerte dans Mon compte, Mes alertes : catégories, distance et horaires.'],
                ['question' => 'Que se passe-t-il si je ne viens pas ?', 'answer' => sprintf('Au-delà de %d absence%s non prévenue%s, les réservations sont suspendues pendant %d jours.', $rules['toleratedNoShows'], $rules['toleratedNoShows'] > 1 ? 's' : '', $rules['toleratedNoShows'] > 1 ? 's' : '', $rules['suspensionDays'])],
                ['question' => 'Comment récupérer mes données ?', 'answer' => 'Dans Mon compte, Réglages, rubrique Mes données : le téléchargement est immédiat.'],
                ['question' => 'Comment supprimer mon compte ?', 'answer' => 'Dans Mon compte, Réglages, en bas de page. La suppression est définitive.'],
            ]],
            ['key' => 'report', 'label' => 'Problème sur place', 'items' => [
                ['question' => 'Le prix n’est pas celui affiché', 'answer' => sprintf('Signalez-le depuis le détail de la réservation dans les %d h. Nous contactons le professionnel.', $rules['reportWindowHours'])],
                ['question' => 'L’établissement était fermé', 'answer' => sprintf('Signalez-le depuis le détail de la réservation dans les %d h. Nous vérifions avec le professionnel.', $rules['reportWindowHours'])],
            ]],
            ['key' => 'pro', 'label' => 'Professionnels', 'items' => [
                ['question' => 'Comment publier mes créneaux ?', 'answer' => 'Créez votre espace pro depuis la page Pour les pros, puis publiez un créneau libéré en quelques secondes.'],
            ]],
        ];
    }

    private function rules(): array
    {
        $rule = $this->website->getRule();

        return [
            'freeCancellationMinutes' => $rule->getFreeCancellationMinutes(),
            'toleratedNoShows' => $rule->getToleratedNoShows(),
            'suspensionDays' => $rule->getSuspensionDays(),
            'minDiscount' => $rule->getMinDiscount(),
            'maxDiscount' => $rule->getMaxDiscount(),
            'reportWindowHours' => ReportService::CUSTOMER_WINDOW_HOURS,
        ];
    }
}
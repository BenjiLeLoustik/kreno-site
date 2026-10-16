<?php

namespace App\Website\Provider;

use App\Entity\AppContact;
use App\Entity\User\User;
use App\Support\Dto\ContactInput;
use App\Support\Enum\ContactTopic;
use App\Support\Mapper\ContactRequestMapper;
use App\Website\Enum\FaqSection;
use App\Website\Enum\HowItWorksStep;
use App\Website\Enum\LegalDocument;
use NeoPHP\Component\Container\Attribute\Autowire;
use NeoPHP\Component\Session\SessionManagerInterface;

class SitePageProvider
{
    public const OLD_CONTACT = 'page.help.old';

    public const CONTACT_SENT = 'page.help.sent';

    public const ISSUER_DEFAULTS = ['name' => null, 'address' => null, 'siren' => null, 'vatNumber' => null];

    public function __construct(
        private SiteRulesProvider $rules,
        private SessionManagerInterface $session,
        #[Autowire(config: 'packages.billing.issuer')]
        private array $issuer = [],
        #[Autowire(config: 'packages.mail.supportAddress')]
        private string $supportAddress = 'aide@kreno.fr',
    ) {
    }

    public function howItWorks(): array
    {
        return HowItWorksStep::grouped($this->rules->rules());
    }

    public function help(?User $user): array
    {
        $old = $this->pull(self::OLD_CONTACT);
        $sent = $this->pull(self::CONTACT_SENT);

        return [
            'faq' => FaqSection::all($this->rules->rules()),
            'topics' => ContactTopic::choices(),
            'form' => $this->helpForm($this->oldValues($old), $user),
            'sent' => $this->sentToken($sent),
            'supportEmail' => $this->supportAddress,
        ];
    }

    public function rememberHelp(ContactInput $input, ?AppContact $contact): void
    {
        if ($contact !== null) {
            $this->session->set(self::CONTACT_SENT, $contact->getToken());

            return;
        }

        $this->session->set(self::OLD_CONTACT, ContactRequestMapper::old($input));
    }

    public function legal(string $document): array
    {
        $selected = LegalDocument::from($document);

        return [
            'document' => $selected->value,
            'title' => $selected->label(),
            'updatedAt' => LegalDocument::UPDATED_AT,
            'documents' => LegalDocument::choices($selected),
            'issuer' => $this->issuer + self::ISSUER_DEFAULTS,
            'rules' => $this->rules->rules(),
            'supportEmail' => $this->supportAddress,
        ];
    }

    private function helpForm(array $old, ?User $user): array
    {
        return [
            'topic' => $old['topic'] ?? null,
            'reference' => $old['reference'] ?? null,
            'email' => $old['email'] ?? $user?->getEmail(),
            'message' => $old['message'] ?? null,
        ];
    }

    private function pull(string $key): mixed
    {
        $value = $this->session->get($key);
        $this->session->remove($key);

        return $value;
    }

    private function oldValues(mixed $old): array
    {
        if (!is_array($old)) {
            return [];
        }

        return $old;
    }

    private function sentToken(mixed $sent): ?string
    {
        if (!is_string($sent)) {
            return null;
        }

        return $sent;
    }
}
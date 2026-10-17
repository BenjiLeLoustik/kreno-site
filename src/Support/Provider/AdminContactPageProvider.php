<?php

namespace App\Support\Provider;

use App\Entity\AppContact;
use App\Repository\AppContactRepository;
use App\Shared\Mapper\AdminQueryMapper;
use App\Support\Enum\AppContactStatus;
use App\Support\Exception\AdminContactNotFoundException;
use App\Support\Presenter\ContactPresenter;

class AdminContactPageProvider
{
    public const PER_PAGE = 50;

    public const ALL_STATUSES = 'all';

    public function __construct(
        private AppContactRepository $contacts,
        private ContactPresenter $presenter,
    ) {
    }

    public function index(int $page, ?AppContactStatus $status, string $search, ?AppContact $selected = null): array
    {
        $contacts = $this->contacts->findForAdmin(
            $status,
            AdminQueryMapper::nullable($search),
            self::PER_PAGE + 1,
            ($page - 1) * self::PER_PAGE,
        );
        $visible = array_slice($contacts, 0, self::PER_PAGE);

        return [
            'filters' => ['q' => $search, 'status' => $status?->value ?? self::ALL_STATUSES, 'page' => $page],
            'hasNext' => count($contacts) > self::PER_PAGE,
            'statuses' => $this->statuses(),
            'contacts' => $this->rows($visible),
            'selected' => $this->detail($selected ?? $visible[0] ?? null),
        ];
    }

    public function contact(int $id): AppContact
    {
        $contact = $this->contacts->find($id);

        if (!$contact instanceof AppContact) {
            throw new AdminContactNotFoundException();
        }

        return $contact;
    }

    private function statuses(): array
    {
        $statuses = [];

        foreach (AppContactStatus::cases() as $status) {
            $statuses[] = [
                'value' => $status->value,
                'label' => $status->label(),
                'count' => $this->contacts->countByStatus($status),
            ];
        }

        return $statuses;
    }

    private function rows(array $contacts): array
    {
        $rows = [];

        foreach ($contacts as $contact) {
            $rows[] = $this->presenter->row($contact);
        }

        return $rows;
    }

    private function detail(?AppContact $contact): ?array
    {
        if ($contact === null) {
            return null;
        }

        return $this->presenter->detail($contact);
    }
}
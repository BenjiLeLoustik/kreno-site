<?php

namespace App\Log\Provider;

use App\Entity\Website\WebsiteConfig;
use App\Log\Helper\ActivityGroupHelper;
use App\Log\Presenter\ActivityLogPresenter;
use App\Repository\ActivityLogRepository;
use App\Shared\Mapper\AdminQueryMapper;

class ActivityJournalProvider
{
    public function __construct(
        private ActivityLogRepository $logs,
        private ActivityLogPresenter $presenter,
    ) {
    }

    public function journal(array $query, ?WebsiteConfig $website, int $size): array
    {
        $group = ActivityGroupHelper::normalize($query['group']);
        $search = AdminQueryMapper::nullable($query['search']);
        $page = $query['page'];
        $types = ActivityGroupHelper::types($group);
        $total = $this->logs->countForAdmin($types, $website, $search);
        $entries = $this->logs->findForAdmin($types, $website, $search, $size, ($page - 1) * $size);

        return [
            'group' => $group,
            'filters' => ActivityGroupHelper::filters($group),
            'q' => $query['search'],
            'version' => $website?->getCode(),
            'page' => $page,
            'pages' => max(1, (int) ceil($total / $size)),
            'total' => $total,
            'entries' => $this->presenter->presentAll($entries),
        ];
    }
}
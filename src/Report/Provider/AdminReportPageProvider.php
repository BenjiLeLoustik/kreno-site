<?php

namespace App\Report\Provider;

use App\Entity\Report\Report;
use App\Report\Enum\AdminReportFilter;
use App\Report\Enum\ReportDirection;
use App\Report\Exception\ReportNotFoundException;
use App\Report\Presenter\ReportPresenter;
use App\Repository\Report\ReportRepository;

class AdminReportPageProvider
{
    public function __construct(
        private ReportRepository $reports,
        private ReportPresenter $presenter,
    ) {
    }

    public function index(AdminReportFilter $filter, ?ReportDirection $direction, ?Report $selected = null): array
    {
        $items = $this->reports->findForAdmin($filter->statuses(), $direction);

        return [
            'filter' => $filter->value,
            'direction' => $direction?->value,
            'filters' => AdminReportFilter::choices(),
            'directions' => $this->directions(),
            'openCount' => $this->reports->countOpen(),
            'rows' => $this->presenter->forAdminList($items),
            'selected' => $this->detail($selected ?? $items[0] ?? null),
        ];
    }

    public function report(int $id): Report
    {
        $report = $this->reports->find($id);

        if (!$report instanceof Report) {
            throw new ReportNotFoundException();
        }

        return $report;
    }

    private function directions(): array
    {
        $directions = [];

        foreach (ReportDirection::cases() as $direction) {
            $directions[] = ['key' => $direction->value, 'label' => $direction->label()];
        }

        return $directions;
    }

    private function detail(?Report $report): ?array
    {
        if ($report === null) {
            return null;
        }

        return $this->presenter->forAdminDetail($report);
    }
}
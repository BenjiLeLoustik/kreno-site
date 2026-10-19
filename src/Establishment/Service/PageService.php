<?php

namespace App\Establishment\Service;

use App\Entity\Company\CompanyEstablishment;
use App\Establishment\Dto\PageInput;
use NeoPHP\Package\Orm\OrmManagerInterface;

class PageService
{
    public function __construct(
        private OrmManagerInterface $orm,
        private OpeningHoursService $openingHours,
    ) {
    }

    public function update(CompanyEstablishment $establishment, PageInput $input): CompanyEstablishment
    {
        return $this->orm->transactional(function () use ($establishment, $input): CompanyEstablishment {
            $establishment->setDescription($this->description($input->description));
            $this->openingHours->replace($establishment, $input->hours, false);
            $this->orm->flush();

            return $establishment;
        });
    }

    private function description(?string $description): ?string
    {
        $description = trim((string) $description);

        if ($description === '') {
            return null;
        }

        return $description;
    }
}
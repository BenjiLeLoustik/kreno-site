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
            $description = trim((string) $input->description);

            $establishment->setDescription($description !== '' ? $description : null);
            $this->openingHours->replace($establishment, $input->hours, false);
            $this->orm->flush();

            return $establishment;
        });
    }
}
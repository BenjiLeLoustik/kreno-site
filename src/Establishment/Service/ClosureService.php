<?php

namespace App\Establishment\Service;

use App\Entity\Company\CompanyEstablishment;
use App\Entity\Establishment\EstablishmentClosure;
use App\Establishment\Dto\ClosureInput;
use App\Establishment\Exception\ClosureEndBeforeStartException;
use App\Establishment\Exception\ClosureOverlapException;
use App\Establishment\Exception\InvalidClosureDatesException;
use App\Establishment\Exception\PastClosureException;
use App\Repository\Establishment\EstablishmentClosureRepository;
use DateTimeImmutable;
use NeoPHP\Package\Orm\OrmManagerInterface;

class ClosureService
{
    public function __construct(
        private OrmManagerInterface $orm,
        private EstablishmentClosureRepository $closures,
    ) {
    }

    public function create(CompanyEstablishment $establishment, ClosureInput $input): EstablishmentClosure
    {
        $startsOn = $this->date($input->startsOn);
        $endsOn = $this->date($input->endsOn);

        if ($endsOn < $startsOn) {
            throw new ClosureEndBeforeStartException();
        }

        if ($endsOn < (new DateTimeImmutable())->setTime(0, 0)) {
            throw new PastClosureException();
        }

        if ($this->closures->overlaps($establishment, $startsOn, $endsOn)) {
            throw new ClosureOverlapException();
        }

        $closure = new EstablishmentClosure();
        $closure->setStartsOn($startsOn);
        $closure->setEndsOn($endsOn);
        $closure->setReason($this->reason($input->reason));

        $establishment->addClosure($closure);
        $this->orm->persist($closure);
        $this->orm->flush();

        return $closure;
    }

    public function delete(EstablishmentClosure $closure): void
    {
        $closure->getEstablishment()?->removeClosure($closure);
        $this->orm->remove($closure);
        $this->orm->flush();
    }

    private function date(?string $value): DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $value);

        if ($date === false) {
            throw new InvalidClosureDatesException();
        }

        return $date;
    }

    private function reason(?string $reason): ?string
    {
        $reason = trim((string) $reason);

        if ($reason === '') {
            return null;
        }

        return $reason;
    }
}
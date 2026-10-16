<?php

namespace App\Establishment\Service;

use App\Entity\Company\CompanyEstablishment;
use App\Entity\Establishment\EstablishmentClosure;
use App\Establishment\Dto\ClosureInput;
use App\Repository\Establishment\EstablishmentClosureRepository;
use App\Shared\Exception\BusinessRuleException;
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
        $startsOn = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $input->startsOn);
        $endsOn = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $input->endsOn);

        if ($startsOn === false || $endsOn === false) {
            throw new BusinessRuleException('Les dates de fermeture sont invalides.');
        }

        if ($endsOn < $startsOn) {
            throw new BusinessRuleException('La date de fin doit suivre la date de début.');
        }

        if ($endsOn < (new DateTimeImmutable())->setTime(0, 0)) {
            throw new BusinessRuleException('Cette fermeture est déjà passée.');
        }

        if ($this->closures->overlaps($establishment, $startsOn, $endsOn)) {
            throw new BusinessRuleException('Une fermeture existe déjà sur cette période.');
        }

        $reason = trim((string) $input->reason);

        $closure = new EstablishmentClosure();
        $closure->setStartsOn($startsOn);
        $closure->setEndsOn($endsOn);
        $closure->setReason($reason !== '' ? $reason : null);

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
}
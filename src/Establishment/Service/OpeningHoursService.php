<?php

namespace App\Establishment\Service;

use App\Entity\Company\CompanyEstablishment;
use App\Entity\Establishment\EstablishmentOpeningHour;
use App\Enum\Calendar\Weekday;
use App\Establishment\Dto\OpeningHourInput;
use App\Repository\Establishment\EstablishmentOpeningHourRepository;
use App\Shared\Exception\BusinessRuleException;
use DateTimeImmutable;
use NeoPHP\Package\Orm\OrmManagerInterface;

class OpeningHoursService
{
    public function __construct(
        private OrmManagerInterface $orm,
        private EstablishmentOpeningHourRepository $hours,
    ) {
    }

    public function replace(CompanyEstablishment $establishment, array $inputs, bool $flush = true): array
    {
        $ranges = $this->validate($inputs);

        foreach ($this->hours->findForEstablishment($establishment) as $hour) {
            $establishment->removeOpeningHour($hour);
            $this->orm->remove($hour);
        }

        $created = [];

        foreach ($ranges as [$weekday, $opensAt, $closesAt]) {
            $hour = new EstablishmentOpeningHour();
            $hour->setWeekday($weekday);
            $hour->setOpensAt($opensAt);
            $hour->setClosesAt($closesAt);
            $establishment->addOpeningHour($hour);
            $this->orm->persist($hour);
            $created[] = $hour;
        }

        if ($flush) {
            $this->orm->flush();
        }

        return $created;
    }

    private function validate(array $inputs): array
    {
        $ranges = [];
        $byDay = [];

        foreach ($inputs as $input) {
            if (!$input instanceof OpeningHourInput) {
                continue;
            }

            $weekday = Weekday::from((int) $input->weekday);
            $opensAt = DateTimeImmutable::createFromFormat('!H:i', (string) $input->opensAt);
            $closesAt = DateTimeImmutable::createFromFormat('!H:i', (string) $input->closesAt);

            if ($opensAt === false || $closesAt === false || $opensAt >= $closesAt) {
                throw new BusinessRuleException('{day} : l\'heure d\'ouverture doit précéder l\'heure de fermeture.', ['day' => $weekday->label()]);
            }

            foreach ($byDay[$weekday->value] ?? [] as [$otherOpensAt, $otherClosesAt]) {
                if ($opensAt < $otherClosesAt && $closesAt > $otherOpensAt) {
                    throw new BusinessRuleException('{day} : deux plages horaires se chevauchent.', ['day' => $weekday->label()]);
                }
            }

            $byDay[$weekday->value][] = [$opensAt, $closesAt];
            $ranges[] = [$weekday, $opensAt, $closesAt];
        }

        usort($ranges, static fn (array $a, array $b): int => [$a[0]->value, $a[1]] <=> [$b[0]->value, $b[1]]);

        return $ranges;
    }
}
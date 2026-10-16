<?php

namespace App\Establishment\Service;

use App\Entity\Company\CompanyEstablishment;
use App\Entity\Establishment\EstablishmentOpeningHour;
use App\Enum\Calendar\Weekday;
use App\Establishment\Dto\OpeningHourInput;
use App\Establishment\Exception\OpeningHourOrderException;
use App\Establishment\Exception\OpeningHourOverlapException;
use App\Repository\Establishment\EstablishmentOpeningHourRepository;
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
            $opensAt = $this->time($input->opensAt);
            $closesAt = $this->time($input->closesAt);

            if ($opensAt === null || $closesAt === null || $opensAt >= $closesAt) {
                throw new OpeningHourOrderException($weekday->label());
            }

            $this->assertNoOverlap($weekday, $byDay[$weekday->value] ?? [], $opensAt, $closesAt);

            $byDay[$weekday->value][] = [$opensAt, $closesAt];
            $ranges[] = [$weekday, $opensAt, $closesAt];
        }

        return $this->sortRanges($ranges);
    }

    private function time(?string $value): ?DateTimeImmutable
    {
        $time = DateTimeImmutable::createFromFormat('!H:i', (string) $value);

        if ($time === false) {
            return null;
        }

        return $time;
    }

    private function assertNoOverlap(
        Weekday $weekday,
        array $dayRanges,
        DateTimeImmutable $opensAt,
        DateTimeImmutable $closesAt,
    ): void {
        foreach ($dayRanges as [$otherOpensAt, $otherClosesAt]) {
            if ($opensAt < $otherClosesAt && $closesAt > $otherOpensAt) {
                throw new OpeningHourOverlapException($weekday->label());
            }
        }
    }

    private function sortRanges(array $ranges): array
    {
        usort($ranges,
            static function (array $a, array $b): int {
                return [
                        $a[0]->value,
                        $a[1]] <=> [$b[0]->value,
                        $b[1]
                    ];
            }
        );

        return $ranges;
    }
}
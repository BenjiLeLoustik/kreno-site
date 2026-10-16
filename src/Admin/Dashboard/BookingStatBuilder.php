<?php

namespace App\Admin\Dashboard;

use App\Repository\BookingRepository;
use DateTimeImmutable;

class BookingStatBuilder extends AbstractStatBuilder
{
    public function __construct(
        private BookingRepository $bookings,
    ) {
    }

    public function getKey(): string
    {
        return 'bookings';
    }

    public function getLabel(): string
    {
        return 'Réservations aujourd’hui';
    }

    public function getRoute(): string
    {
        return 'admin_bookings';
    }

    public function getPosition(): int
    {
        return 30;
    }

    public function build(DateTimeImmutable $now): DashboardStat
    {
        $today = $now->setTime(0, 0);
        $tomorrow = $today->modify('+1 day');
        $cancellations = $this->bookings->countCancelledBetween($today, $tomorrow);

        return new DashboardStat(
            $this->bookings->countCreatedBetween($today, $tomorrow),
            sprintf('%d %s', $cancellations, $this->plural($cancellations, 'annulation', 'annulations')),
        );
    }
}
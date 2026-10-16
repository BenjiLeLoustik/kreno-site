<?php

namespace App\Billing\Service;

use App\Billing\Helper\PeriodHelper;
use App\Entity\Booking;
use App\Entity\Company\Company;
use App\Repository\BookingRepository;
use App\Repository\Company\CompanyInvoiceRepository;
use App\Repository\Company\CompanySubscriptionRepository;
use DateTimeImmutable;

class CommissionBillingService
{
    public function __construct(
        private BookingRepository $bookings,
        private CompanyInvoiceRepository $invoices,
        private CompanySubscriptionRepository $subscriptions,
        private InvoiceService $invoiceService,
        private PaymentService $payments,
    ) {
    }

    public function invoiceMonth(DateTimeImmutable $month): int
    {
        [$from, $to] = PeriodHelper::monthBounds($month);
        $groups = [];

        foreach ($this->bookings->findBillableBetween($from, $to) as $booking) {
            $establishment = $booking->getSlot()?->getEstablishment();
            $company = $establishment?->getCompany();

            if ($company === null) {
                continue;
            }

            $groups[$company->getId()]['company'] = $company;
            $groups[$company->getId()]['lines'][$establishment->getId() . '-' . $booking->getFee()][] = $booking;
        }

        $count = 0;

        foreach ($groups as $group) {
            if ($this->invoiceCompany($group['company'], $group['lines'], $from, $to)) {
                ++$count;
            }
        }

        return $count;
    }

    private function invoiceCompany(Company $company, array $groups, DateTimeImmutable $from, DateTimeImmutable $to): bool
    {
        if ($this->invoices->commissionInvoiceExists($company, $from)) {
            return false;
        }

        $lines = [];

        foreach ($groups as $bookings) {
            $first = $bookings[0];

            if (!$first instanceof Booking) {
                continue;
            }

            $establishment = $first->getSlot()?->getEstablishment();
            $lines[] = [
                'label' => sprintf('Créneaux réservés · %s', $establishment?->getName()),
                'quantity' => count($bookings),
                'unitPrice' => $first->getFee(),
                'establishment' => $establishment,
                'bookings' => $bookings,
            ];
        }

        if ($lines === []) {
            return false;
        }

        $invoice = $this->invoiceService->issue($company, $lines, $from, $to);

        if ($invoice->getTotalIncludingTax() > 0) {
            $this->payments->collect($invoice);
        }

        return true;
    }
}
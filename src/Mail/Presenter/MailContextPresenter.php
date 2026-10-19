<?php

namespace App\Mail\Presenter;

use App\Billing\Helper\PaymentMethodHelper;
use App\Billing\Helper\PeriodHelper;
use App\Entity\Booking;
use App\Entity\Company\CompanyEstablishment;
use App\Entity\Company\CompanyInvoice;
use App\Entity\Company\CompanyPaymentMethod;
use App\Entity\Company\CompanySubscription;
use App\Entity\Establishment\EstablishmentSlot;
use App\Entity\Report\Report;
use App\Entity\User\User;
use App\Establishment\Helper\CalendarHelper;
use App\Mail\Service\MailService;
use App\Report\Helper\ReportStatusHelper;
use App\Shared\Helper\DateHelper;
use App\Shared\Helper\DurationHelper;
use App\Shared\Helper\MoneyHelper;
use App\User\Helper\UserNameHelper;
use DateTimeImmutable;

class MailContextPresenter
{
    public function __construct(
        private MailService $mail,
    ) {
    }

    public function establishment(?CompanyEstablishment $establishment): array
    {
        if ($establishment === null) {
            return [];
        }

        return [
            'name' => $establishment->getName(),
            'address' => $this->address($establishment),
            'phone' => $establishment->getPhone(),
            'city' => $establishment->getCity()?->getName(),
            'category' => $establishment->getCategory()?->getName(),
            'url' => $this->establishmentUrl($establishment),
        ];
    }

    public function slot(EstablishmentSlot $slot): array
    {
        $startsAt = $slot->getStartsAt();

        return [
            'id' => $slot->getId(),
            'service' => $slot->getService()?->getName(),
            'duration' => DurationHelper::format($slot->getService()?->getDuration() ?? 0),
            'day' => $this->longDate($startsAt),
            'time' => DateHelper::time($startsAt),
            'price' => MoneyHelper::format($slot->getPrice()),
            'originalPrice' => MoneyHelper::format($slot->getOriginalPrice()),
            'discount' => sprintf('-%d %%', $slot->getDiscount()),
            'url' => $this->mail->url('slot_show', ['id' => $slot->getId()]),
            'establishment' => $this->establishment($slot->getEstablishment()),
        ];
    }

    public function booking(Booking $booking): array
    {
        $slot = $booking->getSlot();
        $startsAt = $slot?->getStartsAt();
        $originalPrice = $slot?->getOriginalPrice();
        $reference = ['reference' => $booking->getReference()];

        return [
            'reference' => $booking->getReference(),
            'service' => $slot?->getService()?->getName(),
            'duration' => DurationHelper::format($slot?->getService()?->getDuration() ?? 0),
            'day' => $this->longDate($startsAt),
            'time' => DateHelper::time($startsAt),
            'price' => MoneyHelper::format($booking->getPrice()),
            'originalPrice' => MoneyHelper::format($originalPrice ?? $booking->getPrice()),
            'saved' => MoneyHelper::format(max(0, ($originalPrice ?? 0) - $booking->getPrice())),
            'note' => $booking->getNote(),
            'cancellationReason' => $booking->getCancellationReason(),
            'customer' => $this->customer($booking->getCustomer()),
            'establishment' => $this->establishment($slot?->getEstablishment()),
            'url' => $this->mail->url('account_booking_show', $reference),
            'proUrl' => $this->mail->url('pro_booking_show', ['id' => $booking->getId()]),
            'calendarUrl' => $this->mail->url('account_booking_calendar', $reference),
        ];
    }

    public function subscription(CompanySubscription $subscription): array
    {
        $plan = $subscription->getPlan();

        return [
            'plan' => $plan?->getName(),
            'planCode' => $plan?->getCode(),
            'period' => $subscription->getPeriod()->label(),
            'price' => MoneyHelper::format($subscription->getUnitPrice()),
            'commissionFree' => $plan?->isCommissionFree() ?? false,
            'maxEstablishments' => $plan?->getMaxEstablishments(),
            'startedAt' => PeriodHelper::day($subscription->getStartedAt()),
            'periodEnd' => PeriodHelper::day($subscription->getCurrentPeriodEnd()),
            'cancelAt' => PeriodHelper::day($subscription->getCancelAt()),
            'pausedUntil' => PeriodHelper::day($subscription->getPausedUntil()),
            'graceUntil' => PeriodHelper::day($subscription->getGraceUntil()),
            'url' => $this->mail->url('pro_subscription'),
        ];
    }

    public function invoice(CompanyInvoice $invoice): array
    {
        $number = ['number' => $invoice->getNumber()];

        return [
            'number' => $invoice->getNumber(),
            'type' => $invoice->getType()->value,
            'month' => $this->invoiceMonth($invoice),
            'period' => $this->invoicePeriod($invoice),
            'issuedAt' => PeriodHelper::day($invoice->getIssuedAt()),
            'dueAt' => PeriodHelper::day($invoice->getDueAt()),
            'paidAt' => PeriodHelper::day($invoice->getPaidAt()),
            'totalExcludingTax' => MoneyHelper::formatExact($invoice->getTotalExcludingTax()),
            'tax' => MoneyHelper::formatExact($invoice->getTaxAmount()),
            'total' => MoneyHelper::formatExact($invoice->getTotalIncludingTax()),
            'lines' => $this->invoiceLines($invoice),
            'url' => $this->mail->url('pro_billing_invoice', $number),
            'payUrl' => $this->mail->url('pro_billing_invoice_pay', $number),
        ];
    }

    public function report(Report $report): array
    {
        $id = ['id' => $report->getId()];

        return [
            'id' => $report->getId(),
            'reference' => ReportStatusHelper::reference($report),
            'reason' => $report->getReason()?->label(),
            'description' => $report->getDescription(),
            'resolution' => $report->getResolution(),
            'createdAt' => PeriodHelper::day($report->getCreatedAt()),
            'booking' => $this->reportBooking($report),
            'establishment' => $this->establishment($report->getEstablishment()),
            'url' => $this->mail->url('account_report_show', $id),
            'proUrl' => $this->mail->url('pro_report_show', $id),
        ];
    }

    public function paymentMethod(?CompanyPaymentMethod $method): ?array
    {
        if ($method === null) {
            return null;
        }

        return [
            'label' => PaymentMethodHelper::label($method),
            'brand' => PaymentMethodHelper::brand($method),
            'expiry' => PaymentMethodHelper::expiry($method),
            'type' => $method->getType()?->value,
        ];
    }

    private function address(CompanyEstablishment $establishment): string
    {
        $address = sprintf(
            '%s, %s %s',
            $establishment->getAddressLine1(),
            $establishment->getPostalCode(),
            $establishment->getLocality(),
        );

        return trim($address, ', ');
    }

    private function establishmentUrl(CompanyEstablishment $establishment): string
    {
        $citySlug = $establishment->getCity()?->getSlug();

        if ($citySlug === null || $establishment->getSlug() === null) {
            return $this->mail->url('home_index');
        }

        return $this->mail->url('shop_show', [
            'city' => $citySlug,
            'slug' => $establishment->getSlug()
        ]);
    }

    private function longDate(?DateTimeImmutable $date): ?string
    {
        if ($date === null) {
            return null;
        }

        return CalendarHelper::longDate($date);
    }

    private function customer(?User $customer): array
    {
        $name = null;

        if ($customer !== null) {
            $name = UserNameHelper::fullName($customer);
        }

        return [
            'name' => $name,
            'firstName' => $customer?->getFirstName(),
            'phone' => $customer?->getPhone(),
        ];
    }

    private function invoiceMonth(CompanyInvoice $invoice): ?string
    {
        $start = $invoice->getPeriodStart();

        if ($start === null) {
            return null;
        }

        return sprintf('%s %s', PeriodHelper::monthName($start), $start->format('Y'));
    }

    private function invoicePeriod(CompanyInvoice $invoice): ?string
    {
        if ($invoice->getPeriodStart() === null || $invoice->getPeriodEnd() === null) {
            return null;
        }

        return PeriodHelper::label($invoice->getPeriodStart(), $invoice->getPeriodEnd());
    }

    private function invoiceLines(CompanyInvoice $invoice): array
    {
        $lines = [];

        foreach ($invoice->getLines() as $line) {
            $lines[] = [
                'label' => $line->getLabel(),
                'quantity' => $line->getQuantity(),
                'amount' => MoneyHelper::format($line->getTotal()),
            ];
        }

        return $lines;
    }

    private function reportBooking(Report $report): ?array
    {
        if ($report->getBooking() === null) {
            return null;
        }

        return $this->booking($report->getBooking());
    }
}
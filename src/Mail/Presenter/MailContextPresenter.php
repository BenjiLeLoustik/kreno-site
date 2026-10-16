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
use App\Establishment\Helper\CalendarHelper;
use App\Mail\Service\MailService;
use App\Report\Helper\ReportStatusHelper;
use App\Shared\Helper\DateHelper;
use App\Shared\Helper\DurationHelper;
use App\Shared\Helper\MoneyHelper;
use App\User\Helper\UserNameHelper;

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
            'address' => trim(sprintf('%s, %s %s', $establishment->getAddressLine1(), $establishment->getPostalCode(), $establishment->getLocality()), ', '),
            'phone' => $establishment->getPhone(),
            'city' => $establishment->getCity()?->getName(),
            'category' => $establishment->getCategory()?->getName(),
            'url' => $establishment->getCity()?->getSlug() !== null && $establishment->getSlug() !== null
                ? $this->mail->url('shop_show', ['city' => $establishment->getCity()->getSlug(), 'slug' => $establishment->getSlug()])
                : $this->mail->url('home_index'),
        ];
    }

    public function slot(EstablishmentSlot $slot): array
    {
        $startsAt = $slot->getStartsAt();

        return [
            'id' => $slot->getId(),
            'service' => $slot->getService()?->getName(),
            'duration' => DurationHelper::format($slot->getService()?->getDuration() ?? 0),
            'day' => $startsAt !== null ? CalendarHelper::longDate($startsAt) : null,
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
        $customer = $booking->getCustomer();

        return [
            'reference' => $booking->getReference(),
            'service' => $slot?->getService()?->getName(),
            'duration' => DurationHelper::format($slot?->getService()?->getDuration() ?? 0),
            'day' => $startsAt !== null ? CalendarHelper::longDate($startsAt) : null,
            'time' => DateHelper::time($startsAt),
            'price' => MoneyHelper::format($booking->getPrice()),
            'originalPrice' => MoneyHelper::format($slot?->getOriginalPrice() ?? $booking->getPrice()),
            'saved' => MoneyHelper::format(max(0, ($slot?->getOriginalPrice() ?? 0) - $booking->getPrice())),
            'note' => $booking->getNote(),
            'cancellationReason' => $booking->getCancellationReason(),
            'customer' => [
                'name' => $customer !== null ? UserNameHelper::fullName($customer) : null,
                'firstName' => $customer?->getFirstName(),
                'phone' => $customer?->getPhone(),
            ],
            'establishment' => $this->establishment($slot?->getEstablishment()),
            'url' => $this->mail->url('account_booking_show', ['reference' => $booking->getReference()]),
            'proUrl' => $this->mail->url('pro_booking_show', ['id' => $booking->getId()]),
            'calendarUrl' => $this->mail->url('account_booking_calendar', ['reference' => $booking->getReference()]),
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
        $lines = [];

        foreach ($invoice->getLines() as $line) {
            $lines[] = [
                'label' => $line->getLabel(),
                'quantity' => $line->getQuantity(),
                'amount' => MoneyHelper::format($line->getTotal()),
            ];
        }

        return [
            'number' => $invoice->getNumber(),
            'type' => $invoice->getType()->value,
            'month' => $invoice->getPeriodStart() !== null ? sprintf('%s %s', PeriodHelper::monthName($invoice->getPeriodStart()), $invoice->getPeriodStart()->format('Y')) : null,
            'period' => $invoice->getPeriodStart() !== null && $invoice->getPeriodEnd() !== null ? PeriodHelper::label($invoice->getPeriodStart(), $invoice->getPeriodEnd()) : null,
            'issuedAt' => PeriodHelper::day($invoice->getIssuedAt()),
            'dueAt' => PeriodHelper::day($invoice->getDueAt()),
            'paidAt' => PeriodHelper::day($invoice->getPaidAt()),
            'totalExcludingTax' => MoneyHelper::formatExact($invoice->getTotalExcludingTax()),
            'tax' => MoneyHelper::formatExact($invoice->getTaxAmount()),
            'total' => MoneyHelper::formatExact($invoice->getTotalIncludingTax()),
            'lines' => $lines,
            'url' => $this->mail->url('pro_billing_invoice', ['number' => $invoice->getNumber()]),
            'payUrl' => $this->mail->url('pro_billing_invoice_pay', ['number' => $invoice->getNumber()]),
        ];
    }

    public function report(Report $report): array
    {
        return [
            'id' => $report->getId(),
            'reference' => ReportStatusHelper::reference($report),
            'reason' => $report->getReason()?->label(),
            'description' => $report->getDescription(),
            'resolution' => $report->getResolution(),
            'createdAt' => PeriodHelper::day($report->getCreatedAt()),
            'booking' => $report->getBooking() !== null ? $this->booking($report->getBooking()) : null,
            'establishment' => $this->establishment($report->getEstablishment()),
            'url' => $this->mail->url('account_report_show', ['id' => $report->getId()]),
            'proUrl' => $this->mail->url('pro_report_show', ['id' => $report->getId()]),
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
}
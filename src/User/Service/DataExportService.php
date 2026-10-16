<?php

namespace App\User\Service;

use App\Entity\Booking;
use App\Entity\Establishment\EstablishmentFollower;
use App\Entity\Report\Report;
use App\Entity\Report\ReportMessage;
use App\Entity\User\User;
use App\Entity\User\UserAlert;
use App\Entity\User\UserNotification;
use App\Report\Helper\ReportStatusHelper;
use App\Repository\Report\ReportRepository;
use App\Shared\Helper\MoneyHelper;
use App\User\Enum\ExportFile;
use App\User\Enum\ExportText;
use App\User\Exception\ExportFailedException;
use DateTimeInterface;
use ZipArchive;

class DataExportService
{
    private const DATE_FORMAT = 'd/m/Y H:i';

    private const MAX_REPORTS = 1000;

    private const FILE_NAME = 'kreno-mes-donnees-%s.zip';

    private const BOM = "\xEF\xBB\xBF";

    private const FORMULA_PREFIXES = ['=', '+', '-', '@', "\t", "\r"];

    public function __construct(
        private ReportRepository $reports,
    ) {
    }

    public function build(User $user): string
    {
        $file = tempnam(sys_get_temp_dir(), 'kreno-export-');
        $zip = new ZipArchive();

        if ($file === false || $zip->open($file, ZipArchive::OVERWRITE) !== true) {
            throw new ExportFailedException();
        }

        foreach ($this->files($user) as $name => $rows) {
            $zip->addFromString($name, $this->csv(ExportFile::from($name)->header(), $rows));
        }

        $zip->close();
        $content = file_get_contents($file);
        unlink($file);

        if ($content === false) {
            throw new ExportFailedException();
        }

        return $content;
    }

    public function fileName(): string
    {
        return sprintf(self::FILE_NAME, date('Y-m-d'));
    }

    private function files(User $user): array
    {
        $reports = $this->reports->findForReporter($user, self::MAX_REPORTS);

        return [
            ExportFile::PROFILE->value => [$this->profileRow($user)],
            ExportFile::BOOKINGS->value => $this->rows($user->getBookings()->toArray(), $this->bookingRow(...)),
            ExportFile::ALERTS->value => $this->rows($user->getAlerts()->toArray(), $this->alertRow(...)),
            ExportFile::FOLLOWS->value => $this->rows($user->getFollows()->toArray(), $this->followRow(...)),
            ExportFile::REPORTS->value => $this->rows($reports, $this->reportRow(...)),
            ExportFile::REPORT_MESSAGES->value => $this->messages($user, $reports),
            ExportFile::NOTIFICATIONS->value => $this->rows(
                $user->getNotifications()->toArray(),
                $this->notificationRow(...),
            ),
        ];
    }

    private function rows(array $items, callable $row): array
    {
        $rows = [];

        foreach ($items as $item) {
            $rows[] = $row($item);
        }

        return $rows;
    }

    private function profileRow(User $user): array
    {
        return [
            $user->getEmail(),
            $user->getFirstName(),
            $user->getLastName(),
            $user->getPhone(),
            $user->getStatus()->label(),
            $this->date($user->getEmailVerifiedAt()),
            ExportText::yesNo($user->isEmailNotifications()),
            $this->date($user->getCreatedAt()),
            $this->date($user->getLastLoginAt()),
        ];
    }

    private function bookingRow(Booking $booking): array
    {
        return [
            $booking->getReference(),
            $booking->getSlot()?->getEstablishment()?->getName(),
            $booking->getSlot()?->getService()?->getName(),
            $this->date($booking->getSlot()?->getStartsAt()),
            MoneyHelper::formatExact($booking->getPrice()),
            $booking->getStatus()->label(),
            $booking->getNote(),
            $this->date($booking->getCreatedAt()),
            $this->date($booking->getCancelledAt()),
            $booking->getCancellationReason(),
        ];
    }

    private function alertRow(UserAlert $alert): array
    {
        return [
            $alert->getLabel(),
            $alert->getCity()?->getName(),
            $alert->getRadiusKm(),
            $this->categoryNames($alert),
            $alert->getStartTime()?->format('H:i'),
            $alert->getEndTime()?->format('H:i'),
            $this->price($alert->getMaxPrice()),
            ExportText::yesNo($alert->isEnabled()),
            $this->date($alert->getCreatedAt()),
        ];
    }

    private function followRow(EstablishmentFollower $follow): array
    {
        return [
            $follow->getEstablishment()?->getName(),
            $follow->getEstablishment()?->getCity()?->getName(),
            $this->date($follow->getCreatedAt()),
        ];
    }

    private function reportRow(Report $report): array
    {
        return [
            ReportStatusHelper::reference($report),
            $report->getEstablishment()?->getName(),
            $report->getBooking()?->getReference(),
            $report->getReason()?->label(),
            $report->getDescription(),
            $report->getStatus()->label(),
            $report->getResolution(),
            $this->date($report->getCreatedAt()),
        ];
    }

    private function notificationRow(UserNotification $notification): array
    {
        return [
            $notification->getType()?->label(),
            $notification->getTitle(),
            $notification->getBody(),
            $notification->getLink(),
            $this->date($notification->getCreatedAt()),
            $this->date($notification->getReadAt()),
        ];
    }

    private function messages(User $user, array $reports): array
    {
        $rows = [];

        foreach ($reports as $report) {
            foreach ($report->getMessages() as $message) {
                if ($message->isInternal()) {
                    continue;
                }

                $rows[] = [
                    ReportStatusHelper::reference($report),
                    $this->author($user, $message),
                    $message->getBody(),
                    $this->date($message->getCreatedAt()),
                ];
            }
        }

        return $rows;
    }

    private function author(User $user, ReportMessage $message): string
    {
        if ($message->getAuthor() === $user) {
            return ExportText::ME->label();
        }

        if ($message->getAuthor() === null) {
            return ExportText::KRENO->label();
        }

        return ExportText::OTHER_AUTHOR->label();
    }

    private function categoryNames(UserAlert $alert): string
    {
        $names = [];

        foreach ($alert->getCategories() as $category) {
            $names[] = (string) $category->getName();
        }

        return implode(', ', $names);
    }

    private function price(?int $amount): ?string
    {
        if ($amount === null) {
            return null;
        }

        return MoneyHelper::formatExact($amount);
    }

    private function csv(array $header, array $rows): string
    {
        $stream = fopen('php://temp', 'r+');

        if ($stream === false) {
            throw new ExportFailedException();
        }

        fwrite($stream, self::BOM);
        fputcsv($stream, $header, ';', '"', '');

        foreach ($rows as $row) {
            fputcsv($stream, array_map(fn (mixed $value): string => $this->cell($value), $row), ';', '"', '');
        }

        rewind($stream);
        $content = (string) stream_get_contents($stream);
        fclose($stream);

        return $content;
    }

    private function cell(mixed $value): string
    {
        $value = (string) ($value ?? '');

        if ($value === '' || !in_array($value[0], self::FORMULA_PREFIXES, true)) {
            return $value;
        }

        return "'" . $value;
    }

    private function date(?DateTimeInterface $date): ?string
    {
        return $date?->format(self::DATE_FORMAT);
    }
}
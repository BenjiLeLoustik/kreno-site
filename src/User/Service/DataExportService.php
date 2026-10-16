<?php

namespace App\User\Service;

use App\Entity\Booking;
use App\Entity\Category;
use App\Entity\Establishment\EstablishmentFollower;
use App\Entity\Report\Report;
use App\Entity\User\User;
use App\Entity\User\UserAlert;
use App\Entity\User\UserNotification;
use App\Report\Helper\ReportStatusHelper;
use App\Repository\Report\ReportRepository;
use App\Shared\Exception\StorageException;
use App\Shared\Helper\MoneyHelper;
use DateTimeInterface;
use ZipArchive;

class DataExportService
{
    private const DATE_FORMAT = 'd/m/Y H:i';

    private const MAX_REPORTS = 1000;

    public function __construct(
        private ReportRepository $reports,
    ) {
    }

    public function build(User $user): string
    {
        $file = tempnam(sys_get_temp_dir(), 'kreno-export-');
        $zip = new ZipArchive();

        if ($file === false || $zip->open($file, ZipArchive::OVERWRITE) !== true) {
            throw new StorageException('L\'export n\'a pas pu être préparé.');
        }

        $reports = $this->reports->findForReporter($user, self::MAX_REPORTS);

        $zip->addFromString('profil.csv', $this->csv(['E-mail', 'Prénom', 'Nom', 'Téléphone', 'Statut', 'E-mail vérifié le', 'Notifications par e-mail', 'Créé le', 'Dernière connexion'], [[
            $user->getEmail(),
            $user->getFirstName(),
            $user->getLastName(),
            $user->getPhone(),
            $user->getStatus()->label(),
            $this->date($user->getEmailVerifiedAt()),
            $user->isEmailNotifications() ? 'oui' : 'non',
            $this->date($user->getCreatedAt()),
            $this->date($user->getLastLoginAt()),
        ]]));

        $zip->addFromString('reservations.csv', $this->csv(['Référence', 'Établissement', 'Prestation', 'Rendez-vous', 'Prix', 'Statut', 'Note', 'Réservée le', 'Annulée le', 'Motif d\'annulation'], array_map(fn (Booking $booking): array => [
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
        ], $user->getBookings()->toArray())));

        $zip->addFromString('alertes.csv', $this->csv(['Nom', 'Ville', 'Rayon (km)', 'Catégories', 'De', 'À', 'Prix maximum', 'Active', 'Créée le'], array_map(fn (UserAlert $alert): array => [
            $alert->getLabel(),
            $alert->getCity()?->getName(),
            $alert->getRadiusKm(),
            implode(', ', array_map(static fn (Category $category): string => (string) $category->getName(), $alert->getCategories()->toArray())),
            $alert->getStartTime()?->format('H:i'),
            $alert->getEndTime()?->format('H:i'),
            $alert->getMaxPrice() !== null ? MoneyHelper::formatExact($alert->getMaxPrice()) : null,
            $alert->isEnabled() ? 'oui' : 'non',
            $this->date($alert->getCreatedAt()),
        ], $user->getAlerts()->toArray())));

        $zip->addFromString('etablissements-suivis.csv', $this->csv(['Établissement', 'Ville', 'Suivi depuis le'], array_map(fn (EstablishmentFollower $follow): array => [
            $follow->getEstablishment()?->getName(),
            $follow->getEstablishment()?->getCity()?->getName(),
            $this->date($follow->getCreatedAt()),
        ], $user->getFollows()->toArray())));

        $zip->addFromString('signalements.csv', $this->csv(['Référence', 'Établissement', 'Réservation', 'Motif', 'Description', 'Statut', 'Décision', 'Créé le'], array_map(fn (Report $report): array => [
            ReportStatusHelper::reference($report),
            $report->getEstablishment()?->getName(),
            $report->getBooking()?->getReference(),
            $report->getReason()?->label(),
            $report->getDescription(),
            $report->getStatus()->label(),
            $report->getResolution(),
            $this->date($report->getCreatedAt()),
        ], $reports)));

        $zip->addFromString('messages-signalements.csv', $this->csv(['Signalement', 'Auteur', 'Message', 'Envoyé le'], $this->messages($user, $reports)));

        $zip->addFromString('notifications.csv', $this->csv(['Type', 'Titre', 'Message', 'Lien', 'Reçue le', 'Lue le'], array_map(fn (UserNotification $notification): array => [
            $notification->getType()?->label(),
            $notification->getTitle(),
            $notification->getBody(),
            $notification->getLink(),
            $this->date($notification->getCreatedAt()),
            $this->date($notification->getReadAt()),
        ], $user->getNotifications()->toArray())));

        $zip->close();
        $content = file_get_contents($file);
        unlink($file);

        if ($content === false) {
            throw new StorageException('L\'export n\'a pas pu être préparé.');
        }

        return $content;
    }

    public function fileName(): string
    {
        return sprintf('kreno-mes-donnees-%s.zip', date('Y-m-d'));
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
                    match ($message->getAuthor()) {
                        $user => 'Moi',
                        null => 'Kréno',
                        default => 'Établissement ou équipe Kréno',
                    },
                    $message->getBody(),
                    $this->date($message->getCreatedAt()),
                ];
            }
        }

        return $rows;
    }

    private function csv(array $header, array $rows): string
    {
        $stream = fopen('php://temp', 'r+');

        if ($stream === false) {
            throw new StorageException('L\'export n\'a pas pu être préparé.');
        }

        fwrite($stream, "\xEF\xBB\xBF");
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

        return $value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) ? "'" . $value : $value;
    }

    private function date(?DateTimeInterface $date): ?string
    {
        return $date?->format(self::DATE_FORMAT);
    }
}
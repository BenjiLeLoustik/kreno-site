<?php

namespace App\User\Enum;

use App\Shared\Helper\Translation;

enum ExportFile: string
{
    case PROFILE = 'profil.csv';
    case BOOKINGS = 'reservations.csv';
    case ALERTS = 'alertes.csv';
    case FOLLOWS = 'etablissements-suivis.csv';
    case REPORTS = 'signalements.csv';
    case REPORT_MESSAGES = 'messages-signalements.csv';
    case NOTIFICATIONS = 'notifications.csv';

    public function header(): array
    {
        return match ($this) {
            self::PROFILE => [
                Translation::translate('E-mail'),
                Translation::translate('Prénom'),
                Translation::translate('Nom'),
                Translation::translate('Téléphone'),
                Translation::translate('Statut'),
                Translation::translate('E-mail vérifié le'),
                Translation::translate('Notifications par e-mail'),
                Translation::translate('Créé le'),
                Translation::translate('Dernière connexion'),
            ],
            self::BOOKINGS => [
                Translation::translate('Référence'),
                Translation::translate('Établissement'),
                Translation::translate('Prestation'),
                Translation::translate('Rendez-vous'),
                Translation::translate('Prix'),
                Translation::translate('Statut'),
                Translation::translate('Note'),
                Translation::translate('Réservée le'),
                Translation::translate('Annulée le'),
                Translation::translate('Motif d\'annulation'),
            ],
            self::ALERTS => [
                Translation::translate('Nom'),
                Translation::translate('Ville'),
                Translation::translate('Rayon (km)'),
                Translation::translate('Catégories'),
                Translation::translate('De'),
                Translation::translate('À'),
                Translation::translate('Prix maximum'),
                Translation::translate('Active'),
                Translation::translate('Créée le'),
            ],
            self::FOLLOWS => [
                Translation::translate('Établissement'),
                Translation::translate('Ville'),
                Translation::translate('Suivi depuis le'),
            ],
            self::REPORTS => [
                Translation::translate('Référence'),
                Translation::translate('Établissement'),
                Translation::translate('Réservation'),
                Translation::translate('Motif'),
                Translation::translate('Description'),
                Translation::translate('Statut'),
                Translation::translate('Décision'),
                Translation::translate('Créé le'),
            ],
            self::REPORT_MESSAGES => [
                Translation::translate('Signalement'),
                Translation::translate('Auteur'),
                Translation::translate('Message'),
                Translation::translate('Envoyé le'),
            ],
            self::NOTIFICATIONS => [
                Translation::translate('Type'),
                Translation::translate('Titre'),
                Translation::translate('Message'),
                Translation::translate('Lien'),
                Translation::translate('Reçue le'),
                Translation::translate('Lue le'),
            ],
        };
    }
}
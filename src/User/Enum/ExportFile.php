<?php

namespace App\User\Enum;

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
                'E-mail',
                'Prénom',
                'Nom',
                'Téléphone',
                'Statut',
                'E-mail vérifié le',
                'Notifications par e-mail',
                'Créé le',
                'Dernière connexion',
            ],
            self::BOOKINGS => [
                'Référence',
                'Établissement',
                'Prestation',
                'Rendez-vous',
                'Prix',
                'Statut',
                'Note',
                'Réservée le',
                'Annulée le',
                'Motif d\'annulation',
            ],
            self::ALERTS => [
                'Nom',
                'Ville',
                'Rayon (km)',
                'Catégories',
                'De',
                'À',
                'Prix maximum',
                'Active',
                'Créée le',
            ],
            self::FOLLOWS => ['Établissement', 'Ville', 'Suivi depuis le'],
            self::REPORTS => [
                'Référence',
                'Établissement',
                'Réservation',
                'Motif',
                'Description',
                'Statut',
                'Décision',
                'Créé le',
            ],
            self::REPORT_MESSAGES => ['Signalement', 'Auteur', 'Message', 'Envoyé le'],
            self::NOTIFICATIONS => ['Type', 'Titre', 'Message', 'Lien', 'Reçue le', 'Lue le'],
        };
    }
}
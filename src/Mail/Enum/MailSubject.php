<?php

namespace App\Mail\Enum;

use App\Shared\Helper\Translation;

enum MailSubject
{
    case SUBSCRIPTION_STARTED;
    case SUBSCRIPTION_CHANGED;
    case SUBSCRIPTION_CANCELLED;
    case SUBSCRIPTION_REVOKED;
    case SUBSCRIPTION_PAUSED;
    case SUBSCRIPTION_SUSPENDED;
    case CREDIT_NOTE;
    case INVOICE;
    case PAYMENT_CONFIRMED;
    case PAYMENT_FAILED;
    case PAYMENT_ACTION_REQUIRED;
    case PAYMENT_METHOD_ADDED;
    case PRICE_CORRECTION;
    case PRICE_DECREASE;
    case PRICE_INCREASE;
    case PRICE_CHANGE_CANCELLED;
    case BOOKING_CONFIRMED;
    case PRO_BOOKING;
    case BOOKING_CANCELLED;
    case PRO_BOOKING_CANCELLED;
    case BOOKING_CANCELLED_BY_ESTABLISHMENT;
    case BOOKING_REMINDER;
    case BOOKING_NO_SHOW;
    case PRO_VERIFY_CODE;
    case PRO_INVITE;
    case ROLE_CHANGED;
    case MEMBER_JOINED;
    case MEMBER_REMOVED;
    case ESTABLISHMENT_SUBMITTED;
    case MODERATION_SUBMITTED;
    case ESTABLISHMENT_APPROVED;
    case ESTABLISHMENT_REJECTED;
    case DOCUMENT_REQUESTED;
    case ESTABLISHMENT_SUSPENDED;
    case ESTABLISHMENT_RESUMED;
    case PRO_REPORT_SENT;
    case REPORT_RECEIVED;
    case PRO_REPORT_RECEIVED;
    case PRO_REPORT_MESSAGE;
    case REPORT_MESSAGE;
    case PRO_REPORT_DECIDED;
    case REPORT_DECIDED;
    case SLOT_ALERT;
    case STAFF_INVITE;
    case WELCOME;
    case PRO_RESET_PASSWORD;
    case RESET_PASSWORD;
    case PRO_PASSWORD_CHANGED;
    case PASSWORD_CHANGED;
    case ACCOUNT_DELETED;
    case ACCOUNT_SUSPENDED;
    case CONTACT_REPLY;
    case SUPPORT_CONTACT;
    case SUPPORT_FOLLOW_UP;

    public function text(string|int|null ...$values): string
    {
        return sprintf($this->pattern(), ...$values);
    }

    private function pattern(): string
    {
        return match ($this) {
            self::SUBSCRIPTION_STARTED => Translation::translate('%s est en ligne, votre abonnement est actif'),
            self::SUBSCRIPTION_CHANGED => Translation::translate('Votre formule %s est active'),
            self::SUBSCRIPTION_CANCELLED => Translation::translate('Votre résiliation Kréno Pro est enregistrée'),
            self::SUBSCRIPTION_REVOKED => Translation::translate('Votre abonnement Kréno Pro continue'),
            self::SUBSCRIPTION_PAUSED => Translation::translate('Votre abonnement Kréno Pro est en pause jusqu’au %s'),
            self::SUBSCRIPTION_SUSPENDED => Translation::translate('Vos créneaux Kréno ne sont plus publiés : paiement en attente'),
            self::CREDIT_NOTE => Translation::translate('Avoir %s sur votre compte Kréno Pro'),
            self::INVOICE => Translation::translate('Votre facture Kréno Pro de %s'),
            self::PAYMENT_CONFIRMED => Translation::translate('Paiement reçu : facture %s réglée'),
            self::PAYMENT_FAILED => Translation::translate('Action requise : votre paiement Kréno Pro a échoué'),
            self::PAYMENT_ACTION_REQUIRED => Translation::translate('Action requise : confirmez votre paiement Kréno Pro'),
            self::PAYMENT_METHOD_ADDED => Translation::translate('Nouveau moyen de paiement enregistré sur Kréno Pro'),
            self::PRICE_CORRECTION => Translation::translate('Mise à jour : nouveau prix de votre abonnement Kréno Pro au %s'),
            self::PRICE_DECREASE => Translation::translate('Bonne nouvelle : votre abonnement baisse le %s'),
            self::PRICE_INCREASE => Translation::translate('Évolution du prix de votre abonnement Kréno Pro au %s'),
            self::PRICE_CHANGE_CANCELLED => Translation::translate('Annulation : le prix de votre abonnement Kréno Pro ne change pas'),
            self::BOOKING_CONFIRMED => Translation::translate('C’est réservé : %s, %s à %s'),
            self::PRO_BOOKING => Translation::translate('Votre créneau de %s a trouvé preneur'),
            self::BOOKING_CANCELLED => Translation::translate('Ta réservation chez %s est annulée'),
            self::PRO_BOOKING_CANCELLED => Translation::translate('Annulation : le rendez-vous de %s, %s, est libéré'),
            self::BOOKING_CANCELLED_BY_ESTABLISHMENT => Translation::translate('%s a dû annuler ton rendez-vous'),
            self::BOOKING_REMINDER => Translation::translate('Dans 1 h : %s à %s'),
            self::BOOKING_NO_SHOW => Translation::translate('Ton absence à un rendez-vous a été signalée'),
            self::PRO_VERIFY_CODE => Translation::translate('Votre code de vérification Kréno Pro : %s'),
            self::PRO_INVITE => Translation::translate('%s vous invite à rejoindre %s sur Kréno Pro'),
            self::ROLE_CHANGED => Translation::translate('Votre rôle a changé sur Kréno Pro'),
            self::MEMBER_JOINED => Translation::translate('%s a rejoint %s sur Kréno Pro'),
            self::MEMBER_REMOVED => Translation::translate('Votre accès à %s sur Kréno Pro a été retiré'),
            self::ESTABLISHMENT_SUBMITTED => Translation::translate('%s : inscription reçue, vérification en cours'),
            self::MODERATION_SUBMITTED => Translation::translate('Nouvel établissement à valider : %s'),
            self::ESTABLISHMENT_APPROVED => Translation::translate('%s est en ligne sur Kréno'),
            self::ESTABLISHMENT_REJECTED => Translation::translate('%s : votre inscription n’a pas pu être validée'),
            self::DOCUMENT_REQUESTED => Translation::translate('%s : un justificatif est nécessaire'),
            self::ESTABLISHMENT_SUSPENDED => Translation::translate('%s est suspendu sur Kréno'),
            self::ESTABLISHMENT_RESUMED => Translation::translate('%s est de nouveau en ligne sur Kréno'),
            self::PRO_REPORT_SENT => Translation::translate('Signalement %s transmis à Kréno'),
            self::REPORT_RECEIVED => Translation::translate('Nous avons bien reçu ton signalement'),
            self::PRO_REPORT_RECEIVED => Translation::translate('Un client a signalé un problème : %d h pour répondre'),
            self::PRO_REPORT_MESSAGE => Translation::translate('Nouveau message sur le signalement %s'),
            self::REPORT_MESSAGE => Translation::translate('%s a répondu à ton signalement'),
            self::PRO_REPORT_DECIDED => Translation::translate('Signalement %s : décision de Kréno'),
            self::REPORT_DECIDED => Translation::translate('Ton signalement %s est traité'),
            self::SLOT_ALERT => Translation::translate('%s : créneau %s à %s (%s)'),
            self::STAFF_INVITE => Translation::translate('%s vous invite dans l’équipe Kréno'),
            self::WELCOME => Translation::translate('Bienvenue sur Kréno, %s'),
            self::PRO_RESET_PASSWORD => Translation::translate('Réinitialisation de votre mot de passe Kréno Pro'),
            self::RESET_PASSWORD => Translation::translate('Réinitialise ton mot de passe Kréno'),
            self::PRO_PASSWORD_CHANGED => Translation::translate('Votre mot de passe Kréno Pro a été modifié'),
            self::PASSWORD_CHANGED => Translation::translate('Ton mot de passe Kréno a été modifié'),
            self::ACCOUNT_DELETED => Translation::translate('Ton compte Kréno est supprimé'),
            self::ACCOUNT_SUSPENDED => Translation::translate('Ton compte Kréno est suspendu'),
            self::CONTACT_REPLY => Translation::translate('Réponse à votre demande · %s'),
            self::SUPPORT_CONTACT => Translation::translate('Contact · %s · %s'),
            self::SUPPORT_FOLLOW_UP => Translation::translate('Réponse client · %s · %s'),
        };
    }
}
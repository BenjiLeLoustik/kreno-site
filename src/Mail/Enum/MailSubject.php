<?php

namespace App\Mail\Enum;

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
            self::SUBSCRIPTION_STARTED => '%s est en ligne, votre abonnement est actif',
            self::SUBSCRIPTION_CHANGED => 'Votre formule %s est active',
            self::SUBSCRIPTION_CANCELLED => 'Votre résiliation Kréno Pro est enregistrée',
            self::SUBSCRIPTION_REVOKED => 'Votre abonnement Kréno Pro continue',
            self::SUBSCRIPTION_PAUSED => 'Votre abonnement Kréno Pro est en pause jusqu’au %s',
            self::SUBSCRIPTION_SUSPENDED => 'Vos créneaux Kréno ne sont plus publiés : paiement en attente',
            self::CREDIT_NOTE => 'Avoir %s sur votre compte Kréno Pro',
            self::INVOICE => 'Votre facture Kréno Pro de %s',
            self::PAYMENT_CONFIRMED => 'Paiement reçu : facture %s réglée',
            self::PAYMENT_FAILED => 'Action requise : votre paiement Kréno Pro a échoué',
            self::PAYMENT_ACTION_REQUIRED => 'Action requise : confirmez votre paiement Kréno Pro',
            self::PAYMENT_METHOD_ADDED => 'Nouveau moyen de paiement enregistré sur Kréno Pro',
            self::PRICE_CORRECTION => 'Mise à jour : nouveau prix de votre abonnement Kréno Pro au %s',
            self::PRICE_DECREASE => 'Bonne nouvelle : votre abonnement baisse le %s',
            self::PRICE_INCREASE => 'Évolution du prix de votre abonnement Kréno Pro au %s',
            self::PRICE_CHANGE_CANCELLED => 'Annulation : le prix de votre abonnement Kréno Pro ne change pas',
            self::BOOKING_CONFIRMED => 'C’est réservé : %s, %s à %s',
            self::PRO_BOOKING => 'Votre créneau de %s a trouvé preneur',
            self::BOOKING_CANCELLED => 'Ta réservation chez %s est annulée',
            self::PRO_BOOKING_CANCELLED => 'Annulation : le rendez-vous de %s, %s, est libéré',
            self::BOOKING_CANCELLED_BY_ESTABLISHMENT => '%s a dû annuler ton rendez-vous',
            self::BOOKING_REMINDER => 'Dans 1 h : %s à %s',
            self::BOOKING_NO_SHOW => 'Ton absence à un rendez-vous a été signalée',
            self::PRO_VERIFY_CODE => 'Votre code de vérification Kréno Pro : %s',
            self::PRO_INVITE => '%s vous invite à rejoindre %s sur Kréno Pro',
            self::ROLE_CHANGED => 'Votre rôle a changé sur Kréno Pro',
            self::MEMBER_JOINED => '%s a rejoint %s sur Kréno Pro',
            self::MEMBER_REMOVED => 'Votre accès à %s sur Kréno Pro a été retiré',
            self::ESTABLISHMENT_SUBMITTED => '%s : inscription reçue, vérification en cours',
            self::MODERATION_SUBMITTED => 'Nouvel établissement à valider : %s',
            self::ESTABLISHMENT_APPROVED => '%s est en ligne sur Kréno',
            self::ESTABLISHMENT_REJECTED => '%s : votre inscription n’a pas pu être validée',
            self::DOCUMENT_REQUESTED => '%s : un justificatif est nécessaire',
            self::ESTABLISHMENT_SUSPENDED => '%s est suspendu sur Kréno',
            self::ESTABLISHMENT_RESUMED => '%s est de nouveau en ligne sur Kréno',
            self::PRO_REPORT_SENT => 'Signalement %s transmis à Kréno',
            self::REPORT_RECEIVED => 'Nous avons bien reçu ton signalement',
            self::PRO_REPORT_RECEIVED => 'Un client a signalé un problème : %d h pour répondre',
            self::PRO_REPORT_MESSAGE => 'Nouveau message sur le signalement %s',
            self::REPORT_MESSAGE => '%s a répondu à ton signalement',
            self::PRO_REPORT_DECIDED => 'Signalement %s : décision de Kréno',
            self::REPORT_DECIDED => 'Ton signalement %s est traité',
            self::SLOT_ALERT => '%s : créneau %s à %s (%s)',
            self::STAFF_INVITE => '%s vous invite dans l’équipe Kréno',
            self::WELCOME => 'Bienvenue sur Kréno, %s',
            self::PRO_RESET_PASSWORD => 'Réinitialisation de votre mot de passe Kréno Pro',
            self::RESET_PASSWORD => 'Réinitialise ton mot de passe Kréno',
            self::PRO_PASSWORD_CHANGED => 'Votre mot de passe Kréno Pro a été modifié',
            self::PASSWORD_CHANGED => 'Ton mot de passe Kréno a été modifié',
            self::ACCOUNT_DELETED => 'Ton compte Kréno est supprimé',
            self::ACCOUNT_SUSPENDED => 'Ton compte Kréno est suspendu',
            self::CONTACT_REPLY => 'Réponse à votre demande · %s',
            self::SUPPORT_CONTACT => 'Contact · %s · %s',
            self::SUPPORT_FOLLOW_UP => 'Réponse client · %s · %s',
        };
    }
}
# Kréno

> Le bon moment, au bon prix.

Kréno publie les créneaux qui viennent de se libérer chez les professionnels du quartier (coiffeurs, esthéticiennes, ostéopathes, garages…) à prix réduit. Le client attrape une place au vol, le professionnel remplit un trou dans son planning au lieu de le perdre.

Le site est construit avec NeoPHP v2, Twig et Tailwind CSS.

## Sommaire

- [Le principe](#le-principe)
- [Les acteurs](#les-acteurs)
- [Le parcours d'un créneau](#le-parcours-dun-créneau)
- [Côté client](#côté-client)
- [Côté professionnel](#côté-professionnel)
- [Côté administration](#côté-administration)
- [Facturation](#facturation)
- [Signalements](#signalements)
- [Règles du site](#règles-du-site)
- [Organisation du code](#organisation-du-code)
- [Conventions](#conventions)

## Le principe

1. Un rendez-vous se libère chez un professionnel (annulation, trou dans la journée).
2. Le professionnel publie ce créneau sur Kréno avec une remise.
3. Le créneau apparaît dans la recherche et déclenche les alertes des clients concernés.
4. Un client le réserve en quelques secondes.
5. Le créneau est retiré automatiquement avant son début s'il n'a pas trouvé preneur.

Kréno ne remplace pas l'agenda du professionnel : il ne sert qu'à écouler les places de dernière minute.

## Les acteurs

| Acteur | Espace | Ton |
|---|---|---|
| Client | Site public et compte client | Tutoiement |
| Professionnel | Espace pro (entreprise, établissements, équipe) | Vouvoiement |
| Équipe Kréno | Administration (modération, support, facturation) | Vouvoiement |

Un **professionnel** appartient à une **entreprise** (`Company`), qui possède un ou plusieurs **établissements** (`CompanyEstablishment`). Chaque membre a un rôle dans l'entreprise (propriétaire, administrateur…) et/ou dans un établissement (gérant, planificateur, réceptionniste, employé).

## Le parcours d'un créneau

```
Prestation ──► Créneau publié ──► Réservation ──► Rendez-vous ──► Honoré / Absence
                     │                  │
                     ▼                  ▼
              Retiré ou expiré     Annulée (client ou établissement)
```

- **Prestation** (`EstablishmentService`) : ce que propose l'établissement, avec un prix, une durée et une remise par défaut.
- **Créneau** (`EstablishmentSlot`) : une prestation à une date et une heure, au prix remisé.
    - Statut `available` tant qu'il est réservable.
    - `withdrawn` si le pro le retire.
    - `expired` s'il atteint son heure de retrait sans réservation.
- **Réservation** (`Booking`) : identifiée par une référence `KR-000000`.
    - Elle est confirmée tout de suite.
    - Le client peut l'annuler gratuitement jusqu'à un délai avant le rendez-vous.
    - L'établissement peut aussi l'annuler ; on propose alors au client des créneaux de remplacement.
    - Après le rendez-vous, le pro la marque honorée ou en absence.

## Côté client

- **Recherche** : par ville, catégorie, moment (maintenant, ce matin, cet après-midi, ce soir, demain, cette semaine), distance. Tri par heure, distance ou remise.
- **Fiche établissement** : description, photos, horaires, fermetures, prestations, créneaux disponibles.
- **Réservation** : confirmation immédiate par e-mail, rappel 1 h avant, ajout au calendrier.
- **Alertes** (`UserAlert`) : ville, catégories, budget, plage horaire, rayon. Chaque nouveau créneau qui correspond déclenche une notification et un e-mail.
- **Abonnements** : suivre un établissement pour être prévenu de chacun de ses créneaux.
- **Compte invité** : on peut réserver sans compte ; un compte est créé et on invite le client à choisir son mot de passe.
- **Signalement** : déclarer un problème sur une réservation ou un établissement.
- **Contact** : formulaire d'aide, avec un suivi de la demande par lien privé.

## Côté professionnel

- **Inscription** : création de l'entreprise, vérification de l'e-mail par code, puis déclaration des établissements (SIRET, adresse, catégorie).
- **Modération** : chaque établissement est vérifié par l'équipe Kréno avant d'être mis en ligne. Elle peut l'accepter, le refuser ou demander un justificatif.
- **Page établissement** : description, horaires d'ouverture, fermetures exceptionnelles, jusqu'à 6 photos.
- **Prestations** : catalogue de l'établissement, avec des suggestions propres à chaque catégorie (gérées dans l'administration).
- **Publication** : choix de la prestation, du jour, de l'heure, de la remise et du délai de retrait. Un créneau peut être attribué à un membre de l'équipe.
- **Agenda et réservations** : vue des créneaux publiés, des réservations, présence ou absence des clients.
- **Équipe** : invitations, rôles par entreprise et par établissement.
- **Statistiques** : créneaux publiés, remplis, chiffre généré.
- **Abonnement et factures** : formule, moyens de paiement, factures et avoirs en PDF.

## Côté administration

- **Modération** des inscriptions d'établissements.
- **Suspension et remise en ligne** d'un établissement : suspendre retire tous ses créneaux en ligne.
- **Catalogue** : villes ouvertes, catégories et suggestions de prestations.
- **Règles du site** : remises, délais et fenêtres (voir [Règles du site](#règles-du-site)).
- **Facturation** : abonnements, factures, gestes commerciaux, changements de tarif annoncés à l'avance.
- **Signalements** : examen, notes internes, décision.
- **Support** : demandes de contact, réponses par e-mail.
- **Journaux** :
    - le journal d'activité garde chaque action sensible (modération, sanction, tarifs…) ;
    - le journal d'erreurs regroupe les erreurs 500 par empreinte, avec un statut de suivi.

## Facturation

Le professionnel paie Kréno de deux façons, selon sa formule (`Plan`) :

- **À la réservation** : des frais à chaque réservation (sauf formule sans commission).
- **Par abonnement** : un prix par période qui couvre un nombre d'établissements. Les établissements au-delà paient les frais à la réservation.

Le cycle :

- **Factures** : émises à chaque période, réglées automatiquement par le moyen de paiement par défaut.
- **Échec de paiement** : on relance le paiement automatiquement, avec une période de grâce.
- **Grâce écoulée** : l'abonnement est suspendu et les créneaux de l'entreprise ne sont plus publiés.
- **Avoirs** : ils annulent tout ou partie d'une facture.
- **Changement de tarif** : il est annoncé à l'avance par e-mail, et peut être corrigé ou annulé avant sa date d'effet.

## Signalements

Deux sens possibles :

| Sens | Auteur | Motifs | Délai |
|---|---|---|---|
| Client → établissement | Client | Prix différent, établissement fermé, prestation non conforme, annulation de dernière minute, comportement, autre | 48 h après le rendez-vous |
| Établissement → client | Pro | Absence, retard, comportement, autre | Une fois l'heure du rendez-vous passée |

Le déroulé :

- **Délai de réponse** : un signalement client laisse 72 h à l'établissement pour répondre. Passé ce délai, il remonte en examen.
- **Priorité dans l'administration** : elle dépend du motif et du nombre de signalements similaires sur 30 jours.
- **Décision de l'équipe** : classer, avertir, suspendre (l'établissement, ou le client pour N jours) ou résoudre.
- **Information des parties** : chacune est prévenue par e-mail et par notification.

## Règles du site

Les règles métier sont réglables dans l'administration (`WebsiteRule`), sans déploiement :

| Règle | Rôle |
|---|---|
| Remise min / max / par défaut | Bornes de la remise d'un créneau |
| Délai de retrait automatique | Combien de minutes avant son début un créneau disparaît |
| Délai de réservation | Délai minimum entre la réservation et le retrait du créneau |
| Fenêtre de publication | Combien de jours à l'avance on peut publier |
| Annulation gratuite | Jusqu'à combien de minutes avant le rendez-vous le client peut annuler |

## Organisation du code

Le code est rangé par domaine métier dans `src/` :

| Dossier | Contenu |
|---|---|
| `Admin/` | Écrans de l'administration (builders NeoAdmin) |
| `Billing/` | Formules, abonnements, factures, paiements, commissions |
| `Booking/` | Réservations, frais, présence, rappels |
| `Catalog/` | Villes, catégories, suggestions de prestations |
| `Company/` | Entreprises, inscription pro, équipe, modération |
| `Establishment/` | Pages, prestations, créneaux, recherche, alertes, photos |
| `Log/` | Journal d'activité et journal d'erreurs |
| `Mail/` | Envoi des e-mails (file `emails`), listeners par domaine |
| `Report/` | Signalements |
| `Support/` | Demandes de contact |
| `User/` | Comptes, notifications, sanctions |
| `Shared/` | Exceptions de base, stockage privé, pages d'erreur, helpers communs |

Dans chaque domaine, on trouve le même découpage :

| Sous-dossier | Rôle |
|---|---|
| `Dto/` | Données d'un formulaire, validées par les contraintes du framework |
| `Enum/` | Valeurs fermées et tous les textes affichés (`label()`…) |
| `Event/` / `Listener/` | Événements métier et réactions (e-mails, notifications) |
| `Exception/` | Une classe par règle métier violée |
| `Helper/` | Fonctions pures, sans dépendance |
| `Presenter/` / `Provider/` | Préparation des données pour les vues |
| `Service/` | Logique métier et écriture en base |

## Conventions

- **Textes affichés** : ils vivent dans des enums (`label()`, `text()`…), jamais en dur dans les services ou les présentateurs.
    - Exceptions à cette règle : les builders d'administration, les messages des DTO (traduits par le validateur), les textes de log et les libellés stockés en base.
- **Exceptions métier** : une classe par cas dans `App\<Domaine>\Exception`, qui étend `BusinessRuleException` (réponse HTTP 422). Le message reste écrit dans la classe.
- **Lisibilité** :
    - des `if` avec retour anticipé plutôt que des ternaires ;
    - des méthodes privées courtes ;
    - 120 caractères maximum par ligne.
- **Ton** : tutoiement pour les clients, vouvoiement pour les pros et sur les pages du site.
- **Vues** : Twig avec des classes sémantiques, et un style écrit en `@apply` dans `assets/css`, sans classes Tailwind dans les templates.
- **Charte graphique** :
    - couleurs : corail pour l'action et le prix réduit, menthe pour « disponible », encre pour l'espace pro ;
    - polices : Bricolage Grotesque pour les titres, Inter pour l'interface ;
    - icônes : Lucide.

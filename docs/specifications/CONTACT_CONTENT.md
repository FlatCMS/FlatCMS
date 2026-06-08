# CONTACT_CONTENT — Contacter FlatCMS

> **Contenu éditorial prêt à intégrer**
>
> Projet : FlatCMS  
> Page : Contact `fr-FR`  
> URL cible : `https://flat-cms.fr/fr-FR/contact/`  
> Version de référence : FlatCMS v1.0.0 LTS Core  
> Date de création : 8 juin 2026  
> Brief parent : `PAGE_BRIEFS.md` — `P0-CONTACT-FR`  
> Documents associés : `PRIVACY_CONTENT.md`, `LICENSING_CONTENT.md`, `CONTENT_STYLE_GUIDE.md`  
> Statut : première version rédactionnelle à valider contre les adresses, canaux et délais réellement disponibles

---

## 1. Objectif de la page

La page Contact doit orienter chaque demande vers le bon canal.

Elle doit permettre de contacter FlatCMS pour :

- une question générale ;
- une demande commerciale ;
- une question sur les licences ;
- un besoin multi-domaines ;
- un partenariat ;
- une demande presse ;
- un problème de documentation ;
- une anomalie reproductible ;
- un signalement de sécurité ;
- une question relative aux données personnelles.

La page ne doit pas laisser croire que :

- toutes les demandes reçoivent une réponse immédiate ;
- le support gratuit couvre une prestation de développement ;
- une vulnérabilité peut être publiée dans un formulaire général ;
- un message envoyé garantit une prise en charge contractuelle ;
- FlatCMS dispose déjà d’une équipe de support permanente ;
- un délai de réponse est garanti sans engagement écrit.

---

# 2. Métadonnées SEO

## URL canonique

```text
https://flat-cms.fr/fr-FR/contact/
```

## Balise `<title>`

```text
Contacter FlatCMS — Support, licences et partenariats
```

## Meta description

```text
Contactez FlatCMS pour une question générale, une demande commerciale,
une licence, un partenariat, la documentation ou un signalement de sécurité.
```

## Directive robots

```text
index, follow, max-image-preview:large
```

## Open Graph

### `og:title`

```text
Contacter FlatCMS
```

### `og:description`

```text
Choisissez le bon canal pour contacter FlatCMS : support, licence,
partenariat, presse, documentation ou sécurité.
```

### `og:type`

```text
website
```

### `og:url`

```text
https://flat-cms.fr/fr-FR/contact/
```

### `og:image`

```text
https://flat-cms.fr/assets/images/og/contact-flatcms-fr-FR.webp
```

---

# 3. Hero

## Sur-titre

```text
Nous contacter
```

## H1

```text
Contacter FlatCMS
```

## Introduction

```text
Choisissez le motif correspondant à votre demande afin qu’elle soit
orientée vers le bon canal.
```

```text
Avant d’écrire, consultez la documentation et le centre de dépannage :
la réponse à votre question y est peut-être déjà disponible.
```

## CTA principal

```text
Envoyer un message
```

Destination :

```text
/fr-FR/contact/#formulaire
```

## CTA secondaire

```text
Consulter la documentation
```

Destination :

```text
/fr-FR/documentation/
```

## Lien tertiaire

```text
Résoudre un problème
```

Destination :

```text
/fr-FR/documentation/depannage/
```

---

# 4. Choisir le bon motif

## H2

```text
Quel est le sujet de votre demande ?
```

## H3 — Question générale

Pour :

- comprendre FlatCMS ;
- demander une information publique ;
- signaler une erreur non technique sur le site ;
- demander une orientation.

CTA :

```text
Poser une question générale
```

Valeur du formulaire :

```text
general
```

---

## H3 — Support technique

Pour :

- une erreur reproductible ;
- une installation ;
- un module ;
- un Builder ;
- une mise à jour ;
- une activation de licence ;
- un problème lié à une version prise en charge.

CTA :

```text
Demander une aide technique
```

Valeur du formulaire :

```text
support
```

---

## H3 — Licence ou abonnement

Pour :

- activation ;
- renouvellement ;
- expiration ;
- changement de domaine ;
- facture ;
- Bundle Duo ;
- Suite complète ;
- conditions commerciales.

CTA :

```text
Contacter le service licences
```

Valeur du formulaire :

```text
license
```

---

## H3 — Besoin multi-domaines

Pour :

- plusieurs sites ;
- une agence ;
- un parc client ;
- une offre spécifique ;
- une migration de plusieurs domaines.

CTA :

```text
Demander une offre multi-domaines
```

Valeur du formulaire :

```text
multi-domain
```

---

## H3 — Partenariat ou presse

Pour :

- collaboration ;
- article ;
- interview ;
- événement ;
- intégration ;
- partenariat technique ;
- demande de logo ou kit média.

CTA :

```text
Proposer un partenariat
```

Valeur du formulaire :

```text
partnership
```

---

## H3 — Documentation

Pour :

- une erreur dans un guide ;
- une commande incorrecte ;
- un lien cassé ;
- une capture obsolète ;
- une traduction ;
- une amélioration éditoriale.

CTA :

```text
Signaler une erreur de documentation
```

Valeur du formulaire :

```text
documentation
```

---

## H3 — Sécurité

Pour :

- une vulnérabilité ;
- une fuite de données ;
- un comportement exploitable ;
- une faiblesse de contrôle d’accès ;
- une exposition de secret ;
- une faille dans une release officielle.

CTA :

```text
Signaler une vulnérabilité
```

Destination :

```text
/fr-FR/securite/signaler-une-vulnerabilite/
```

## Règle

```text
Ne transmettez pas les détails techniques d’une vulnérabilité dans le
formulaire général.
```

---

## H3 — Données personnelles

Pour :

- droit d’accès ;
- rectification ;
- effacement ;
- limitation ;
- opposition ;
- question relative aux traitements.

CTA :

```text
Exercer un droit relatif à mes données
```

Valeur du formulaire :

```text
privacy
```

---

# 5. Avant de contacter le support

## H2

```text
Préparer les informations utiles
```

Pour accélérer le diagnostic, indiquez :

- version exacte de FlatCMS ;
- version PHP ;
- serveur Apache ou Nginx ;
- système d’exploitation ;
- module concerné ;
- URL ou écran concerné ;
- action effectuée ;
- résultat attendu ;
- résultat obtenu ;
- message d’erreur exact ;
- extrait de log pertinent ;
- étapes de reproduction ;
- modifications récentes.

## Ne jamais envoyer

- mot de passe ;
- clé API ;
- clé de licence complète ;
- cookie de session ;
- fichier `.env.local` ;
- sauvegarde complète non demandée ;
- données personnelles de vos utilisateurs ;
- accès administrateur dans le premier message ;
- secret SMTP ;
- clé SSH privée.

## Masquage

Exemple :

```text
OPENAI_API_KEY=sk-...abcd
```

et non la valeur complète.

---

# 6. Périmètre du support

## H2

```text
Ce que le support peut couvrir
```

## Support communautaire ou général

Selon les ressources disponibles :

- orientation vers la documentation ;
- clarification d’une fonction ;
- signalement d’une anomalie ;
- vérification d’un problème connu ;
- demande d’information sur une release.

## Support d’un Builder actif

Selon l’abonnement et les conditions commerciales :

- activation ;
- domaine ;
- mise à jour ;
- bug reproductible ;
- compatibilité avec une version prise en charge ;
- fonctionnement d’un widget ;
- accès aux ressources premium ;
- erreur du système de licence.

## Non inclus par défaut

- création complète du site ;
- développement sur mesure ;
- administration du serveur ;
- audit complet ;
- migration de contenu ;
- rédaction ;
- référencement ;
- personnalisation profonde d’un thème ;
- correction d’un module tiers ;
- formation individuelle ;
- intervention urgente garantie.

## Formulation recommandée

```text
Le support aide à diagnostiquer les fonctions officielles de FlatCMS et
des composants couverts. Les prestations de développement, d’intégration
ou d’administration peuvent nécessiter une offre distincte.
```

---

# 7. Délais de réponse

## H2

```text
Traitement des demandes
```

## Formulation publique prudente

```text
Les demandes sont traitées les jours ouvrés dans les meilleurs délais.
```

## Ne pas annoncer avant validation

- réponse sous 24 heures ;
- support 7 jours sur 7 ;
- astreinte ;
- SLA ;
- délai garanti ;
- assistance téléphonique permanente.

## Priorisation possible

1. signalement de sécurité critique ;
2. licence ou paiement bloquant ;
3. bug reproductible sur une version stable ;
4. installation ;
5. documentation ;
6. demande générale ;
7. partenariat.

## Important

La priorité réelle dépend :

- de la gravité ;
- de la reproductibilité ;
- de la version ;
- du contrat ;
- des ressources disponibles ;
- de la qualité des informations transmises.

---

# 8. Formulaire de contact

## H2

```text
Envoyer un message
```

## Ancre

```text
#formulaire
```

## Champs requis

### Motif

Type :

```text
select
```

Valeurs :

```text
Question générale
Support technique
Licence ou abonnement
Besoin multi-domaines
Partenariat ou presse
Documentation
Données personnelles
Autre
```

### Nom

```text
Votre nom
```

### Adresse e-mail

```text
Votre adresse e-mail
```

### Sujet

```text
Résumez votre demande
```

### Message

```text
Décrivez votre demande avec les informations nécessaires.
```

### Consentement ou information

```text
J’ai pris connaissance de la politique de confidentialité et des
informations relatives au traitement de ma demande.
```

## Champs conditionnels pour le support

- version FlatCMS ;
- version PHP ;
- serveur web ;
- système ;
- module ;
- message d’erreur ;
- URL concernée ;
- étapes de reproduction.

## Champs conditionnels pour une licence

- produit ;
- offre ;
- domaine partiellement masqué si nécessaire ;
- référence de commande ;
- adresse de facturation professionnelle si utile.

## Champs non obligatoires

- entreprise ;
- site web ;
- téléphone.

Un numéro de téléphone ne doit pas être imposé pour une demande qui peut
être traitée par e-mail.

---

# 9. Principe de minimisation

## H2

```text
Demander uniquement les données utiles
```

Le formulaire ne doit collecter que les informations nécessaires à :

- comprendre la demande ;
- répondre ;
- assurer le suivi ;
- respecter une obligation légale éventuelle ;
- prévenir les abus.

## Éviter

- date de naissance ;
- adresse postale pour une question générale ;
- téléphone obligatoire ;
- fonction professionnelle obligatoire ;
- pièce d’identité hors procédure spécifique ;
- données sensibles ;
- compte utilisateur obligatoire pour un simple contact.

## Champs dynamiques

Afficher les informations techniques uniquement lorsque le motif choisi
le justifie.

Exemple :

```text
Motif : Partenariat
→ ne pas demander la version PHP
```

---

# 10. Validation du formulaire

## H2

```text
Valider côté navigateur et côté serveur
```

## Validation côté navigateur

Elle améliore l’expérience :

- champ requis ;
- taille minimale ;
- taille maximale ;
- format d’e-mail ;
- choix du motif ;
- message d’aide.

## Validation côté serveur

Elle est obligatoire, car la validation JavaScript peut être contournée.

Le serveur doit vérifier :

- type ;
- longueur ;
- encodage ;
- valeurs autorisées ;
- motif reconnu ;
- e-mail ;
- taille du message ;
- pièce jointe ;
- fréquence ;
- token CSRF ;
- origine selon la politique.

## Longueurs proposées

```text
Nom : 2 à 100 caractères
Sujet : 5 à 160 caractères
Message : 20 à 10 000 caractères
Entreprise : 0 à 160 caractères
URL : 0 à 2 048 caractères
```

Ces limites doivent être adaptées au code et testées.

## Liste blanche

Pour un champ de sélection, le serveur accepte uniquement une valeur
connue.

Il ne doit pas faire confiance à la valeur reçue du navigateur.

---

# 11. Protection CSRF

## H2

```text
Empêcher l’envoi d’une requête forgée
```

Le formulaire doit utiliser une protection CSRF adaptée à l’architecture
FlatCMS.

## Contrôles

- token lié à la session ;
- validation côté serveur ;
- durée de vie maîtrisée ;
- rotation selon la politique ;
- rejet explicite ;
- aucune action sur un simple `GET`.

## Cookies

Les cookies de session doivent utiliser selon le contexte :

```text
Secure
HttpOnly
SameSite
```

## Message utilisateur

```text
Votre session a expiré. Rechargez le formulaire puis envoyez à nouveau
votre message.
```

---

# 12. Protection contre le spam et les abus

## H2

```text
Limiter les abus sans rendre le formulaire inaccessible
```

## Mesures possibles

- champ honeypot ;
- délai minimum de soumission ;
- rate limiting ;
- limitation par IP et session ;
- détection de répétition ;
- blocage temporaire ;
- journalisation ;
- challenge seulement lorsque nécessaire ;
- filtrage du contenu ;
- limitation des URLs.

## Éviter par défaut

- CAPTCHA systématique difficile à utiliser ;
- blocage de tous les VPN ;
- interdiction de domaines e-mail légitimes ;
- filtrage par mots isolés ;
- erreur silencieuse ;
- dépendance à un service tiers sans alternative.

## Rate limiting conceptuel

```text
5 envois par heure et par contexte
```

La valeur réelle doit être ajustée après observation.

## Réponse

```text
Votre demande n’a pas pu être envoyée pour le moment. Réessayez plus tard
ou utilisez le canal alternatif indiqué.
```

Ne pas révéler les règles exactes de détection à un attaquant.

---

# 13. Échappement et affichage

## H2

```text
Traiter le contenu reçu comme non fiable
```

Toute donnée utilisateur affichée dans :

- l’administration ;
- un e-mail ;
- un journal ;
- une page de confirmation ;

doit être encodée selon son contexte.

## Interdictions

- rendre le message comme HTML brut ;
- exécuter une balise ;
- transformer automatiquement une URL en contenu actif ;
- injecter un sujet dans un en-tête sans validation ;
- construire une commande avec une donnée reçue.

## Message libre

Le message peut contenir des caractères légitimes comme :

```text
<
>
'
"
&
```

La sécurité repose sur la validation et l’encodage approprié, pas sur la
suppression aveugle de toute ponctuation.

---

# 14. Pièces jointes

## H2

```text
Ne les activer que si elles sont réellement nécessaires
```

## Recommandation initiale

```text
Aucune pièce jointe dans le formulaire général au lancement.
```

Pour le support, préférer :

- copier un message d’erreur ;
- fournir une URL ;
- envoyer un extrait de log nettoyé ;
- créer ultérieurement un canal sécurisé authentifié.

## Si les pièces jointes sont activées

- liste blanche d’extensions ;
- contrôle MIME réel ;
- taille maximale ;
- nom généré par le serveur ;
- stockage hors du document root ;
- analyse ;
- suppression programmée ;
- interdiction des scripts ;
- journal d’accès ;
- consentement.

## Ne jamais autoriser directement

```text
.php
.phtml
.phar
.cgi
.pl
.js
.htaccess
.env
```

---

# 15. Envoi des e-mails

## H2

```text
Acheminer les demandes de manière fiable
```

## Le système doit définir

- adresse d’expéditeur ;
- adresse de réponse ;
- destinataire par motif ;
- sujet normalisé ;
- identifiant de demande ;
- modèle HTML et texte ;
- journal d’envoi ;
- gestion des erreurs ;
- nouvelle tentative limitée.

## En-têtes

Les données utilisateur ne doivent pas être insérées sans validation dans :

- `From` ;
- `To` ;
- `Cc` ;
- `Bcc` ;
- sujet ;
- en-têtes personnalisés.

## Réponse à l’utilisateur

Utiliser :

```text
Reply-To: adresse validée de l’utilisateur
```

si l’implémentation le permet de manière sûre.

## Délivrabilité

Configurer le domaine avec :

- SPF ;
- DKIM ;
- DMARC ;
- adresse d’expéditeur cohérente ;
- serveur autorisé.

## Important

Un message « envoyé » par l’application ne garantit pas sa réception.

Les erreurs SMTP et les rejets doivent être journalisés.

---

# 16. Confirmation après envoi

## H2

```text
Informer sans révéler de données sensibles
```

## Message de succès

```text
Votre message a été envoyé.

Une confirmation a été transmise à l’adresse indiquée lorsque cela est
possible. Conservez la référence de votre demande :
FC-2026-000123.
```

## Message d’échec

```text
Votre message n’a pas pu être envoyé.

Aucune confirmation de traitement n’a été créée. Réessayez plus tard ou
utilisez le canal alternatif indiqué.
```

## Ne pas afficher

- stack trace ;
- serveur SMTP ;
- identifiant interne ;
- mot de passe ;
- adresse d’administration ;
- règle anti-spam déclenchée ;
- contenu complet du message dans l’URL.

---

# 17. Accusé de réception

## H2

```text
Confirmer la réception de la demande
```

## Objet

```text
FlatCMS — Nous avons reçu votre demande
```

## Contenu proposé

```text
Bonjour,

Votre demande a bien été reçue sous la référence [RÉFÉRENCE].

Motif : [MOTIF]
Sujet : [SUJET]

Ce message confirme uniquement la réception de votre demande. Il ne
constitue pas un engagement de délai, de résolution ou de prise en charge
contractuelle.

Pour votre sécurité, ne répondez pas avec un mot de passe, une clé API,
une clé privée ou une sauvegarde contenant des données personnelles.

FlatCMS
```

## Règle

Ne pas recopier automatiquement l’intégralité d’un message sensible dans
l’accusé de réception.

---

# 18. Routage interne

## H2

```text
Orienter la demande selon son motif
```

## Exemple conceptuel

| Motif | Destination interne |
|---|---|
| Général | contact général |
| Support | file support |
| Licence | file commerciale |
| Multi-domaines | commercial |
| Partenariat | projet / communication |
| Documentation | documentation |
| Données personnelles | responsable du traitement |
| Sécurité | canal de sécurité séparé |

## Règle

Une vulnérabilité ne doit pas être envoyée dans une boîte partagée
générale si un canal de sécurité dédié existe.

---

# 19. Signalement de vulnérabilité

## H2

```text
Utiliser un canal confidentiel dédié
```

## URL recommandée

```text
/fr-FR/securite/signaler-une-vulnerabilite/
```

## Adresse recommandée à créer

```text
security@flat-cms.fr
```

À publier uniquement lorsque :

- la boîte existe ;
- elle est surveillée ;
- les accès sont limités ;
- une procédure de traitement existe ;
- un accusé de réception est configuré.

## Informations utiles

- version ;
- composant ;
- environnement ;
- description ;
- impact ;
- étapes de reproduction ;
- preuve minimale ;
- contournement éventuel ;
- coordonnées du chercheur ;
- préférence d’attribution.

## Ne pas demander

- exploitation contre un site tiers ;
- données personnelles réelles ;
- maintien d’un accès ;
- publication immédiate ;
- destruction d’un système.

## Message

```text
Ne rendez pas la vulnérabilité publique avant que FlatCMS ait pu
l’analyser et préparer une correction raisonnable.
```

## Aucun engagement fictif

Ne pas annoncer :

- programme de bug bounty ;
- récompense financière ;
- délai fixe de correction ;
- protection juridique absolue ;

tant que ces éléments ne sont pas formalisés.

---

# 20. Security.txt

## H2

```text
Publier un point de contact standardisé
```

Chemin recommandé :

```text
/.well-known/security.txt
```

## Contenu conceptuel

```text
Contact: mailto:security@flat-cms.fr
Preferred-Languages: fr, en
Canonical: https://flat-cms.fr/.well-known/security.txt
Policy: https://flat-cms.fr/fr-FR/securite/signaler-une-vulnerabilite/
Expires: [DATE À MAINTENIR]
```

## Règles

- adresse réelle ;
- date d’expiration renouvelée ;
- URL HTTPS ;
- page de politique publiée ;
- langues prises en charge ;
- signature éventuelle si maintenue.

---

# 21. Données personnelles du formulaire

## H2

```text
Informer la personne au moment de la collecte
```

## Informations à afficher près du formulaire

- responsable du traitement ;
- finalité ;
- base juridique ;
- champs obligatoires ;
- destinataires ;
- durée de conservation ;
- droits ;
- moyen d’exercice ;
- lien vers la politique de confidentialité ;
- transfert éventuel ;
- sous-traitants pertinents.

## Texte court conceptuel

```text
Les informations saisies sont utilisées pour traiter votre demande et
assurer son suivi. Les champs signalés comme obligatoires sont nécessaires
pour vous répondre. Pour en savoir plus sur les destinataires, la durée
de conservation et vos droits, consultez la politique de confidentialité.
```

## Base juridique

La base exacte doit être validée selon le motif :

- intérêt légitime ;
- mesures précontractuelles ;
- contrat ;
- obligation légale ;
- consentement lorsqu’il est réellement nécessaire.

Ne pas utiliser automatiquement le consentement pour tous les traitements.

---

# 22. Conservation

## H2

```text
Ne pas conserver les demandes indéfiniment
```

## Politique à définir

Exemple de travail à valider :

| Type de demande | Durée indicative à confirmer |
|---|---:|
| Question générale | 12 mois après clôture |
| Support | durée du contrat + délai de preuve |
| Commercial | durée de la relation ou de la négociation |
| Facturation | durée légale applicable |
| Données personnelles | durée nécessaire au traitement |
| Sécurité | durée nécessaire au suivi et aux obligations |
| Spam bloqué | durée courte de sécurité |

## Règles

- documenter la durée ;
- supprimer ou anonymiser ;
- limiter les accès ;
- inclure les sauvegardes dans la politique ;
- distinguer message et facture ;
- conserver une preuve uniquement si justifiée.

Les durées finales nécessitent une validation juridique et comptable.

---

# 23. Journalisation

## H2

```text
Tracer le traitement sans dupliquer inutilement les données
```

## Journal possible

- référence ;
- date ;
- motif ;
- statut ;
- destinataire interne ;
- utilisateur ayant traité ;
- dates de réponse ;
- erreur technique ;
- clôture.

## Ne pas journaliser en clair

- message complet si inutile ;
- clé ;
- mot de passe ;
- pièce jointe ;
- cookie ;
- données sensibles ;
- détails de vulnérabilité dans un log général.

## Accès

Les journaux doivent être accessibles uniquement aux personnes ayant un
besoin opérationnel.

---

# 24. Statuts d’une demande

## H2

```text
Suivre la demande de manière cohérente
```

Statuts proposés :

```text
received
qualified
waiting_for_information
in_progress
resolved
closed
rejected
spam
```

## Règles

- chaque changement est daté ;
- un rejet indique une raison interne ;
- la personne reçoit une information lorsque cela est approprié ;
- une demande clôturée peut être rouverte selon la politique ;
- un message de sécurité suit un workflow distinct.

---

# 25. Accessibilité du formulaire

## H2

```text
Permettre à chacun d’envoyer une demande
```

## Exigences

- label associé à chaque champ ;
- indication des champs obligatoires ;
- instructions avant la saisie ;
- messages d’erreur précis ;
- erreurs reliées aux champs ;
- focus déplacé vers le résumé des erreurs ;
- navigation clavier ;
- contraste ;
- aucune information uniquement par couleur ;
- autocomplete adapté ;
- bouton explicite ;
- conservation des données en cas d’erreur ;
- délai de session raisonnable ;
- alternative au challenge anti-spam.

## Bouton

Utiliser :

```text
Envoyer le message
```

et non :

```text
Valider
```

---

# 26. Internationalisation

## H2

```text
Traiter les demandes dans plusieurs langues
```

## Locales du site

```text
fr-FR
en-US
de-DE
es-ES
it-IT
pt-PT
```

## Formulaire

- libellés traduits ;
- messages d’erreur traduits ;
- motif normalisé côté serveur ;
- locale enregistrée ;
- accusé de réception dans la langue du formulaire ;
- données techniques non traduites ;
- redirection vers la politique correspondante.

## Capacités de réponse

Ne pas promettre une réponse humaine complète dans les six langues avant
de pouvoir l’assurer.

Formulation prudente :

```text
Les demandes peuvent être envoyées dans les langues proposées par le
site. Le délai et la langue de réponse peuvent dépendre des ressources
disponibles.
```

---

# 27. Messages d’erreur

## H2

```text
Aider l’utilisateur à corriger le formulaire
```

## E-mail

```text
Saisissez une adresse e-mail valide afin que nous puissions vous répondre.
```

## Sujet

```text
Le sujet doit contenir entre 5 et 160 caractères.
```

## Message

```text
Décrivez votre demande en au moins 20 caractères.
```

## Motif

```text
Choisissez le motif qui correspond le mieux à votre demande.
```

## CSRF

```text
Votre session a expiré. Rechargez la page puis envoyez à nouveau le formulaire.
```

## Rate limit

```text
Trop de demandes ont été envoyées récemment. Réessayez plus tard.
```

## Erreur serveur

```text
Le message n’a pas pu être transmis. Réessayez plus tard ou utilisez le
canal alternatif indiqué.
```

---

# 28. Questions fréquentes éditoriales

> Ces réponses n’impliquent pas automatiquement un balisage `FAQPage`.

## H2

```text
Questions fréquentes sur le contact
```

### H3 — Dois-je créer un compte pour contacter FlatCMS ?

```text
Non pour une demande générale. Certains services liés à une licence ou à
une commande peuvent nécessiter une vérification de compte.
```

### H3 — Puis-je envoyer une clé API pour faciliter le diagnostic ?

```text
Non. Ne transmettez jamais une clé API, un mot de passe, une clé privée
ou un fichier `.env.local`.
```

### H3 — Où signaler une vulnérabilité ?

```text
Utilisez la page et l’adresse de sécurité dédiées. Ne publiez pas les
détails dans un formulaire général, un commentaire ou une issue publique.
```

### H3 — Le support peut-il créer mon site ?

```text
Le support standard porte sur les fonctions officielles et les anomalies
reproductibles. Une création ou personnalisation complète relève d’une
prestation distincte.
```

### H3 — Quel délai de réponse est garanti ?

```text
Aucun délai garanti n’est annoncé sans contrat spécifique. Les demandes
sont traitées les jours ouvrés dans les meilleurs délais.
```

### H3 — Puis-je contacter FlatCMS pour plusieurs domaines ?

```text
Oui. Sélectionnez le motif « Besoin multi-domaines » afin de décrire le
nombre de sites et les Builders nécessaires.
```

### H3 — Mon message sera-t-il conservé ?

```text
Les demandes sont conservées pendant une durée adaptée à leur motif et
aux obligations applicables. Les durées définitives sont décrites dans la
politique de confidentialité.
```

---

# 29. CTA final

## H2

```text
Envoyez une demande claire et sans information sensible
```

## Texte

```text
Choisissez le motif adapté, décrivez le contexte et conservez vos mots de
passe, clés et données privées hors du message.
```

## CTA principal

```text
Accéder au formulaire
```

Destination :

```text
/fr-FR/contact/#formulaire
```

## CTA secondaire

```text
Consulter le dépannage
```

Destination :

```text
/fr-FR/documentation/depannage/
```

## Lien tertiaire

```text
Signaler une vulnérabilité
```

Destination :

```text
/fr-FR/securite/signaler-une-vulnerabilite/
```

---

# 30. Données structurées attendues

```text
ContactPage
WebPage
Organization
BreadcrumbList
ImageObject
```

## Identifiants

```text
https://flat-cms.fr/fr-FR/contact/#webpage
https://flat-cms.fr/fr-FR/contact/#breadcrumb
https://flat-cms.fr/fr-FR/contact/#primaryimage
https://flat-cms.fr/#organization
```

## ContactPoint

L’entité `Organization` peut inclure des `ContactPoint` uniquement pour
les canaux réellement publiés.

Exemples à confirmer :

```text
customer support
sales
technical support
```

## Ne pas publier

- numéro non surveillé ;
- horaires inexistants ;
- langues non assurées ;
- délai de réponse garanti sans contrat ;
- adresse personnelle non destinée au public.

---

# 31. Maillage interne attendu

| Section | Destination |
|---|---|
| Hero | Formulaire, documentation, dépannage |
| Support | Centre de dépannage |
| Licence | Tarifs et licences |
| Multi-domaines | Tarifs |
| Partenariat | À propos |
| Documentation | Hub documentation |
| Sécurité | Politique de divulgation |
| Données personnelles | Confidentialité |
| Avant support | Installation et logs |
| CTA final | Formulaire, dépannage, sécurité |

---

# 32. Médias à produire

## Image Open Graph

Concept :

```text
Interface FlatCMS avec plusieurs canaux clairement identifiés :
support, licence, partenariat, documentation et sécurité
```

## Icônes

- question ;
- outil ou support ;
- clé de licence ;
- domaines ;
- partenariat ;
- documentation ;
- bouclier de sécurité ;
- confidentialité.

## Règles

- aucune adresse fictive dans l’image ;
- pas de téléphone non disponible ;
- texte traduit par locale ;
- contenu essentiel également présent en HTML.

---

# 33. Textes alternatifs suggérés

## Canaux

```text
Canaux de contact FlatCMS pour le support, les licences, les partenariats,
la documentation et la sécurité
```

## Formulaire

```text
Formulaire de contact FlatCMS avec choix du motif de la demande
```

## Sécurité

```text
Canal confidentiel destiné au signalement d’une vulnérabilité FlatCMS
```

Les textes finaux doivent correspondre aux médias produits.

---

# 34. Composants du thème suggérés

```text
HeroContact
ContactReasonCards
SupportPreparationChecklist
ContactForm
ConditionalSupportFields
PrivacyNotice
SecurityDisclosureBanner
ResponseExpectations
FaqAccordion
CallToActionBanner
```

---

# 35. Architecture technique recommandée

```text
ContactController
├── ContactRequestValidator
├── ContactRateLimiter
├── ContactCsrfGuard
├── ContactRoutingService
├── ContactMessageService
├── ContactMailService
├── ContactAuditLogger
└── ContactRetentionService
```

## Séparation

```text
Controller
→ reçoit et coordonne

Validator
→ valide les champs

RoutingService
→ choisit le canal

MessageService
→ crée la référence et le statut

MailService
→ envoie

AuditLogger
→ trace les événements minimaux

RetentionService
→ applique la conservation
```

## Ne pas placer

- logique SMTP dans la vue ;
- règle anti-spam uniquement en JavaScript ;
- destinataire contrôlé par l’utilisateur ;
- secret dans le formulaire ;
- message complet dans une URL ;
- clé dans les logs.

---

# 36. Tests unitaires obligatoires

```text
testValidGeneralContactRequest
testInvalidReasonIsRejected
testServerSideValidationIsRequired
testCsrfTokenIsRequired
testRateLimitBlocksRepeatedRequests
testEmailHeaderInjectionIsRejected
testMessageIsEscapedInAdmin
testMessageIsEscapedInEmailTemplate
testSensitiveFieldsAreNeverLogged
testSecurityReasonIsRedirectedToDedicatedChannel
testReferenceIsGenerated
testFailedMailDoesNotMarkRequestAsSent
testPrivacyNoticeIsPresent
```

---

# 37. Tests d’intégration obligatoires

## Formulaire général

1. charger la page ;
2. envoyer des champs valides ;
3. vérifier la référence ;
4. vérifier l’e-mail interne ;
5. vérifier l’accusé ;
6. vérifier le stockage ;
7. vérifier les logs.

## Validation

1. contourner JavaScript ;
2. envoyer une valeur de motif inconnue ;
3. envoyer un sujet trop long ;
4. envoyer du HTML ;
5. vérifier le rejet ou l’encodage ;
6. vérifier l’absence d’exécution.

## CSRF

1. envoyer sans token ;
2. envoyer un token expiré ;
3. envoyer un token d’une autre session ;
4. vérifier le rejet.

## Spam

1. envoyer plusieurs messages ;
2. vérifier le rate limit ;
3. vérifier qu’un utilisateur légitime reçoit un message compréhensible.

## Sécurité

1. sélectionner le motif sécurité ;
2. vérifier qu’aucun détail sensible n’est envoyé à une liste générale ;
3. afficher le canal dédié.

---

# 38. Éléments à confirmer avant publication

- adresse générale ;
- adresse support ;
- adresse commerciale ;
- adresse licences ;
- adresse sécurité ;
- adresse confidentialité ;
- existence de boîtes séparées ;
- identité du responsable du traitement ;
- destinataires réels ;
- outil de ticketing ;
- canal de support ;
- délai public ;
- horaires ;
- langues de réponse ;
- champs obligatoires ;
- pièces jointes ;
- rate limit ;
- protection anti-spam ;
- prestataire SMTP ;
- durée de conservation ;
- suppression ;
- accusé de réception ;
- référence ;
- procédure vulnérabilité ;
- `security.txt` ;
- numéro de téléphone éventuel ;
- adresse postale éventuelle ;
- mentions juridiques.

---

# 39. Checklist éditoriale

- [ ] Chaque motif correspond à un canal réel.
- [ ] Le support n’est pas présenté comme une prestation complète.
- [ ] Aucun délai fictif n’est annoncé.
- [ ] Les informations sensibles sont interdites.
- [ ] La sécurité utilise un canal séparé.
- [ ] Les champs sont minimisés.
- [ ] Le téléphone n’est pas obligatoire.
- [ ] La confidentialité est expliquée.
- [ ] Les durées restent à valider juridiquement.
- [ ] Les messages d’erreur sont compréhensibles.
- [ ] Les six locales sont prévues.
- [ ] Aucun canal inexistant n’est publié.
- [ ] La page renvoie vers la documentation.
- [ ] Les CTA sont explicites.

---

# 40. Checklist technique

- [ ] Validation serveur.
- [ ] Validation client pour l’UX.
- [ ] Token CSRF.
- [ ] Rate limiting.
- [ ] Honeypot ou protection équivalente.
- [ ] Encodage de sortie.
- [ ] Protection des en-têtes e-mail.
- [ ] Pas de pièce jointe par défaut.
- [ ] Routage interne par liste blanche.
- [ ] Référence unique.
- [ ] Statut de traitement.
- [ ] Logs minimisés.
- [ ] Secrets masqués.
- [ ] SMTP avec timeout.
- [ ] Gestion d’erreur.
- [ ] Accusé de réception.
- [ ] Politique de conservation.
- [ ] Canal sécurité.
- [ ] `security.txt`.
- [ ] Tests automatisés.

---

# 41. Checklist d’intégration SEO et UX

- [ ] URL correcte.
- [ ] Canonique auto-référencée.
- [ ] `<html lang="fr-FR">`.
- [ ] Groupe `hreflang`.
- [ ] Title.
- [ ] Meta description.
- [ ] Open Graph.
- [ ] H1 unique.
- [ ] Labels de formulaire.
- [ ] Messages d’erreur accessibles.
- [ ] Navigation clavier.
- [ ] Focus visible.
- [ ] Aucun CAPTCHA bloquant sans alternative.
- [ ] Liens HTML explorables.
- [ ] JSON-LD.
- [ ] Sitemap.
- [ ] Directive robots.
- [ ] Test mobile.
- [ ] Test clavier.
- [ ] Test des liens.
- [ ] Test HTTP `200`.

---

# 42. Sources internes

- `README.md`
- `PAGE_BRIEFS.md`
- `CONTENT_STYLE_GUIDE.md`
- `LICENSING_CONTENT.md`
- `PRICING_CONTENT.md`
- futur `PRIVACY_CONTENT.md`
- module Contact ;
- configuration e-mail ;
- système de rôles ;
- système CSRF ;
- logs ;
- politique de sécurité.

---

# 43. Références externes

- OWASP — Input Validation Cheat Sheet  
  https://cheatsheetseries.owasp.org/cheatsheets/Input_Validation_Cheat_Sheet.html

- OWASP — Cross-Site Request Forgery Prevention Cheat Sheet  
  https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html

- OWASP — File Upload Cheat Sheet  
  https://cheatsheetseries.owasp.org/cheatsheets/File_Upload_Cheat_Sheet.html

- CERT-FR — Contact  
  https://www.cert.ssi.gouv.fr/contact/

- RFC 9116 — A File Format to Aid in Security Vulnerability Disclosure  
  https://www.rfc-editor.org/rfc/rfc9116

Ces références encadrent la validation des entrées, le CSRF, les
téléversements et l’organisation d’un canal de signalement de
vulnérabilités.

---

# 44. Journal des mises à jour

| Date | Version | Modification | Auteur |
|---|---:|---|---|
| 2026-06-08 | 1.0 | Première rédaction complète de la page Contact | ChatGPT / Alain BROYE |

---

# 45. Prochaine action

Après validation et ajout dans le Drive :

```text
Créer LEGAL_NOTICE_CONTENT.md
```

Ce document contiendra la rédaction de la page :

```text
/fr-FR/mentions-legales/
```

Il nécessitera de confirmer l’identité et le statut juridique de
l’éditeur, le directeur de publication, l’hébergeur, les coordonnées et
les informations relatives à la marque.

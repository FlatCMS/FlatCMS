# BUILDER_LICENSE_ENFORCEMENT — Contrat fonctionnel de contrôle des licences Builders

> **Spécification impérative pour Codex**
>
> Projet : FlatCMS  
> Composants concernés : `PagesBuilder`, `MenuBuilder`, `FooterBuilder`  
> Date : 8 juin 2026  
> Statut : règle produit à auditer dans le code avant commercialisation  
> Priorité : critique  
> Principe absolu : une licence ne doit jamais casser le frontend ni supprimer un contenu construit

---

## 1. Objet

Ce document définit le comportement obligatoire des Builders FlatCMS selon :

- l’environnement ;
- la présence ou non d’une licence ;
- la validité de l’abonnement ;
- le domaine autorisé ;
- l’état du service distant de licences.

Codex doit utiliser ce document pour :

1. auditer le code existant ;
2. identifier les écarts ;
3. corriger l’implémentation ;
4. ajouter les tests automatisés ;
5. garantir qu’aucun contrôle de licence ne puisse dégrader le site public.

---

# 2. Périmètre commercial

## Produits

```text
PagesBuilder
MenuBuilder
FooterBuilder
```

## Tarifs annuels HT

```text
PagesBuilder              29,90 € HT/an
MenuBuilder               29,90 € HT/an
FooterBuilder             29,90 € HT/an
Bundle de 2 builders      49,90 € HT/an
Suite des 3 builders      59,90 € HT/an
```

## Unité de licence

```text
1 licence
=
1 abonnement de 12 mois
=
1 nom de domaine de production
```

## Environnements associés

Une licence de production peut reconnaître comme environnements associés :

```text
localhost
127.0.0.1
*.local
*.test
*.localhost
environnement de développement déclaré
environnement de staging déclaré
```

La règle exacte de détection doit être centralisée, configurable et testée.

---

# 3. Principe non négociable

## Règle absolue

```text
Le contrôle de licence ne doit jamais interrompre, masquer, altérer ou
supprimer le rendu frontend déjà construit avec un Builder.
```

Cette règle s’applique dans tous les états :

```text
licence valide
licence absente
licence expirée
licence révoquée
domaine non autorisé
serveur de licence indisponible
timeout réseau
réponse distante invalide
erreur interne du service de licence
```

## Conséquences obligatoires

Le système de licence ne doit jamais :

- retourner une page blanche ;
- provoquer une erreur HTTP 500 ;
- empêcher le chargement d’une page publique ;
- remplacer le contenu construit par un message de licence ;
- supprimer des blocs, widgets, menus ou footers ;
- modifier les données JSON du Builder ;
- effacer un layout existant ;
- désactiver le thème public ;
- injecter une exception non gérée dans le rendu ;
- bloquer le routeur public ;
- rendre un site inutilisable après expiration ;
- dépendre d’un appel distant synchrone pour afficher le frontend.

---

# 4. Séparation obligatoire des responsabilités

Le code doit distinguer trois responsabilités indépendantes.

## 4.1 Rendu public

```text
Lecture des données enregistrées
→ rendu du contenu existant
→ aucune dépendance obligatoire à une licence distante
```

Le moteur de rendu public doit continuer à fonctionner avec les données
déjà enregistrées.

## 4.2 Éditeur Builder

```text
Création
modification
prévisualisation
enregistrement
publication
```

Les droits d’édition peuvent dépendre de l’état de licence selon les
règles définies dans ce document.

## 4.3 Services commerciaux

```text
activation
renouvellement
mises à jour
téléchargement de ressources premium
support
bibliothèque distante
```

Ces services peuvent être limités lorsque la licence n’est pas valide,
sans affecter le rendu public existant.

---

# 5. États de licence

Le service doit exposer un état normalisé.

```php
enum LicenseStatus: string
{
    case LOCAL_DEVELOPMENT = 'local_development';
    case VALID = 'valid';
    case MISSING = 'missing';
    case EXPIRED = 'expired';
    case DOMAIN_MISMATCH = 'domain_mismatch';
    case REVOKED = 'revoked';
    case GRACE_PERIOD = 'grace_period';
    case UNKNOWN = 'unknown';
    case SERVICE_UNAVAILABLE = 'service_unavailable';
}
```

Une implémentation équivalente est acceptable si les états restent
explicites et testables.

---

# 6. Matrice de comportement

| État | Frontend existant | Éditeur | Enregistrement | Mises à jour | Support | Bandeau |
|---|---|---|---|---|---|---|
| Local / développement | Actif | Actif | Actif | Selon canal | Non garanti | Aucun bandeau public |
| Licence valide | Actif | Actif | Actif | Actives | Actif | Aucun |
| Période de grâce | Actif | Actif | Actif | Actives ou limitées | Actif | Administration |
| Licence absente en production de test | Actif | Actif pour test | Actif pour test | Bloquées | Inactif | Bandeau de test |
| Licence expirée | Actif | Lecture seule recommandée | Bloqué pour nouvelle publication | Bloquées | Inactif | Administration |
| Domaine différent | Actif | Limité | Limité | Bloquées | Inactif | Administration |
| Licence révoquée | Actif | Bloqué ou lecture seule | Bloqué | Bloquées | Inactif | Administration |
| Serveur de licence indisponible | Actif | Dernier état connu | Selon cache | Selon cache | Inchangé | Aucun bandeau public d’erreur |

---

# 7. Environnement local

## Règle

```text
Les Builders peuvent être testés gratuitement en local sans licence de
production.
```

## Comportement

- éditeur entièrement accessible ;
- création autorisée ;
- modification autorisée ;
- enregistrement autorisé ;
- prévisualisation autorisée ;
- aucune suppression automatique ;
- aucun bandeau de licence sur le frontend ;
- indication discrète « Environnement local » possible dans l’administration ;
- aucune activation de domaine de production consommée.

## Sécurité

La détection locale ne doit pas se limiter à une valeur contrôlable
facilement par une requête HTTP.

Elle peut combiner :

- hôte ;
- configuration d’environnement ;
- variable serveur ;
- configuration FlatCMS ;
- liste de suffixes autorisés ;
- déclaration de staging dans le compte de licence.

---

# 8. Production sans licence : mode de test

## Règle confirmée

```text
Un Builder peut être testé en production sans licence, mais un bandeau
doit signaler clairement que le Builder n’est pas licencié.
```

## Comportement obligatoire

- le contenu frontend reste affiché ;
- le Builder reste utilisable dans le périmètre de test prévu ;
- les données ne sont jamais supprimées ;
- les mises à jour premium sont indisponibles ;
- le support commercial est indisponible ;
- l’administration affiche un avertissement permanent ;
- le mode sans licence ne doit pas être présenté comme une activation valide.

## Bandeau de test

Texte recommandé :

```text
Mode test — Ce Builder n’est pas activé pour ce domaine de production.
Le contenu reste affiché, mais une licence annuelle est requise pour une
utilisation commerciale, les mises à jour et le support.
```

CTA :

```text
Activer une licence
```

Lien secondaire :

```text
Voir les offres
```

## Emplacement recommandé

Le bandeau doit être visible :

- dans l’administration ;
- dans l’interface du Builder ;
- sur les écrans de configuration du composant.

## Bandeau frontend public

Le bandeau public ne doit être injecté que si cette politique commerciale
est explicitement retenue pour le **mode de test en production**.

Il doit alors :

- être distinct du contenu éditorial ;
- ne pas modifier les données enregistrées ;
- ne pas être inclus dans les exports ;
- ne pas être inclus dans le JSON-LD ;
- ne pas être inclus dans les flux ou API ;
- ne pas apparaître dans l’éditeur local ;
- disparaître immédiatement après activation ;
- ne pas empêcher l’accès au site ;
- être accessible et responsive.

## Règle de prudence

Une licence précédemment valide devenue expirée ne doit pas provoquer
l’apparition soudaine d’un bandeau commercial public sur le site du
client. L’avertissement d’expiration doit rester dans l’administration.

---

# 9. Licence expirée

## Principe

```text
L’expiration de l’abonnement ne casse jamais le frontend.
```

## Ce qui reste garanti

- pages existantes affichées ;
- menus existants affichés ;
- footers existants affichés ;
- widgets existants affichés ;
- données Builder conservées ;
- assets locaux conservés ;
- cache public fonctionnel ;
- export ou sauvegarde des données possible ;
- réactivation sans perte.

## Ce qui peut être désactivé

- téléchargement des mises à jour ;
- nouvelles versions ;
- bibliothèque distante ;
- nouveaux templates premium ;
- support commercial ;
- activation sur un autre domaine ;
- création de nouveaux projets en production ;
- modification et publication en production, selon la politique finale.

## Mode recommandé

```text
Lecture seule dans l’éditeur de production
```

Le client peut :

- ouvrir le Builder ;
- consulter la structure ;
- prévisualiser ;
- exporter ou sauvegarder ;
- consulter l’état de licence ;
- renouveler.

Le client ne peut pas :

- publier une nouvelle version ;
- enregistrer une modification en production ;
- télécharger une mise à jour premium ;
- utiliser les services premium distants.

## Message recommandé

```text
Votre abonnement a expiré. Le site public et les contenus existants
continuent de fonctionner. Renouvelez la licence pour réactiver
l’édition, les mises à jour et le support.
```

---

# 10. Période de grâce

## Recommandation

Prévoir une période de grâce configurable :

```text
15 jours
```

## Pendant cette période

- frontend actif ;
- éditeur actif ;
- enregistrement actif ;
- mises à jour disponibles ou limitées selon décision commerciale ;
- avertissement visible dans l’administration ;
- aucun bandeau public ;
- rappels de renouvellement.

## Objectif

Éviter qu’un échec de paiement ou un problème temporaire de facturation
bloque brutalement le travail du client.

---

# 11. Domaine autorisé

## Normalisation

Le contrôle de domaine doit normaliser :

- schéma HTTP/HTTPS ;
- `www` ;
- port ;
- casse ;
- slash final ;
- noms internationaux ;
- sous-domaines de staging reconnus.

## Exemple

Une licence pour :

```text
example.com
```

doit pouvoir reconnaître, selon la politique définie :

```text
https://example.com
https://www.example.com
```

## Sous-domaines publics

Un sous-domaine public distinct ne doit pas être considéré automatiquement
comme le même site de production.

Exemple :

```text
shop.example.com
client.example.com
portal.example.com
```

La règle doit être explicite dans le contrat et dans le code.

## Changement de domaine

Le système doit permettre :

1. de désactiver l’ancien domaine ;
2. d’activer le nouveau domaine ;
3. de conserver les données Builder ;
4. d’éviter une double activation durable ;
5. de journaliser l’opération.

---

# 12. Indisponibilité du serveur de licences

## Règle absolue

```text
Le frontend ne doit jamais dépendre en temps réel du serveur de licences.
```

## Interdictions

Ne jamais effectuer dans le chemin critique du rendu public :

- appel HTTP vers l’API de licence ;
- validation distante bloquante ;
- attente réseau ;
- renouvellement de jeton obligatoire ;
- vérification DNS externe ;
- exception si l’API ne répond pas.

## Cache local

Le dernier état valide doit être conservé localement avec :

- statut ;
- domaine ;
- produit ;
- date de validation ;
- date d’expiration ;
- prochaine vérification ;
- signature ou preuve d’intégrité ;
- période de tolérance.

## Comportement en panne

```text
Frontend
→ toujours actif

Administration
→ utilise le dernier état connu

Vérification distante
→ réessayée avec backoff

Erreur
→ journalisée sans données sensibles
```

## Backoff recommandé

```text
1 heure
6 heures
24 heures
48 heures
```

Ne pas lancer une requête distante à chaque page vue ou à chaque
chargement de l’administration.

---

# 13. Stockage des données Builder

## Règle

Les données créées par un Builder appartiennent au projet du client et
doivent rester indépendantes de l’état de licence.

## Interdictions

Le système de licence ne doit jamais :

- chiffrer les contenus pour les rendre illisibles après expiration ;
- modifier le schéma des données pour empêcher le rendu ;
- supprimer les widgets premium déjà utilisés ;
- remplacer les données par un message de licence ;
- rendre une sauvegarde inutilisable ;
- empêcher l’export des contenus existants ;
- lier le rendu à un jeton distant éphémère.

## Compatibilité

Un site doit pouvoir afficher les contenus existants après :

- expiration ;
- désactivation ;
- panne réseau ;
- restauration d’une sauvegarde ;
- migration du domaine ;
- mise en maintenance du serveur de licences.

---

# 14. Architecture recommandée

```text
BuilderLicenseManager
├── EnvironmentDetector
├── DomainNormalizer
├── LicenseRepository
├── LicenseApiClient
├── LicenseCache
├── LicensePolicy
├── LicenseBannerPresenter
└── LicenseAuditLogger
```

## `EnvironmentDetector`

Détermine :

```text
local
staging
production
```

## `DomainNormalizer`

Produit un identifiant de domaine stable.

## `LicenseRepository`

Lit et écrit l’état local.

## `LicenseApiClient`

Communique avec le service distant hors du chemin critique frontend.

## `LicenseCache`

Conserve le dernier état valide et les délais de revérification.

## `LicensePolicy`

Décide les capacités autorisées :

```text
canRender
canOpenEditor
canSave
canPublish
canUpdate
canAccessSupport
shouldShowAdminBanner
shouldShowPublicTestBanner
```

## `LicenseBannerPresenter`

Affiche les avertissements sans modifier les contenus enregistrés.

## `LicenseAuditLogger`

Journalise :

- activation ;
- désactivation ;
- expiration ;
- changement de domaine ;
- panne API ;
- invalidation ;
- renouvellement.

---

# 15. API de politique recommandée

```php
interface BuilderLicensePolicyInterface
{
    public function canRender(string $builder): bool;

    public function canOpenEditor(string $builder): bool;

    public function canSave(string $builder): bool;

    public function canPublish(string $builder): bool;

    public function canUpdate(string $builder): bool;

    public function canAccessSupport(string $builder): bool;

    public function shouldShowAdminBanner(string $builder): bool;

    public function shouldShowPublicTestBanner(string $builder): bool;
}
```

## Invariant

```php
public function canRender(string $builder): bool
{
    return true;
}
```

Pour un contenu Builder déjà enregistré, `canRender()` doit rester vrai
quel que soit l’état de licence.

## Important

Le moteur de rendu ne doit pas conditionner son fonctionnement à :

```php
if (!$license->isValid()) {
    return '';
}
```

ou :

```php
throw new LicenseException();
```

---

# 16. Anti-patterns interdits

Codex doit rechercher et supprimer tout code équivalent à :

```php
if (!$license->isValid()) {
    exit;
}
```

```php
if ($license->isExpired()) {
    return null;
}
```

```php
if (!$licenseServer->check()) {
    throw new RuntimeException('License invalid');
}
```

```php
if (!$license->isValid()) {
    $repository->deleteBuilderData();
}
```

```php
if (!$license->isValid()) {
    $html = '<div>Licence requise</div>';
}
```

```php
$licenseApi->validateSynchronouslyDuringFrontendRender();
```

```php
$router->disablePublicBuilderRoutesWhenExpired();
```

```php
$cache->purgeRenderedBuilderContentWhenLicenseExpires();
```

---

# 17. Bandeaux et accessibilité

## Administration

Le bandeau doit :

- être visible ;
- expliquer l’état ;
- indiquer les conséquences réelles ;
- proposer une action ;
- pouvoir être lu au clavier et par lecteur d’écran ;
- ne pas masquer les boutons essentiels ;
- ne pas bloquer la sauvegarde si l’état autorise la sauvegarde ;
- ne pas réapparaître à chaque requête sous forme de modal bloquante.

## Couleurs

Le message ne doit pas dépendre uniquement d’une couleur.

Utiliser :

- icône ;
- titre ;
- texte ;
- statut ;
- CTA.

## Messages

### Licence absente

```text
Builder non activé pour ce domaine
```

### Expiration prochaine

```text
Votre abonnement expire dans 7 jours
```

### Expirée

```text
Abonnement expiré — le frontend reste actif
```

### Service indisponible

```text
Impossible de vérifier la licence pour le moment. Le dernier état connu
est conservé.
```

---

# 18. Confidentialité et sécurité

## Ne jamais envoyer au service de licence

- contenu des pages ;
- données personnelles des visiteurs ;
- mots de passe ;
- clés API tierces ;
- fichiers médias ;
- données Builder complètes ;
- journaux complets ;
- cookies de session.

## Données minimales possibles

- clé ou jeton de licence ;
- identifiant du produit ;
- domaine normalisé ;
- version du Builder ;
- version de FlatCMS ;
- version PHP si nécessaire ;
- identifiant d’installation pseudonyme ;
- date de demande.

## Transport

- HTTPS obligatoire ;
- délai d’expiration court ;
- validation TLS ;
- signature de réponse si prévue ;
- protection contre le rejeu ;
- aucune clé secrète de validation embarquée côté navigateur.

## Logs

Masquer :

- clé complète ;
- jeton ;
- adresse e-mail ;
- détails de paiement.

---

# 19. Tests unitaires obligatoires

## Rendu

```text
testFrontendRendersWithValidLicense
testFrontendRendersWithoutLicense
testFrontendRendersWithExpiredLicense
testFrontendRendersWithRevokedLicense
testFrontendRendersOnDomainMismatch
testFrontendRendersWhenLicenseApiTimesOut
testFrontendRendersWhenLicenseCacheIsCorrupted
```

## Données

```text
testLicenseFailureNeverDeletesBuilderData
testExpirationNeverMutatesSavedLayout
testRevocationNeverMutatesSavedMenu
testDomainMismatchNeverMutatesSavedFooter
```

## Local

```text
testLocalEnvironmentAllowsBuilderWithoutProductionLicense
testLocalEnvironmentDoesNotConsumeProductionActivation
testLocalEnvironmentShowsNoPublicLicenseBanner
```

## Production de test

```text
testUnlicensedProductionShowsTestBanner
testUnlicensedProductionKeepsFrontendContent
testBannerIsNotStoredInsideBuilderData
testBannerIsNotIncludedInStructuredData
```

## Expiration

```text
testExpiredLicenseKeepsFrontendActive
testExpiredLicenseDisablesUpdates
testExpiredLicenseDisablesSupport
testExpiredLicenseShowsAdminWarning
testRenewalRestoresCapabilitiesWithoutDataLoss
```

## Service distant

```text
testFrontendMakesNoLicenseApiCall
testApiTimeoutUsesLastKnownState
testUnknownStateFailsOpenForRendering
testLicenseApiUsesBackoff
```

---

# 20. Tests d’intégration obligatoires

## PagesBuilder

1. créer une page avec licence valide ;
2. publier ;
3. expirer artificiellement la licence ;
4. vider les caches ;
5. recharger le frontend ;
6. vérifier que la page est identique ;
7. renouveler ;
8. vérifier que l’éditeur retrouve toutes les données.

## MenuBuilder

1. créer un méga-menu ;
2. publier ;
3. supprimer la licence locale ;
4. simuler une API indisponible ;
5. vérifier que la navigation reste accessible ;
6. vérifier que les liens restent fonctionnels ;
7. vérifier que le HTML ne contient pas d’erreur de licence.

## FooterBuilder

1. créer un footer ;
2. publier ;
3. simuler expiration et domaine différent ;
4. vérifier que le footer reste affiché ;
5. vérifier que les mentions légales restent accessibles ;
6. vérifier qu’aucune donnée n’est modifiée.

---

# 21. Tests end-to-end obligatoires

## Scénario A — Local sans licence

```text
Étant donné une installation locale sans licence
Quand l’utilisateur ouvre un Builder
Alors il peut créer, enregistrer et prévisualiser
Et aucun bandeau public n’est affiché
```

## Scénario B — Production sans licence

```text
Étant donné un domaine de production sans licence
Quand le Builder est utilisé en mode test
Alors le contenu reste rendu
Et le bandeau de test est affiché selon la politique retenue
Et aucune mise à jour premium n’est disponible
```

## Scénario C — Licence valide

```text
Étant donné une licence valide pour le domaine
Quand le site et l’éditeur sont ouverts
Alors toutes les capacités souscrites sont actives
Et aucun bandeau de licence n’est affiché
```

## Scénario D — Expiration

```text
Étant donné une page publiée avec une licence valide
Quand l’abonnement expire
Alors le rendu public reste strictement fonctionnel
Et les données restent intactes
Et l’administration affiche le statut expiré
Et les services commerciaux sont désactivés
```

## Scénario E — API indisponible

```text
Étant donné un site déjà activé
Quand le serveur de licences ne répond plus
Alors le frontend reste fonctionnel
Et le dernier état valide est utilisé
Et une nouvelle tentative est planifiée
Et aucune exception n’est affichée au visiteur
```

---

# 22. Audit demandé à Codex

Codex doit produire un rapport contenant :

## 22.1 Inventaire

- fichiers de licence ;
- services ;
- middlewares ;
- contrôleurs ;
- hooks ;
- routes ;
- appels réseau ;
- caches ;
- bandeaux ;
- conditions dans les renderers ;
- conditions dans les repositories.

## 22.2 Écarts

Pour chaque écart :

```text
Fichier
Ligne
Comportement actuel
Risque
Comportement attendu
Correction proposée
Test associé
```

## 22.3 Vérifications critiques

Codex doit confirmer explicitement :

- [ ] aucune validation distante dans le rendu public ;
- [ ] aucune suppression de données liée à la licence ;
- [ ] aucun `return ''` lié à une licence invalide ;
- [ ] aucune exception de licence non gérée sur le frontend ;
- [ ] les pages existantes restent rendues après expiration ;
- [ ] les menus existants restent rendus après expiration ;
- [ ] les footers existants restent rendus après expiration ;
- [ ] le mode local fonctionne sans licence de production ;
- [ ] le bandeau n’est pas stocké dans les contenus ;
- [ ] le renouvellement restaure les capacités sans migration destructive ;
- [ ] l’API de licence utilise cache, timeout et backoff ;
- [ ] les clés de licence sont masquées dans les logs ;
- [ ] les statuts sont testés automatiquement.

---

# 23. Critères d’acceptation finaux

L’implémentation est acceptée uniquement si :

1. le frontend construit reste identique avant et après expiration ;
2. aucune donnée Builder n’est modifiée par le service de licence ;
3. une panne du serveur de licences ne provoque aucune panne publique ;
4. le mode local est utilisable sans licence de production ;
5. la production sans licence affiche le bandeau prévu sans casser le site ;
6. une licence valide retire immédiatement les avertissements ;
7. une expiration désactive uniquement les capacités commerciales prévues ;
8. une réactivation restaure les capacités sans perte ;
9. les trois Builders respectent exactement la même politique ;
10. tous les tests unitaires, d’intégration et end-to-end passent.

---

# 24. Texte commercial public correspondant

```text
Une licence Builder est valable pendant un an pour un domaine de
production. Les environnements locaux peuvent être utilisés gratuitement
pour construire et tester le site.

Si l’abonnement expire, les pages, menus et footers déjà publiés restent
affichés : FlatCMS ne coupe jamais le frontend et ne supprime jamais les
contenus construits. Le renouvellement réactive les fonctions
commerciales concernées, notamment l’édition en production, les mises à
jour et le support.

Lorsqu’un Builder est testé sur un domaine de production sans licence,
un bandeau signale le mode test jusqu’à l’activation d’un abonnement.
```

---

# 25. Décisions encore configurables

Les points suivants doivent être centralisés dans la politique produit,
et non dispersés dans le code :

```text
durée de la période de grâce
emplacement exact du bandeau public de test
droits d’enregistrement en production sans licence
mode lecture seule après expiration
règles des sous-domaines
nombre d’environnements de staging
fréquence de validation distante
durée du cache de licence
conditions de transfert de domaine
```

La valeur par défaut recommandée dans ce document est :

```text
période de grâce : 15 jours
frontend après expiration : toujours actif
édition après expiration : lecture seule
mises à jour après expiration : désactivées
support après expiration : désactivé
bandeau d’expiration : administration uniquement
bandeau public : uniquement pour le mode test en production sans licence
```

---

# 26. Journal

| Date | Version | Modification | Auteur |
|---|---:|---|---|
| 2026-06-08 | 1.0 | Formalisation du contrat de contrôle des licences Builders | ChatGPT / Alain BROYE |

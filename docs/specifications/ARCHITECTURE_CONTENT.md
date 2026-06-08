# ARCHITECTURE_CONTENT — Architecture technique de FlatCMS

> **Contenu éditorial prêt à intégrer**
>
> Projet : FlatCMS  
> Page : Architecture `fr-FR`  
> URL cible : `https://flat-cms.fr/fr-FR/architecture/`  
> Version de référence : FlatCMS v1.0.0 LTS Core  
> Date de création : 8 juin 2026  
> Brief parent : `PAGE_BRIEFS.md` — `P0-ARCH-FR`  
> Document directeur associé : `SITE_ARCHITECTURE.md`  
> Statut : première version rédactionnelle à relire, illustrer et valider contre le package final

---

## 1. Objectif de la page

Cette page explique l’architecture technique de FlatCMS à un public composé de :

- développeurs PHP ;
- intégrateurs ;
- agences ;
- exploitants ;
- contributeurs ;
- décideurs techniques.

Elle doit permettre de comprendre :

- comment une requête traverse le CMS ;
- le rôle du cœur applicatif ;
- la séparation entre les modules HMVC et les services ;
- le fonctionnement de l’autoloading PSR-4 ;
- l’organisation du stockage JSON ;
- le rôle des contrôleurs et des vues ;
- le système de hooks ;
- la séparation des thèmes ;
- la configuration du document root ;
- les limites entre le LTS Core et les composants optionnels, commerciaux ou expérimentaux.

Cette page constitue une vue d’ensemble.

Les explications détaillées doivent être publiées sur des pages canoniques distinctes :

```text
/fr-FR/architecture/hmvc/
/fr-FR/architecture/psr-4/
/fr-FR/architecture/cycle-requete/
/fr-FR/architecture/routage/
/fr-FR/architecture/controleurs-et-vues/
/fr-FR/architecture/services/
/fr-FR/architecture/hooks/
/fr-FR/architecture/stockage-json/
/fr-FR/architecture/themes/
/fr-FR/architecture/securite/
```

---

# 2. Métadonnées SEO

## URL canonique

```text
https://flat-cms.fr/fr-FR/architecture/
```

## Balise `<title>`

```text
Architecture FlatCMS — PHP natif, HMVC, PSR-4 et JSON
```

## Meta description

```text
Comprenez l’architecture de FlatCMS : cœur PHP natif, modules HMVC,
autoloading PSR-4, services, hooks, vues, thèmes et stockage JSON.
```

## Directive robots

```text
index, follow, max-image-preview:large
```

## Open Graph

### `og:title`

```text
Architecture de FlatCMS v1.0.0
```

### `og:description`

```text
Découvrez le cycle de requête, le cœur applicatif, les modules HMVC,
les services, les hooks, les thèmes et le stockage JSON de FlatCMS.
```

### `og:type`

```text
article
```

### `og:url`

```text
https://flat-cms.fr/fr-FR/architecture/
```

### `og:image`

```text
https://flat-cms.fr/assets/images/og/architecture-flatcms-fr-FR.webp
```

---

# 3. Hero

## Sur-titre

```text
FlatCMS v1.0.0 LTS Core
```

## H1

```text
Architecture de FlatCMS
```

## Introduction

```text
FlatCMS repose sur un cœur PHP natif, une architecture HMVC, un
autoloading conforme aux conventions PSR-4 et un stockage flat-file
principalement structuré en JSON.
```

```text
Le projet sépare les fondations du runtime, les fonctionnalités métier,
les services transversaux, les thèmes, la configuration et les données
afin de conserver une base lisible et extensible.
```

## Message de contexte

```text
Cette page décrit l’architecture de la ligne stable FlatCMS LTS Core.
La présence d’un composant dans le code ne signifie pas automatiquement
qu’il est activé, gratuit ou destiné à la production dans toutes les
distributions.
```

## CTA principal

```text
Consulter la documentation développeur
```

Destination :

```text
/fr-FR/documentation/developpement/
```

## CTA secondaire

```text
Comprendre le cycle d’une requête
```

Destination :

```text
/fr-FR/architecture/cycle-requete/
```

## Lien tertiaire

```text
Créer un module FlatCMS
```

Destination :

```text
/fr-FR/documentation/developpement/creer-un-module/
```

---

# 4. Vue d’ensemble

## H2

```text
Une architecture organisée par responsabilités
```

## Texte

```text
FlatCMS distingue plusieurs couches qui coopèrent sans remplir le même
rôle :
```

- la racine du projet porte les contrats et points d’entrée ;
- `app/` contient la logique applicative ;
- `app/Core/` fournit les fondations du runtime ;
- `app/Modules/` regroupe les fonctionnalités HMVC ;
- `app/Services/` isole les traitements transversaux ;
- `config/` centralise les configurations et déclarations globales ;
- `data/` contient les données flat-file ;
- `public/` constitue la racine exposée au Web ;
- `resources/` contient des ressources internes ;
- `storage/` reçoit les fichiers d’exécution et données techniques ;
- `themes/` contient les thèmes du frontend et de l’administration.

## Schéma général

```text
Navigateur ou client HTTP
        ↓
public/index.php
        ↓
Bootstrap
        ↓
App
        ↓
Router
        ↓
Module et contrôleur
        ↓
Services, modèles et stockage JSON
        ↓
View
        ↓
Response HTTP
```

## Principe

```text
Le cœur orchestre la requête.
Les modules portent les fonctionnalités.
Les services portent les traitements partagés.
Les vues produisent le rendu.
Le stockage conserve les données.
```

---

# 5. Arborescence de référence

## H2

```text
Les principaux dossiers de FlatCMS
```

## Arborescence simplifiée

```text
FlatCMS/
├── app/
│   ├── Bootstrap/
│   ├── Controllers/
│   ├── Core/
│   ├── Extensions/
│   ├── Helpers/
│   ├── Modules/
│   ├── Services/
│   └── ThirdParty/
├── config/
├── data/
├── public/
├── resources/
├── storage/
├── themes/
├── index.php
├── flatcms.json
├── VERSION
├── README.md
└── nginx.conf
```

## Règle éditoriale

```text
Cette arborescence est une vue pédagogique. Le package distribué reste
la source de vérité et peut évoluer entre deux versions.
```

## Responsabilités

| Emplacement | Responsabilité principale |
|---|---|
| `app/Bootstrap/` | Initialisation du runtime |
| `app/Controllers/` | Contrôleurs transversaux |
| `app/Core/` | Fondations applicatives |
| `app/Extensions/` | Points d’extension |
| `app/Helpers/` | Fonctions utilitaires |
| `app/Modules/` | Fonctionnalités HMVC |
| `app/Services/` | Services métier et techniques |
| `app/ThirdParty/` | Dépendances tierces isolées |
| `config/` | Configuration et déclarations |
| `data/` | Données JSON et fichiers métier |
| `public/` | Fichiers accessibles depuis le Web |
| `resources/` | Ressources internes |
| `storage/` | Cache, logs et fichiers techniques selon configuration |
| `themes/` | Thèmes frontend et administration |

---

# 6. Le point d’entrée public

## H2

```text
Une requête entre par le dossier public/
```

## Texte

```text
En production, le serveur web doit exposer le dossier public/ et non la
racine complète du projet.
```

```text
Cette séparation empêche l’accès HTTP direct aux fichiers de
configuration, aux données JSON, aux classes PHP, aux ressources
internes et aux fichiers techniques.
```

## Configuration Nginx conceptuelle

```nginx
server {
    root /path/to/flatcms/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /index.php {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_pass 127.0.0.1:9000;
    }

    location ~ \.php$ {
        return 404;
    }
}
```

## Explication

- Nginx recherche d’abord un fichier ou un dossier public réel ;
- à défaut, la requête est transmise au front controller ;
- seul `public/index.php` doit être exécuté comme point d’entrée PHP public ;
- les autres fichiers PHP ne doivent pas être exposés directement.

## Apache

```text
Sous Apache, les règles de réécriture doivent produire le même résultat :
les assets publics sont servis directement et les autres URLs sont
transmises au front controller.
```

## Important

```text
Les règles serveur constituent une protection complémentaire. La
séparation physique du document root reste la configuration recommandée.
```

## CTA

```text
Configurer le document root public
```

Destination :

```text
/fr-FR/documentation/deploiement/document-root-public/
```

---

# 7. Le cycle d’une requête

## H2

```text
Comment FlatCMS traite une requête HTTP
```

## Étape 1 — Réception

```text
Le serveur web reçoit une requête et la dirige vers un fichier public
réel ou vers le front controller public/index.php.
```

## Étape 2 — Bootstrap

```text
La couche Bootstrap prépare l’environnement d’exécution : constantes,
autoloading, configuration, gestion des erreurs et initialisation des
composants nécessaires.
```

## Étape 3 — Application

```text
App orchestre le runtime et coordonne les principaux composants du CMS.
```

## Étape 4 — Requête

```text
Request représente les informations utiles de la requête HTTP :
méthode, chemin, paramètres, données envoyées et contexte.
```

## Étape 5 — Routage

```text
Router compare le chemin et la méthode aux routes déclarées afin
d’identifier le contrôleur ou l’action à exécuter.
```

## Étape 6 — Middleware et contrôles

```text
Selon la route, le runtime peut appliquer des contrôles d’authentification,
de rôle, de maintenance, d’installation ou d’autres règles transversales.
```

## Étape 7 — Module et contrôleur

```text
Le contrôleur reçoit la requête, valide le contexte et délègue les
traitements métier aux services ou aux composants du module.
```

## Étape 8 — Données et services

```text
Les services lisent ou modifient les données, appliquent les règles
métier et communiquent avec le stockage flat-file ou avec un fournisseur
externe autorisé.
```

## Étape 9 — Vue

```text
View reçoit des données déjà préparées et produit le rendu HTML ou une
autre représentation attendue.
```

## Étape 10 — Réponse

```text
Response définit le statut HTTP, les en-têtes et le corps transmis au
navigateur ou au client.
```

## Diagramme

```text
Request
  ↓
Bootstrap
  ↓
App
  ↓
Router
  ↓
Middleware
  ↓
Controller
  ↓
Service / FlatFile
  ↓
View
  ↓
Response
```

## Règle Clean Code

```text
Le contrôleur coordonne.
Le service traite.
La vue affiche.
Le stockage persiste.
```

## CTA

```text
Étudier le cycle de requête en détail
```

Destination :

```text
/fr-FR/architecture/cycle-requete/
```

---

# 8. Le cœur applicatif

## H2

```text
Les fondations du runtime dans app/Core/
```

## Éléments de référence

```text
App.php
Router.php
Request.php
Response.php
BaseController.php
View.php
FlatFile.php
ModuleManager.php
Hook.php
I18n.php
Session.php
CoreManifest.php
TranslationScanner.php
Mail/
Security/
```

## `App`

```text
App représente l’orchestrateur principal du runtime. Il assemble les
composants nécessaires au traitement de la requête.
```

## `Router`

```text
Router associe une méthode et un chemin à une action applicative.
```

## `Request`

```text
Request encapsule les entrées HTTP afin d’éviter que chaque contrôleur
interroge directement les variables globales de manière dispersée.
```

## `Response`

```text
Response centralise le statut, les en-têtes et le contenu de sortie.
```

## `BaseController`

```text
BaseController fournit un socle commun aux contrôleurs lorsque le projet
a besoin de comportements partagés.
```

## `View`

```text
View prépare le rendu et transmet aux templates uniquement les données
nécessaires.
```

## `FlatFile`

```text
FlatFile regroupe les opérations communes liées au stockage par fichiers.
Son contrat réel doit définir la lecture, l’écriture, la validation, les
erreurs et les garanties de cohérence.
```

## `ModuleManager`

```text
ModuleManager découvre et orchestre les modules selon les manifestes,
la configuration et les statuts d’activation.
```

## `Hook`

```text
Hook permet d’enregistrer et d’exécuter des extensions autour
d’événements définis par le runtime.
```

## `I18n`

```text
I18n fournit les mécanismes d’internationalisation et de résolution des
chaînes selon la locale.
```

## `Session`

```text
Session encadre l’accès aux données de session et aux fonctions
d’authentification associées.
```

## Principe

```text
app/Core/ ne doit pas devenir un dossier générique où sont déposées les
fonctions métier. Une fonctionnalité métier appartient en priorité à son
module ou à un service clairement identifié.
```

---

# 9. Architecture HMVC

## H2

```text
Des fonctionnalités regroupées en modules HMVC
```

## Définition dans FlatCMS

```text
HMVC signifie Hierarchical Model–View–Controller. Dans FlatCMS, ce choix
consiste à organiser les fonctionnalités en modules autonomes qui
peuvent posséder leurs propres contrôleurs, vues, services, routes,
configurations, traductions et assets.
```

## Important

```text
HMVC n’est pas une spécification normative unique comparable à PSR-4.
Le terme décrit ici l’organisation choisie par FlatCMS.
```

## Structure conceptuelle d’un module

```text
app/Modules/Example/
├── Controllers/
├── Models/
├── Services/
├── Views/
├── Config/
├── Routes/
├── Resources/
├── Languages/
└── manifest.json
```

La structure exacte dépend du contrat réel de FlatCMS.

## Responsabilités possibles

| Élément | Responsabilité |
|---|---|
| Contrôleur | Recevoir la requête du module |
| Modèle ou dépôt | Représenter ou accéder aux données |
| Service | Appliquer les règles métier |
| Vue | Produire le rendu |
| Routes | Déclarer les points d’entrée |
| Configuration | Définir les options |
| Traductions | Localiser le module |
| Assets | Fournir CSS, JavaScript et médias |
| Manifeste | Décrire le module et sa compatibilité |

## Modules observés dans la version de référence

```text
AiAgent
Auth
Backups
Categories
Core
Dashboard
Footer
HookManager
Install
Languages
Media
Menu
Modules
Pages
Posts
Themes
Trash
Users
```

## Classification publique

### Contenus

```text
Pages
Posts
Categories
Media
Menu
Footer
```

### Administration

```text
Dashboard
Users
Languages
Themes
Modules
Trash
Backups
```

### Système

```text
Core
Auth
Install
HookManager
```

### IA ou commercial

```text
AiAgent
```

Le statut de chaque module doit être confirmé avant publication :

```text
Core
optionnel
premium
expérimental
prévu
```

## Bénéfices

- code regroupé par fonctionnalité ;
- responsabilités plus faciles à localiser ;
- tests plus ciblés ;
- dépendances visibles ;
- activation contrôlée ;
- évolution indépendante ;
- documentation par module.

## Limites

- dépendances circulaires possibles ;
- duplication entre modules ;
- surcharge de manifestes ;
- ordre de chargement ;
- contrats mal définis ;
- logique métier placée dans les contrôleurs ;
- couplage excessif au cœur.

## Règle

```text
Un module ne doit pas contourner les services et contrats publics du CMS
pour modifier directement les données internes d’un autre module.
```

## CTA

```text
Comprendre HMVC dans FlatCMS
```

Destination :

```text
/fr-FR/architecture/hmvc/
```

---

# 10. Autoloading PSR-4

## H2

```text
Des namespaces reliés aux chemins de classes
```

## Texte

```text
PSR-4 définit une convention d’autoloading qui associe un préfixe de
namespace à un ou plusieurs répertoires de base.
```

```text
Les sous-namespaces correspondent ensuite à des sous-dossiers et le nom
terminal de la classe correspond au nom du fichier PHP.
```

## Exemple conceptuel

Classe :

```php
App\Modules\Pages\Controllers\AdminPageController
```

Chemin possible :

```text
app/Modules/Pages/Controllers/AdminPageController.php
```

## Règles essentielles

- la classe utilise un namespace racine ;
- les séparateurs de namespace correspondent aux séparateurs de dossier ;
- le fichier se termine par `.php` ;
- le nom du fichier respecte la casse de la classe ;
- les références de classes respectent la casse ;
- l’autoloader ne doit pas déclencher d’erreur ou d’exception selon la spécification PSR-4.

## Bénéfices

- suppression des longues listes de `require` ;
- structure prévisible ;
- déplacement contrôlé des classes ;
- organisation des modules ;
- interopérabilité avec des outils PHP ;
- meilleure navigation dans l’IDE.

## Prudence

```text
PSR-4 organise le chargement des classes. Il ne définit ni l’architecture
HMVC, ni les règles métier, ni le cycle de vie des modules.
```

## CTA

```text
Lire la page PSR-4 de FlatCMS
```

Destination :

```text
/fr-FR/architecture/psr-4/
```

---

# 11. Routage

## H2

```text
Associer les URLs aux actions du CMS
```

## Sources de routes

Le projet peut utiliser :

- des routes globales ;
- des routes de modules ;
- des routes d’administration ;
- des routes publiques ;
- des routes d’installation ;
- des routes techniques contrôlées.

## Configuration observée

```text
config/routes.php
```

## Responsabilités du routeur

- analyser la méthode HTTP ;
- normaliser le chemin ;
- trouver la route ;
- extraire les paramètres ;
- appeler le contrôleur ;
- produire une erreur `404` en l’absence de route ;
- empêcher l’accès à une route non autorisée ;
- conserver un comportement cohérent entre Apache et Nginx.

## Exemple conceptuel

```php
$router->get('/fr-FR/blog/{slug}', [PostController::class, 'show']);
```

## Règles

- une route publique possède une URL stable ;
- une action destructive n’utilise pas une requête `GET` ;
- les paramètres sont validés ;
- les routes d’administration sont protégées ;
- les collisions entre modules sont détectées ;
- les routes techniques ne sont pas indexées ;
- les URLs sont générées par un composant central lorsque possible.

## CTA

```text
Comprendre le routage FlatCMS
```

Destination :

```text
/fr-FR/architecture/routage/
```

---

# 12. Contrôleurs et vues

## H2

```text
Coordonner la requête sans mélanger le rendu et le métier
```

## Contrôleur

Le contrôleur doit principalement :

- recevoir le contexte ;
- valider les paramètres ;
- vérifier les permissions ;
- appeler un service ;
- préparer les données de la vue ;
- retourner une réponse.

## À éviter dans un contrôleur

- logique métier volumineuse ;
- accès direct dispersé aux fichiers ;
- génération manuelle de HTML ;
- manipulation globale de session ;
- envoi d’e-mail non encapsulé ;
- appel direct à plusieurs fournisseurs externes ;
- règles de licence dupliquées.

## Vue

La vue doit :

- afficher des données préparées ;
- échapper les sorties ;
- utiliser des composants réutilisables ;
- respecter la hiérarchie HTML ;
- éviter la logique métier ;
- rester compatible avec l’accessibilité ;
- ne pas accéder directement au stockage.

## Séparation

```text
Contrôleur
→ choisit quoi faire

Service
→ effectue le traitement

Vue
→ affiche le résultat

Response
→ transporte le résultat
```

## CTA

```text
Étudier les contrôleurs et les vues
```

Destination :

```text
/fr-FR/architecture/controleurs-et-vues/
```

---

# 13. Services

## H2

```text
Isoler les traitements transversaux et les intégrations
```

## Texte

```text
Les services regroupent les traitements partagés qui ne doivent pas être
dupliqués dans plusieurs contrôleurs ou modules.
```

## Services observés

```text
app/Services/
├── AI/
├── Licensing/
├── StructuredData/
├── UpdateArtifactService.php
└── UpdateCatalogService.php
```

## Couche IA

```text
La couche AI doit abstraire les fournisseurs externes afin que les
modules ne dépendent pas directement d’un SDK ou d’un modèle précis.
```

## Licences

```text
La couche Licensing centralise la validation et l’interprétation des
licences lorsque des composants commerciaux sont utilisés.
```

## Données structurées

```text
La couche StructuredData orchestre la construction d’un graphe JSON-LD
pour le site, les pages et les articles.
```

## Mise à jour

```text
Les services de catalogue et d’artefact de mise à jour séparent la
découverte des versions de la logique de distribution.
```

## Bon contrat de service

Un service doit préciser :

- ses entrées ;
- ses sorties ;
- ses erreurs ;
- ses dépendances ;
- ses effets de bord ;
- ses permissions ;
- son comportement en cas d’indisponibilité externe.

## À éviter

- service « fourre-tout » ;
- dépendance cachée à l’état global ;
- écriture non journalisée ;
- exception non traduite ;
- couplage au HTML ;
- appel réseau sans délai d’expiration ;
- secret codé en dur.

## CTA

```text
Explorer les services FlatCMS
```

Destination :

```text
/fr-FR/architecture/services/
```

---

# 14. Hooks et extensions

## H2

```text
Étendre le CMS autour d’événements documentés
```

## Texte

```text
Le système de hooks permet à un module ou à une extension de réagir à
un événement sans modifier directement le code de la fonctionnalité
d’origine.
```

## Configuration observée

```text
config/hooks.php
```

## Exemples conceptuels

- avant la publication d’un article ;
- après l’enregistrement d’une page ;
- avant le rendu d’une vue ;
- après l’authentification ;
- lors du chargement d’un menu ;
- avant la génération du JSON-LD.

## Contrat d’un hook

Un hook doit documenter :

- son nom ;
- le moment où il est déclenché ;
- les données reçues ;
- les données modifiables ;
- le type de retour ;
- la priorité ;
- la gestion des erreurs ;
- les restrictions de sécurité.

## Risques

- ordre d’exécution imprévisible ;
- dépendances invisibles ;
- ralentissement ;
- exception bloquante ;
- modification non traçable ;
- hook obsolète après mise à jour.

## Règle

```text
Un hook doit étendre un comportement. Il ne doit pas devenir une
dépendance cachée indispensable au fonctionnement du cœur.
```

## CTA

```text
Comprendre les hooks FlatCMS
```

Destination :

```text
/fr-FR/architecture/hooks/
```

---

# 15. Stockage JSON

## H2

```text
Conserver les contenus dans une arborescence flat-file
```

## Structure observée

```text
data/
├── core/
│   ├── pages/
│   ├── posts/
│   ├── categories/
│   ├── media/
│   └── contact_forms/
├── extensions/
├── users/
├── modules.json
└── .htaccess
```

## Texte

```text
FlatCMS conserve ses données principales dans des fichiers structurés.
Le format JSON permet de représenter les champs, les statuts, les dates,
les relations simples et les métadonnées d’un contenu.
```

## Lecture

Une lecture correcte doit :

- vérifier l’existence du fichier ;
- contrôler l’accès ;
- lire de manière sûre ;
- décoder le JSON ;
- détecter les erreurs ;
- valider la structure attendue ;
- retourner un résultat typé ou documenté.

## Écriture

Une écriture correcte doit :

- valider les données ;
- normaliser les champs ;
- contrôler les permissions ;
- sérialiser le JSON ;
- limiter les écritures concurrentes ;
- utiliser une stratégie atomique lorsque le contrat le prévoit ;
- journaliser les erreurs ;
- préserver une possibilité de restauration.

## Exemple conceptuel

```json
{
  "id": "about-flatcms",
  "locale": "fr-FR",
  "title": "À propos de FlatCMS",
  "slug": "a-propos",
  "status": "published",
  "updated_at": "2026-06-08T14:30:00+02:00"
}
```

## Avantages

- absence de serveur SQL ;
- données lisibles par des outils ;
- sauvegarde avec les fichiers du site ;
- déploiement simplifié dans certains contextes ;
- séparation claire entre les familles de données ;
- intégration avec des scripts ou agents contrôlés.

## Limites

- concurrence d’écriture ;
- relations complexes ;
- grandes agrégations ;
- indexation interne ;
- volume très important ;
- migrations de schéma ;
- permissions ;
- corruption possible en cas d’écriture interrompue ;
- besoin de sauvegardes cohérentes.

## Règle

```text
Le mot « flat-file » ne signifie pas « absence de modèle de données ».
Les schémas, validations, identifiants et migrations restent nécessaires.
```

## CTA

```text
Comprendre le stockage JSON
```

Destination :

```text
/fr-FR/architecture/stockage-json/
```

---

# 16. Configuration

## H2

```text
Séparer la configuration publique, applicative et privée
```

## Fichiers observés

```text
config/
├── app.php
├── routes.php
├── hooks.php
└── .htaccess
```

## `app.php`

```text
Ce fichier peut centraliser les paramètres applicatifs non secrets
nécessaires au runtime.
```

## `routes.php`

```text
Ce fichier déclare les routes globales ou les agrège selon le contrat du
projet.
```

## `hooks.php`

```text
Ce fichier déclare ou configure les hooks globaux.
```

## Secrets

```text
Les clés API, mots de passe et jetons ne doivent pas être stockés dans
les fichiers versionnés ou exposés publiquement.
```

## Source privée

Selon le contrat du projet, utiliser :

```text
.env.local
variables d’environnement
stockage privé de l’hébergeur
secret manager
```

## Règles

- ne pas committer les secrets ;
- ne pas afficher les secrets dans l’administration ;
- limiter les droits de lecture ;
- séparer développement et production ;
- documenter les valeurs obligatoires sans publier leur contenu ;
- invalider une clé exposée ;
- masquer les valeurs dans les logs.

---

# 17. Internationalisation

## H2

```text
Séparer les contenus, les chaînes et les locales
```

## Composants observés

```text
I18n.php
TranslationScanner.php
Languages
```

## Responsabilités

- résolution de la locale ;
- chargement des chaînes ;
- fallback contrôlé ;
- scan des traductions manquantes ;
- contenus par locale ;
- messages d’administration ;
- formats de date et de nombre ;
- URLs localisées.

## Règle publique

```text
Une URL locale ne doit pas afficher silencieusement le contenu complet
d’une autre langue.
```

## Règle interne

```text
Un fallback d’interface peut être accepté s’il est visible dans les
outils de contrôle et ne masque pas durablement une traduction absente.
```

## Relations SEO

- attribut `lang` ;
- canonique locale ;
- `hreflang` ;
- liens vers les équivalents ;
- sitemaps ;
- métadonnées ;
- données structurées `inLanguage`.

## CTA

```text
Comprendre l’architecture multilingue
```

Destination :

```text
/fr-FR/architecture/internationalisation/
```

---

# 18. Thèmes

## H2

```text
Séparer le rendu public de l’interface d’administration
```

## Texte

```text
FlatCMS distingue les thèmes du frontend et les thèmes de
l’administration.
```

## Thème frontend

Responsabilités :

- layout public ;
- header ;
- navigation ;
- footer ;
- templates ;
- composants ;
- assets ;
- responsive ;
- accessibilité ;
- rendu des contenus.

## Thème d’administration

Responsabilités :

- navigation interne ;
- formulaires ;
- listes ;
- tableaux ;
- tableaux de bord ;
- états ;
- messages ;
- ergonomie des modules.

## Contrat d’un thème

Un thème doit définir :

- identifiant ;
- nom ;
- version ;
- compatibilité ;
- auteur ;
- licence ;
- emplacements ;
- templates ;
- assets ;
- options ;
- dépendances éventuelles.

## Règle

```text
Un thème ne doit pas contenir la logique métier principale du CMS.
```

## Sécurité

- échapper les sorties ;
- ne pas exposer de secret ;
- éviter le PHP arbitraire provenant d’un contenu ;
- valider les chemins de templates ;
- protéger les assets privés ;
- limiter les dépendances externes.

## CTA

```text
Découvrir l’architecture des thèmes
```

Destination :

```text
/fr-FR/architecture/themes/
```

---

# 19. Données structurées

## H2

```text
Construire un graphe JSON-LD à partir de providers
```

## Structure observée

```text
app/Services/StructuredData/
├── Contracts/
│   └── StructuredDataProviderInterface.php
├── Providers/
│   ├── SiteSchemaProvider.php
│   ├── PageSchemaProvider.php
│   └── PostSchemaProvider.php
├── StructuredDataManager.php
└── SchemaGraphBuilder.php
```

## Manager

```text
StructuredDataManager orchestre les providers applicables au contexte.
```

## Builder

```text
SchemaGraphBuilder assemble, relie et déduplique les entités du graphe.
```

## Providers

```text
Les providers transforment un contexte de page, de site ou d’article
en entités Schema.org.
```

## Principes

- un `@id` stable par entité ;
- URLs canoniques ;
- contenu visible ;
- aucune note fictive ;
- dates exactes ;
- images accessibles ;
- locale correcte ;
- sérialisation sûre ;
- erreur non bloquante pour le frontend.

## CTA

```text
Explorer les données structurées
```

Destination :

```text
/fr-FR/architecture/donnees-structurees/
```

---

# 20. Services IA

## H2

```text
Abstraire les fournisseurs d’intelligence artificielle
```

## Texte

```text
La couche AI est destinée à séparer les besoins fonctionnels du CMS des
API, SDK et modèles d’un fournisseur précis.
```

## Structure conceptuelle

```text
Module ou fonctionnalité
        ↓
AIManager
        ↓
Contrat fournisseur
        ↓
OpenAI ou autre provider
        ↓
Réponse normalisée
```

## Responsabilités

- messages et requêtes ;
- fournisseurs ;
- modèles ;
- délais d’expiration ;
- erreurs ;
- suivi d’usage ;
- limitations ;
- permissions ;
- configuration ;
- réponses normalisées.

## Principe

```text
Un module ne doit pas construire directement une requête HTTP vers un
fournisseur si une couche de service commune existe.
```

## Sécurité

- clés privées ;
- aucun secret dans le navigateur ;
- contrôle des rôles ;
- quotas ;
- journalisation limitée ;
- protection des données personnelles ;
- validation humaine ;
- filtrage des contenus envoyés ;
- possibilité de désactivation.

## Statut

```text
La présence d’une fondation AI ne prouve pas que toutes les fonctions
d’agent sont disponibles dans le LTS Core.
```

## CTA

```text
Découvrir l’architecture agent-ready
```

Destination :

```text
/fr-FR/agent-ready/
```

---

# 21. Licences et composants commerciaux

## H2

```text
Séparer le cœur open source et les composants commerciaux
```

## Texte

```text
L’architecture technique doit permettre d’identifier les composants du
Core, les modules optionnels et les composants soumis à une licence
commerciale.
```

## Principes

- manifeste de composant ;
- identifiant stable ;
- version ;
- compatibilité ;
- licence ;
- fournisseur ;
- signature lorsque prévue ;
- vérification côté serveur ;
- fonctionnement dégradé documenté ;
- absence de secret dans le code client.

## Règle

```text
La licence d’un composant ne doit pas être déduite de son emplacement.
Elle doit être déterminée par les documents et métadonnées officiels.
```

## CTA

```text
Comprendre les licences FlatCMS
```

Destination :

```text
/fr-FR/licences/
```

---

# 22. Sécurité architecturale

## H2

```text
Réduire l’exposition sans promettre une sécurité absolue
```

## Principes

### Document root

```text
Exposer uniquement public/.
```

### Entrée unique

```text
Acheminer les requêtes dynamiques vers le front controller.
```

### Fichiers sensibles

```text
Empêcher l’accès à config/, data/, storage/, app/, resources/ et aux
fichiers d’environnement.
```

### Sessions

```text
Configurer les cookies et régénérer les sessions aux moments sensibles.
```

### Entrées

```text
Valider les paramètres, formulaires, fichiers et chemins.
```

### Sorties

```text
Échapper le contenu selon le contexte HTML, attribut, URL ou JavaScript.
```

### Permissions

```text
Appliquer le principe du moindre privilège.
```

### Erreurs

```text
Journaliser les détails côté serveur sans les afficher au visiteur.
```

### Mises à jour

```text
Sauvegarder, tester et prévoir un retour arrière.
```

## En-têtes observés dans le template Nginx

```text
X-Content-Type-Options
X-Frame-Options
Referrer-Policy
Permissions-Policy
Content-Security-Policy
Strict-Transport-Security
Cross-Origin-Opener-Policy
Cross-Origin-Resource-Policy
```

## Prudence

```text
Une politique CSP ou HSTS ne doit pas être copiée sans validation. Elle
doit correspondre aux domaines, scripts, médias et conditions HTTPS du
site réellement déployé.
```

## CTA

```text
Consulter l’architecture de sécurité
```

Destination :

```text
/fr-FR/architecture/securite/
```

---

# 23. Validation et intégrité

## H2

```text
Valider le runtime avant de publier une version
```

## Validation annoncée du dépôt LTS Core

- intégrité syntaxique PHP ;
- intégrité des fichiers JSON ;
- comportement stable du frontend ;
- comportement stable de l’administration ;
- parcours d’installation livré.

## Contrôles supplémentaires recommandés

- tests unitaires ;
- tests d’intégration ;
- tests du routeur ;
- tests de permissions ;
- tests d’authentification ;
- tests de sérialisation JSON ;
- tests de migration ;
- tests de module ;
- tests des six locales ;
- tests Apache et Nginx ;
- tests de restauration ;
- tests de sécurité ;
- analyse statique ;
- contrôle des licences.

## Règle

```text
Une fonctionnalité n’est pas considérée comme stable uniquement parce
que son code existe. Elle doit être testée dans le parcours réel de la
distribution.
```

---

# 24. Les limites de l’architecture

## H2

```text
Les compromis à connaître
```

## Stockage par fichiers

```text
Le modèle doit gérer les écritures concurrentes, les sauvegardes, les
migrations de schéma et les volumes importants.
```

## Modularité

```text
Une mauvaise gestion des dépendances peut créer des modules couplés ou
un ordre de chargement fragile.
```

## Hooks

```text
Des hooks trop nombreux ou mal documentés peuvent rendre l’exécution
difficile à suivre.
```

## Services externes

```text
Une intégration de licence, d’IA ou de mise à jour peut devenir
indisponible et doit prévoir des délais, erreurs et comportements de
repli.
```

## Thèmes

```text
Un thème trop puissant peut réintroduire de la logique métier dans la
présentation.
```

## Multilingue

```text
Le nombre de locales augmente le coût de traduction, de contrôle et de
maintenance.
```

## Compatibilité

```text
Une architecture lisible ne garantit pas automatiquement la compatibilité
avec toutes les versions PHP, tous les hébergeurs ou tous les modules.
```

---

# 25. Principes Clean Code retenus

## H2

```text
Les règles de conception à préserver
```

## Une classe, une responsabilité principale

```text
Le nom et l’emplacement doivent indiquer le rôle du composant.
```

## Dépendances explicites

```text
Un service doit recevoir ou déclarer ses dépendances au lieu de les
chercher silencieusement dans l’état global.
```

## Contrats stables

```text
Les interfaces et manifestes définissent ce qu’un composant peut
attendre du runtime.
```

## Contrôleurs minces

```text
Les contrôleurs coordonnent et délèguent la logique métier.
```

## Vues sans métier

```text
Les vues affichent des données préparées et échappées.
```

## Erreurs utiles

```text
Les erreurs doivent être exploitables dans les logs sans révéler de
données sensibles au visiteur.
```

## Données validées

```text
Une donnée JSON n’est pas fiable uniquement parce qu’elle provient d’un
fichier local.
```

## Évolution documentée

```text
Toute modification d’un contrat public doit être versionnée et
accompagnée d’une migration ou d’une note de compatibilité.
```

---

# 26. Parcours de lecture technique

## H2

```text
Approfondir l’architecture
```

## Débutant

1. Vue d’ensemble
2. Cycle de requête
3. Contrôleurs et vues
4. Stockage JSON
5. Créer un premier module

## Développeur PHP

1. PSR-4
2. HMVC
3. Routage
4. Services
5. Hooks
6. Manifestes
7. Tests

## Intégrateur

1. Thèmes
2. Vues
3. Menus
4. Widgets
5. Multilingue
6. Accessibilité
7. Performance

## Exploitant

1. Document root
2. Nginx
3. Apache
4. Permissions
5. Sauvegardes
6. Logs
7. Mises à jour

---

# 27. Questions fréquentes éditoriales

> Ces questions-réponses ne déclenchent pas automatiquement un balisage
> `FAQPage`.

## H2

```text
Questions fréquentes sur l’architecture
```

### H3 — FlatCMS utilise-t-il un framework PHP ?

```text
Le LTS Core est présenté comme un runtime PHP natif. Il utilise ses
propres fondations applicatives et peut isoler des dépendances tierces
dans un emplacement dédié.
```

### H3 — HMVC est-il une norme officielle ?

```text
Non. Dans cette documentation, HMVC décrit l’organisation modulaire
retenue par FlatCMS. PSR-4 est en revanche une spécification publiée par
la PHP-FIG pour l’autoloading des classes.
```

### H3 — Les modules peuvent-ils modifier le cœur ?

```text
Ils doivent utiliser les contrats, services, hooks et points d’extension
documentés. Une modification directe du cœur complique les mises à jour
et la maintenance.
```

### H3 — Où sont stockées les pages et les configurations ?

```text
Les contenus et paramètres principaux sont conservés dans l’arborescence
de données flat-file, selon les modèles JSON et conventions de la version
utilisée. Les fichiers précis sont documentés dans la référence.
```

### H3 — Pourquoi le serveur doit-il pointer vers public/ ?

```text
Cette configuration sépare les fichiers destinés au Web des classes,
configurations, données et ressources internes.
```

### H3 — PSR-4 impose-t-il HMVC ?

```text
Non. PSR-4 définit une convention de correspondance entre namespaces et
chemins de fichiers. L’architecture HMVC est un choix distinct.
```

### H3 — Le stockage JSON remplace-t-il toutes les bases de données ?

```text
Non. Il convient aux besoins compatibles avec le modèle flat-file. Des
écritures concurrentes importantes ou des relations complexes peuvent
nécessiter une architecture différente.
```

### H3 — AiAgent fait-il partie du Core open source ?

```text
La présence du module dans une arborescence de référence ne suffit pas à
déterminer son statut. La distribution, le manifeste et les documents de
licence doivent préciser s’il est optionnel, premium ou expérimental.
```

---

# 28. CTA final

## H2

```text
Explorer une architecture conçue pour rester lisible
```

## Texte

```text
FlatCMS sépare le runtime, les fonctionnalités, les services, les thèmes
et les données afin que chaque évolution puisse être localisée,
documentée et testée.
```

## CTA principal

```text
Consulter la documentation développeur
```

Destination :

```text
/fr-FR/documentation/developpement/
```

## CTA secondaire

```text
Créer un module
```

Destination :

```text
/fr-FR/documentation/developpement/creer-un-module/
```

## Lien tertiaire

```text
Télécharger FlatCMS
```

Destination :

```text
/fr-FR/telechargement/
```

---

# 29. Maillage interne attendu

| Section | Destination |
|---|---|
| Hero | Documentation développeur, cycle de requête |
| Document root | Guide document root public |
| Cycle | Page cycle de requête |
| Cœur | Référence app/Core |
| HMVC | Page HMVC |
| PSR-4 | Page PSR-4 |
| Routage | Page Routage |
| Contrôleurs | Contrôleurs et vues |
| Services | Page Services |
| Hooks | Page Hooks |
| Stockage | Page Stockage JSON |
| Configuration | Documentation configuration |
| Internationalisation | Architecture multilingue |
| Thèmes | Architecture des thèmes |
| StructuredData | Données structurées |
| IA | Agent-ready |
| Licences | Page Licences |
| Sécurité | Architecture sécurité |
| Validation | Documentation tests |
| CTA final | Documentation, création module, téléchargement |

---

# 30. Médias à produire

## P0 — Diagramme du cycle de requête

```text
Navigateur
→ public/index.php
→ Bootstrap
→ App
→ Router
→ Middleware
→ Controller
→ Service / FlatFile
→ View
→ Response
```

## P0 — Arborescence illustrée

```text
app/
config/
data/
public/
resources/
storage/
themes/
```

## P0 — Schéma HMVC

```text
Core
├── Module Pages
├── Module Posts
├── Module Media
└── Module Users
```

Chaque module montre :

```text
Controller
Service
View
Data
```

## P1 — Schéma PSR-4

```text
Namespace
→ préfixe
→ dossier de base
→ sous-dossiers
→ fichier PHP
```

## P1 — Schéma de sécurité

```text
Internet
→ public/
X app/
X config/
X data/
X storage/
```

## P1 — Schéma des services

```text
Modules
→ Services
→ Providers / stockage / systèmes externes
```

## Règles

- représentation fidèle ;
- légende ;
- texte alternatif ;
- description textuelle ;
- version indiquée ;
- aucun composant futur présenté comme stable.

---

# 31. Textes alternatifs suggérés

## Cycle de requête

```text
Cycle d’une requête FlatCMS depuis public/index.php jusqu’à la réponse HTTP
```

## Arborescence

```text
Principaux dossiers de FlatCMS : app, config, data, public, resources,
storage et themes
```

## HMVC

```text
Architecture HMVC de FlatCMS regroupant les contrôleurs, services et vues
dans des modules fonctionnels
```

## PSR-4

```text
Correspondance entre un namespace PHP et le chemin du fichier de classe
selon PSR-4
```

## Sécurité

```text
Dossier public exposé au Web tandis que le code, la configuration et les
données restent privés
```

Les textes doivent être ajustés aux illustrations finales.

---

# 32. Données structurées attendues

```text
WebPage
TechArticle
BreadcrumbList
ImageObject
```

## Identifiants

```text
https://flat-cms.fr/fr-FR/architecture/#webpage
https://flat-cms.fr/fr-FR/architecture/#article
https://flat-cms.fr/fr-FR/architecture/#breadcrumb
https://flat-cms.fr/fr-FR/architecture/#primaryimage
```

## Relations

```text
about
→ https://flat-cms.fr/#software

isPartOf
→ https://flat-cms.fr/#website

publisher
→ https://flat-cms.fr/#organization
```

## Propriétés techniques

- version concernée ;
- auteur ;
- date de publication ;
- date de modification ;
- image ;
- langue ;
- dépendances si pertinentes ;
- niveau technique si visible.

---

# 33. Composants du thème suggérés

```text
HeroArchitecture
ArchitectureOverview
RequestLifecycleDiagram
DirectoryTree
CoreComponentsGrid
ModuleArchitecture
Psr4Mapping
ServiceLayer
HookFlow
JsonStorageOverview
SecurityBoundary
TechnicalPathways
FaqAccordion
CallToActionBanner
```

Les noms définitifs doivent correspondre aux widgets réellement
disponibles dans le thème ou dans PagesBuilder.

---

# 34. Éléments à confirmer avant intégration

- arborescence exacte du package v1.0.0 ;
- namespaces réels ;
- mécanisme précis d’autoloading ;
- fichiers de bootstrap ;
- format exact des routes ;
- middleware réellement présents ;
- structure contractuelle d’un module ;
- nom et format des manifestes ;
- garanties de `FlatFile` ;
- verrouillage et atomicité ;
- stratégie des migrations JSON ;
- structure exacte des thèmes ;
- emplacements de cache et logs ;
- comportement d’erreur ;
- liste et statut des modules ;
- contrat des hooks ;
- contrat de service IA ;
- mécanisme de licence ;
- mécanisme de mise à jour ;
- compatibilité PHP finale ;
- diagrammes validés par le code.

---

# 35. Checklist éditoriale

- [ ] L’architecture correspond au package réel.
- [ ] Les composants cités existent.
- [ ] HMVC est présenté comme un choix de FlatCMS.
- [ ] PSR-4 est présenté comme une spécification d’autoloading.
- [ ] Le cycle de requête est techniquement exact.
- [ ] Le rôle du document root est expliqué.
- [ ] Les contrôleurs, services et vues sont distingués.
- [ ] Le stockage JSON présente avantages et limites.
- [ ] Les garanties non vérifiées sont signalées.
- [ ] Le statut d’AiAgent n’est pas déduit de son emplacement.
- [ ] Les composants premium sont distingués.
- [ ] Aucun secret n’est montré.
- [ ] Les exemples sont identifiés comme conceptuels.
- [ ] Les diagrammes possèdent une description textuelle.
- [ ] Les termes correspondent à FlatCMS v1.0.0.
- [ ] Les liens pointent vers les pages canoniques.
- [ ] La page est compréhensible sans lire le code.

---

# 36. Checklist d’intégration

- [ ] URL correcte.
- [ ] Canonique auto-référencée.
- [ ] `<html lang="fr-FR">`.
- [ ] Groupe `hreflang`.
- [ ] Title.
- [ ] Meta description.
- [ ] Open Graph.
- [ ] H1 unique.
- [ ] Fil d’Ariane.
- [ ] Diagrammes responsive.
- [ ] Descriptions textuelles.
- [ ] Blocs de code accessibles.
- [ ] Liens HTML explorables.
- [ ] Textes alternatifs.
- [ ] JSON-LD.
- [ ] Sitemap.
- [ ] Directive robots.
- [ ] Test mobile.
- [ ] Test clavier.
- [ ] Test des liens.
- [ ] Test HTTP `200`.
- [ ] Relecture par un développeur FlatCMS.

---

# 37. Sources internes

- `README.md`
- `VERSION`
- `flatcms.json`
- `nginx.conf`
- `SITE_ARCHITECTURE.md`
- arborescence `app/`
- arborescence `app/Core/`
- arborescence `app/Modules/`
- arborescence `app/Services/`
- `config/routes.php`
- `config/hooks.php`
- arborescence `data/`
- arborescence `public/`
- arborescence `themes/`
- manifestes de modules ;
- tests et documentation technique validée.

---

# 38. Références externes

- PHP-FIG — PSR-4: Autoloader  
  https://www.php-fig.org/psr/psr-4/

- PHP Manual — Autoloading Classes  
  https://www.php.net/manual/en/language.oop5.autoload.php

- Nginx — Module ngx_http_core_module et `try_files`  
  https://nginx.org/en/docs/http/ngx_http_core_module.html#try_files

- OWASP — Web Security Testing Guide  
  https://owasp.org/www-project-web-security-testing-guide/

Les sources externes encadrent PSR-4, l’autoloading, la configuration
Nginx et les contrôles de sécurité. L’architecture spécifique de FlatCMS
reste définie par son code et ses documents officiels.

---

# 39. Journal des mises à jour

| Date | Version | Modification | Auteur |
|---|---:|---|---|
| 2026-06-08 | 1.0 | Première rédaction complète de la page Architecture | ChatGPT / Alain BROYE |

---

# 40. Prochaine action

Après validation et ajout dans le Drive :

```text
Créer DOCUMENTATION_CONTENT.md
```

Ce document contiendra la rédaction complète du hub :

```text
/fr-FR/documentation/
```

Il organisera les parcours de démarrage, d’administration, de création
de contenus, de développement, de déploiement, de maintenance et de
dépannage.

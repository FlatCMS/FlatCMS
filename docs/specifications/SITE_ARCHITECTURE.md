# SITE_ARCHITECTURE — Architecture du futur site officiel FlatCMS

> **Document directeur**
>
> Projet : FlatCMS  
> Domaine cible : `https://flat-cms.fr`  
> Version de référence du CMS : FlatCMS v1.0.0 LTS Core  
> Date de création : 8 juin 2026  
> Document parent : `SEO.md`  
> Statut : architecture fonctionnelle initiale à valider et maintenir

---

## 1. Objet du document

Ce document définit l’architecture fonctionnelle, éditoriale, technique et sémantique du futur site officiel de FlatCMS.

Il poursuit quatre objectifs :

1. construire un site cohérent avec l’architecture réelle de FlatCMS v1.0.0 ;
2. réunir le site produit, le blog et la documentation sous `flat-cms.fr` ;
3. préparer un référencement solide en SEO classique ;
4. rendre les contenus facilement accessibles, compréhensibles, réutilisables et citables par les moteurs de recherche et les systèmes génératifs.

Le présent document ne remplace pas la documentation du code. Il organise la manière dont l’architecture réelle de FlatCMS doit être expliquée au public.

---

## 2. Sources de vérité

La conception du futur site doit respecter l’ordre de priorité suivant.

### 2.1 Source primaire : code réel de FlatCMS v1.0.0

La source de vérité principale est le dossier Drive `FlatCMS`, qui contient notamment :

```text
FlatCMS/
├── app/
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
├── LICENSE
├── LICENSING.md
├── COMMERCIAL_LICENSE.md
├── TRADEMARK.md
├── CLA.md
└── nginx.conf
```

Aucune page du futur site ne doit présenter comme native une fonctionnalité qui n’existe pas dans cette version ou qui appartient à une ligne expérimentale, commerciale ou future.

### 2.2 Source secondaire : documents officiels du projet

Les documents suivants complètent le code :

- `README.md`
- `VERSION`
- `flatcms.json`
- `LICENSING.md`
- `COMMERCIAL_LICENSE.md`
- `TRADEMARK.md`
- `CLA.md`
- documentation technique validée
- notes de version officielles

### 2.3 Source éditoriale

Les fichiers de travail du dossier Drive `https://flat-cms.fr` décrivent la future présentation publique :

```text
https://flat-cms.fr/
├── SEO.md
├── SITE_ARCHITECTURE.md
├── CONTENT_MATRIX.md
├── DOCUMENTATION_MAP.md
├── KEYWORDS.md
├── REDIRECTS.md
└── ...
```

Ces documents doivent rester cohérents avec le code réel.

---

## 3. Principes d’architecture

### 3.1 Séparation des responsabilités

Le futur site doit distinguer clairement :

- le **produit** : ce qu’est FlatCMS et ce qu’il permet ;
- l’**architecture** : comment FlatCMS fonctionne ;
- la **documentation** : comment installer, configurer et utiliser FlatCMS ;
- le **blog** : analyses, actualités, comparatifs et contenus pédagogiques ;
- la **démonstration** : environnement de test séparé ;
- le **dépôt de code** : sources, contributions et historique technique.

### 3.2 Source unique par sujet

Chaque sujet important doit posséder une page canonique.

Exemples :

```text
Architecture HMVC
→ /fr-FR/architecture/hmvc/

Installation Nginx
→ /fr-FR/documentation/installation/nginx/

Stockage JSON
→ /fr-FR/architecture/stockage-json/

Création d’un module
→ /fr-FR/documentation/developpement/creer-un-module/
```

Les autres pages peuvent résumer le sujet, mais doivent pointer vers la page canonique.

### 3.3 Architecture centrée utilisateur

L’arborescence publique ne doit pas reproduire mécaniquement l’arborescence PHP.

Exemple :

```text
app/Modules/Pages/
```

devient publiquement :

```text
/fr-FR/fonctionnalites/gestion-des-pages/
/fr-FR/documentation/contenus/pages/
```

Le code reste la source de vérité ; le site traduit cette réalité dans une structure compréhensible.

---

## 4. Architecture technique réelle de FlatCMS v1.0.0

## 4.1 Racine du projet

La racine contient les points d’entrée et contrats principaux :

```text
index.php
flatcms.json
VERSION
README.md
LICENSE
nginx.conf
```

### Rôle public à documenter

- lancement de l’installateur ;
- identification de la version ;
- manifeste du produit ;
- licence ;
- configuration du serveur ;
- sécurité du document root.

### Page publique correspondante

```text
/fr-FR/documentation/installation/
/fr-FR/documentation/deploiement/nginx/
/fr-FR/licences/
/fr-FR/telechargement/
```

---

## 4.2 Dossier `app/`

Le dossier `app/` contient la logique applicative.

Structure observée :

```text
app/
├── Bootstrap/
├── Controllers/
├── Core/
├── Extensions/
├── Helpers/
├── Modules/
├── Services/
└── ThirdParty/
```

### Responsabilités

| Dossier | Responsabilité |
|---|---|
| `Bootstrap/` | Initialisation du runtime |
| `Controllers/` | Contrôleurs transversaux |
| `Core/` | Fondations du CMS |
| `Extensions/` | Points d’extension |
| `Helpers/` | Fonctions utilitaires |
| `Modules/` | Fonctionnalités HMVC |
| `Services/` | Services métier et techniques |
| `ThirdParty/` | Dépendances tierces isolées |

### Pages publiques correspondantes

```text
/fr-FR/architecture/vue-ensemble/
/fr-FR/architecture/hmvc/
/fr-FR/architecture/cycle-requete/
/fr-FR/architecture/services/
/fr-FR/architecture/extensions/
/fr-FR/documentation/developpement/
```

---

## 4.3 Dossier `app/Core/`

Éléments observés :

```text
app/Core/
├── App.php
├── Router.php
├── Request.php
├── Response.php
├── BaseController.php
├── View.php
├── FlatFile.php
├── ModuleManager.php
├── Hook.php
├── I18n.php
├── Session.php
├── CoreManifest.php
├── TranslationScanner.php
├── Mail/
└── Security/
```

### Modèle conceptuel

```text
Requête HTTP
    ↓
Bootstrap
    ↓
App
    ↓
Router
    ↓
Module / Contrôleur
    ↓
Services / Stockage JSON
    ↓
View
    ↓
Response HTTP
```

### Pages publiques correspondantes

```text
/fr-FR/architecture/cycle-requete/
/fr-FR/architecture/routage/
/fr-FR/architecture/controleurs-et-vues/
/fr-FR/architecture/stockage-flat-file/
/fr-FR/architecture/hooks/
/fr-FR/architecture/internationalisation/
/fr-FR/architecture/securite/
```

---

## 4.4 Dossier `app/Modules/`

Le dossier `app/Modules/` regroupe les fonctionnalités HMVC.

Modules observés dans la version de référence :

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

D’autres modules doivent être ajoutés au document uniquement après validation de leur présence et de leur statut dans la version LTS étudiée.

### Classification publique

#### Modules de contenu

```text
Pages
Posts
Categories
Media
Menu
Footer
```

#### Modules d’administration

```text
Dashboard
Users
Languages
Themes
Modules
Trash
Backups
```

#### Modules système

```text
Core
Auth
Install
HookManager
```

#### Modules IA ou premium

```text
AiAgent
```

Leur statut libre, premium, expérimental ou optionnel doit être clairement indiqué.

### Pages publiques correspondantes

```text
/fr-FR/fonctionnalites/pages/
/fr-FR/fonctionnalites/articles/
/fr-FR/fonctionnalites/categories/
/fr-FR/fonctionnalites/medias/
/fr-FR/fonctionnalites/menus/
/fr-FR/fonctionnalites/footer/
/fr-FR/fonctionnalites/utilisateurs/
/fr-FR/fonctionnalites/multilingue/
/fr-FR/fonctionnalites/themes/
/fr-FR/fonctionnalites/sauvegardes/
/fr-FR/fonctionnalites/corbeille/
/fr-FR/agent-ready/
```

---

## 4.5 Dossier `app/Services/`

Éléments observés :

```text
app/Services/
├── AI/
├── Licensing/
├── StructuredData/
├── UpdateArtifactService.php
└── UpdateCatalogService.php
```

### Responsabilités publiques

- abstraction et orchestration de services IA ;
- gestion des licences ;
- données structurées ;
- catalogue et mises à jour.

### Pages publiques correspondantes

```text
/fr-FR/architecture/services/
/fr-FR/architecture/donnees-structurees/
/fr-FR/licences/
/fr-FR/agent-ready/
/fr-FR/documentation/mises-a-jour/
```

Le site ne doit pas promettre une fonctionnalité IA opérationnelle simplement parce qu’un socle de services existe. Chaque capacité doit être qualifiée comme :

- disponible ;
- optionnelle ;
- premium ;
- expérimentale ;
- prévue.

---

## 4.6 Dossier `config/`

Fichiers observés :

```text
config/
├── app.php
├── routes.php
├── hooks.php
└── .htaccess
```

### Rôle

- configuration générale ;
- routes globales ;
- déclaration des hooks ;
- protection du dossier.

### Pages publiques correspondantes

```text
/fr-FR/documentation/configuration/
/fr-FR/documentation/configuration/application/
/fr-FR/documentation/developpement/routes/
/fr-FR/documentation/developpement/hooks/
```

Les secrets ne doivent jamais être documentés comme étant stockés dans les fichiers versionnés. Les clés et secrets doivent être placés dans les mécanismes privés prévus par le projet.

---

## 4.7 Dossier `data/`

Structure observée :

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

### Rôle

Le dossier `data/` constitue le stockage flat-file principal :

- pages ;
- articles ;
- catégories ;
- références médias ;
- formulaires ;
- utilisateurs ;
- états et configurations de modules.

### Pages publiques correspondantes

```text
/fr-FR/architecture/stockage-json/
/fr-FR/architecture/modeles-de-donnees/
/fr-FR/documentation/donnees/
/fr-FR/documentation/sauvegardes/
/fr-FR/documentation/securite/proteger-les-donnees/
```

### Règles de communication

Le site doit expliquer que :

- FlatCMS ne dépend pas d’un serveur SQL pour son fonctionnement normal ;
- les données sont structurées en fichiers ;
- l’intégrité JSON doit être validée ;
- les dossiers de données ne doivent pas être exposés publiquement ;
- les sauvegardes doivent couvrir le code, les données et les médias.

---

## 4.8 Dossier `public/`

Le dossier `public/` doit constituer le document root en production.

### Rôle

- point d’entrée public ;
- assets CSS et JavaScript ;
- images et médias publics ;
- fichiers accessibles par le navigateur ;
- routage frontal.

### Pages publiques correspondantes

```text
/fr-FR/documentation/deploiement/document-root-public/
/fr-FR/documentation/deploiement/apache/
/fr-FR/documentation/deploiement/nginx/
/fr-FR/documentation/securite/document-root/
```

### Principe de sécurité

Les dossiers suivants ne doivent pas être servis directement :

```text
app/
config/
data/
resources/
storage/
```

Le futur site officiel doit respecter lui-même cette règle pour démontrer les bonnes pratiques qu’il recommande.

---

## 4.9 Dossier `resources/`

Le dossier `resources/` regroupe les ressources non directement publiques ou destinées à être intégrées au rendu.

### Pages publiques correspondantes

```text
/fr-FR/architecture/ressources/
/fr-FR/documentation/themes/assets/
```

Le détail exact doit être complété après inventaire récursif du dossier.

---

## 4.10 Dossier `storage/`

Le dossier `storage/` est réservé aux données d’exécution :

- cache ;
- journaux ;
- fichiers temporaires ;
- éventuels artefacts générés.

### Pages publiques correspondantes

```text
/fr-FR/documentation/maintenance/cache/
/fr-FR/documentation/maintenance/journaux/
/fr-FR/documentation/depannage/
```

Le contenu précis doit être validé contre l’arborescence réelle avant publication.

---

## 4.11 Dossier `themes/`

Structure observée :

```text
themes/
├── admin/
└── frontend/
```

### Rôle

- thèmes de l’administration ;
- thèmes du site public ;
- séparation claire entre interface de gestion et rendu frontend.

### Pages publiques correspondantes

```text
/fr-FR/fonctionnalites/themes/
/fr-FR/documentation/themes/
/fr-FR/documentation/themes/creer-un-theme/
/fr-FR/documentation/themes/theme-administration/
/fr-FR/documentation/themes/theme-frontend/
```

---

## 5. Architecture éditoriale du futur site

## 5.1 Structure racine

```text
https://flat-cms.fr/
├── fr-FR/
├── en-US/
├── de-DE/
├── es-ES/
├── it-IT/
├── pt-PT/
├── assets/
├── sitemap.xml
├── robots.txt
└── .well-known/
```

La racine `/` joue le rôle de page internationale ou de version `x-default`.

---

## 5.2 Arborescence française cible

```text
/fr-FR/
├── fonctionnalites/
│   ├── pages/
│   ├── articles/
│   ├── categories/
│   ├── medias/
│   ├── menus/
│   ├── footer/
│   ├── utilisateurs/
│   ├── multilingue/
│   ├── themes/
│   ├── modules/
│   ├── sauvegardes/
│   ├── corbeille/
│   └── builders/
├── architecture/
│   ├── vue-ensemble/
│   ├── hmvc/
│   ├── psr-4/
│   ├── cycle-requete/
│   ├── routage/
│   ├── controleurs-et-vues/
│   ├── services/
│   ├── modules/
│   ├── hooks/
│   ├── stockage-json/
│   ├── modeles-de-donnees/
│   ├── internationalisation/
│   ├── donnees-structurees/
│   └── securite/
├── documentation/
│   ├── demarrage/
│   ├── installation/
│   ├── configuration/
│   ├── administration/
│   ├── contenus/
│   ├── medias/
│   ├── menus/
│   ├── themes/
│   ├── modules/
│   ├── widgets/
│   ├── builders/
│   ├── developpement/
│   ├── deploiement/
│   ├── maintenance/
│   ├── securite/
│   └── depannage/
├── blog/
├── comparatifs/
├── agent-ready/
├── telechargement/
├── tarifs/
├── licences/
├── roadmap/
├── a-propos/
└── contact/
```

---

## 6. Navigation principale

### 6.1 Menu desktop

```text
Produit
Fonctionnalités
Architecture
Documentation
Blog
Tarifs
```

Actions visibles :

```text
Télécharger
Tester la démo
```

### 6.2 Menu mobile

Le menu mobile doit conserver les mêmes priorités sans reproduire un méga-menu illisible.

Ordre recommandé :

1. Produit
2. Fonctionnalités
3. Documentation
4. Architecture
5. Blog
6. Tarifs
7. Télécharger
8. Tester la démo

---

## 7. Méga-menu Produit

```text
Produit
├── Pourquoi FlatCMS
├── Fonctionnalités
├── Architecture
├── Sécurité
├── Performances
├── Multilingue
├── Agent-ready
├── Roadmap
└── Comparatifs
```

Chaque entrée doit comporter :

- un titre court ;
- une description de une ligne ;
- une icône accessible ;
- un lien HTML standard ;
- aucune navigation uniquement pilotée par JavaScript.

---

## 8. Méga-menu Documentation

```text
Documentation
├── Démarrage
│   ├── Présentation
│   ├── Prérequis
│   ├── Installation
│   └── Premier site
├── Utilisation
│   ├── Pages
│   ├── Articles
│   ├── Catégories
│   ├── Médias
│   ├── Menus
│   └── Utilisateurs
├── Personnalisation
│   ├── Thèmes
│   ├── Builders
│   ├── Widgets
│   └── Multilingue
├── Développement
│   ├── Architecture HMVC
│   ├── PSR-4
│   ├── Modules
│   ├── Routes
│   ├── Hooks
│   └── Services
└── Exploitation
    ├── Apache
    ├── Nginx
    ├── Sauvegardes
    ├── Sécurité
    ├── Maintenance
    └── Dépannage
```

---

## 9. Footer

### Colonne Produit

- Fonctionnalités
- Architecture
- Agent-ready
- Comparatifs
- Roadmap

### Colonne Ressources

- Documentation
- Blog
- Tutoriels
- Démo
- GitHub

### Colonne Écosystème

- Modules
- Thèmes
- Builders
- Licences
- Tarifs

### Colonne Projet

- À propos
- Contact
- Contribuer
- Marque
- Mentions légales
- Confidentialité

### Bas de page

- copyright ;
- version stable actuelle ;
- sélecteur de langue ;
- statut du service ;
- liens sociaux officiels.

---

## 10. Modèles de pages

## 10.1 Page produit

Structure :

```text
H1
Résumé de valeur
CTA principal et secondaire
Preuves ou caractéristiques principales
Cas d’usage
Fonctionnalités
Architecture associée
Documentation associée
FAQ
CTA final
```

Données structurées possibles :

```text
WebPage
SoftwareApplication
BreadcrumbList
FAQPage si éligible
```

---

## 10.2 Page fonctionnalité

Structure :

```text
H1 explicite
Problème résolu
Fonctionnement
Captures ou démonstration
Avantages
Limites ou prérequis
Documentation liée
Fonctionnalités connexes
CTA démo
```

Chaque fonctionnalité doit indiquer son statut :

```text
Core
Optionnelle
Premium
Expérimentale
Prévue
```

---

## 10.3 Page d’architecture

Structure :

```text
H1 technique
Résumé simple
Schéma conceptuel
Composants réels
Flux de fonctionnement
Emplacements dans le projet
Exemple minimal
Sécurité
Liens vers la documentation
```

Cette page doit être compréhensible à deux niveaux :

- résumé pour décideurs ;
- détails pour développeurs.

---

## 10.4 Documentation procédurale

Structure :

```text
H1 orienté action
Objectif
Prérequis
Étapes numérotées
Commandes ou code
Résultat attendu
Vérification
Erreurs fréquentes
Étape suivante
```

Chaque commande doit préciser :

- système concerné ;
- chemin ;
- version PHP ;
- serveur ;
- résultat attendu.

---

## 10.5 Article de blog

Structure :

```text
H1
Chapeau
Auteur
Dates
Sommaire
Contexte
Analyse originale
Exemples FlatCMS
Conclusion
Sources
Pages liées
```

L’article ne doit pas dupliquer une page de documentation. Il doit expliquer, comparer, analyser ou contextualiser.

---

## 10.6 Comparatif

Structure :

```text
H1
Méthodologie
Périmètre
Tableau comparatif
Analyse critère par critère
Avantages de chaque solution
Limites
Profils adaptés
Conclusion nuancée
```

Les comparatifs ne doivent pas dénigrer les concurrents ni inventer des performances.

---

## 11. Architecture SEO

Chaque page indexable doit fournir :

- URL stable et descriptive ;
- code HTTP `200` ;
- titre unique ;
- H1 unique ;
- meta description spécifique ;
- canonique auto-référencée ;
- contenu textuel disponible dans le HTML ;
- fil d’Ariane ;
- liens internes contextuels ;
- image Open Graph ;
- données structurées correspondant au contenu visible ;
- langue de document ;
- alternatives `hreflang`.

Les pages non indexables doivent utiliser des directives explicites adaptées.

---

## 12. Architecture GEO/GIO

## 12.1 Terminologie

Dans ce projet :

- **SEO** désigne l’optimisation pour les moteurs de recherche ;
- **GEO** désigne l’optimisation de la visibilité et de la citation dans les moteurs génératifs ;
- **GIO** désigne l’organisation des contenus pour l’indexation et la réutilisation par des systèmes génératifs.

Ces termes ne constituent pas une certification technique officielle universelle.

Le site ne doit donc pas prétendre :

```text
100 % conforme GEO
Certifié GIO
Garanti dans les réponses IA
```

Il peut présenter FlatCMS comme :

```text
Conçu pour produire des contenus structurés, accessibles, traçables et
faciles à interpréter par les moteurs de recherche et les systèmes génératifs.
```

---

## 12.2 Principes de citabilité

Chaque page stratégique doit privilégier :

- une définition claire au début ;
- des phrases autonomes ;
- des titres descriptifs ;
- des réponses directes aux questions ;
- des tableaux synthétiques ;
- des listes d’étapes ;
- des exemples vérifiables ;
- des sources ;
- un auteur identifiable ;
- des dates exactes ;
- un historique de mise à jour ;
- une page À propos complète ;
- des informations de licence et de marque explicites.

---

## 12.3 Blocs de réponse

Les pages techniques doivent pouvoir contenir des blocs comme :

```text
Définition
En bref
Prérequis
Procédure
Exemple
Résultat attendu
Limites
Questions fréquentes
Sources
```

Ces blocs facilitent la lecture humaine, l’extraction de passages pertinents et la citation contextuelle.

---

## 12.4 Accès des robots

La stratégie de crawl doit distinguer :

- moteurs de recherche ;
- moteurs génératifs de recherche ;
- robots d’entraînement ;
- robots inconnus ou abusifs.

Une politique dédiée sera définie ultérieurement dans :

```text
CRAWL_POLICY.md
```

Le choix d’autoriser un robot de recherche et de bloquer un robot d’entraînement doit être documenté séparément.

---

## 12.5 Données structurées

Le service `StructuredData` de FlatCMS constitue un socle intéressant, mais le futur site doit vérifier chaque balisage généré.

Types prioritaires :

```text
Organization
WebSite
WebPage
SoftwareApplication
Article
BlogPosting
BreadcrumbList
FAQPage
VideoObject
ProfilePage
```

Règles :

- les données structurées décrivent uniquement du contenu visible ;
- JSON-LD est privilégié ;
- les propriétés sont complètes et exactes ;
- chaque template est validé ;
- aucune donnée fictive n’est publiée.

---

## 13. Architecture multilingue

Locales :

```text
fr-FR
en-US
de-DE
es-ES
it-IT
pt-PT
```

Chaque page doit posséder :

- une URL locale ;
- un contenu traduit ;
- un title traduit ;
- une meta description traduite ;
- un H1 traduit ;
- des liens `hreflang` réciproques ;
- un lien `x-default` ;
- une canonique vers elle-même.

Exemple :

```text
/fr-FR/architecture/hmvc/
/en-US/architecture/hmvc/
/de-DE/architektur/hmvc/
/es-ES/arquitectura/hmvc/
/it-IT/architettura/hmvc/
/pt-PT/arquitetura/hmvc/
```

Les slugs peuvent être localisés si FlatCMS garantit durablement leur correspondance.

---

## 14. Architecture des médias

Arborescence publique recommandée :

```text
/assets/
├── brand/
├── icons/
├── images/
├── screenshots/
├── diagrams/
├── videos/
└── downloads/
```

Règles :

- noms de fichiers descriptifs ;
- dimensions explicites ;
- variantes responsive ;
- WebP ou AVIF lorsque pertinent ;
- texte alternatif utile ;
- légende pour les schémas complexes ;
- image Open Graph par page importante ;
- pas de texte essentiel uniquement intégré dans une image.

---

## 15. Architecture de la démonstration

```text
https://demo.flat-cms.fr/
```

La démo reste séparée.

### Page d’accueil

Indexable si elle décrit clairement :

- ce que la démo permet ;
- les identifiants temporaires ;
- la remise à zéro ;
- les limites ;
- les liens vers le site officiel.

### Contenus fictifs

Les articles, pages et univers de démonstration doivent être placés en :

```html
<meta name="robots" content="noindex, follow">
```

Ils ne doivent pas être inclus dans les sitemaps officiels.

---

## 16. Architecture des téléchargements

```text
/fr-FR/telechargement/
├── version-lts/
├── prerequis/
├── installation/
├── notes-de-version/
├── verification/
└── archives/
```

Chaque version doit indiquer :

- numéro ;
- date ;
- canal ;
- prérequis PHP ;
- checksum ;
- licence ;
- changelog ;
- documentation correspondante.

---

## 17. Architecture des licences

```text
/fr-FR/licences/
├── vue-ensemble/
├── open-source/
├── commerciale/
├── contributions/
└── marque/
```

Correspondances :

```text
LICENSE
LICENSING.md
COMMERCIAL_LICENSE.md
CLA.md
TRADEMARK.md
```

Le site doit clairement distinguer :

- licence du code ;
- composants premium ;
- droits de contribution ;
- droits de marque.

---

## 18. Maillage interne

### Règle des trois niveaux

Une page importante doit rester accessible en trois clics maximum depuis l’accueil.

### Liens obligatoires

Chaque page doit proposer :

- page parent ;
- pages sœurs ;
- documentation associée ;
- fonctionnalité associée ;
- article pédagogique associé ;
- CTA pertinent.

### Ancres

Préférer :

```text
Comprendre le stockage JSON de FlatCMS
```

Éviter :

```text
Cliquez ici
En savoir plus
Lien
```

---

## 19. Migration du wiki

La migration doit suivre ce flux :

```text
Inventaire
→ Classification
→ Nouvelle URL
→ Réécriture
→ Validation
→ Publication
→ Redirection 301
→ Contrôle
```

Aucune ancienne page importante ne doit être redirigée génériquement vers l’accueil.

La table complète sera maintenue dans :

```text
REDIRECTS.md
```

---

## 20. Documents complémentaires à produire

Ordre recommandé :

1. `CONTENT_MATRIX.md`
2. `DOCUMENTATION_MAP.md`
3. `KEYWORDS.md`
4. `REDIRECTS.md`
5. `STRUCTURED_DATA.md`
6. `CRAWL_POLICY.md`
7. `CONTENT_STYLE_GUIDE.md`
8. `MEDIA_GUIDE.md`
9. `MULTILINGUAL.md`
10. `LAUNCH_CHECKLIST.md`

---

## 21. Roadmap d’implémentation

## Phase A — Validation de l’architecture

- [ ] Valider les rubriques
- [ ] Valider les URLs
- [ ] Valider les menus
- [ ] Valider le footer
- [ ] Valider les modèles de page
- [ ] Valider les statuts Core/Premium/Expérimental
- [ ] Compléter l’inventaire des modules

## Phase B — Modèles FlatCMS

- [ ] Template page produit
- [ ] Template fonctionnalité
- [ ] Template architecture
- [ ] Template documentation
- [ ] Template article
- [ ] Template comparatif
- [ ] Template téléchargement
- [ ] Template licence
- [ ] Template roadmap

## Phase C — Navigation

- [ ] Menu principal
- [ ] Méga-menu Produit
- [ ] Méga-menu Documentation
- [ ] Menu mobile
- [ ] Footer
- [ ] Fil d’Ariane
- [ ] Navigation précédente/suivante

## Phase D — SEO technique

- [ ] Titles
- [ ] Meta descriptions
- [ ] Canonicals
- [ ] Hreflang
- [ ] Sitemaps
- [ ] Robots
- [ ] Données structurées
- [ ] Open Graph
- [ ] Pages 404
- [ ] Redirections

## Phase E — Contenus prioritaires

- [ ] Accueil
- [ ] Fonctionnalités
- [ ] Architecture
- [ ] Documentation
- [ ] Installation
- [ ] Stockage JSON
- [ ] HMVC
- [ ] PSR-4
- [ ] Modules
- [ ] Thèmes
- [ ] Sécurité
- [ ] Agent-ready
- [ ] Téléchargement
- [ ] Licences
- [ ] Tarifs

## Phase F — Migration

- [ ] Inventaire wiki
- [ ] Réécriture
- [ ] Import médias
- [ ] Redirections
- [ ] Contrôle indexation
- [ ] Suppression des doublons

## Phase G — Multilingue

- [ ] fr-FR
- [ ] en-US
- [ ] de-DE
- [ ] es-ES
- [ ] it-IT
- [ ] pt-PT

---

## 22. Critères de validation

Le document est validé lorsque :

- l’arborescence publique correspond aux fonctions réelles ;
- chaque fonctionnalité possède un statut clair ;
- le Core et le Premium sont distingués ;
- la documentation ne repose pas sur une ancienne architecture ;
- les URLs sont stables et compréhensibles ;
- le site, le blog et la documentation sont reliés ;
- la démo est isolée ;
- le multilingue est prévu dès la conception ;
- les données structurées correspondent au contenu visible ;
- les contenus importants sont accessibles en HTML ;
- les revendications SEO/GEO/GIO restent démontrables ;
- chaque page a une finalité utilisateur claire.

---

## 23. Décisions actées

1. `flat-cms.fr` devient le domaine central.
2. Blog et documentation sont intégrés au domaine principal.
3. `demo.flat-cms.fr` reste séparé.
4. Le code FlatCMS v1.0.0 constitue la source de vérité.
5. L’arborescence publique traduit l’architecture technique sans la copier.
6. Le statut de chaque fonctionnalité est affiché.
7. Le site utilise les six locales natives.
8. Le contenu reste prioritairement destiné aux utilisateurs.
9. Les optimisations génératives s’appuient sur les fondamentaux SEO.
10. Aucune garantie de classement ou de citation par une IA n’est formulée.

---

## 24. Références externes

- Google Search Central — AI features and your website  
  https://developers.google.com/search/docs/appearance/ai-features

- Google Search Central — Helpful, reliable, people-first content  
  https://developers.google.com/search/docs/fundamentals/creating-helpful-content

- Google Search Central — Structured data  
  https://developers.google.com/search/docs/appearance/structured-data/intro-structured-data

- PHP-FIG — PSR-4  
  https://www.php-fig.org/psr/psr-4/

---

## 25. Journal des mises à jour

| Date | Version | Modification | Auteur |
|---|---:|---|---|
| 2026-06-08 | 1.0 | Création de l’architecture initiale du futur site officiel | ChatGPT / Alain BROYE |

---

## 26. Prochaine étape

Créer :

```text
CONTENT_MATRIX.md
```

Ce document associera à chaque URL :

- audience ;
- intention ;
- mot-clé principal ;
- mots-clés secondaires ;
- H1 ;
- title ;
- meta description ;
- structure H2/H3 ;
- CTA ;
- liens internes ;
- données structurées ;
- statut de rédaction ;
- statut de traduction.

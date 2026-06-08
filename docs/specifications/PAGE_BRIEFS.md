# PAGE_BRIEFS — Briefs des pages P0 du futur site officiel FlatCMS

> **Document directeur de conception éditoriale**
>
> Projet : FlatCMS  
> Domaine cible : `https://flat-cms.fr`  
> Version de référence : FlatCMS v1.0.0 LTS Core  
> Locale source : `fr-FR`  
> Date de création : 8 juin 2026  
> Documents parents : `INDEX.md`, `SEO.md`, `SITE_ARCHITECTURE.md`, `CONTENT_MATRIX.md`, `DOCUMENTATION_MAP.md`, `KEYWORDS.md`, `STRUCTURED_DATA.md`, `CONTENT_STYLE_GUIDE.md`, `MULTILINGUAL.md`  
> Statut : briefs initiaux à valider avant rédaction complète des pages

---

## 1. Objet du document

Ce document définit les briefs détaillés des pages P0 du futur site officiel FlatCMS.

Il précède :

- la rédaction complète ;
- la conception des wireframes ;
- la création du thème ;
- la production des médias ;
- l’intégration dans FlatCMS ;
- la traduction dans les cinq autres locales.

Chaque brief fixe :

- l’intention principale ;
- l’audience ;
- la promesse ;
- l’URL ;
- le title ;
- la meta description ;
- le H1 ;
- la structure H2/H3 ;
- les preuves attendues ;
- les CTA ;
- le maillage interne ;
- les médias ;
- les données structurées ;
- les règles d’indexation ;
- les critères de validation.

---

# 2. Règles communes aux pages P0

## 2.1 Une intention principale

Chaque page répond à une question principale.

Elle ne doit pas essayer de se positionner sur plusieurs intentions différentes sans hiérarchie claire.

---

## 2.2 Une réponse essentielle visible rapidement

La première section doit permettre de comprendre :

```text
ce qu’est la page
à qui elle s’adresse
ce que l’utilisateur peut y faire
```

---

## 2.3 Une affirmation, une preuve

Toute promesse importante doit être reliée à :

- un composant réel ;
- un document officiel ;
- un test ;
- une mesure ;
- une licence ;
- un statut produit.

---

## 2.4 Contenu visible dans le HTML

Le contenu essentiel doit être rendu sous forme de texte HTML.

Il ne doit pas dépendre uniquement :

- d’une animation ;
- d’un carrousel ;
- d’une vidéo ;
- d’un composant JavaScript ;
- d’une image.

---

## 2.5 Statuts

Chaque brief utilise :

```text
À valider
À rédiger
En rédaction
À relire
Validé
Intégré
Publié
```

---

# 3. Brief 01 — Accueil

## Identité

| Champ | Valeur |
|---|---|
| ID | `P0-HOME-FR` |
| Priorité | P0 |
| Statut | À valider |
| URL | `/fr-FR/` |
| Type | Page produit principale |
| Audience | Décideurs, développeurs, agences, créateurs de sites |
| Intention | Comprendre rapidement FlatCMS et choisir la prochaine action |
| Mot-clé principal | CMS PHP sans base de données |
| Secondaires | FlatCMS, CMS flat-file PHP, CMS PHP JSON, CMS open source français, CMS léger |

## Title

```text
FlatCMS — CMS PHP open source sans base de données
```

## Meta description

```text
Découvrez FlatCMS, un CMS flat-file en PHP natif avec architecture HMVC,
autoloading PSR-4, stockage JSON, modules et gestion multilingue.
```

## H1

```text
FlatCMS, le CMS PHP open source sans base de données
```

## Promesse principale

```text
Créer, administrer et déployer un site moderne sans dépendre d’un serveur
SQL, avec un cœur PHP natif et une architecture modulaire.
```

## Réponse essentielle

Le premier écran doit expliquer en quelques lignes :

- ce qu’est FlatCMS ;
- ce qui le différencie ;
- à qui il s’adresse ;
- comment le tester ou le télécharger.

## CTA principal

```text
Télécharger FlatCMS
```

## CTA secondaire

```text
Tester la démo
```

## CTA tertiaire

```text
Consulter la documentation
```

## Structure

```text
H2 — Un CMS sans serveur SQL
H2 — Les fonctionnalités essentielles
H2 — Une architecture PHP native et modulaire
H2 — Pages, articles, médias et navigation
H2 — Un CMS multilingue
H2 — Builders et composants premium
H2 — Sécurité et document root public
H2 — Données structurées et agent-ready
H2 — Comparer FlatCMS à d’autres CMS
H2 — Commencer avec FlatCMS
H2 — Derniers articles et mises à jour
H2 — Questions fréquentes
```

## Contenu attendu

### Hero

- logo et identité FlatCMS ;
- H1 ;
- introduction courte ;
- deux CTA ;
- capture ou représentation réelle de l’administration ;
- pas de superlatif non démontré.

### Valeur

Expliquer :

- absence de serveur SQL ;
- stockage JSON ;
- simplicité de déploiement ;
- modularité ;
- multilingue ;
- séparation administration/frontend.

### Fonctionnalités

Présenter uniquement les fonctionnalités validées.

Les composants premium doivent être identifiés comme tels.

### Architecture

Résumé court avec liens vers :

- HMVC ;
- PSR-4 ;
- stockage JSON ;
- services ;
- hooks.

### Preuves

- version stable ;
- liens vers GitHub ;
- documentation ;
- démo ;
- éventuellement benchmarks publiés.

## Preuves attendues

- `README.md` ;
- `VERSION` ;
- arborescence réelle ;
- modules réels ;
- captures de la version stable ;
- liens vers les pages techniques.

## Médias

- logo officiel ;
- capture d’administration ;
- diagramme d’architecture simplifié ;
- image Open Graph dédiée ;
- aucun texte essentiel uniquement dans les images.

## Liens internes

- Pourquoi FlatCMS
- Fonctionnalités
- Architecture
- Documentation
- Installation
- Téléchargement
- Licences
- Tarifs
- Agent-ready
- À propos
- Démo

## Données structurées

```text
Organization
WebSite
SoftwareApplication
WebPage
ImageObject
```

## Indexation

```text
index, follow, max-image-preview:large
```

## Critères de validation

- [ ] La proposition de valeur est comprise en moins de 15 secondes.
- [ ] Le H1 contient le positionnement principal.
- [ ] Les CTA sont visibles sans défilement excessif.
- [ ] Les statuts Core/Premium sont clairs.
- [ ] Les affirmations techniques ont des liens de preuve.
- [ ] La page ne duplique pas la page Fonctionnalités.
- [ ] Le contenu essentiel existe en HTML.
- [ ] La page fonctionne sur mobile.

---

# 4. Brief 02 — Pourquoi FlatCMS

## Identité

| Champ | Valeur |
|---|---|
| ID | `P0-WHY-FR` |
| Priorité | P0 |
| Statut | À valider |
| URL | `/fr-FR/pourquoi-flatcms/` |
| Type | Page d’évaluation |
| Audience | Porteurs de projet, agences, développeurs |
| Intention | Déterminer si FlatCMS convient à un projet |
| Mot-clé principal | pourquoi choisir FlatCMS |
| Secondaires | CMS léger, CMS sans MySQL, CMS pour site vitrine, CMS flat-file |

## Title

```text
Pourquoi choisir FlatCMS pour créer un site web ?
```

## Meta description

```text
Découvrez les avantages, limites et cas d’usage de FlatCMS, CMS PHP
flat-file sans serveur SQL, destiné aux sites modernes et modulaires.
```

## H1

```text
Pourquoi choisir FlatCMS ?
```

## Promesse

Aider le lecteur à prendre une décision honnête, y compris lorsque FlatCMS n’est pas le meilleur choix.

## CTA principal

```text
Vérifier les prérequis
```

## CTA secondaire

```text
Comparer FlatCMS et WordPress
```

## Structure

```text
H2 — À quels projets FlatCMS est-il destiné ?
H2 — Les bénéfices d’un CMS flat-file
H2 — Déploiement et maintenance
H2 — Architecture et extensibilité
H2 — Multilingue et contenus structurés
H2 — Sécurité et surface d’exposition
H2 — Les limites à connaître
H2 — Dans quels cas choisir une autre solution ?
H2 — Tableau d’aide à la décision
H2 — Tester FlatCMS
```

## Cas d’usage prioritaires

- site vitrine ;
- blog ;
- documentation ;
- projet multilingue ;
- petite agence ;
- serveur à ressources limitées ;
- projet nécessitant une architecture PHP lisible.

## Limites à expliquer

- besoins transactionnels très élevés ;
- grand écosystème d’extensions attendu ;
- cas nécessitant une base relationnelle ;
- exigences e-commerce avancées non couvertes nativement ;
- dépendance à des modules premium pour certains builders.

## Preuves attendues

- architecture réelle ;
- limites techniques validées ;
- cas de test ;
- démo ;
- benchmarks uniquement s’ils sont disponibles.

## Liens internes

- Fonctionnalités
- Architecture
- Stockage JSON
- Comparatifs
- Installation
- Démo
- Téléchargement

## Données structurées

```text
WebPage
BreadcrumbList
```

## Critères

- [ ] La page présente avantages et limites.
- [ ] Aucun concurrent n’est dénigré.
- [ ] Les cas d’usage sont précis.
- [ ] Le tableau de décision est lisible.
- [ ] La page ne répète pas intégralement l’accueil.

---

# 5. Brief 03 — Fonctionnalités

## Identité

| Champ | Valeur |
|---|---|
| ID | `P0-FEATURES-FR` |
| Priorité | P0 |
| Statut | À valider |
| URL | `/fr-FR/fonctionnalites/` |
| Type | Page catalogue |
| Audience | Utilisateurs et évaluateurs |
| Intention | Connaître les capacités actuelles de FlatCMS |
| Mot-clé principal | fonctionnalités FlatCMS |
| Secondaires | CMS pages articles médias, CMS modulaire, CMS multilingue |

## Title

```text
Fonctionnalités de FlatCMS : pages, articles, médias et modules
```

## Meta description

```text
Explorez les fonctionnalités de FlatCMS : pages, articles, catégories,
médias, menus, utilisateurs, thèmes, langues, sauvegardes et modules.
```

## H1

```text
Les fonctionnalités de FlatCMS
```

## CTA principal

```text
Tester les fonctionnalités dans la démo
```

## CTA secondaire

```text
Consulter la documentation
```

## Structure

```text
H2 — Gestion des pages
H2 — Articles et catégories
H2 — Médiathèque
H2 — Menus et footer
H2 — Utilisateurs, rôles et authentification
H2 — Langues et traductions
H2 — Thèmes frontend et administration
H2 — Modules et hooks
H2 — Sauvegardes et corbeille
H2 — Builders visuels
H2 — Services IA
H2 — Tableau Core, optionnel, premium et prévu
```

## Règle essentielle

Chaque fonctionnalité affiche un badge :

```text
Core
Optionnelle
Premium
Expérimentale
Prévue
```

## Preuves attendues

- module correspondant ;
- capture réelle ;
- documentation ;
- statut de licence ;
- version concernée.

## Médias

- grille de captures ;
- icônes cohérentes ;
- courtes vidéos ou GIF uniquement en complément ;
- image Open Graph.

## Données structurées

```text
CollectionPage
BreadcrumbList
SoftwareApplication
```

## Critères

- [ ] Aucune fonctionnalité future n’est présentée comme disponible.
- [ ] Les fonctions premium sont identifiées.
- [ ] Chaque bloc renvoie vers une page ou une documentation.
- [ ] Les captures correspondent à la version stable.
- [ ] La page reste lisible sans JavaScript.

---

# 6. Brief 04 — Architecture

## Identité

| Champ | Valeur |
|---|---|
| ID | `P0-ARCH-FR` |
| Priorité | P0 |
| Statut | À valider |
| URL | `/fr-FR/architecture/` |
| Type | Page technique pilier |
| Audience | Développeurs, intégrateurs, décideurs techniques |
| Intention | Comprendre comment FlatCMS fonctionne |
| Mot-clé principal | architecture FlatCMS |
| Secondaires | architecture HMVC PHP, PSR-4 CMS, CMS JSON |

## Title

```text
Architecture FlatCMS — PHP natif, HMVC, PSR-4 et JSON
```

## Meta description

```text
Comprenez l’architecture de FlatCMS : cœur PHP natif, modules HMVC,
autoloading PSR-4, services, hooks, vues et stockage JSON.
```

## H1

```text
Architecture de FlatCMS v1.0.0
```

## CTA principal

```text
Explorer la documentation développeur
```

## Structure

```text
H2 — Vue d’ensemble
H2 — Cycle d’une requête
H2 — Le cœur applicatif
H2 — Les modules HMVC
H2 — L’autoloading PSR-4
H2 — Les services
H2 — Les hooks et extensions
H2 — Le stockage flat-file JSON
H2 — Les thèmes
H2 — La sécurité du document root
H2 — Arborescence de référence
```

## Diagramme attendu

```text
Request
→ Bootstrap
→ App
→ Router
→ Module / Controller
→ Service / FlatFile
→ View
→ Response
```

## Preuves attendues

- `app/Core` ;
- `app/Modules` ;
- `app/Services` ;
- `config/routes.php` ;
- `data/` ;
- `themes/` ;
- code et fichiers réels.

## Liens internes

- HMVC
- PSR-4
- Cycle de requête
- Services
- Hooks
- Stockage JSON
- Sécurité
- Créer un module

## Données structurées

```text
WebPage
TechArticle
BreadcrumbList
ImageObject
```

## Critères

- [ ] L’architecture correspond à FlatCMS v1.0.0.
- [ ] Aucun terme d’une ancienne architecture n’est repris.
- [ ] Les chemins cités existent.
- [ ] Le diagramme possède une description textuelle.
- [ ] Les détails renvoient vers des pages spécialisées.

---

# 7. Brief 05 — Documentation

## Identité

| Champ | Valeur |
|---|---|
| ID | `P0-DOCS-FR` |
| Priorité | P0 |
| Statut | À valider |
| URL | `/fr-FR/documentation/` |
| Type | Hub documentaire |
| Audience | Utilisateurs, administrateurs, développeurs |
| Intention | Trouver le bon guide ou la bonne référence |
| Mot-clé principal | documentation FlatCMS |
| Secondaires | guide FlatCMS, tutoriel FlatCMS, installer FlatCMS |

## Title

```text
Documentation FlatCMS — Installation, utilisation et développement
```

## Meta description

```text
Accédez à la documentation officielle de FlatCMS : installation,
configuration, contenus, thèmes, modules, déploiement et dépannage.
```

## H1

```text
Documentation officielle de FlatCMS
```

## CTA principal

```text
Commencer l’installation
```

## Structure

```text
H2 — Nouveau sur FlatCMS
H2 — Administrer un site
H2 — Créer et publier des contenus
H2 — Personnaliser les thèmes
H2 — Développer des modules
H2 — Déployer en production
H2 — Sécuriser FlatCMS
H2 — Maintenir et sauvegarder
H2 — Résoudre un problème
H2 — Consulter la référence
H2 — Documentation par version
```

## Fonctionnalités de la page

- recherche locale ;
- filtres par rôle ;
- filtres par type documentaire ;
- filtres par version ;
- parcours recommandés ;
- contenus récents ou mis à jour.

## Preuves attendues

- pages réellement publiées ;
- version de documentation ;
- statuts des guides ;
- navigation cohérente avec `DOCUMENTATION_MAP.md`.

## Données structurées

```text
CollectionPage
BreadcrumbList
```

## Indexation

La page est indexable.

Les pages de résultats de recherche interne sont en `noindex, follow`.

## Critères

- [ ] Chaque carte mène vers une page existante.
- [ ] Les quatre types Diátaxis sont distingués.
- [ ] La version stable est visible.
- [ ] La recherche fonctionne sans générer des pages indexables inutiles.
- [ ] Les parcours débutant et développeur sont évidents.

---

# 8. Brief 06 — Installation

## Identité

| Champ | Valeur |
|---|---|
| ID | `P0-INSTALL-FR` |
| Priorité | P0 |
| Statut | À valider |
| URL | `/fr-FR/documentation/installation/` |
| Type | Guide principal |
| Audience | Nouveaux utilisateurs |
| Intention | Installer FlatCMS correctement |
| Mot-clé principal | installer FlatCMS |
| Secondaires | installation CMS PHP, FlatCMS MAMP, WAMP, Apache, Nginx |

## Title

```text
Installer FlatCMS sur Apache, Nginx, MAMP ou WAMP
```

## Meta description

```text
Installez FlatCMS, vérifiez PHP, configurez le document root public,
appliquez les permissions et lancez l’installateur officiel.
```

## H1

```text
Installer FlatCMS
```

## CTA principal

```text
Télécharger FlatCMS LTS
```

## Structure

```text
H2 — Environnements pris en charge
H2 — Prérequis
H2 — Télécharger et vérifier l’archive
H2 — Extraire les fichiers
H2 — Configurer le document root sur public/
H2 — Appliquer les permissions
H2 — Lancer l’installateur
H2 — Finaliser la configuration
H2 — Vérifier le frontend
H2 — Vérifier l’administration
H2 — Sécuriser l’installation
H2 — Erreurs fréquentes
H2 — Étapes suivantes
```

## Sous-guides liés

- Apache ;
- Nginx ;
- MAMP ;
- WAMP ;
- Synology ;
- Raspberry Pi ;
- hébergement mutualisé.

## Preuves attendues

- prérequis réels ;
- URL ou point d’entrée de l’installateur ;
- permissions testées ;
- configurations serveur ;
- tests sur environnements annoncés.

## Données structurées

```text
TechArticle
HowTo si toutes les étapes sont visibles
BreadcrumbList
```

## Critères

- [ ] La procédure a été testée sur une archive vierge.
- [ ] Le document root `public/` est clairement expliqué.
- [ ] Les versions PHP sont exactes.
- [ ] Les commandes indiquent leur système.
- [ ] Chaque étape possède un résultat attendu.
- [ ] Les erreurs fréquentes sont liées au dépannage.

---

# 9. Brief 07 — Téléchargement

## Identité

| Champ | Valeur |
|---|---|
| ID | `P0-DOWNLOAD-FR` |
| Priorité | P0 |
| Statut | À valider |
| URL | `/fr-FR/telechargement/` |
| Type | Page de conversion |
| Audience | Utilisateurs prêts à installer |
| Intention | Télécharger la version officielle |
| Mot-clé principal | télécharger FlatCMS |
| Secondaires | FlatCMS LTS, version FlatCMS, CMS PHP téléchargement |

## Title

```text
Télécharger FlatCMS LTS — CMS PHP open source
```

## Meta description

```text
Téléchargez la version stable de FlatCMS et consultez les prérequis,
la licence, le checksum, le changelog et les notes de version.
```

## H1

```text
Télécharger FlatCMS
```

## CTA principal

```text
Télécharger FlatCMS v1.0.0 LTS
```

## Structure

```text
H2 — Version stable actuelle
H2 — Informations sur le fichier
H2 — Prérequis
H2 — Vérifier l’intégrité
H2 — Licence
H2 — Installer FlatCMS
H2 — Notes de version
H2 — Dépôt GitHub
H2 — Versions précédentes
```

## Informations obligatoires

- version ;
- date ;
- canal stable ;
- poids ;
- format ;
- checksum ;
- prérequis ;
- licence ;
- changelog ;
- lien d’installation.

## Preuves attendues

- artefact réel ;
- checksum généré ;
- `VERSION` ;
- notes de version ;
- source officielle.

## Données structurées

```text
WebPage
SoftwareApplication
Offer
BreadcrumbList
```

## Critères

- [ ] Le téléchargement pointe vers le bon fichier.
- [ ] Le checksum correspond.
- [ ] La version affichée correspond à l’archive.
- [ ] Le bouton n’est pas ambigu.
- [ ] Les archives précédentes sont séparées.
- [ ] Aucun fichier de développement ou secret n’est inclus.

---

# 10. Brief 08 — Licences

## Identité

| Champ | Valeur |
|---|---|
| ID | `P0-LICENSE-FR` |
| Priorité | P0 |
| Statut | À valider |
| URL | `/fr-FR/licences/` |
| Type | Page institutionnelle et juridique |
| Audience | Utilisateurs, agences, contributeurs |
| Intention | Comprendre les droits et obligations |
| Mot-clé principal | licence FlatCMS |
| Secondaires | FlatCMS open source, licence commerciale, CLA, marque FlatCMS |

## Title

```text
Licences FlatCMS — Open source, commercial et marque
```

## Meta description

```text
Comprenez les licences de FlatCMS : cœur open source, composants
commerciaux, contributions et règles d’utilisation de la marque.
```

## H1

```text
Licences et droits d’utilisation de FlatCMS
```

## CTA principal

```text
Consulter les textes officiels
```

## Structure

```text
H2 — Vue d’ensemble
H2 — Licence du Core
H2 — Composants commerciaux
H2 — Utilisation en agence
H2 — Contributions et CLA
H2 — Marque FlatCMS
H2 — Questions fréquentes
H2 — Textes officiels
```

## Sources obligatoires

- `LICENSE`
- `LICENSING.md`
- `COMMERCIAL_LICENSE.md`
- `CLA.md`
- `TRADEMARK.md`

## Règle

La page résume les textes, mais ne les remplace pas.

## Données structurées

```text
WebPage
BreadcrumbList
```

## Critères

- [ ] Chaque résumé correspond au document officiel.
- [ ] Les différences Core/Premium sont claires.
- [ ] Les termes juridiques sensibles sont relus.
- [ ] La page indique que les textes complets prévalent.
- [ ] Aucun prix n’est présenté ici s’il relève de la page Tarifs.

---

# 11. Brief 09 — Tarifs

## Identité

| Champ | Valeur |
|---|---|
| ID | `P0-PRICING-FR` |
| Priorité | P0 si vente active |
| Statut | À valider |
| URL | `/fr-FR/tarifs/` |
| Type | Page commerciale |
| Audience | Professionnels, agences, acheteurs |
| Intention | Comprendre les offres et choisir une licence |
| Mot-clé principal | tarifs FlatCMS |
| Secondaires | prix PagesBuilder, MenuBuilder, FooterBuilder, licence FlatCMS |

## Title

```text
Tarifs FlatCMS — Builders et licences commerciales
```

## Meta description

```text
Découvrez les tarifs des builders FlatCMS, les licences par site,
les bundles, les mises à jour et les conditions commerciales.
```

## H1

```text
Tarifs des composants premium FlatCMS
```

## CTA principal

```text
Choisir une licence
```

## Structure

```text
H2 — Les composants premium
H2 — PagesBuilder
H2 — MenuBuilder
H2 — FooterBuilder
H2 — Bundles
H2 — Licences par nombre de sites
H2 — Mises à jour et support
H2 — Ce qui reste gratuit
H2 — Questions commerciales
```

## Données commerciales attendues

- prix HT ;
- prix TTC si nécessaire ;
- nombre de sites ;
- durée ;
- mises à jour ;
- support ;
- conditions ;
- remboursement si applicable ;
- moyen de contact.

## Preuves

- grille tarifaire validée ;
- licence commerciale ;
- mécanisme de vente réel ;
- conditions générales.

## Données structurées

```text
WebPage
Product ou SoftwareApplication
Offer
BreadcrumbList
```

## Critères

- [ ] Tous les prix correspondent au système de vente.
- [ ] HT et TTC sont distingués.
- [ ] Les offres gratuites et premium ne sont pas confondues.
- [ ] Les bundles sont compréhensibles.
- [ ] Aucun faux avis ni fausse urgence n’est utilisé.
- [ ] La page est relue juridiquement et commercialement.

---

# 12. Brief 10 — Agent-ready

## Identité

| Champ | Valeur |
|---|---|
| ID | `P0-AGENT-FR` |
| Priorité | P0 |
| Statut | À valider |
| URL | `/fr-FR/agent-ready/` |
| Type | Page de différenciation |
| Audience | Développeurs, agences, décideurs IA |
| Intention | Comprendre le positionnement agent-ready |
| Mot-clé principal | CMS agent-ready |
| Secondaires | CMS AI-ready, CMS pour agents IA, FlatCMS OpenAI, contenu structuré IA |

## Title

```text
FlatCMS : un CMS conçu pour les agents IA
```

## Meta description

```text
Découvrez comment l’architecture modulaire, le stockage JSON, les
services, les données structurées et le multilingue préparent FlatCMS aux agents IA.
```

## H1

```text
FlatCMS, une architecture pensée pour les agents IA
```

## CTA principal

```text
Découvrir l’architecture IA
```

## CTA secondaire

```text
Consulter les fonctionnalités disponibles
```

## Structure

```text
H2 — Que signifie agent-ready ?
H2 — Une architecture modulaire
H2 — Des contenus structurés en JSON
H2 — Une couche de services
H2 — Des données structurées
H2 — Un socle multilingue
H2 — Les composants IA existants
H2 — Les fonctions optionnelles ou premium
H2 — Contrôle humain et sécurité
H2 — SEO, GEO et GIO
H2 — Limites et feuille de route
```

## Distinctions obligatoires

- socle technique existant ;
- module AiAgent ;
- services AI ;
- fonctionnalités disponibles ;
- fonctionnalités prévues ;
- fonctions premium ;
- dépendances externes.

## Preuves attendues

- `app/Services/AI` ;
- module `AiAgent` ;
- contrats et providers ;
- configuration réelle ;
- captures lorsque disponible.

## Données structurées

```text
WebPage
TechArticle
BreadcrumbList
SoftwareApplication
```

## Critères

- [ ] « Agent-ready » est défini.
- [ ] Les capacités réelles sont distinguées des projets.
- [ ] Aucune garantie de citation IA n’est formulée.
- [ ] La sécurité et le contrôle humain sont abordés.
- [ ] Le contenu évite le vocabulaire marketing vide.

---

# 13. Brief 11 — À propos

## Identité

| Champ | Valeur |
|---|---|
| ID | `P0-ABOUT-FR` |
| Priorité | P0 |
| Statut | À valider |
| URL | `/fr-FR/a-propos/` |
| Type | Page de confiance |
| Audience | Utilisateurs, partenaires, médias, moteurs |
| Intention | Identifier le projet, son auteur et sa mission |
| Mot-clé principal | à propos FlatCMS |
| Secondaires | FlatCMS France, créateur FlatCMS, projet FlatCMS |

## Title

```text
À propos de FlatCMS et de son projet open source
```

## Meta description

```text
Découvrez l’origine de FlatCMS, sa mission, son architecture, son auteur,
ses valeurs et la gouvernance du projet.
```

## H1

```text
À propos de FlatCMS
```

## CTA principal

```text
Découvrir la roadmap
```

## CTA secondaire

```text
Contribuer au projet
```

## Structure

```text
H2 — La mission de FlatCMS
H2 — Pourquoi le projet a été créé
H2 — Les principes techniques
H2 — L’auteur du projet
H2 — Gouvernance et contributions
H2 — Modèle open source et commercial
H2 — Marque et identité
H2 — Roadmap
H2 — Contacter FlatCMS
```

## Preuves attendues

- histoire datée ;
- identité de l’auteur ;
- liens publics ;
- licences ;
- dépôt GitHub ;
- roadmap.

## Données structurées

```text
AboutPage
Organization
Person ou ProfilePage
BreadcrumbList
```

## Critères

- [ ] La page désambiguïse FlatCMS de l’ancien produit homonyme.
- [ ] Les informations biographiques sont validées.
- [ ] Les profils `sameAs` sont officiels.
- [ ] La gouvernance est décrite honnêtement.
- [ ] Aucun détail personnel non nécessaire n’est publié.

---

# 14. Brief 12 — Contact

## Identité

| Champ | Valeur |
|---|---|
| ID | `P0-CONTACT-FR` |
| Priorité | P0 |
| Statut | À valider |
| URL | `/fr-FR/contact/` |
| Type | Page fonctionnelle |
| Audience | Utilisateurs, prospects, partenaires, médias |
| Intention | Contacter FlatCMS par le bon canal |
| Mot-clé principal | contacter FlatCMS |
| Secondaires | support FlatCMS, contact FlatCMS, partenariat FlatCMS |

## Title

```text
Contacter FlatCMS
```

## Meta description

```text
Contactez FlatCMS pour une question, un partenariat, une demande
commerciale, un problème de licence ou un signalement de sécurité.
```

## H1

```text
Contacter FlatCMS
```

## Structure

```text
H2 — Choisir le bon motif
H2 — Question générale
H2 — Support et documentation
H2 — Demande commerciale
H2 — Partenariat ou presse
H2 — Licence et marque
H2 — Signaler une vulnérabilité
H2 — Formulaire de contact
H2 — Délais et confidentialité
```

## Champs du formulaire

- nom ;
- adresse e-mail ;
- motif ;
- sujet ;
- message ;
- consentement ;
- pièce jointe uniquement si nécessaire et sécurisée.

## CTA

```text
Envoyer le message
```

## Données structurées

```text
ContactPage
Organization
BreadcrumbList
```

## Critères

- [ ] Le formulaire fonctionne.
- [ ] Les erreurs sont claires.
- [ ] L’adresse de destination est correcte.
- [ ] La protection anti-spam est active.
- [ ] Les messages de sécurité utilisent un canal adapté.
- [ ] La politique de confidentialité est liée.
- [ ] Les délais annoncés sont réalistes.

---

# 15. Brief 13 — Mentions légales

## Identité

| Champ | Valeur |
|---|---|
| ID | `P0-LEGAL-FR` |
| Priorité | P0 |
| Statut | À faire valider juridiquement |
| URL | `/fr-FR/mentions-legales/` |
| Type | Page juridique |
| Audience | Tous les visiteurs |
| Intention | Identifier l’éditeur et les responsabilités |
| Mot-clé principal | mentions légales FlatCMS |

## Title

```text
Mentions légales | FlatCMS
```

## Meta description

```text
Consultez les mentions légales du site officiel FlatCMS : éditeur,
hébergeur, propriété intellectuelle et responsabilités.
```

## H1

```text
Mentions légales
```

## Structure indicative

```text
H2 — Éditeur du site
H2 — Directeur de publication
H2 — Hébergeur
H2 — Contact
H2 — Propriété intellectuelle
H2 — Marque FlatCMS
H2 — Responsabilité
H2 — Liens externes
H2 — Droit applicable
```

## Sources

- identité légale de l’éditeur ;
- informations de l’hébergeur ;
- `TRADEMARK.md` ;
- licences.

## Données structurées

```text
WebPage
BreadcrumbList
```

## Indexation

Indexable, sauf décision juridique différente.

## Critères

- [ ] Informations légales exactes.
- [ ] Hébergeur actuel.
- [ ] Contact fonctionnel.
- [ ] Texte validé par une personne compétente.
- [ ] Pas de modèle générique publié sans adaptation.

---

# 16. Brief 14 — Politique de confidentialité

## Identité

| Champ | Valeur |
|---|---|
| ID | `P0-PRIVACY-FR` |
| Priorité | P0 |
| Statut | À faire valider juridiquement |
| URL | `/fr-FR/confidentialite/` |
| Type | Page juridique |
| Audience | Tous les visiteurs |
| Intention | Comprendre le traitement des données |
| Mot-clé principal | politique de confidentialité FlatCMS |

## Title

```text
Politique de confidentialité | FlatCMS
```

## Meta description

```text
Découvrez quelles données sont collectées par FlatCMS, pourquoi elles
sont utilisées, combien de temps elles sont conservées et quels sont vos droits.
```

## H1

```text
Politique de confidentialité
```

## Structure

```text
H2 — Responsable du traitement
H2 — Données collectées
H2 — Finalités
H2 — Bases légales
H2 — Formulaire de contact
H2 — Analytics
H2 — Cookies
H2 — Hébergement et sous-traitants
H2 — Durées de conservation
H2 — Sécurité
H2 — Transferts éventuels
H2 — Vos droits
H2 — Contact
H2 — Mise à jour de la politique
```

## Preuves attendues

- outils analytics réellement utilisés ;
- formulaires ;
- hébergement ;
- fournisseurs e-mail ;
- cookies ;
- sous-traitants ;
- durées validées.

## Données structurées

```text
WebPage
BreadcrumbList
```

## Critères

- [ ] La page correspond aux outils réellement actifs.
- [ ] Les cookies sont décrits.
- [ ] Les droits et le contact sont exacts.
- [ ] La date de mise à jour est visible.
- [ ] Le texte est validé juridiquement.
- [ ] Les autres locales disposent d’une adaptation appropriée.

---

# 17. Brief 15 — Page 404

## Identité

| Champ | Valeur |
|---|---|
| ID | `P0-404-FR` |
| Priorité | P0 |
| Statut | À valider |
| URL | Toute URL inexistante |
| Type | Page système |
| Audience | Visiteurs arrivant sur une URL invalide |
| Intention | Retrouver rapidement une ressource utile |
| Mot-clé principal | Aucun ciblage SEO |

## Title

```text
Page introuvable | FlatCMS
```

## H1

```text
Cette page est introuvable
```

## Message principal

```text
L’adresse est peut-être incorrecte, ou la page a été déplacée.
```

## Actions

```text
Revenir à l’accueil
Consulter la documentation
Rechercher sur le site
Télécharger FlatCMS
Signaler un lien cassé
```

## Structure

```text
H2 — Rechercher une page
H2 — Accès rapides
H2 — Signaler le problème
```

## Règle HTTP

La réponse doit être :

```text
404 Not Found
```

et non :

```text
200 OK
```

## Indexation

La page 404 n’est pas destinée à être indexée.

## Données structurées

Aucune donnée structurée complexe nécessaire.

Un `WebPage` générique n’apporte pas de bénéfice si la page retourne correctement `404`.

## Critères

- [ ] Le code HTTP est 404.
- [ ] La navigation principale reste disponible.
- [ ] La recherche fonctionne.
- [ ] Aucun message technique interne n’est affiché.
- [ ] La page fonctionne dans chaque locale.
- [ ] Les liens proposés répondent 200.

---

# 18. Tableau de synthèse

| ID | Page | URL | Priorité | Statut |
|---|---|---|---|---|
| P0-HOME-FR | Accueil | `/fr-FR/` | P0 | À valider |
| P0-WHY-FR | Pourquoi FlatCMS | `/fr-FR/pourquoi-flatcms/` | P0 | À valider |
| P0-FEATURES-FR | Fonctionnalités | `/fr-FR/fonctionnalites/` | P0 | À valider |
| P0-ARCH-FR | Architecture | `/fr-FR/architecture/` | P0 | À valider |
| P0-DOCS-FR | Documentation | `/fr-FR/documentation/` | P0 | À valider |
| P0-INSTALL-FR | Installation | `/fr-FR/documentation/installation/` | P0 | À valider |
| P0-DOWNLOAD-FR | Téléchargement | `/fr-FR/telechargement/` | P0 | À valider |
| P0-LICENSE-FR | Licences | `/fr-FR/licences/` | P0 | À valider |
| P0-PRICING-FR | Tarifs | `/fr-FR/tarifs/` | P0 conditionnel | À valider |
| P0-AGENT-FR | Agent-ready | `/fr-FR/agent-ready/` | P0 | À valider |
| P0-ABOUT-FR | À propos | `/fr-FR/a-propos/` | P0 | À valider |
| P0-CONTACT-FR | Contact | `/fr-FR/contact/` | P0 | À valider |
| P0-LEGAL-FR | Mentions légales | `/fr-FR/mentions-legales/` | P0 | Validation juridique |
| P0-PRIVACY-FR | Confidentialité | `/fr-FR/confidentialite/` | P0 | Validation juridique |
| P0-404-FR | Page 404 | système | P0 | À valider |

---

# 19. Dépendances entre les pages

```text
Accueil
├── Pourquoi FlatCMS
├── Fonctionnalités
├── Architecture
├── Documentation
├── Téléchargement
├── Tarifs
└── Agent-ready

Documentation
└── Installation
    ├── Téléchargement
    └── Dépannage

Licences
├── Tarifs
├── Téléchargement
└── À propos

À propos
├── Roadmap
├── Contact
└── Licences
```

---

# 20. Ordre de rédaction recommandé

1. Accueil
2. Pourquoi FlatCMS
3. Fonctionnalités
4. Architecture
5. Documentation
6. Installation
7. Téléchargement
8. Agent-ready
9. Licences
10. Tarifs
11. À propos
12. Contact
13. Mentions légales
14. Confidentialité
15. Page 404

Les pages juridiques peuvent être préparées en parallèle, mais nécessitent une validation compétente avant publication.

---

# 21. Ordre de conception UX recommandé

1. Header et navigation
2. Footer
3. Accueil
4. Page produit générique
5. Page fonctionnalité
6. Page architecture
7. Hub documentation
8. Guide documentation
9. Page téléchargement
10. Page tarifs
11. Page juridique
12. Page 404

---

# 22. Matrice des CTA

| Page | CTA principal | CTA secondaire |
|---|---|---|
| Accueil | Télécharger FlatCMS | Tester la démo |
| Pourquoi FlatCMS | Vérifier les prérequis | Comparer |
| Fonctionnalités | Tester la démo | Documentation |
| Architecture | Documentation développeur | Voir le code |
| Documentation | Commencer l’installation | Rechercher |
| Installation | Télécharger FlatCMS | Dépannage |
| Téléchargement | Télécharger la version LTS | Installation |
| Licences | Lire les textes officiels | Voir les tarifs |
| Tarifs | Choisir une licence | Poser une question |
| Agent-ready | Architecture IA | Fonctionnalités |
| À propos | Roadmap | Contribuer |
| Contact | Envoyer le message | Documentation |
| 404 | Revenir à l’accueil | Rechercher |

---

# 23. Matrice des preuves

| Page | Preuves principales |
|---|---|
| Accueil | README, VERSION, modules, captures |
| Pourquoi FlatCMS | architecture, cas d’usage, limites |
| Fonctionnalités | modules, statuts, documentation |
| Architecture | code, arborescence, routes, services |
| Documentation | pages publiées, version |
| Installation | tests réels, configurations |
| Téléchargement | archive, checksum, changelog |
| Licences | documents officiels |
| Tarifs | grille commerciale, contrats |
| Agent-ready | services AI, module AiAgent |
| À propos | histoire, auteur, GitHub |
| Contact | formulaire, e-mail, politiques |
| Mentions légales | identité éditeur, hébergeur |
| Confidentialité | outils et traitements réels |
| 404 | statut HTTP et navigation |

---

# 24. Médias à produire

## Marque

- logo principal ;
- logo clair et sombre ;
- favicon ;
- image Open Graph générique.

## Produit

- administration ;
- gestion des pages ;
- articles ;
- médiathèque ;
- menus ;
- thèmes ;
- modules ;
- multilingue.

## Architecture

- schéma du cycle de requête ;
- schéma HMVC ;
- arborescence ;
- stockage JSON ;
- graphe des services.

## Installation

- document root ;
- écran de l’installateur ;
- configuration Apache/Nginx ;
- résultat final.

## Agent-ready

- schéma des services AI ;
- flux fournisseur ;
- contrôle humain ;
- contenus structurés.

---

# 25. Règles de validation globale

Avant rédaction complète :

- [ ] URLs validées ;
- [ ] titles validés ;
- [ ] H1 validés ;
- [ ] intentions non concurrentes ;
- [ ] CTA validés ;
- [ ] statuts produit validés ;
- [ ] preuves disponibles ;
- [ ] composants UX identifiés ;
- [ ] médias listés ;
- [ ] données structurées cohérentes ;
- [ ] pages juridiques confiées à validation ;
- [ ] pages multilingues planifiées.

---

# 26. Références de méthode

- Google Search Central — Helpful, reliable, people-first content  
  https://developers.google.com/search/docs/fundamentals/creating-helpful-content

- Google Search Central — Title links  
  https://developers.google.com/search/docs/appearance/title-link

- Google Search Central — Crawlable links  
  https://developers.google.com/search/docs/crawling-indexing/links-crawlable

- Google Search Central — AI features and your website  
  https://developers.google.com/search/docs/appearance/ai-features

- Google Search Central — Structured data  
  https://developers.google.com/search/docs/appearance/structured-data/intro-structured-data

- Google Search Central — Localized versions  
  https://developers.google.com/search/docs/specialty/international/localized-versions

---

# 27. Journal des mises à jour

| Date | Version | Modification | Auteur |
|---|---:|---|---|
| 2026-06-08 | 1.0 | Création des briefs initiaux des pages P0 | ChatGPT / Alain BROYE |

---

# 28. Prochaine action

Après validation de ce fichier :

```text
Créer HOMEPAGE_CONTENT.md
```

Ce document contiendra le contenu rédactionnel complet de la page d’accueil `fr-FR`, prêt à intégrer dans FlatCMS, avec :

- title ;
- meta description ;
- H1 ;
- textes de chaque section ;
- CTA ;
- FAQ éditoriale ;
- liens internes ;
- suggestions de médias ;
- données structurées attendues.

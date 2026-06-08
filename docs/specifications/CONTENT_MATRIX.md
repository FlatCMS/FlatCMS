# CONTENT_MATRIX — Matrice éditoriale du futur site officiel FlatCMS

> **Document directeur de production**
>
> Projet : FlatCMS  
> Domaine cible : `https://flat-cms.fr`  
> Version de référence : FlatCMS v1.0.0 LTS Core  
> Date de création : 8 juin 2026  
> Documents parents : `SEO.md`, `SITE_ARCHITECTURE.md`  
> Statut : matrice initiale du socle français, à enrichir et traduire

---

## 1. Objet du document

Ce document transforme l’architecture du futur site officiel en plan de production concret.

Pour chaque page stratégique, il définit :

- son rôle ;
- son audience ;
- son intention de recherche ;
- son mot-clé principal ;
- ses expressions secondaires ;
- son URL ;
- sa balise `<title>` ;
- sa meta description ;
- son H1 ;
- ses sections H2/H3 ;
- son appel à l’action ;
- ses liens internes ;
- ses données structurées ;
- la preuve technique attendue ;
- son statut de rédaction ;
- son statut de traduction.

Cette matrice est la référence avant toute création de page dans FlatCMS.

---

## 2. Principes éditoriaux

## 2.1 Une page, une intention principale

Chaque URL doit répondre à un besoin principal clairement identifiable.

Exemples :

```text
Comprendre FlatCMS
Installer FlatCMS
Comparer FlatCMS à WordPress
Créer un module
Configurer Nginx
Comprendre le stockage JSON
```

Une page ne doit pas tenter de se positionner simultanément sur plusieurs intentions sans rapport direct.

---

## 2.2 Un contenu utile avant l’optimisation

Chaque page doit :

- répondre clairement à la question de l’utilisateur ;
- fournir une information originale ou une synthèse réellement utile ;
- éviter le remplissage ;
- expliquer les limites ;
- distinguer les faits, les choix du projet et les perspectives ;
- présenter les sources lorsque nécessaire ;
- rester compréhensible sans connaissance préalable de FlatCMS.

---

## 2.3 Une affirmation, une preuve

Toute affirmation importante doit être soutenue par au moins un élément vérifiable.

| Type d’affirmation | Preuve attendue |
|---|---|
| Fonctionnalité native | Module, service, configuration ou vue réelle |
| Compatibilité | Test documenté ou prérequis officiel |
| Performance | Benchmark reproductible |
| Sécurité | Mécanisme réel et limites expliquées |
| Licence | Document juridique officiel |
| Multilingue | Locales et mécanismes réellement disponibles |
| Données structurées | Service ou rendu JSON-LD validé |
| Agent-ready | Architecture, contrats et capacités réellement disponibles |

---

## 2.4 Contenu accessible et citable

Chaque page stratégique doit privilégier :

- une définition courte dès le début ;
- des titres explicites ;
- des paragraphes autonomes ;
- des tableaux ;
- des procédures numérotées ;
- des exemples ;
- des résultats attendus ;
- des limites ;
- une date de mise à jour ;
- un auteur ou responsable éditorial ;
- des liens vers les sources primaires.

---

## 3. Statuts utilisés dans la matrice

### Statut de production

```text
À cadrer
À rédiger
En rédaction
À relire
Validé
Publié
À mettre à jour
```

### Statut produit

```text
Core
Optionnel
Premium
Expérimental
Prévu
Institutionnel
Éditorial
```

### Priorité

```text
P0 — indispensable avant lancement
P1 — lancement
P2 — croissance
P3 — enrichissement
```

---

## 4. Conventions SEO

### Longueur indicative des titres

La priorité est la clarté. Les titres doivent rester suffisamment courts pour être lisibles, sans appliquer de limite rigide artificielle.

### Meta descriptions

Elles doivent :

- résumer la page ;
- contenir la proposition de valeur ;
- éviter les répétitions ;
- rester naturelles ;
- ne pas promettre un résultat non démontré.

### H1

- un seul H1 principal ;
- différent du title lorsque cela améliore la lisibilité ;
- formulé dans la langue de la page ;
- cohérent avec l’intention de recherche.

### Liens internes

Chaque page stratégique doit recevoir au moins :

- un lien depuis sa page parent ;
- deux liens contextuels depuis des pages connexes ;
- un lien depuis un contenu éditorial lorsque pertinent.

---

# 5. Matrice du socle P0

## 5.1 Accueil

| Champ | Valeur |
|---|---|
| Priorité | P0 |
| Statut produit | Institutionnel |
| URL | `/fr-FR/` |
| Audience | Décideurs, développeurs, agences, créateurs de sites |
| Intention | Comprendre ce qu’est FlatCMS et décider de l’explorer |
| Mot-clé principal | CMS PHP sans base de données |
| Secondaires | FlatCMS, CMS flat-file PHP, CMS PHP JSON, CMS open source français, CMS léger |
| Title | `FlatCMS — CMS PHP open source sans base de données` |
| Meta description | `Découvrez FlatCMS, un CMS flat-file en PHP natif avec architecture HMVC, autoloading PSR-4, stockage JSON, modules et gestion multilingue.` |
| H1 | `FlatCMS, le CMS PHP open source sans base de données` |
| CTA principal | Télécharger FlatCMS |
| CTA secondaire | Tester la démo |
| Données structurées | `Organization`, `WebSite`, `SoftwareApplication`, `BreadcrumbList` |
| Preuve attendue | README, VERSION, architecture réelle, captures, liens GitHub |
| Statut rédaction | À rédiger |
| Traductions | Après validation fr-FR |

### Structure

```text
H2 — Un CMS simple à déployer
H2 — Pourquoi supprimer la dépendance à SQL ?
H2 — Les fonctionnalités essentielles
H2 — Une architecture PHP native et modulaire
H2 — Pages, articles, médias, menus et thèmes
H2 — Multilingue dès la conception
H2 — Sécurité et document root public
H2 — Données structurées et contenus exploitables
H2 — FlatCMS et les agents IA
H2 — Tester ou télécharger FlatCMS
H2 — Questions fréquentes
```

### Liens internes

- Fonctionnalités
- Architecture
- Stockage JSON
- Sécurité
- Agent-ready
- Documentation
- Téléchargement
- Démo

---

## 5.2 Pourquoi FlatCMS

| Champ | Valeur |
|---|---|
| Priorité | P0 |
| Statut produit | Institutionnel |
| URL | `/fr-FR/pourquoi-flatcms/` |
| Audience | Porteurs de projet, agences, développeurs |
| Intention | Évaluer si FlatCMS correspond à un projet |
| Mot-clé principal | pourquoi choisir FlatCMS |
| Secondaires | CMS léger, CMS simple, CMS sans MySQL, CMS pour site vitrine |
| Title | `Pourquoi choisir FlatCMS pour créer un site web ?` |
| Meta description | `Découvrez les cas d’usage, avantages, limites et profils de projets adaptés à FlatCMS, CMS PHP flat-file sans serveur SQL.` |
| H1 | `Pourquoi choisir FlatCMS ?` |
| CTA | Vérifier les prérequis |
| Données structurées | `WebPage`, `BreadcrumbList`, `FAQPage` si éligible |
| Preuve attendue | Cas d’usage concrets, limites, comparaison technique |
| Statut rédaction | À rédiger |

### Structure

```text
H2 — À quels projets FlatCMS est-il destiné ?
H2 — Les bénéfices d’un CMS flat-file
H2 — Déploiement et maintenance
H2 — Architecture et extensibilité
H2 — Multilingue et contenus structurés
H2 — Dans quels cas choisir une autre solution ?
H2 — Tableau de décision
H2 — Commencer avec FlatCMS
```

---

## 5.3 Fonctionnalités

| Champ | Valeur |
|---|---|
| Priorité | P0 |
| Statut produit | Institutionnel |
| URL | `/fr-FR/fonctionnalites/` |
| Audience | Utilisateurs et évaluateurs |
| Intention | Connaître les capacités du CMS |
| Mot-clé principal | fonctionnalités FlatCMS |
| Secondaires | CMS pages articles médias, CMS modulaire, CMS multilingue |
| Title | `Fonctionnalités de FlatCMS : pages, articles, médias et modules` |
| Meta description | `Explorez les fonctionnalités de FlatCMS : pages, articles, catégories, médias, menus, utilisateurs, thèmes, langues, sauvegardes et modules.` |
| H1 | `Les fonctionnalités de FlatCMS` |
| CTA | Tester les fonctionnalités dans la démo |
| Données structurées | `CollectionPage`, `BreadcrumbList` |
| Preuve attendue | Modules réels et statuts Core/Premium |
| Statut rédaction | À rédiger |

### Structure

```text
H2 — Gestion des contenus
H2 — Médias
H2 — Navigation
H2 — Utilisateurs et rôles
H2 — Thèmes
H2 — Langues
H2 — Sauvegardes et corbeille
H2 — Modules et extensions
H2 — Builders
H2 — Intelligence artificielle
H2 — Tableau Core / Optionnel / Premium
```

---

## 5.4 Architecture générale

| Champ | Valeur |
|---|---|
| Priorité | P0 |
| Statut produit | Core |
| URL | `/fr-FR/architecture/` |
| Audience | Développeurs, intégrateurs, profils techniques |
| Intention | Comprendre l’architecture de FlatCMS |
| Mot-clé principal | architecture FlatCMS |
| Secondaires | architecture HMVC PHP, PSR-4 CMS, CMS JSON |
| Title | `Architecture FlatCMS — PHP natif, HMVC, PSR-4 et JSON` |
| Meta description | `Comprenez l’architecture de FlatCMS : cœur PHP natif, modules HMVC, autoloading PSR-4, services, hooks, vues et stockage JSON.` |
| H1 | `Architecture de FlatCMS v1.0.0` |
| CTA | Explorer la documentation développeur |
| Données structurées | `TechArticle`, `BreadcrumbList` |
| Preuve attendue | Arborescence réelle et fichiers du Core |
| Statut rédaction | À rédiger |

### Structure

```text
H2 — Vue d’ensemble
H2 — Cycle d’une requête
H2 — Le cœur applicatif
H2 — Les modules HMVC
H2 — Les services
H2 — Les hooks et extensions
H2 — Le stockage flat-file
H2 — Les thèmes
H2 — La sécurité du document root
H2 — Schéma complet
```

---

## 5.5 Documentation

| Champ | Valeur |
|---|---|
| Priorité | P0 |
| Statut produit | Institutionnel |
| URL | `/fr-FR/documentation/` |
| Audience | Utilisateurs, administrateurs, développeurs |
| Intention | Trouver une procédure ou une référence |
| Mot-clé principal | documentation FlatCMS |
| Secondaires | guide FlatCMS, tutoriel FlatCMS, installer FlatCMS |
| Title | `Documentation FlatCMS — Installation, utilisation et développement` |
| Meta description | `Accédez à la documentation officielle de FlatCMS : installation, configuration, contenus, thèmes, modules, déploiement, sécurité et dépannage.` |
| H1 | `Documentation officielle de FlatCMS` |
| CTA | Commencer l’installation |
| Données structurées | `CollectionPage`, `BreadcrumbList` |
| Preuve attendue | Navigation complète, version documentée |
| Statut rédaction | À rédiger |

### Structure

```text
H2 — Démarrage
H2 — Utilisation
H2 — Personnalisation
H2 — Développement
H2 — Déploiement
H2 — Maintenance
H2 — Sécurité
H2 — Dépannage
H2 — Documentation par version
```

---

## 5.6 Installation

| Champ | Valeur |
|---|---|
| Priorité | P0 |
| Statut produit | Core |
| URL | `/fr-FR/documentation/installation/` |
| Audience | Nouveaux utilisateurs |
| Intention | Installer FlatCMS |
| Mot-clé principal | installer FlatCMS |
| Secondaires | installation CMS PHP, installer FlatCMS MAMP, WAMP, Apache, Nginx |
| Title | `Installer FlatCMS sur Apache, Nginx, MAMP ou WAMP` |
| Meta description | `Suivez la procédure officielle pour installer FlatCMS, vérifier PHP, configurer le document root public et lancer l’installateur.` |
| H1 | `Installer FlatCMS` |
| CTA | Télécharger la version LTS |
| Données structurées | `HowTo`, `BreadcrumbList` |
| Preuve attendue | Contrat installateur, tests serveurs |
| Statut rédaction | À rédiger |

### Structure

```text
H2 — Prérequis
H2 — Télécharger et extraire FlatCMS
H2 — Configurer le document root
H2 — Vérifier les permissions
H2 — Lancer l’installateur
H2 — Finaliser l’installation
H2 — Vérifier le frontend et l’administration
H2 — Résoudre les erreurs fréquentes
H2 — Étapes suivantes
```

---

## 5.7 Téléchargement

| Champ | Valeur |
|---|---|
| Priorité | P0 |
| Statut produit | Institutionnel |
| URL | `/fr-FR/telechargement/` |
| Audience | Utilisateurs prêts à installer |
| Intention | Télécharger FlatCMS |
| Mot-clé principal | télécharger FlatCMS |
| Secondaires | FlatCMS LTS, CMS PHP téléchargement, version FlatCMS |
| Title | `Télécharger FlatCMS LTS — CMS PHP open source` |
| Meta description | `Téléchargez la version stable de FlatCMS, consultez les prérequis, la licence, les sommes de contrôle et les notes de version.` |
| H1 | `Télécharger FlatCMS` |
| CTA | Télécharger FlatCMS LTS |
| Données structurées | `SoftwareApplication`, `BreadcrumbList` |
| Preuve attendue | Version, date, checksum, licence, changelog |
| Statut rédaction | À rédiger |

### Structure

```text
H2 — Version stable actuelle
H2 — Prérequis
H2 — Contenu de la distribution
H2 — Vérifier le téléchargement
H2 — Licence
H2 — Installation
H2 — Notes de version
H2 — Versions précédentes
```

---

## 5.8 Licences

| Champ | Valeur |
|---|---|
| Priorité | P0 |
| Statut produit | Institutionnel |
| URL | `/fr-FR/licences/` |
| Audience | Utilisateurs, agences, contributeurs |
| Intention | Comprendre les licences FlatCMS |
| Mot-clé principal | licence FlatCMS |
| Secondaires | FlatCMS AGPL, licence commerciale FlatCMS, marque FlatCMS |
| Title | `Licences FlatCMS — Open source, commercial et marque` |
| Meta description | `Comprenez le modèle de licence de FlatCMS : cœur open source, composants commerciaux, contributions et règles d’utilisation de la marque.` |
| H1 | `Licences et droits d’utilisation de FlatCMS` |
| CTA | Consulter les textes officiels |
| Données structurées | `WebPage`, `BreadcrumbList` |
| Preuve attendue | LICENSE, LICENSING, COMMERCIAL_LICENSE, CLA, TRADEMARK |
| Statut rédaction | À rédiger |

---

## 5.9 Tarifs

| Champ | Valeur |
|---|---|
| Priorité | P0 |
| Statut produit | Premium |
| URL | `/fr-FR/tarifs/` |
| Audience | Professionnels, agences, acheteurs |
| Intention | Connaître le prix des composants premium |
| Mot-clé principal | tarifs FlatCMS |
| Secondaires | prix PagesBuilder, licence FlatCMS, builders FlatCMS |
| Title | `Tarifs FlatCMS — Builders et licences commerciales` |
| Meta description | `Découvrez les tarifs des composants premium FlatCMS, les licences par site et les conditions commerciales applicables.` |
| H1 | `Tarifs des composants premium FlatCMS` |
| CTA | Choisir une licence |
| Données structurées | `Product`, `Offer`, `BreadcrumbList` si données réelles |
| Preuve attendue | Prix HT/TTC, périmètre, durée, support |
| Statut rédaction | À cadrer |

---

## 5.10 Agent-ready

| Champ | Valeur |
|---|---|
| Priorité | P0 |
| Statut produit | Core + Optionnel/Premium selon fonction |
| URL | `/fr-FR/agent-ready/` |
| Audience | Développeurs, agences, décideurs IA |
| Intention | Comprendre comment FlatCMS s’intègre aux usages IA |
| Mot-clé principal | CMS agent-ready |
| Secondaires | CMS AI-ready, CMS pour agents IA, FlatCMS OpenAI, contenu structuré IA |
| Title | `FlatCMS : un CMS conçu pour les agents IA` |
| Meta description | `Découvrez comment l’architecture modulaire, le stockage JSON, les services, les données structurées et le multilingue préparent FlatCMS aux agents IA.` |
| H1 | `FlatCMS, une architecture pensée pour les agents IA` |
| CTA | Découvrir l’architecture IA |
| Données structurées | `TechArticle`, `BreadcrumbList` |
| Preuve attendue | Services AI, module AiAgent, contrats, limites |
| Statut rédaction | À rédiger |

### Structure

```text
H2 — Que signifie agent-ready ?
H2 — Les fondations déjà présentes
H2 — Contenus structurés et stockage JSON
H2 — Architecture modulaire et services
H2 — Données structurées
H2 — Multilingue
H2 — Fonctionnalités IA disponibles
H2 — Fonctionnalités premium ou prévues
H2 — Sécurité et contrôle humain
H2 — Ce que FlatCMS ne garantit pas
```

---

# 6. Matrice architecture P1

## 6.1 HMVC

| Champ | Valeur |
|---|---|
| Priorité | P1 |
| URL | `/fr-FR/architecture/hmvc/` |
| Intention | Comprendre HMVC dans FlatCMS |
| Mot-clé principal | architecture HMVC PHP |
| Title | `Architecture HMVC de FlatCMS : modules autonomes en PHP` |
| H1 | `Comment FlatCMS applique l’architecture HMVC` |
| Données structurées | `TechArticle`, `BreadcrumbList` |
| Preuve | Arborescence réelle des modules |
| Statut | À rédiger |

### H2

```text
Définition de HMVC
Pourquoi FlatCMS utilise HMVC
Structure d’un module
Contrôleurs, vues et services
Chargement des modules
Isolation et dépendances
Exemple réel
Bonnes pratiques
```

---

## 6.2 PSR-4

| Champ | Valeur |
|---|---|
| Priorité | P1 |
| URL | `/fr-FR/architecture/psr-4/` |
| Intention | Comprendre l’autoloading de FlatCMS |
| Mot-clé principal | autoloading PSR-4 CMS |
| Title | `PSR-4 dans FlatCMS : namespaces et chargement automatique` |
| H1 | `L’autoloading PSR-4 dans FlatCMS` |
| Données structurées | `TechArticle`, `BreadcrumbList` |
| Preuve | Mappings et namespaces réels |
| Statut | À rédiger |

---

## 6.3 Stockage JSON

| Champ | Valeur |
|---|---|
| Priorité | P1 |
| URL | `/fr-FR/architecture/stockage-json/` |
| Intention | Comprendre le stockage sans SQL |
| Mot-clé principal | CMS stockage JSON |
| Secondaires | CMS sans base de données, flat-file JSON, CMS sans MySQL |
| Title | `Stockage JSON de FlatCMS : un CMS sans base de données SQL` |
| Meta description | `Découvrez comment FlatCMS stocke pages, articles, catégories, médias et configurations dans des fichiers structurés sans serveur SQL.` |
| H1 | `Comment fonctionne le stockage JSON de FlatCMS ?` |
| Données structurées | `TechArticle`, `BreadcrumbList` |
| Preuve | Dossiers `data/core`, fichiers JSON et classe FlatFile |
| Statut | À rédiger |

### H2

```text
Qu’est-ce qu’un stockage flat-file ?
Organisation du dossier data
Pages, articles et catégories
Références médias
Configurations et modules
Lecture et écriture
Intégrité et validation
Sauvegarde
Sécurité
Limites et cas d’usage
```

---

## 6.4 Cycle d’une requête

| Champ | Valeur |
|---|---|
| Priorité | P1 |
| URL | `/fr-FR/architecture/cycle-requete/` |
| Intention | Comprendre le runtime FlatCMS |
| Mot-clé principal | cycle requête PHP CMS |
| Title | `Cycle d’une requête dans FlatCMS : du routeur à la réponse` |
| H1 | `Le cycle d’une requête dans FlatCMS` |
| Données structurées | `TechArticle`, `BreadcrumbList` |
| Preuve | App, Router, Request, Response, Controller, View |
| Statut | À rédiger |

---

## 6.5 Services

| Champ | Valeur |
|---|---|
| Priorité | P1 |
| URL | `/fr-FR/architecture/services/` |
| Intention | Comprendre la couche de services |
| Mot-clé principal | services architecture PHP |
| Title | `Les services de FlatCMS : logique métier et intégrations` |
| H1 | `La couche de services de FlatCMS` |
| Preuve | `app/Services` et services réels |
| Statut | À rédiger |

---

## 6.6 Hooks

| Champ | Valeur |
|---|---|
| Priorité | P1 |
| URL | `/fr-FR/architecture/hooks/` |
| Intention | Comprendre les extensions sans modifier le Core |
| Mot-clé principal | hooks CMS PHP |
| Title | `Hooks FlatCMS : étendre le CMS sans modifier le cœur` |
| H1 | `Le système de hooks de FlatCMS` |
| Preuve | Hook.php, HookManager, hooks.php |
| Statut | À rédiger |

---

## 6.7 Données structurées

| Champ | Valeur |
|---|---|
| Priorité | P1 |
| URL | `/fr-FR/architecture/donnees-structurees/` |
| Intention | Comprendre le JSON-LD et Schema.org dans FlatCMS |
| Mot-clé principal | données structurées CMS |
| Secondaires | JSON-LD CMS, Schema.org FlatCMS, SEO CMS |
| Title | `Données structurées dans FlatCMS : JSON-LD et Schema.org` |
| H1 | `Les données structurées de FlatCMS` |
| Preuve | `app/Services/StructuredData` et rendu réel |
| Statut | À rédiger |

---

# 7. Matrice fonctionnalités P1

## 7.1 Pages

| Champ | Valeur |
|---|---|
| URL | `/fr-FR/fonctionnalites/pages/` |
| Mot-clé principal | gestion pages CMS |
| Title | `Gestion des pages avec FlatCMS` |
| H1 | `Créer et organiser des pages dans FlatCMS` |
| Statut produit | Core |
| Preuve | Module Pages, stockage pages |
| Statut | À rédiger |

## 7.2 Articles

| Champ | Valeur |
|---|---|
| URL | `/fr-FR/fonctionnalites/articles/` |
| Mot-clé principal | blog CMS PHP |
| Title | `Articles et blog avec FlatCMS` |
| H1 | `Publier des articles avec FlatCMS` |
| Statut produit | Core |
| Preuve | Module Posts, catégories |
| Statut | À rédiger |

## 7.3 Médias

| Champ | Valeur |
|---|---|
| URL | `/fr-FR/fonctionnalites/medias/` |
| Mot-clé principal | médiathèque CMS |
| Title | `Médiathèque FlatCMS : gérer images et fichiers` |
| H1 | `Gérer les médias dans FlatCMS` |
| Statut produit | Core |
| Preuve | Module Media, data/core/media |
| Statut | À rédiger |

## 7.4 Menus

| Champ | Valeur |
|---|---|
| URL | `/fr-FR/fonctionnalites/menus/` |
| Mot-clé principal | gestion menus CMS |
| Title | `Créer et gérer les menus avec FlatCMS` |
| H1 | `La gestion des menus dans FlatCMS` |
| Statut produit | Core |
| Preuve | Module Menu |
| Statut | À rédiger |

## 7.5 Utilisateurs

| Champ | Valeur |
|---|---|
| URL | `/fr-FR/fonctionnalites/utilisateurs/` |
| Mot-clé principal | utilisateurs rôles CMS |
| Title | `Utilisateurs, rôles et permissions dans FlatCMS` |
| H1 | `Gérer les utilisateurs et les rôles` |
| Statut produit | Core |
| Preuve | Auth, Users, stockage users |
| Statut | À rédiger |

## 7.6 Multilingue

| Champ | Valeur |
|---|---|
| URL | `/fr-FR/fonctionnalites/multilingue/` |
| Mot-clé principal | CMS multilingue PHP |
| Title | `FlatCMS multilingue : gérer six locales nativement` |
| H1 | `Créer un site multilingue avec FlatCMS` |
| Statut produit | Core |
| Preuve | Languages, I18n, TranslationScanner |
| Statut | À rédiger |

## 7.7 Thèmes

| Champ | Valeur |
|---|---|
| URL | `/fr-FR/fonctionnalites/themes/` |
| Mot-clé principal | thèmes FlatCMS |
| Title | `Thèmes FlatCMS : administration et frontend séparés` |
| H1 | `Personnaliser FlatCMS avec des thèmes` |
| Statut produit | Core |
| Preuve | themes/admin, themes/frontend |
| Statut | À rédiger |

## 7.8 Sauvegardes

| Champ | Valeur |
|---|---|
| URL | `/fr-FR/fonctionnalites/sauvegardes/` |
| Mot-clé principal | sauvegarde CMS flat-file |
| Title | `Sauvegarder et restaurer un site FlatCMS` |
| H1 | `Les sauvegardes dans FlatCMS` |
| Statut produit | Core/Optionnel selon version |
| Preuve | Module Backups |
| Statut | À rédiger |

## 7.9 Corbeille

| Champ | Valeur |
|---|---|
| URL | `/fr-FR/fonctionnalites/corbeille/` |
| Mot-clé principal | corbeille CMS |
| Title | `Corbeille FlatCMS : restaurer les contenus supprimés` |
| H1 | `La corbeille de FlatCMS` |
| Statut produit | Core |
| Preuve | Module Trash |
| Statut | À rédiger |

## 7.10 Builders

| Champ | Valeur |
|---|---|
| URL | `/fr-FR/fonctionnalites/builders/` |
| Mot-clé principal | page builder FlatCMS |
| Title | `Builders FlatCMS : pages, menus et footer visuels` |
| H1 | `Les builders visuels de FlatCMS` |
| Statut produit | Premium |
| Preuve | Distribution commerciale correspondante |
| Statut | À cadrer |

---

# 8. Matrice documentation P1

## 8.1 Configurer Nginx

| Champ | Valeur |
|---|---|
| URL | `/fr-FR/documentation/deploiement/nginx/` |
| Intention | Déployer FlatCMS sur Nginx |
| Mot-clé principal | FlatCMS Nginx |
| Title | `Configurer Nginx pour FlatCMS` |
| H1 | `Déployer FlatCMS avec Nginx` |
| Données structurées | `HowTo`, `BreadcrumbList` |
| Preuve | nginx.conf officiel |
| Statut | À rédiger |

## 8.2 Configurer Apache

| Champ | Valeur |
|---|---|
| URL | `/fr-FR/documentation/deploiement/apache/` |
| Intention | Déployer FlatCMS sur Apache |
| Mot-clé principal | FlatCMS Apache |
| Title | `Configurer Apache pour FlatCMS` |
| H1 | `Déployer FlatCMS avec Apache` |
| Preuve | .htaccess et tests |
| Statut | À rédiger |

## 8.3 Document root public

| Champ | Valeur |
|---|---|
| URL | `/fr-FR/documentation/deploiement/document-root-public/` |
| Intention | Sécuriser le déploiement |
| Mot-clé principal | document root public PHP |
| Title | `Pourquoi le document root de FlatCMS doit pointer vers public/` |
| H1 | `Configurer le document root public de FlatCMS` |
| Preuve | arborescence et configuration serveurs |
| Statut | À rédiger |

## 8.4 Créer un module

| Champ | Valeur |
|---|---|
| URL | `/fr-FR/documentation/developpement/creer-un-module/` |
| Intention | Développer une extension |
| Mot-clé principal | créer module FlatCMS |
| Title | `Créer un module HMVC pour FlatCMS` |
| H1 | `Comment créer un module FlatCMS` |
| Données structurées | `HowTo`, `TechArticle`, `BreadcrumbList` |
| Preuve | Structure réelle d’un module |
| Statut | À rédiger |

## 8.5 Créer un thème

| Champ | Valeur |
|---|---|
| URL | `/fr-FR/documentation/themes/creer-un-theme/` |
| Intention | Développer un thème |
| Mot-clé principal | créer thème FlatCMS |
| Title | `Créer un thème frontend pour FlatCMS` |
| H1 | `Comment créer un thème FlatCMS` |
| Preuve | thèmes frontend existants |
| Statut | À rédiger |

## 8.6 Créer un widget

| Champ | Valeur |
|---|---|
| URL | `/fr-FR/documentation/widgets/creer-un-widget/` |
| Intention | Développer un widget de builder |
| Mot-clé principal | créer widget FlatCMS |
| Title | `Créer un widget pour les builders FlatCMS` |
| H1 | `Comment créer un widget FlatCMS` |
| Statut produit | Premium/Écosystème |
| Preuve | structure widget réelle |
| Statut | À cadrer |

## 8.7 Sauvegarder FlatCMS

| Champ | Valeur |
|---|---|
| URL | `/fr-FR/documentation/maintenance/sauvegardes/` |
| Intention | Sauvegarder un site |
| Mot-clé principal | sauvegarder FlatCMS |
| Title | `Sauvegarder et restaurer FlatCMS` |
| H1 | `Sauvegarder un site FlatCMS` |
| Preuve | module et périmètre réel |
| Statut | À rédiger |

## 8.8 Dépannage

| Champ | Valeur |
|---|---|
| URL | `/fr-FR/documentation/depannage/` |
| Intention | Résoudre une erreur |
| Mot-clé principal | erreur FlatCMS |
| Title | `Dépannage FlatCMS : erreurs fréquentes et solutions` |
| H1 | `Résoudre les problèmes courants de FlatCMS` |
| Données structurées | `CollectionPage`, `BreadcrumbList` |
| Preuve | erreurs réelles documentées |
| Statut | À rédiger |

---

# 9. Matrice comparatifs P2

## 9.1 FlatCMS vs WordPress

| Champ | Valeur |
|---|---|
| URL | `/fr-FR/comparatifs/flatcms-vs-wordpress/` |
| Intention | Comparer deux solutions |
| Mot-clé principal | FlatCMS vs WordPress |
| Title | `FlatCMS vs WordPress : quel CMS choisir ?` |
| H1 | `FlatCMS ou WordPress : comparaison complète` |
| Données structurées | `Article`, `BreadcrumbList` |
| Preuve | Méthodologie et versions datées |
| Statut | À rédiger |

### Critères

```text
Installation
Stockage
Performances
Administration
Extensions
Thèmes
Sécurité
Maintenance
Multilingue
Écosystème
Cas d’usage
Coût
```

---

## 9.2 FlatCMS vs Grav

| Champ | Valeur |
|---|---|
| URL | `/fr-FR/comparatifs/flatcms-vs-grav/` |
| Mot-clé principal | FlatCMS vs Grav |
| Title | `FlatCMS vs Grav : comparaison des CMS flat-file PHP` |
| H1 | `FlatCMS ou Grav : quel CMS flat-file choisir ?` |
| Preuve | Versions et sources officielles |
| Statut | À rédiger |

---

## 9.3 CMS sans base de données

| Champ | Valeur |
|---|---|
| URL | `/fr-FR/comparatifs/cms-sans-base-de-donnees/` |
| Mot-clé principal | CMS sans base de données |
| Title | `CMS sans base de données : fonctionnement, avantages et choix` |
| H1 | `Comment choisir un CMS sans base de données ?` |
| Statut | À rédiger |

---

# 10. Matrice blog initiale P2

| Priorité | Slug | Titre de travail | Mot-clé principal | Type |
|---|---|---|---|---|
| P2 | `quest-ce-quun-cms-flat-file/` | Qu’est-ce qu’un CMS flat-file ? | CMS flat-file | Pilier |
| P2 | `cms-sans-base-de-donnees/` | Pourquoi utiliser un CMS sans base de données ? | CMS sans base de données | Pilier |
| P2 | `hmvc-explique-simplement/` | Architecture HMVC expliquée simplement | architecture HMVC | Pédagogique |
| P2 | `psr-4-explique/` | PSR-4 : comprendre l’autoloading PHP | PSR-4 | Pédagogique |
| P2 | `json-vs-sql-petit-site/` | JSON ou SQL pour un petit site ? | JSON vs SQL CMS | Comparatif |
| P2 | `cms-agent-ready/` | Qu’est-ce qu’un CMS agent-ready ? | CMS agent-ready | Pilier |
| P2 | `gio-geo-seo/` | SEO, GEO et GIO : quelles différences ? | GEO GIO SEO | Analyse |
| P2 | `securiser-cms-php/` | Sécuriser un CMS PHP flat-file | sécurité CMS PHP | Guide |
| P2 | `deployer-cms-hebergement-mutualise/` | Déployer un CMS PHP sur un hébergement mutualisé | CMS PHP hébergement | Guide |
| P2 | `site-multilingue-flatcms/` | Construire un site multilingue avec FlatCMS | CMS multilingue | Guide |

---

# 11. Pages institutionnelles

## 11.1 À propos

| Champ | Valeur |
|---|---|
| Priorité | P1 |
| URL | `/fr-FR/a-propos/` |
| Mot-clé principal | à propos FlatCMS |
| Title | `À propos de FlatCMS et de son projet open source` |
| H1 | `À propos de FlatCMS` |
| Données structurées | `AboutPage`, `Organization`, `ProfilePage` si pertinent |
| Preuve | Histoire datée, auteur, gouvernance |
| Statut | À rédiger |

## 11.2 Roadmap

| Champ | Valeur |
|---|---|
| Priorité | P1 |
| URL | `/fr-FR/roadmap/` |
| Mot-clé principal | roadmap FlatCMS |
| Title | `Roadmap FlatCMS — Versions, priorités et évolutions` |
| H1 | `Roadmap de FlatCMS` |
| Preuve | Statuts datés et distinction livré/prévu |
| Statut | À rédiger |

## 11.3 Contact

| Champ | Valeur |
|---|---|
| Priorité | P0 |
| URL | `/fr-FR/contact/` |
| Title | `Contacter l’équipe FlatCMS` |
| H1 | `Contacter FlatCMS` |
| Données structurées | `ContactPage`, `Organization` |
| Statut | À rédiger |

## 11.4 Contribuer

| Champ | Valeur |
|---|---|
| Priorité | P2 |
| URL | `/fr-FR/contribuer/` |
| Title | `Contribuer à FlatCMS : code, documentation et traductions` |
| H1 | `Contribuer au projet FlatCMS` |
| Preuve | CLA, dépôt, processus réel |
| Statut | À rédiger |

---

# 12. Maillage interne par cluster

## Cluster « CMS sans base de données »

Page pilier :

```text
/fr-FR/comparatifs/cms-sans-base-de-donnees/
```

Pages liées :

- Accueil
- Pourquoi FlatCMS
- Stockage JSON
- FlatCMS vs WordPress
- FlatCMS vs Grav
- Article « Qu’est-ce qu’un CMS flat-file ? »
- Installation

---

## Cluster « Architecture »

Page pilier :

```text
/fr-FR/architecture/
```

Pages liées :

- HMVC
- PSR-4
- Cycle de requête
- Services
- Hooks
- Stockage JSON
- Données structurées
- Sécurité
- Créer un module

---

## Cluster « Agent-ready »

Page pilier :

```text
/fr-FR/agent-ready/
```

Pages liées :

- Architecture
- Services
- Données structurées
- Stockage JSON
- Multilingue
- Article GEO/GIO/SEO
- Documentation IA
- Sécurité

---

## Cluster « Installation »

Page pilier :

```text
/fr-FR/documentation/installation/
```

Pages liées :

- Téléchargement
- Prérequis
- Apache
- Nginx
- Document root public
- Permissions
- Dépannage
- Sauvegardes

---

# 13. Matrice des preuves

| Page | Source de preuve principale |
|---|---|
| Accueil | README, VERSION, arborescence |
| Architecture | `app/Core`, `app/Modules`, `app/Services` |
| HMVC | structure des modules |
| PSR-4 | autoload et namespaces réels |
| Stockage JSON | `FlatFile.php`, `data/` |
| Multilingue | `I18n.php`, Languages, TranslationScanner |
| Hooks | `Hook.php`, HookManager, `config/hooks.php` |
| Données structurées | `app/Services/StructuredData` |
| Agent-ready | `app/Services/AI`, AiAgent |
| Nginx | `nginx.conf` |
| Licences | documents de licence |
| Thèmes | `themes/admin`, `themes/frontend` |
| Utilisateurs | Auth, Users, `data/users` |
| Sauvegardes | Backups |
| Téléchargement | artefact signé, VERSION, checksum |

---

# 14. Matrice multilingue

Pour chaque page validée en français, créer une ligne de suivi :

| URL fr-FR | en-US | de-DE | es-ES | it-IT | pt-PT |
|---|---|---|---|---|---|
| `/fr-FR/` | À faire | À faire | À faire | À faire | À faire |
| `/fr-FR/fonctionnalites/` | À faire | À faire | À faire | À faire | À faire |
| `/fr-FR/architecture/` | À faire | À faire | À faire | À faire | À faire |
| `/fr-FR/documentation/` | À faire | À faire | À faire | À faire | À faire |
| `/fr-FR/telechargement/` | À faire | À faire | À faire | À faire | À faire |
| `/fr-FR/agent-ready/` | À faire | À faire | À faire | À faire | À faire |

Une traduction ne passe au statut « Validée » que lorsque :

- le contenu est intégralement traduit ;
- le title et la meta description sont localisés ;
- le slug est validé ;
- les liens internes pointent vers la même locale ;
- les `hreflang` sont réciproques ;
- les exemples et termes techniques sont adaptés ;
- aucune chaîne française résiduelle n’est présente.

---

# 15. Données structurées par template

| Template | Types principaux |
|---|---|
| Accueil | `Organization`, `WebSite`, `SoftwareApplication` |
| Page produit | `WebPage`, `SoftwareApplication`, `BreadcrumbList` |
| Fonctionnalité | `WebPage`, `SoftwareApplication`, `BreadcrumbList` |
| Architecture | `TechArticle`, `BreadcrumbList` |
| Documentation procédure | `HowTo`, `TechArticle`, `BreadcrumbList` |
| Documentation référence | `TechArticle`, `BreadcrumbList` |
| Article | `BlogPosting`, `BreadcrumbList` |
| Comparatif | `Article`, `BreadcrumbList` |
| FAQ | `FAQPage` uniquement si éligible |
| Vidéo | `VideoObject` |
| Contact | `ContactPage`, `Organization` |
| À propos | `AboutPage`, `Organization` |

---

# 16. Workflow de production

Pour chaque page :

```text
1. Vérifier la fonctionnalité dans FlatCMS
2. Confirmer son statut produit
3. Valider l’intention
4. Valider l’URL
5. Rédiger le brief
6. Rédiger le contenu
7. Vérifier les faits
8. Ajouter les liens internes
9. Ajouter les médias
10. Ajouter les données structurées
11. Tester le rendu
12. Relire
13. Valider
14. Traduire
15. Publier
16. Mesurer
17. Mettre à jour
```

---

# 17. Priorités de production recommandées

## Lot 1 — Identité et conversion

- [ ] Accueil
- [ ] Pourquoi FlatCMS
- [ ] Fonctionnalités
- [ ] Téléchargement
- [ ] Tarifs
- [ ] Licences
- [ ] Contact

## Lot 2 — Autorité technique

- [ ] Architecture
- [ ] HMVC
- [ ] PSR-4
- [ ] Stockage JSON
- [ ] Cycle d’une requête
- [ ] Services
- [ ] Hooks
- [ ] Données structurées

## Lot 3 — Adoption

- [ ] Documentation
- [ ] Installation
- [ ] Nginx
- [ ] Apache
- [ ] Document root public
- [ ] Dépannage
- [ ] Créer un module
- [ ] Créer un thème

## Lot 4 — Différenciation

- [ ] Agent-ready
- [ ] Multilingue
- [ ] Sécurité
- [ ] Performances
- [ ] FlatCMS vs WordPress
- [ ] FlatCMS vs Grav
- [ ] CMS sans base de données

## Lot 5 — Croissance éditoriale

- [ ] Articles piliers
- [ ] Tutoriels
- [ ] Vidéos
- [ ] Cas d’usage
- [ ] Retours d’expérience
- [ ] Notes de version

---

# 18. Critères de validation d’une page

Une page est prête à publier lorsque :

- [ ] l’intention est claire ;
- [ ] le mot-clé principal est cohérent ;
- [ ] le title est unique ;
- [ ] la meta description est spécifique ;
- [ ] le H1 est unique ;
- [ ] le contenu répond à l’intention ;
- [ ] les affirmations sont vérifiées ;
- [ ] le statut produit est visible ;
- [ ] les limites sont expliquées ;
- [ ] les liens internes sont présents ;
- [ ] les médias sont optimisés ;
- [ ] les textes alternatifs sont utiles ;
- [ ] les données structurées correspondent au visible ;
- [ ] la canonique est correcte ;
- [ ] les `hreflang` sont complets ;
- [ ] le HTML contient le contenu essentiel ;
- [ ] la page est responsive ;
- [ ] la page est accessible ;
- [ ] la performance est contrôlée ;
- [ ] l’auteur et les dates sont présents lorsque pertinents.

---

# 19. Documents suivants

Après validation de cette matrice :

1. `DOCUMENTATION_MAP.md`
2. `KEYWORDS.md`
3. `REDIRECTS.md`
4. `STRUCTURED_DATA.md`
5. `CRAWL_POLICY.md`
6. `CONTENT_STYLE_GUIDE.md`
7. `MULTILINGUAL.md`
8. `LAUNCH_CHECKLIST.md`

---

# 20. Références officielles

- Google Search Central — Creating helpful, reliable, people-first content  
  https://developers.google.com/search/docs/fundamentals/creating-helpful-content

- Google Search Central — Title links  
  https://developers.google.com/search/docs/appearance/title-link

- Google Search Central — AI features and your website  
  https://developers.google.com/search/docs/appearance/ai-features

- Google Search Central — Structured data  
  https://developers.google.com/search/docs/appearance/structured-data/intro-structured-data

- Google Search Central — Localized versions  
  https://developers.google.com/search/docs/specialty/international/localized-versions

- Google Search Central — Crawlable links  
  https://developers.google.com/search/docs/crawling-indexing/links-crawlable

---

# 21. Journal des mises à jour

| Date | Version | Modification | Auteur |
|---|---:|---|---|
| 2026-06-08 | 1.0 | Création de la matrice éditoriale initiale | ChatGPT / Alain BROYE |

---

# 22. Prochaine action

Créer `DOCUMENTATION_MAP.md` afin de décrire précisément :

- les catégories de documentation ;
- l’ordre de lecture ;
- les pages parents et enfants ;
- les prérequis ;
- les versions concernées ;
- les dépendances entre tutoriels ;
- les anciennes pages du wiki à migrer ;
- les lacunes documentaires à combler.

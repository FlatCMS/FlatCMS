# DOCUMENTATION_CONTENT — Documentation officielle de FlatCMS

> **Contenu éditorial prêt à intégrer**
>
> Projet : FlatCMS  
> Page : Hub Documentation `fr-FR`  
> URL cible : `https://flat-cms.fr/fr-FR/documentation/`  
> Version de référence : FlatCMS v1.0.0 LTS Core  
> Date de création : 8 juin 2026  
> Brief parent : `PAGE_BRIEFS.md` — `P0-DOCS-FR`  
> Documents directeurs associés : `DOCUMENTATION_MAP.md`, `CONTENT_STYLE_GUIDE.md`, `MULTILINGUAL.md`  
> Statut : première version rédactionnelle à relire, illustrer et relier aux guides réellement publiés

---

## 1. Objectif de la page

Cette page constitue le point d’entrée de la documentation officielle de FlatCMS.

Elle doit permettre à chaque visiteur de trouver rapidement une ressource adaptée à son objectif :

- découvrir FlatCMS ;
- installer le CMS ;
- administrer un site ;
- créer des contenus ;
- configurer les thèmes ;
- développer un module ;
- déployer en production ;
- sécuriser l’installation ;
- sauvegarder et maintenir le site ;
- résoudre une erreur ;
- consulter une référence technique ;
- comprendre les choix d’architecture.

Le hub ne doit pas devenir une liste exhaustive et non hiérarchisée de tous les documents.

Il doit orienter le lecteur selon :

```text
son profil
son objectif
son environnement
la version de FlatCMS
le type de documentation recherché
```

---

# 2. Métadonnées SEO

## URL canonique

```text
https://flat-cms.fr/fr-FR/documentation/
```

## Balise `<title>`

```text
Documentation FlatCMS — Installation, utilisation et développement
```

## Meta description

```text
Accédez à la documentation officielle de FlatCMS : installation,
configuration, contenus, thèmes, modules, déploiement et dépannage.
```

## Directive robots

```text
index, follow, max-image-preview:large
```

## Open Graph

### `og:title`

```text
Documentation officielle de FlatCMS
```

### `og:description`

```text
Guides de démarrage, administration, développement, déploiement,
maintenance et dépannage pour FlatCMS v1.0.0 LTS Core.
```

### `og:type`

```text
website
```

### `og:url`

```text
https://flat-cms.fr/fr-FR/documentation/
```

### `og:image`

```text
https://flat-cms.fr/assets/images/og/documentation-flatcms-fr-FR.webp
```

---

# 3. Hero

## Sur-titre

```text
FlatCMS v1.0.0 LTS Core
```

## H1

```text
Documentation officielle de FlatCMS
```

## Introduction

```text
Installez, configurez, administrez et développez FlatCMS avec des
tutoriels, des guides pratiques, une référence technique et des
explications d’architecture organisés selon votre objectif.
```

## Complément

```text
Chaque page indique la version concernée, les prérequis, le résultat
attendu et les limites connues afin de vous aider à appliquer les
instructions dans un environnement compatible.
```

## CTA principal

```text
Commencer l’installation
```

Destination :

```text
/fr-FR/documentation/installation/
```

## CTA secondaire

```text
Découvrir FlatCMS en 15 minutes
```

Destination :

```text
/fr-FR/documentation/tutoriels/premier-site/
```

## Lien tertiaire

```text
Consulter la référence développeur
```

Destination :

```text
/fr-FR/documentation/reference/
```

## Recherche

Placeholder :

```text
Rechercher dans la documentation…
```

Aide :

```text
Recherchez un module, une commande, un fichier, un message d’erreur ou
une fonctionnalité.
```

## Règle d’indexation

```text
La page du hub est indexable.
Les résultats de recherche interne utilisent noindex, follow.
```

---

# 4. Choisir le bon type de documentation

## H2

```text
Quel type de documentation recherchez-vous ?
```

## Introduction

```text
La documentation FlatCMS distingue quatre besoins différents. Cette
séparation évite de mélanger un apprentissage guidé, une procédure
rapide, une référence exhaustive et une explication conceptuelle.
```

---

## H3 — Tutoriels

### Question

```text
Je veux apprendre en réalisant un projet.
```

### Texte

```text
Les tutoriels vous accompagnent étape par étape dans un parcours complet.
Ils expliquent quoi faire, dans quel ordre et quel résultat observer.
```

### Exemples

- installer FlatCMS en local ;
- créer un premier site vitrine ;
- publier une page et un article ;
- créer un menu ;
- activer une seconde langue ;
- développer un premier module.

### CTA

```text
Parcourir les tutoriels
```

Destination :

```text
/fr-FR/documentation/tutoriels/
```

---

## H3 — Guides pratiques

### Question

```text
Je veux accomplir une tâche précise.
```

### Texte

```text
Les guides pratiques répondent à un objectif concret sans reprendre
l’ensemble du contexte pédagogique.
```

### Exemples

- configurer Nginx ;
- modifier les permissions ;
- créer un utilisateur ;
- traduire une page ;
- restaurer une sauvegarde ;
- corriger une erreur 500.

### CTA

```text
Consulter les guides pratiques
```

Destination :

```text
/fr-FR/documentation/guides/
```

---

## H3 — Référence

### Question

```text
Je cherche une information exacte sur un composant.
```

### Texte

```text
La référence décrit les classes, routes, fichiers, modèles JSON,
manifestes, paramètres, rôles et contrats du CMS.
```

### Exemples

- structure d’un module ;
- configuration de `app.php` ;
- format d’une page JSON ;
- routes publiques ;
- hooks disponibles ;
- manifeste d’un thème.

### CTA

```text
Ouvrir la référence
```

Destination :

```text
/fr-FR/documentation/reference/
```

---

## H3 — Explications

### Question

```text
Je veux comprendre pourquoi FlatCMS fonctionne ainsi.
```

### Texte

```text
Les explications présentent les concepts, les compromis et les choix
d’architecture du projet.
```

### Exemples

- pourquoi utiliser HMVC ;
- rôle de PSR-4 ;
- stockage JSON et concurrence ;
- document root public ;
- séparation des modules et services ;
- architecture agent-ready.

### CTA

```text
Comprendre l’architecture
```

Destination :

```text
/fr-FR/architecture/
```

---

# 5. Nouveau sur FlatCMS

## H2

```text
Nouveau sur FlatCMS ?
```

## Texte

```text
Suivez ce parcours si vous découvrez le CMS et souhaitez obtenir un site
fonctionnel avant d’explorer les fonctions avancées.
```

## Parcours recommandé

### Étape 1 — Vérifier les prérequis

```text
Contrôlez la version de PHP, les extensions, le serveur web, les
permissions et la possibilité de pointer le document root vers public/.
```

Lien :

```text
/fr-FR/documentation/demarrage/prerequis/
```

### Étape 2 — Télécharger la version stable

```text
Récupérez l’archive officielle, sa version et son checksum.
```

Lien :

```text
/fr-FR/telechargement/
```

### Étape 3 — Installer FlatCMS

```text
Extrayez les fichiers, configurez le serveur et lancez l’installateur.
```

Lien :

```text
/fr-FR/documentation/installation/
```

### Étape 4 — Se connecter à l’administration

```text
Ouvrez l’administration avec le compte créé pendant l’installation.
```

Lien :

```text
/fr-FR/documentation/demarrage/premiere-connexion/
```

### Étape 5 — Créer une première page

```text
Publiez une page simple et vérifiez son rendu sur le frontend.
```

Lien :

```text
/fr-FR/documentation/contenus/creer-une-page/
```

### Étape 6 — Configurer la navigation

```text
Ajoutez la page au menu principal.
```

Lien :

```text
/fr-FR/documentation/navigation/creer-un-menu/
```

### Étape 7 — Sauvegarder le site

```text
Créez une première sauvegarde avant d’ajouter des extensions ou de
modifier le thème.
```

Lien :

```text
/fr-FR/documentation/maintenance/sauvegarder/
```

## CTA

```text
Commencer le parcours débutant
```

Destination :

```text
/fr-FR/documentation/tutoriels/premier-site/
```

---

# 6. Documentation par profil

## H2

```text
Choisissez votre parcours
```

---

## H3 — Administrateur du site

### Objectifs

- configurer les paramètres ;
- gérer les utilisateurs ;
- activer les modules ;
- choisir les thèmes ;
- effectuer les sauvegardes ;
- surveiller le site.

### Pages recommandées

- première connexion ;
- paramètres généraux ;
- utilisateurs et rôles ;
- modules ;
- thèmes ;
- sauvegardes ;
- mises à jour ;
- sécurité.

### CTA

```text
Documentation administrateur
```

Destination :

```text
/fr-FR/documentation/administration/
```

---

## H3 — Rédacteur ou éditeur

### Objectifs

- créer des pages ;
- publier des articles ;
- organiser les catégories ;
- gérer les médias ;
- préparer les métadonnées ;
- traduire les contenus.

### Pages recommandées

- gestion des pages ;
- articles ;
- catégories ;
- médiathèque ;
- publication ;
- SEO éditorial ;
- multilingue.

### CTA

```text
Documentation des contenus
```

Destination :

```text
/fr-FR/documentation/contenus/
```

---

## H3 — Intégrateur ou designer

### Objectifs

- personnaliser le frontend ;
- configurer les menus ;
- adapter le footer ;
- créer ou modifier un thème ;
- intégrer des composants accessibles ;
- optimiser les médias.

### Pages recommandées

- structure d’un thème ;
- templates ;
- assets ;
- menus ;
- footer ;
- composants ;
- accessibilité ;
- responsive ;
- performance.

### CTA

```text
Documentation d’intégration
```

Destination :

```text
/fr-FR/documentation/integration/
```

---

## H3 — Développeur PHP

### Objectifs

- comprendre le runtime ;
- créer un module ;
- déclarer des routes ;
- utiliser des services ;
- enregistrer des hooks ;
- lire et écrire les données ;
- tester une extension.

### Pages recommandées

- architecture ;
- HMVC ;
- PSR-4 ;
- cycle de requête ;
- créer un module ;
- routes ;
- contrôleurs ;
- services ;
- hooks ;
- manifestes ;
- stockage JSON ;
- tests.

### CTA

```text
Documentation développeur
```

Destination :

```text
/fr-FR/documentation/developpement/
```

---

## H3 — Exploitant ou administrateur serveur

### Objectifs

- installer FlatCMS ;
- configurer Apache ou Nginx ;
- gérer PHP-FPM ;
- appliquer les permissions ;
- sécuriser le document root ;
- surveiller les logs ;
- sauvegarder et restaurer.

### Pages recommandées

- prérequis ;
- installation ;
- Apache ;
- Nginx ;
- PHP ;
- permissions ;
- HTTPS ;
- cache ;
- sauvegardes ;
- logs ;
- dépannage.

### CTA

```text
Documentation de déploiement
```

Destination :

```text
/fr-FR/documentation/deploiement/
```

---

# 7. Installer FlatCMS

## H2

```text
Installer FlatCMS dans votre environnement
```

## Texte

```text
La procédure commune consiste à vérifier PHP, télécharger l’archive,
extraire les fichiers, configurer le document root sur public/, appliquer
les permissions et lancer l’installateur.
```

## Environnements

### Apache

```text
Configurer le VirtualHost, le document root, les réécritures et PHP.
```

Lien :

```text
/fr-FR/documentation/installation/apache/
```

### Nginx

```text
Configurer le bloc server, try_files, PHP-FPM et la protection des
fichiers internes.
```

Lien :

```text
/fr-FR/documentation/installation/nginx/
```

### MAMP sur macOS

```text
Configurer un hôte local, sélectionner la version PHP et définir le
dossier public comme racine.
```

Lien :

```text
/fr-FR/documentation/installation/mamp/
```

### WAMP sur Windows

```text
Configurer Apache, PHP, les modules requis et les permissions du projet.
```

Lien :

```text
/fr-FR/documentation/installation/wamp/
```

### Linux

```text
Installer le serveur web, PHP-FPM et les extensions nécessaires.
```

Lien :

```text
/fr-FR/documentation/installation/linux/
```

### Synology DSM

```text
Configurer Web Station, PHP, les permissions et l’exposition de public/.
```

Lien :

```text
/fr-FR/documentation/installation/synology/
```

### Raspberry Pi

```text
Déployer FlatCMS sur une architecture ARM avec Apache ou Nginx.
```

Lien :

```text
/fr-FR/documentation/installation/raspberry-pi/
```

## CTA principal

```text
Lire le guide d’installation
```

Destination :

```text
/fr-FR/documentation/installation/
```

## CTA secondaire

```text
Consulter les prérequis
```

Destination :

```text
/fr-FR/documentation/demarrage/prerequis/
```

---

# 8. Administrer FlatCMS

## H2

```text
Configurer et administrer le CMS
```

## Rubriques

### Tableau de bord

- informations du site ;
- état des modules ;
- version ;
- alertes ;
- raccourcis ;
- checklist de démarrage.

### Paramètres généraux

- titre ;
- slogan ;
- description ;
- URL ;
- locale ;
- fuseau horaire ;
- e-mail ;
- paramètres d’affichage.

### Utilisateurs

- création ;
- modification ;
- rôles ;
- activation ;
- réinitialisation ;
- sécurité.

### Modules

- liste ;
- statut ;
- activation ;
- désactivation ;
- compatibilité ;
- dépendances ;
- licence.

### Thèmes

- thèmes frontend ;
- thèmes d’administration ;
- activation ;
- compatibilité ;
- options.

### Intégrations

- e-mail ;
- analytics ;
- intelligence artificielle ;
- services externes ;
- secrets.

## CTA

```text
Ouvrir la documentation d’administration
```

Destination :

```text
/fr-FR/documentation/administration/
```

---

# 9. Créer et publier des contenus

## H2

```text
Gérer les pages, articles et médias
```

## Pages

- créer une page ;
- modifier le titre et le slug ;
- enregistrer un brouillon ;
- publier ;
- associer une locale ;
- définir les métadonnées ;
- ajouter au menu ;
- supprimer et restaurer.

## Articles

- créer un article ;
- rédiger le résumé ;
- ajouter une image ;
- choisir une catégorie ;
- définir l’auteur ;
- publier immédiatement ;
- programmer si la fonction est disponible ;
- gérer les commentaires.

## Catégories

- créer une catégorie ;
- traduire le titre ;
- définir le slug ;
- rédiger la description ;
- associer les articles.

## Médias

- téléverser ;
- renseigner le texte alternatif ;
- choisir les dimensions ;
- réutiliser ;
- remplacer ;
- supprimer ;
- optimiser.

## CTA

```text
Documentation de gestion des contenus
```

Destination :

```text
/fr-FR/documentation/contenus/
```

---

# 10. Navigation, menus et footer

## H2

```text
Construire la navigation du site
```

## Menu standard

```text
Le module Menu organise les liens, les pages et les niveaux de
navigation disponibles dans le thème.
```

## Footer standard

```text
Le module Footer gère les informations communes du pied de page.
```

## Builders premium

```text
MenuBuilder et FooterBuilder ajoutent des fonctions visuelles avancées.
Leur documentation et leur licence restent distinctes du Core.
```

## Guides recommandés

- créer un menu ;
- ajouter une page ;
- créer un lien externe ;
- gérer un sous-menu ;
- configurer le menu mobile ;
- traduire un menu ;
- modifier le footer ;
- utiliser MenuBuilder ;
- utiliser FooterBuilder.

## CTA

```text
Documentation de la navigation
```

Destination :

```text
/fr-FR/documentation/navigation/
```

---

# 11. Personnaliser les thèmes

## H2

```text
Adapter l’apparence du frontend et de l’administration
```

## Texte

```text
FlatCMS sépare le thème public et le thème de l’administration.
La documentation doit préciser le contrat de chaque type de thème.
```

## Parcours intégrateur

1. comprendre la structure d’un thème ;
2. dupliquer un thème de référence ;
3. modifier les templates ;
4. organiser les assets ;
5. intégrer les composants ;
6. gérer les menus et le footer ;
7. tester le responsive ;
8. vérifier l’accessibilité ;
9. mesurer les performances ;
10. préparer le manifeste et la version.

## Guides recommandés

- structure d’un thème ;
- créer un thème frontend ;
- modifier un template ;
- ajouter une feuille de style ;
- ajouter un script ;
- gérer les images ;
- créer un composant ;
- créer un thème d’administration ;
- préparer un package de thème.

## CTA

```text
Documentation des thèmes
```

Destination :

```text
/fr-FR/documentation/themes/
```

---

# 12. Développer des modules

## H2

```text
Étendre FlatCMS avec un module HMVC
```

## Texte

```text
Un module regroupe une fonctionnalité autour de ses contrôleurs, services,
vues, routes, configurations, traductions, permissions et ressources.
```

## Parcours développeur

1. comprendre HMVC ;
2. vérifier le namespace PSR-4 ;
3. créer l’arborescence ;
4. rédiger le manifeste ;
5. déclarer les routes ;
6. créer le contrôleur ;
7. isoler la logique dans un service ;
8. créer les vues ;
9. gérer les permissions ;
10. ajouter les traductions ;
11. enregistrer les hooks ;
12. tester l’installation et la désactivation ;
13. documenter le module.

## Références nécessaires

- manifeste ;
- routes ;
- contrôleurs ;
- services ;
- vues ;
- stockage ;
- hooks ;
- permissions ;
- assets ;
- traductions ;
- cycle de vie ;
- erreurs ;
- tests.

## CTA principal

```text
Créer un module FlatCMS
```

Destination :

```text
/fr-FR/documentation/developpement/creer-un-module/
```

## CTA secondaire

```text
Consulter la référence des modules
```

Destination :

```text
/fr-FR/documentation/reference/modules/
```

---

# 13. Déployer en production

## H2

```text
Préparer un déploiement fiable
```

## Étapes essentielles

- sauvegarder ;
- vérifier la version ;
- préparer la configuration privée ;
- pointer le document root vers `public/` ;
- appliquer les permissions ;
- configurer HTTPS ;
- désactiver l’affichage des erreurs ;
- activer les logs ;
- configurer le cache ;
- tester les e-mails ;
- vérifier `robots.txt` ;
- vérifier les sitemaps ;
- tester les redirections ;
- surveiller après publication.

## Environnements

- Apache ;
- Nginx ;
- PHP-FPM ;
- hébergement mutualisé ;
- VPS ;
- Synology ;
- Raspberry Pi ;
- conteneur si officiellement documenté.

## CTA

```text
Documentation de déploiement
```

Destination :

```text
/fr-FR/documentation/deploiement/
```

---

# 14. Sécuriser FlatCMS

## H2

```text
Appliquer les bonnes pratiques de sécurité
```

## Principes

- exposer uniquement `public/` ;
- protéger les secrets ;
- limiter les permissions ;
- utiliser HTTPS ;
- maintenir PHP et le serveur ;
- contrôler les téléversements ;
- sécuriser les sessions ;
- protéger les formulaires ;
- limiter les tentatives ;
- sauvegarder ;
- journaliser ;
- tester les mises à jour.

## Pages recommandées

- document root public ;
- permissions ;
- sessions ;
- CSRF ;
- téléversements ;
- secrets ;
- en-têtes HTTP ;
- CSP ;
- HTTPS ;
- sauvegardes ;
- signalement de vulnérabilité.

## Message

```text
FlatCMS peut fournir des mécanismes de sécurité, mais aucun CMS ne
garantit une sécurité absolue indépendamment de sa configuration et de
son exploitation.
```

## CTA

```text
Consulter le guide de sécurité
```

Destination :

```text
/fr-FR/documentation/securite/
```

---

# 15. Sauvegarder et maintenir

## H2

```text
Maintenir le site dans la durée
```

## Sauvegardes

- données JSON ;
- médias ;
- configuration privée ;
- version du code ;
- règles serveur ;
- tâches planifiées ;
- certificats ou procédures de restauration.

## Maintenance

- vérifier les mises à jour ;
- lire les notes de version ;
- tester en préproduction ;
- créer une sauvegarde ;
- appliquer la mise à jour ;
- reconstruire les caches ;
- vérifier les parcours critiques ;
- surveiller les logs ;
- conserver un rollback.

## Nettoyage

- corbeille ;
- fichiers temporaires ;
- anciennes sauvegardes ;
- logs ;
- modules inutilisés ;
- médias orphelins ;
- traductions obsolètes.

## CTA

```text
Documentation de maintenance
```

Destination :

```text
/fr-FR/documentation/maintenance/
```

---

# 16. Résoudre un problème

## H2

```text
Dépannage FlatCMS
```

## Introduction

```text
Commencez par conserver le message exact, identifier l’environnement et
vérifier les logs avant de modifier les fichiers ou les permissions.
```

## Parcours de diagnostic

1. reproduire l’erreur ;
2. noter l’URL et l’action ;
3. relever le message exact ;
4. vérifier la version de FlatCMS ;
5. vérifier PHP et le serveur ;
6. consulter les logs ;
7. contrôler les permissions ;
8. vérifier la présence des fichiers ;
9. isoler le module concerné ;
10. tester après sauvegarde.

## Erreurs fréquentes

### Installation bloquée

- prérequis ;
- permissions ;
- module absent ;
- document root ;
- URL de l’installateur.

### Erreur 500

- syntaxe PHP ;
- logs ;
- configuration serveur ;
- permissions ;
- extension PHP ;
- cache.

### Module introuvable

- package incomplet ;
- casse du chemin ;
- autoloading ;
- manifeste ;
- activation ;
- cache.

### Impossible d’écrire un fichier

- propriétaire ;
- groupe ;
- permissions ;
- open_basedir ;
- chemin réel ;
- processus PHP.

### URLs propres indisponibles

- `mod_rewrite` ;
- `.htaccess` ;
- `try_files` ;
- base URL ;
- document root.

### E-mail non reçu

- configuration SMTP ;
- logs ;
- SPF ;
- DKIM ;
- DMARC ;
- spam ;
- adresse d’expéditeur.

## CTA principal

```text
Ouvrir le centre de dépannage
```

Destination :

```text
/fr-FR/documentation/depannage/
```

## CTA secondaire

```text
Contacter FlatCMS
```

Destination :

```text
/fr-FR/contact/
```

---

# 17. Référence technique

## H2

```text
Consulter la référence de FlatCMS
```

## Rubriques

### Runtime

- App ;
- Router ;
- Request ;
- Response ;
- BaseController ;
- View ;
- FlatFile ;
- ModuleManager ;
- Hook ;
- I18n ;
- Session.

### Configuration

- `app.php` ;
- `routes.php` ;
- `hooks.php` ;
- variables d’environnement ;
- paramètres privés.

### Modules

- manifeste ;
- statuts ;
- dépendances ;
- permissions ;
- routes ;
- vues ;
- traductions ;
- cycle de vie.

### Modèles JSON

- pages ;
- articles ;
- catégories ;
- médias ;
- utilisateurs ;
- modules ;
- menus ;
- paramètres.

### Thèmes

- manifeste ;
- templates ;
- assets ;
- emplacements ;
- options ;
- compatibilité.

### HTTP

- routes ;
- méthodes ;
- statuts ;
- erreurs ;
- redirections ;
- en-têtes.

### CLI et tâches

Uniquement si des commandes ou tâches officielles existent dans la
version documentée.

## CTA

```text
Consulter la référence technique
```

Destination :

```text
/fr-FR/documentation/reference/
```

---

# 18. Documentation par version

## H2

```text
Choisir la bonne version de la documentation
```

## Version stable

```text
FlatCMS v1.0.0 LTS Core
```

Badge :

```text
Version stable
```

## Versions archivées

```text
Les anciennes documentations restent accessibles lorsqu’elles sont
encore utiles, mais elles doivent être clairement signalées comme
archives.
```

## Version en développement

```text
La documentation d’une version non publiée doit être identifiée comme
préversion et ne pas apparaître comme guide stable par défaut.
```

## Sélecteur

```text
Version : 1.0 LTS
```

## Règles

- une page indique la version concernée ;
- un exemple de code indique sa compatibilité ;
- une fonction future ne figure pas dans la documentation stable ;
- les anciennes URLs sont redirigées uniquement lorsque le contenu est
  réellement équivalent ;
- une archive reste en `noindex` ou indexable selon sa valeur et la
  stratégie validée.

---

# 19. Documentation multilingue

## H2

```text
Lire la documentation dans votre langue
```

## Locales

```text
Français
English
Deutsch
Español
Italiano
Português
```

## Règles

- chaque traduction possède sa propre URL ;
- le sélecteur pointe vers l’équivalent réel ;
- une traduction absente n’est pas déclarée dans `hreflang` ;
- le code et les noms de fichiers restent identiques ;
- les explications, messages et textes alternatifs sont traduits ;
- les traductions obsolètes sont signalées dans l’administration ;
- la source éditoriale initiale est `fr-FR`.

## Message en cas d’absence

```text
Cette page n’est pas encore disponible en allemand.
Consulter la version française
Revenir à la documentation allemande
```

---

# 20. Recherche et filtres

## H2

```text
Trouver rapidement une information
```

## Recherche

La recherche doit pouvoir reconnaître :

- titre ;
- résumé ;
- contenu ;
- module ;
- fichier ;
- classe ;
- commande ;
- message d’erreur ;
- version ;
- tags.

## Filtres

### Par profil

- débutant ;
- administrateur ;
- rédacteur ;
- intégrateur ;
- développeur ;
- exploitant.

### Par type

- tutoriel ;
- guide ;
- référence ;
- explication ;
- dépannage.

### Par domaine

- installation ;
- contenus ;
- navigation ;
- thèmes ;
- modules ;
- sécurité ;
- maintenance ;
- IA.

### Par version

- stable ;
- archives ;
- préversion.

### Par environnement

- Apache ;
- Nginx ;
- MAMP ;
- WAMP ;
- Linux ;
- Windows ;
- macOS ;
- Synology ;
- Raspberry Pi.

## SEO

```text
Les résultats de recherche et les combinaisons de filtres ne doivent pas
créer des milliers de pages indexables sans valeur éditoriale.
```

---

# 21. Contenus populaires et essentiels

## H2

```text
Les guides les plus utiles
```

## Sélection initiale

1. Installer FlatCMS
2. Configurer le document root public
3. Se connecter à l’administration
4. Créer une page
5. Publier un article
6. Gérer les médias
7. Créer un menu
8. Ajouter une langue
9. Sauvegarder le site
10. Résoudre une erreur 500
11. Créer un module
12. Configurer Nginx

## Règle

```text
La sélection doit être fondée sur les parcours critiques, les recherches
internes, Search Console et les demandes de support, pas uniquement sur
la date de publication.
```

---

# 22. Dernières mises à jour documentaires

## H2

```text
Documentation récemment mise à jour
```

Chaque entrée affiche :

- titre ;
- résumé ;
- version ;
- date de modification ;
- type documentaire ;
- catégorie ;
- langue.

## Règle

```text
Une correction purement typographique ne doit pas automatiquement
présenter une page comme substantiellement mise à jour.
```

---

# 23. Contribuer à la documentation

## H2

```text
Améliorer la documentation FlatCMS
```

## Contributions possibles

- signaler une erreur ;
- corriger une commande ;
- améliorer une explication ;
- ajouter un exemple ;
- proposer une capture ;
- traduire une page ;
- documenter un module ;
- reproduire un bug ;
- relire une procédure.

## Prérequis

- identifier la version ;
- décrire l’environnement ;
- fournir un exemple reproductible ;
- respecter le guide éditorial ;
- utiliser les termes officiels ;
- ne pas publier de secret ;
- vérifier les droits sur les médias.

## CTA principal

```text
Contribuer à la documentation
```

Destination :

```text
/fr-FR/documentation/contribuer/
```

## CTA secondaire

```text
Signaler une erreur
```

Destination :

```text
/fr-FR/contact/?motif=documentation
```

---

# 24. Questions fréquentes éditoriales

> Ces questions servent aux lecteurs. Elles ne déclenchent pas
> automatiquement un balisage `FAQPage`.

## H2

```text
Questions fréquentes sur la documentation
```

### H3 — Quelle documentation dois-je suivre ?

```text
Utilisez la documentation correspondant à votre version de FlatCMS et à
votre environnement serveur. La version stable est sélectionnée par
défaut.
```

### H3 — Quelle différence existe entre un tutoriel et un guide ?

```text
Un tutoriel accompagne un apprentissage complet. Un guide pratique
répond à une tâche précise lorsque vous connaissez déjà le contexte.
```

### H3 — Où trouver la structure exacte d’un fichier JSON ?

```text
Consultez la référence des modèles de données. Les tutoriels utilisent
des exemples simplifiés qui ne remplacent pas le contrat complet.
```

### H3 — Les commandes fonctionnent-elles sous Windows ?

```text
Chaque commande doit indiquer son environnement. Une commande macOS ou
Linux ne doit pas être présentée comme universelle.
```

### H3 — La documentation des versions anciennes reste-t-elle disponible ?

```text
Les archives utiles peuvent être conservées sous une section dédiée et
identifiées clairement comme non courantes.
```

### H3 — Puis-je traduire une page avec une IA ?

```text
Une IA peut produire un premier brouillon. Les pages techniques,
juridiques et de sécurité doivent être relues et validées humainement
avant publication.
```

### H3 — Comment signaler une instruction incorrecte ?

```text
Utilisez le lien de signalement présent sur la page en indiquant la
version, l’environnement, l’étape concernée et le résultat obtenu.
```

---

# 25. CTA final

## H2

```text
Commencez avec le parcours adapté à votre objectif
```

## Texte

```text
Installez FlatCMS, apprenez à gérer les contenus ou explorez
l’architecture développeur depuis une documentation organisée par besoin.
```

## CTA principal

```text
Installer FlatCMS
```

Destination :

```text
/fr-FR/documentation/installation/
```

## CTA secondaire

```text
Créer un premier site
```

Destination :

```text
/fr-FR/documentation/tutoriels/premier-site/
```

## Lien tertiaire

```text
Explorer l’architecture
```

Destination :

```text
/fr-FR/architecture/
```

---

# 26. Navigation documentaire proposée

## Niveau principal

```text
Documentation
├── Démarrage
├── Tutoriels
├── Guides pratiques
├── Administration
├── Contenus
├── Navigation
├── Thèmes
├── Développement
├── Déploiement
├── Sécurité
├── Maintenance
├── Dépannage
├── Référence
└── Versions
```

## Démarrage

```text
/demarrage/
├── prerequis/
├── telechargement/
├── installation/
├── premiere-connexion/
└── premier-site/
```

## Développement

```text
/developpement/
├── architecture/
├── creer-un-module/
├── routes/
├── controleurs/
├── services/
├── vues/
├── hooks/
├── manifestes/
├── stockage/
├── traductions/
└── tests/
```

## Déploiement

```text
/deploiement/
├── document-root-public/
├── apache/
├── nginx/
├── php-fpm/
├── permissions/
├── https/
├── cache/
├── logs/
└── production/
```

---

# 27. Modèle d’une carte documentaire

## Titre

```text
Installer FlatCMS sur Nginx
```

## Type

```text
Guide pratique
```

## Résumé

```text
Configurez le document root, try_files, PHP-FPM et les permissions pour
déployer FlatCMS sur Nginx.
```

## Métadonnées visibles

```text
Version 1.0 LTS
Niveau intermédiaire
15 minutes
Nginx
Mis à jour le 8 juin 2026
```

## CTA

```text
Lire le guide
```

---

# 28. Modèle d’une page documentaire

Chaque document doit pouvoir afficher :

- titre ;
- résumé ;
- type documentaire ;
- version ;
- niveau ;
- environnement ;
- auteur ;
- relecteur ;
- date de publication ;
- date de modification ;
- prérequis ;
- résultat attendu ;
- sommaire ;
- contenu ;
- étapes ;
- vérifications ;
- limites ;
- erreurs fréquentes ;
- liens précédent/suivant ;
- contenus associés ;
- signalement d’erreur.

---

# 29. Maillage interne attendu

| Section | Destination |
|---|---|
| Hero | Installation, premier site, référence |
| Types documentaires | Tutoriels, guides, référence, architecture |
| Nouveau | Prérequis, téléchargement, installation |
| Profils | Administration, contenus, intégration, développement, déploiement |
| Installation | Guides par environnement |
| Administration | Paramètres, utilisateurs, modules, thèmes |
| Contenus | Pages, articles, catégories, médias |
| Navigation | Menus, footer, builders |
| Thèmes | Développement de thème |
| Modules | Création module et référence |
| Production | Déploiement et sécurité |
| Maintenance | Sauvegardes et mises à jour |
| Dépannage | Centre de dépannage |
| Versions | Documentation par version |
| Contribution | Guide de contribution |
| CTA final | Installation, tutoriel, architecture |

---

# 30. Médias à produire

## Image Open Graph

Concept :

```text
Interface documentaire FlatCMS avec quatre portes d’entrée :
Tutoriels, Guides, Référence et Explications
```

## Illustration principale

```text
Carte des parcours :
Débutant
Administrateur
Rédacteur
Intégrateur
Développeur
Exploitant
```

## Captures

- page de documentation ;
- recherche ;
- filtre par version ;
- sommaire ;
- bloc de code ;
- avertissement ;
- navigation précédent/suivant.

## Diagrammes

- carte Diátaxis adaptée à FlatCMS ;
- parcours d’installation ;
- parcours développeur ;
- cycle de dépannage.

---

# 31. Textes alternatifs suggérés

## Types documentaires

```text
Organisation de la documentation FlatCMS en tutoriels, guides pratiques,
référence et explications
```

## Parcours utilisateurs

```text
Parcours documentaires pour débutants, administrateurs, rédacteurs,
intégrateurs, développeurs et exploitants
```

## Recherche

```text
Interface de recherche et de filtrage de la documentation FlatCMS
```

Les textes doivent être adaptés aux médias finaux.

---

# 32. Données structurées attendues

```text
CollectionPage
WebPage
BreadcrumbList
ImageObject
```

## Identifiants

```text
https://flat-cms.fr/fr-FR/documentation/#webpage
https://flat-cms.fr/fr-FR/documentation/#breadcrumb
https://flat-cms.fr/fr-FR/documentation/#primaryimage
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

## Pages filles

Les guides techniques peuvent utiliser :

```text
TechArticle
HowTo lorsque la page présente réellement une procédure ordonnée
```

---

# 33. Composants du thème suggérés

```text
HeroDocumentation
DocumentationSearch
DocumentationTypeCards
GettingStartedPath
AudiencePathCards
DocumentationCategoryGrid
EnvironmentCards
PopularDocumentation
RecentlyUpdatedDocumentation
VersionSelector
ContributionBanner
FaqAccordion
CallToActionBanner
```

Les composants définitifs doivent correspondre aux widgets réellement
disponibles dans le thème ou PagesBuilder.

---

# 34. Règles SEO du hub

- une seule page canonique pour le hub ;
- titles uniques pour les catégories ;
- résultats de recherche en `noindex` ;
- filtres non indexables par défaut ;
- liens HTML vers les rubriques ;
- pagination explorables si nécessaire ;
- catégories vides non publiées ;
- canonicals auto-référencées ;
- `hreflang` uniquement vers les traductions publiées ;
- contenu essentiel disponible sans JavaScript ;
- fil d’Ariane visible ;
- sitemaps par locale ;
- anciennes URLs du wiki redirigées page par page.

---

# 35. Règles d’accessibilité

- recherche avec label visible ou accessible ;
- navigation clavier ;
- focus visible ;
- titres hiérarchiques ;
- sommaire accessible ;
- liens descriptifs ;
- blocs de code lisibles ;
- tableaux structurés ;
- avertissements non dépendants de la couleur ;
- bouton de copie accessible ;
- contraste suffisant ;
- zoom sans perte ;
- sélection de version compréhensible ;
- langue du document déclarée.

---

# 36. Éléments à confirmer avant intégration

- liste réelle des guides disponibles au lancement ;
- version stable exacte ;
- structure des URLs ;
- moteur de recherche ;
- filtres ;
- mécanisme de versionnement ;
- auteurs et relecteurs ;
- temps de lecture ;
- niveaux ;
- historique de modification ;
- liens GitHub ;
- processus de contribution ;
- redirections du wiki ;
- statut des pages multilingues ;
- disponibilité des guides Synology et Raspberry Pi ;
- commandes officielles ;
- modèles JSON de référence ;
- pages de dépannage existantes.

---

# 37. Checklist éditoriale

- [ ] Le hub oriente sans surcharger.
- [ ] Les quatre types documentaires sont distingués.
- [ ] Les parcours par profil sont visibles.
- [ ] Les pages citées existent ou sont planifiées.
- [ ] La version stable est affichée.
- [ ] Les environnements sont distingués.
- [ ] Les commandes ne sont pas présentées comme universelles.
- [ ] Les pages futures ne sont pas annoncées comme déjà publiées.
- [ ] Les pages premium sont identifiées.
- [ ] Les limites de sécurité sont visibles.
- [ ] La recherche interne ne crée pas de pages SEO inutiles.
- [ ] Le contenu respecte la terminologie FlatCMS.
- [ ] Les liens sont descriptifs.
- [ ] Les dates et versions sont exactes.
- [ ] Les traductions prévues sont réalistes.
- [ ] La contribution est encadrée.

---

# 38. Checklist d’intégration

- [ ] URL correcte.
- [ ] Canonique auto-référencée.
- [ ] `<html lang="fr-FR">`.
- [ ] Groupe `hreflang`.
- [ ] Title.
- [ ] Meta description.
- [ ] Open Graph.
- [ ] H1 unique.
- [ ] Recherche fonctionnelle.
- [ ] Résultats en `noindex`.
- [ ] Filtres accessibles.
- [ ] Fil d’Ariane.
- [ ] Liens HTML explorables.
- [ ] Images responsive.
- [ ] Textes alternatifs.
- [ ] JSON-LD.
- [ ] Sitemap.
- [ ] Directive robots.
- [ ] Test mobile.
- [ ] Test clavier.
- [ ] Test des liens.
- [ ] Test HTTP `200`.
- [ ] Validation des cartes contre les pages publiées.

---

# 39. Sources internes

- `README.md`
- `VERSION`
- `DOCUMENTATION_MAP.md`
- `SITE_ARCHITECTURE.md`
- `CONTENT_STYLE_GUIDE.md`
- `MULTILINGUAL.md`
- arborescence de FlatCMS ;
- modules réels ;
- guides validés ;
- messages d’erreur réels ;
- procédures d’installation testées ;
- inventaire du wiki actuel.

---

# 40. Références externes

- Diátaxis — A systematic approach to technical documentation  
  https://diataxis.fr/

- Google Search Central — Creating helpful, reliable, people-first content  
  https://developers.google.com/search/docs/fundamentals/creating-helpful-content

- W3C WAI — Headings  
  https://www.w3.org/WAI/tutorials/page-structure/headings/

La structure des quatre types documentaires s’appuie sur Diátaxis. Les
recommandations Google encadrent la qualité, l’originalité et la clarté
des contenus, tandis que le W3C encadre la hiérarchie sémantique et
l’accessibilité des titres.

---

# 41. Journal des mises à jour

| Date | Version | Modification | Auteur |
|---|---:|---|---|
| 2026-06-08 | 1.0 | Première rédaction complète du hub Documentation | ChatGPT / Alain BROYE |

---

# 42. Prochaine action

Après validation et ajout dans le Drive :

```text
Créer INSTALLATION_CONTENT.md
```

Ce document contiendra la rédaction complète du guide principal :

```text
/fr-FR/documentation/installation/
```

Il couvrira les prérequis, le téléchargement, le document root public,
les permissions, l’installateur, les contrôles finaux et les liens vers
les procédures Apache, Nginx, MAMP, WAMP, Synology et Raspberry Pi.

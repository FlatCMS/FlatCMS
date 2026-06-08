# WHY_FLATCMS_CONTENT — Pourquoi choisir FlatCMS ?

> **Contenu éditorial prêt à intégrer**
>
> Projet : FlatCMS  
> Page : Pourquoi FlatCMS `fr-FR`  
> URL cible : `https://flat-cms.fr/fr-FR/pourquoi-flatcms/`  
> Version de référence : FlatCMS v1.0.0 LTS Core  
> Date de création : 8 juin 2026  
> Brief parent : `PAGE_BRIEFS.md` — `P0-WHY-FR`  
> Statut : première version rédactionnelle à relire, illustrer et intégrer

---

## 1. Objectif de la page

Cette page doit aider le visiteur à déterminer si FlatCMS correspond réellement à son projet.

Elle ne doit pas présenter FlatCMS comme une solution universelle.

Elle doit expliquer :

- les problèmes auxquels FlatCMS répond ;
- les projets auxquels son architecture est adaptée ;
- les bénéfices concrets d’un CMS flat-file ;
- les compromis liés au stockage par fichiers ;
- les profils auxquels le CMS s’adresse ;
- les cas où une autre solution peut être plus pertinente ;
- les éléments à vérifier avant de choisir.

La page doit conduire à une décision éclairée :

```text
tester FlatCMS
télécharger FlatCMS
consulter l’architecture
ou choisir une autre solution mieux adaptée
```

---

# 2. Métadonnées SEO

## URL canonique

```text
https://flat-cms.fr/fr-FR/pourquoi-flatcms/
```

## Balise `<title>`

```text
Pourquoi choisir FlatCMS pour créer un site web ?
```

## Meta description

```text
Découvrez les avantages, les limites et les cas d’usage de FlatCMS,
un CMS PHP flat-file sans serveur SQL, modulaire et multilingue.
```

## Directive robots

```text
index, follow, max-image-preview:large
```

## Open Graph

### `og:title`

```text
Pourquoi choisir FlatCMS ?
```

### `og:description`

```text
Évaluez les bénéfices, les compromis et les cas d’usage d’un CMS PHP
modulaire fondé sur un stockage JSON sans serveur SQL.
```

### `og:type`

```text
website
```

### `og:url`

```text
https://flat-cms.fr/fr-FR/pourquoi-flatcms/
```

### `og:image`

```text
https://flat-cms.fr/assets/images/og/pourquoi-flatcms-fr-FR.webp
```

---

# 3. Hero

## Sur-titre

```text
Choisir un CMS adapté à son projet
```

## H1

```text
Pourquoi choisir FlatCMS ?
```

## Introduction

```text
FlatCMS s’adresse aux projets qui recherchent un CMS PHP lisible,
modulaire et simple à déployer, sans dépendance à un serveur de base de
données SQL pour gérer leurs contenus.
```

```text
Son architecture flat-file convient particulièrement aux sites vitrines,
blogs, documentations et projets multilingues dont les besoins restent
compatibles avec un stockage structuré par fichiers.
```

## Message de transparence

```text
FlatCMS ne cherche pas à remplacer toutes les catégories de CMS. Cette
page présente aussi les limites du modèle et les situations dans
lesquelles une autre architecture peut être préférable.
```

## CTA principal

```text
Tester FlatCMS
```

Destination :

```text
https://demo.flat-cms.fr/
```

## CTA secondaire

```text
Vérifier les prérequis
```

Destination :

```text
/fr-FR/documentation/demarrage/prerequis/
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

# 4. Section — Le problème auquel FlatCMS répond

## H2

```text
Réduire la complexité sans renoncer à une architecture claire
```

## Texte

```text
Un site éditorial ne nécessite pas toujours un serveur SQL, une pile
applicative lourde ou un vaste ensemble de dépendances pour gérer ses
pages, ses articles, ses médias et sa navigation.
```

```text
FlatCMS propose une autre approche : un runtime PHP natif, des modules
fonctionnels et un stockage JSON organisé dans une arborescence de
fichiers.
```

```text
Cette conception vise à rendre le fonctionnement du CMS plus visible,
à simplifier certains déploiements et à limiter les services nécessaires
au fonctionnement d’un site.
```

## Ce que FlatCMS cherche à simplifier

- installation et déplacement d’un site ;
- sauvegarde des contenus et configurations ;
- compréhension de l’architecture PHP ;
- séparation entre le cœur, les modules et les thèmes ;
- gestion de plusieurs langues ;
- création de sites éditoriaux sans infrastructure SQL ;
- intégration progressive de services externes.

## Ce que FlatCMS ne cherche pas à masquer

- les fichiers du projet ;
- les données éditoriales ;
- les responsabilités des modules ;
- les limites du stockage flat-file ;
- les composants commerciaux ;
- les dépendances externes ;
- la configuration du serveur.

---

# 5. Section — Les projets adaptés à FlatCMS

## H2

```text
À quels projets FlatCMS est-il destiné ?
```

## Introduction

```text
FlatCMS est particulièrement pertinent lorsque le contenu, la simplicité
de déploiement et la lisibilité technique comptent davantage qu’un très
grand écosystème d’extensions ou qu’un moteur transactionnel complexe.
```

---

## H3 — Site vitrine d’entreprise

```text
FlatCMS peut structurer les pages institutionnelles d’une entreprise,
d’un indépendant, d’une association ou d’un service : accueil, services,
réalisations, équipe, actualités et contact.
```

### Besoins couverts

- pages permanentes ;
- articles ;
- médias ;
- menus ;
- footer ;
- thèmes ;
- formulaires de contact ;
- métadonnées SEO ;
- plusieurs langues.

### Profil adapté

```text
Projet recherchant une administration claire et un déploiement PHP
simple, avec un volume éditorial maîtrisé.
```

---

## H3 — Blog éditorial

```text
Le module d’articles, les catégories, les médias et les URLs propres
permettent de publier un blog intégré au site, sans installer une
plateforme séparée.
```

### Profil adapté

- entreprise publiant régulièrement ;
- auteur ou collectif ;
- documentation éditoriale ;
- actualités d’un produit ;
- contenu multilingue.

### Point de vigilance

```text
Le rythme de publication, le nombre d’auteurs et le volume de contenus
doivent rester compatibles avec le modèle de stockage et le workflow
réellement validé dans la version utilisée.
```

---

## H3 — Documentation technique

```text
FlatCMS peut accueillir des tutoriels, des guides pratiques, des pages
de référence et des explications d’architecture dans une navigation
structurée.
```

### Avantages

- contenus proches du site produit ;
- maillage interne entre produit, blog et documentation ;
- versions linguistiques ;
- données structurées ;
- thèmes dédiés ;
- recherche interne selon l’implémentation.

### Profil adapté

```text
Projet souhaitant réunir son site officiel et sa documentation sans
dépendre d’une plateforme documentaire distincte.
```

---

## H3 — Site multilingue

```text
FlatCMS prévoit nativement les locales fr-FR, en-US, de-DE, es-ES,
it-IT et pt-PT.
```

```text
Chaque traduction peut disposer de sa propre URL, de ses métadonnées,
de ses liens internes et de son cycle de publication.
```

### Profil adapté

- marque européenne ;
- produit open source ;
- agence ;
- documentation internationale ;
- site vitrine export.

### Point de vigilance

```text
La présence de plusieurs locales dans le CMS ne remplace pas la
traduction, la relecture et la maintenance éditoriale de chaque langue.
```

---

## H3 — Projet d’agence ou d’intégrateur

```text
L’architecture modulaire, les thèmes séparés et le stockage lisible
peuvent faciliter la création de sites adaptés à différents clients.
```

### Bénéfices possibles

- base technique commune ;
- thèmes personnalisés ;
- modules fonctionnels ;
- déploiements reproductibles ;
- composants premium optionnels ;
- documentation du projet ;
- maintenance sans serveur SQL.

### Point de vigilance

```text
L’agence doit valider le modèle de licence, la maintenance, les mises à
jour et le périmètre des composants commerciaux pour chaque projet.
```

---

## H3 — Petit serveur ou environnement local

```text
Un CMS sans service SQL séparé peut convenir à un hébergement mutualisé,
un petit serveur privé, un environnement local ou certains appareils à
ressources limitées.
```

### Conditions

- version PHP compatible ;
- extensions requises ;
- permissions d’écriture ;
- document root configuré sur `public/` ;
- ressources suffisantes pour le trafic attendu ;
- sauvegardes ;
- cache et serveur correctement configurés.

### Prudence

```text
L’absence de serveur SQL ne garantit pas à elle seule de bonnes
performances. Le thème, les médias, le cache, le serveur, le trafic et
les traitements applicatifs restent déterminants.
```

---

## H3 — Projet nécessitant une base PHP compréhensible

```text
FlatCMS peut intéresser les développeurs qui souhaitent travailler avec
un cœur PHP natif, une architecture HMVC et des conventions PSR-4 sans
adopter un framework applicatif lourd.
```

### Profil adapté

- développeur PHP ;
- intégrateur souhaitant créer un module ;
- équipe voulant garder la maîtrise de son runtime ;
- projet pédagogique ou technique ;
- produit nécessitant des extensions ciblées.

---

# 6. Section — Les bénéfices du stockage flat-file

## H2

```text
Les bénéfices d’un stockage structuré par fichiers
```

## Introduction

```text
FlatCMS stocke ses données principales dans des fichiers structurés,
notamment au format JSON. Cette approche modifie la manière d’installer,
de sauvegarder et de maintenir le site.
```

---

## H3 — Moins de services à configurer

```text
Le fonctionnement normal du CMS ne nécessite pas de créer une base
MySQL ou MariaDB, un utilisateur SQL et une connexion associée.
```

Cela peut simplifier :

- la première installation ;
- les environnements locaux ;
- les migrations ;
- les petits hébergements ;
- les sauvegardes complètes.

---

## H3 — Données identifiables

```text
Les pages, articles, catégories et configurations sont enregistrés dans
des emplacements définis du projet.
```

Cette organisation peut faciliter :

- l’audit ;
- le diagnostic ;
- la sauvegarde ;
- la restauration ;
- les scripts de migration ;
- les outils d’agent ou d’automatisation, sous contrôle.

---

## H3 — Déplacement du site

```text
Un site peut être déplacé en transférant son code, ses données et ses
médias, puis en adaptant la configuration du serveur.
```

Cela ne supprime pas la nécessité de vérifier :

- les permissions ;
- les URLs ;
- le domaine ;
- les caches ;
- les secrets ;
- les tâches planifiées ;
- les e-mails ;
- les configurations Apache ou Nginx.

---

## H3 — Sauvegarde cohérente

```text
Une sauvegarde complète peut réunir le code de la version déployée, les
fichiers de données, les médias et les paramètres privés nécessaires à
la restauration.
```

## Limite

```text
Copier des fichiers pendant une écriture active peut créer une sauvegarde
incohérente si le mécanisme de sauvegarde ne gère pas correctement les
accès concurrents. La procédure officielle doit être suivie.
```

---

## H3 — Portabilité

```text
Les données ne dépendent pas d’un moteur SQL particulier. Elles restent
néanmoins liées au schéma de données, aux conventions et aux versions
de FlatCMS.
```

La portabilité n’implique pas :

- l’absence de migration de schéma ;
- une compatibilité automatique avec un autre CMS ;
- la possibilité de modifier les fichiers sans validation ;
- l’absence de règles d’intégrité.

---

# 7. Section — Architecture et Clean Code

## H2

```text
Une architecture pensée pour séparer les responsabilités
```

## Texte

```text
FlatCMS organise son runtime autour d’un cœur applicatif, de modules,
de services, de contrôleurs, de vues, de hooks et de thèmes.
```

```text
Cette séparation aide à localiser les responsabilités et à faire évoluer
une fonctionnalité sans mélanger systématiquement son code avec celui
des autres modules.
```

---

## H3 — Modules HMVC

```text
Les fonctions éditoriales et administratives sont regroupées par module :
pages, articles, médias, menus, utilisateurs, langues, thèmes ou
sauvegardes, selon la distribution utilisée.
```

---

## H3 — Autoloading PSR-4

```text
Le projet utilise les conventions PSR-4 pour organiser le chargement
des classes PHP à partir de leurs namespaces et de leurs chemins.
```

---

## H3 — Services transversaux

```text
Les traitements partagés peuvent être isolés dans des services dédiés,
par exemple pour les licences, les données structurées, les mises à jour
ou les fournisseurs d’intelligence artificielle.
```

---

## H3 — Thèmes séparés

```text
Les thèmes de l’administration et du frontend sont séparés afin de ne
pas confondre l’interface de gestion avec le rendu destiné aux visiteurs.
```

---

## H3 — Hooks et extensions

```text
Les hooks et les modules permettent d’étendre le CMS sans modifier
systématiquement le cœur stable.
```

## CTA

```text
Comprendre l’architecture FlatCMS
```

Destination :

```text
/fr-FR/architecture/
```

---

# 8. Section — Déploiement et maintenance

## H2

```text
Un déploiement simplifié, mais pas sans responsabilités
```

## Introduction

```text
Réduire le nombre de services ne supprime pas les exigences d’un site
en production.
```

Un déploiement FlatCMS doit toujours prendre en compte :

- HTTPS ;
- document root ;
- permissions ;
- secrets ;
- sauvegardes ;
- journaux ;
- cache ;
- e-mails ;
- mises à jour ;
- surveillance.

---

## H3 — Document root public

```text
Le serveur doit exposer le dossier public/ et empêcher l’accès direct
aux dossiers applicatifs et aux données.
```

---

## H3 — Permissions ciblées

```text
Le processus PHP doit pouvoir écrire uniquement dans les emplacements
qui en ont réellement besoin.
```

---

## H3 — Sauvegardes complètes

```text
Les sauvegardes doivent couvrir les données, les médias, la version du
code et les paramètres privés indispensables à la restauration.
```

---

## H3 — Mises à jour contrôlées

```text
Une mise à jour doit être testée, sauvegardée et accompagnée d’une
procédure de retour arrière.
```

---

## H3 — Hébergement compatible

```text
La simplicité du modèle flat-file ne remplace pas la vérification de la
version PHP, des extensions, des limites d’exécution et des capacités du
serveur.
```

---

# 9. Section — Multilingue et contenus structurés

## H2

```text
Une base adaptée aux sites multilingues et aux contenus structurés
```

## Texte

```text
FlatCMS associe une architecture multilingue à des contenus stockés dans
des structures identifiables.
```

```text
Cette combinaison peut faciliter la gestion des traductions, des
métadonnées locales, des relations entre versions et des données
structurées publiées en JSON-LD.
```

## Bénéfices

- URL distincte par locale ;
- contenu traduit indépendamment ;
- métadonnées localisées ;
- maillage par langue ;
- données structurées ;
- auteurs et dates ;
- liens entre versions ;
- préparation de workflows de traduction.

## Limite

```text
Le CMS fournit une structure. La qualité du multilingue dépend toujours
de la traduction, de la relecture, du suivi des mises à jour et de la
cohérence des liens.
```

---

# 10. Section — Agent-ready

## H2

```text
Une fondation compatible avec des intégrations IA contrôlées
```

## Texte

```text
Le stockage JSON, les modules et la couche de services peuvent servir de
base à des intégrations avec des fournisseurs d’intelligence artificielle.
```

```text
FlatCMS LTS Core conserve cependant un périmètre stable. Les fonctions
expérimentales, commerciales ou instables doivent rester clairement
séparées de ce cœur.
```

## Capacités visées ou disponibles selon la version

- assistance à la rédaction ;
- reformulation ;
- traduction ;
- métadonnées SEO ;
- données structurées ;
- génération de médias ;
- aide à la création de pages ;
- suivi d’usage ;
- contrôle des fournisseurs.

## Principes

- validation humaine ;
- clés privées ;
- limites d’usage ;
- journalisation ;
- permissions ;
- distinction entre disponible et prévu ;
- possibilité de désactiver l’intégration.

## CTA

```text
Découvrir l’approche agent-ready
```

Destination :

```text
/fr-FR/agent-ready/
```

---

# 11. Section — Les limites du modèle flat-file

## H2

```text
Les limites à connaître avant de choisir
```

## Introduction

```text
Le stockage par fichiers apporte de la simplicité dans certains projets,
mais il ne remplace pas automatiquement une base relationnelle dans tous
les contextes.
```

---

## H3 — Écritures concurrentes importantes

```text
Un site soumis à de nombreuses écritures simultanées peut nécessiter des
mécanismes de verrouillage, de transaction et de concurrence plus
avancés.
```

### Exemples

- très nombreux utilisateurs modifiant simultanément ;
- flux transactionnels intensifs ;
- forte activité en temps réel ;
- grand nombre d’opérations liées.

---

## H3 — Relations de données complexes

```text
Un projet reposant sur de nombreuses relations dynamiques, des requêtes
croisées complexes ou de grands volumes d’enregistrements peut bénéficier
d’un moteur de base de données dédié.
```

---

## H3 — Écosystème plus restreint

```text
FlatCMS ne dispose pas de l’écosystème historique de thèmes, extensions,
prestataires et intégrations d’une plateforme comme WordPress.
```

Cela implique parfois :

- davantage de développement spécifique ;
- moins de connecteurs prêts à l’emploi ;
- un choix de thèmes plus réduit ;
- une communauté plus jeune ;
- une documentation encore en construction.

---

## H3 — Commerce électronique avancé

```text
Un catalogue important, des stocks complexes, plusieurs entrepôts, des
règles fiscales internationales ou des flux de paiement avancés peuvent
nécessiter une plateforme spécialisée.
```

---

## H3 — Recherche et indexation volumineuses

```text
Un très grand corpus peut nécessiter un moteur de recherche spécialisé,
un index externe ou une architecture de données différente.
```

---

## H3 — Hébergement et permissions

```text
Le modèle par fichiers dépend fortement de permissions correctement
configurées. Un hébergement qui empêche PHP d’écrire dans les dossiers
nécessaires peut bloquer l’installation ou l’administration.
```

---

## H3 — Maintenance des traductions

```text
Six locales multiplient le volume de travail éditorial. Une version
traduite doit être mise à jour lorsque la source évolue.
```

---

# 12. Section — Dans quels cas choisir une autre solution ?

## H2

```text
Quand une autre architecture peut être préférable
```

## Texte

```text
Choisir un CMS consiste à rapprocher les besoins du projet des forces
réelles de la solution.
```

Une autre plateforme peut être préférable si le projet exige
prioritairement :

- un écosystème très vaste d’extensions prêtes à l’emploi ;
- un grand nombre d’intégrations commerciales existantes ;
- un commerce électronique complexe ;
- un workflow éditorial d’entreprise très avancé ;
- des milliers d’utilisateurs simultanés en écriture ;
- des relations de données et requêtes complexes ;
- un service managé complet sans administration serveur ;
- un prestataire certifié disponible dans de nombreux pays ;
- un système de publication headless à grande échelle déjà industrialisé ;
- des fonctions métier qui existent déjà dans une plateforme spécialisée.

## Message de confiance

```text
Reconnaître ces limites ne diminue pas l’intérêt de FlatCMS. Cela permet
de l’utiliser dans les projets pour lesquels sa simplicité et son
architecture apportent une valeur réelle.
```

---

# 13. Section — Comparaison des approches

## H2

```text
FlatCMS, CMS généraliste ou plateforme spécialisée ?
```

## Tableau

| Besoin | FlatCMS | CMS généraliste établi | Plateforme spécialisée |
|---|---|---|---|
| Site vitrine | Très adapté | Très adapté | Souvent excessif |
| Blog standard | Adapté | Très adapté | Variable |
| Documentation | Adapté | Adapté avec extensions | Très adapté selon outil |
| Serveur SQL requis | Non pour le Core | Généralement oui | Variable |
| Architecture PHP lisible | Objectif central | Variable | Variable |
| Écosystème d’extensions | En développement | Très vaste | Ciblé |
| Commerce avancé | Non prioritaire | Possible avec extensions | Très adapté |
| Relations de données complexes | Limitées par le modèle | Plus adaptées | Très adaptées |
| Multilingue | Socle natif FlatCMS | Natif ou extensions selon produit | Variable |
| Builders visuels | Composants premium | Nombreuses options | Variable |
| Personnalisation sur mesure | Modules et thèmes | Extensions, thèmes et code | Selon plateforme |
| Déploiement sans SQL | Oui | Rare | Variable |

## Note

```text
Ce tableau compare des catégories générales. Les capacités exactes
dépendent de la version, de la configuration, des extensions et de
l’hébergement de chaque solution.
```

---

# 14. Section — Tableau d’aide à la décision

## H2

```text
FlatCMS correspond-il à votre projet ?
```

| Question | Oui | Non / incertain |
|---|---|---|
| Votre site est principalement éditorial ? | FlatCMS peut convenir | Étudier le besoin métier |
| Vous souhaitez éviter un serveur SQL ? | Point fort de FlatCMS | Un CMS SQL reste possible |
| Le volume d’écriture simultanée est modéré ? | Modèle compatible | Tester ou choisir une base dédiée |
| Vous souhaitez une architecture PHP native ? | Point fort | Une autre stack peut convenir |
| Vous avez besoin de plusieurs langues ? | Socle prévu | Le besoin reste simple |
| Vous acceptez un écosystème encore jeune ? | Continuer l’évaluation | Choisir un acteur plus établi |
| Vous avez besoin d’un commerce avancé ? | Intégration spécifique à étudier | Plateforme spécialisée recommandée |
| Vous voulez créer des modules sur mesure ? | Architecture adaptée | Privilégier une solution clé en main |
| Vous disposez d’un hébergement PHP configurable ? | Déploiement possible | Vérifier les contraintes |
| Vous pouvez maintenir les sauvegardes et mises à jour ? | Projet viable | Choisir un service managé |

---

# 15. Section — Profils d’utilisateurs

## H2

```text
À qui s’adresse FlatCMS ?
```

## H3 — Créateur de site autonome

```text
Vous recherchez une administration pour gérer vos pages, articles,
médias et menus sans maintenir une base SQL.
```

## H3 — Développeur PHP

```text
Vous souhaitez comprendre le runtime, créer un module et travailler avec
une organisation HMVC et PSR-4.
```

## H3 — Agence web

```text
Vous voulez construire des thèmes et réutiliser une base commune pour
des sites vitrines ou éditoriaux.
```

## H3 — Responsable de contenu

```text
Vous avez besoin d’une interface pour publier, organiser des médias et
gérer plusieurs langues.
```

## H3 — Porteur de projet open source

```text
Vous souhaitez un cœur documenté, une licence identifiable et une
architecture extensible.
```

## H3 — Exploitant de petit serveur

```text
Vous souhaitez limiter les services nécessaires tout en conservant les
responsabilités de sécurité, de cache et de sauvegarde.
```

---

# 16. Section — Critères techniques à vérifier

## H2

```text
Les points à vérifier avant l’installation
```

## Liste

- version PHP prise en charge ;
- extensions PHP requises ;
- accès à la configuration Apache ou Nginx ;
- document root configurable sur `public/` ;
- permissions d’écriture ;
- HTTPS ;
- envoi d’e-mails ;
- espace disque ;
- sauvegardes ;
- tâches planifiées si utilisées ;
- compatibilité des modules nécessaires ;
- licence des composants premium ;
- besoins de performance ;
- stratégie de mise à jour ;
- compétences disponibles pour la maintenance.

## CTA

```text
Consulter les prérequis
```

Destination :

```text
/fr-FR/documentation/demarrage/prerequis/
```

---

# 17. Section — Comment évaluer FlatCMS ?

## H2

```text
Évaluer FlatCMS avant de l’adopter
```

## Étape 1 — Définir le besoin

```text
Listez les types de contenus, les utilisateurs, les langues, les
intégrations, le trafic et les opérations d’écriture attendues.
```

## Étape 2 — Consulter les fonctionnalités

```text
Vérifiez que chaque fonction nécessaire est disponible dans le Core,
un module optionnel ou un composant premium.
```

## Étape 3 — Tester la démonstration

```text
Parcourez les modules d’administration et vérifiez que le workflow
correspond à votre équipe.
```

## Étape 4 — Installer en local

```text
Testez la version LTS dans un environnement représentatif de votre
hébergement.
```

## Étape 5 — Créer un prototype

```text
Construisez quelques pages, un article, un menu, un formulaire et une
traduction avant de prendre une décision.
```

## Étape 6 — Tester les contraintes

```text
Mesurez les performances, les sauvegardes, les permissions, les mises à
jour et les intégrations nécessaires.
```

## Étape 7 — Valider la maintenance

```text
Déterminez qui assurera les mises à jour, la sécurité, les contenus et
les traductions.
```

---

# 18. Section — Réponses courtes

## H2

```text
En bref
```

### H3 — FlatCMS est-il adapté à tous les sites ?

```text
Non. Il est principalement adapté aux projets éditoriaux dont les
besoins restent compatibles avec un stockage flat-file.
```

### H3 — FlatCMS est-il plus simple que tous les autres CMS ?

```text
Il réduit certaines dépendances, notamment le serveur SQL, mais la
simplicité réelle dépend du projet, du thème, des modules et de
l’hébergement.
```

### H3 — FlatCMS est-il plus rapide ?

```text
Son architecture peut limiter certains coûts liés à l’infrastructure,
mais toute affirmation de performance doit être vérifiée par des tests
reproductibles dans l’environnement concerné.
```

### H3 — FlatCMS est-il sécurisé ?

```text
FlatCMS intègre des mécanismes applicatifs et recommande un document root
public. La sécurité dépend également de la configuration, des permissions,
des mises à jour et de l’exploitation du serveur.
```

### H3 — FlatCMS convient-il à une agence ?

```text
Oui pour des projets compatibles avec son périmètre, à condition de
valider les licences, les workflows, les modules et la maintenance.
```

### H3 — FlatCMS convient-il à un grand site transactionnel ?

```text
Pas nécessairement. Un volume élevé d’écritures concurrentes ou des
relations de données complexes peuvent justifier une architecture
spécialisée avec base de données.
```

---

# 19. CTA final

## H2

```text
Vérifiez FlatCMS sur un projet réel
```

## Texte

```text
La meilleure façon d’évaluer un CMS consiste à le confronter à vos
contenus, votre hébergement et vos contraintes de maintenance.
```

## CTA principal

```text
Tester la démo
```

Destination :

```text
https://demo.flat-cms.fr/
```

## CTA secondaire

```text
Télécharger FlatCMS
```

Destination :

```text
/fr-FR/telechargement/
```

## Lien tertiaire

```text
Lire le guide d’installation
```

Destination :

```text
/fr-FR/documentation/installation/
```

---

# 20. Maillage interne attendu

| Section | Destination |
|---|---|
| Hero | Démo, prérequis, architecture |
| Projets adaptés | Pages de cas d’usage |
| Stockage flat-file | Architecture stockage JSON |
| Clean Code | Architecture, HMVC, PSR-4 |
| Déploiement | Installation, sécurité |
| Multilingue | Fonctionnalité multilingue |
| Agent-ready | Page Agent-ready |
| Limites | Comparatifs |
| Autre solution | Comparatifs WordPress et Grav |
| Critères | Prérequis |
| Évaluation | Démo, téléchargement, installation |
| CTA final | Démo, téléchargement, documentation |

---

# 21. Médias à produire

## Image Open Graph

Concept :

```text
Deux chemins de décision autour du logo FlatCMS :
« projet éditorial et modulaire » / « besoins transactionnels complexes »
```

Le visuel doit rester positif et ne pas représenter une solution
concurrente de manière dévalorisante.

## Illustration principale

Un diagramme simple :

```text
Votre besoin
→ contenus
→ langues
→ volume d’écriture
→ intégrations
→ hébergement
→ choix du CMS
```

## Tableau de décision

Prévoir un rendu responsive en HTML.

## Icônes

- site vitrine ;
- blog ;
- documentation ;
- multilingue ;
- agence ;
- serveur ;
- développement ;
- commerce ;
- base de données.

---

# 22. Textes alternatifs suggérés

## Diagramme de décision

```text
Critères permettant de déterminer si FlatCMS correspond à un projet web
```

## Cas d’usage

```text
Exemples de projets adaptés à FlatCMS : site vitrine, blog et documentation
```

## Limites

```text
Situations pouvant nécessiter une base de données ou une plateforme spécialisée
```

Les alternatives finales doivent correspondre aux médias réellement produits.

---

# 23. Données structurées attendues

```text
WebPage
BreadcrumbList
ImageObject
```

## Identifiants

```text
https://flat-cms.fr/fr-FR/pourquoi-flatcms/#webpage
https://flat-cms.fr/fr-FR/pourquoi-flatcms/#breadcrumb
https://flat-cms.fr/fr-FR/pourquoi-flatcms/#primaryimage
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

Aucun balisage `Review` ou `AggregateRating` ne doit être utilisé.

---

# 24. Composants du thème suggérés

```text
HeroDecision
UseCaseGrid
BenefitCards
ArchitectureSummary
LimitationsPanel
DecisionTable
AudienceCards
PrerequisitesChecklist
EvaluationSteps
FaqAccordion
CallToActionBanner
```

Les noms doivent être adaptés aux widgets réellement disponibles.

---

# 25. Éléments à confirmer avant intégration

- compatibilité PHP exacte ;
- liste des modules du Core distribué ;
- statut des commentaires et du module Contact ;
- fonctions de sauvegarde disponibles dans la distribution ;
- fonctionnement précis de la corbeille ;
- périmètre du module AiAgent ;
- disponibilité des builders ;
- tarifs ;
- environnement de démonstration ;
- URL GitHub ;
- modèle de support ;
- benchmarks éventuels.

---

# 26. Checklist éditoriale

- [ ] La page aide réellement à prendre une décision.
- [ ] Les cas d’usage sont précis.
- [ ] Les limites sont visibles et détaillées.
- [ ] Le modèle flat-file n’est pas présenté comme universel.
- [ ] Aucune performance n’est affirmée sans test.
- [ ] La sécurité n’est pas présentée comme absolue.
- [ ] L’écosystème plus jeune est reconnu.
- [ ] Le commerce avancé est traité honnêtement.
- [ ] Les composants premium sont distingués.
- [ ] Les fonctions IA disponibles et prévues sont séparées.
- [ ] Les CTA permettent de tester avant de choisir.
- [ ] Les concurrents ne sont pas dénigrés.
- [ ] Les tableaux restent lisibles sur mobile.
- [ ] Les liens internes sont cohérents.
- [ ] Les termes correspondent à FlatCMS v1.0.0.

---

# 27. Checklist d’intégration

- [ ] URL correcte.
- [ ] Canonique auto-référencée.
- [ ] `<html lang="fr-FR">`.
- [ ] Groupe `hreflang`.
- [ ] Title.
- [ ] Meta description.
- [ ] Open Graph.
- [ ] H1 unique.
- [ ] Fil d’Ariane.
- [ ] Liens HTML explorables.
- [ ] Images responsive.
- [ ] Textes alternatifs.
- [ ] Tableau responsive.
- [ ] JSON-LD.
- [ ] Sitemap.
- [ ] Directive robots.
- [ ] Test mobile.
- [ ] Test clavier.
- [ ] Test des liens.
- [ ] Test HTTP `200`.

---

# 28. Sources internes

- `README.md`
- `VERSION`
- `flatcms.json`
- `LICENSING.md`
- `COMMERCIAL_LICENSE.md`
- `TRADEMARK.md`
- `CLA.md`
- architecture `app/`
- stockage `data/`
- thèmes `themes/`
- modules réellement distribués
- documentation d’installation
- tests de la version LTS

---

# 29. Références externes

- Google Search Central — Creating helpful, reliable, people-first content  
  https://developers.google.com/search/docs/fundamentals/creating-helpful-content

- PHP-FIG — PSR-4  
  https://www.php-fig.org/psr/psr-4/

- WordPress — Features  
  https://wordpress.org/about/features/

- Grav — Features  
  https://getgrav.org/features

Les sources concurrentes servent uniquement à vérifier leurs propres
caractéristiques lors de la rédaction des futurs comparatifs.

---

# 30. Journal des mises à jour

| Date | Version | Modification | Auteur |
|---|---:|---|---|
| 2026-06-08 | 1.0 | Première rédaction complète de la page Pourquoi FlatCMS | ChatGPT / Alain BROYE |

---

# 31. Prochaine action

Après validation et ajout dans le Drive :

```text
Créer FEATURES_CONTENT.md
```

Ce document contiendra la rédaction complète de la page :

```text
/fr-FR/fonctionnalites/
```

Il devra présenter les fonctions réellement disponibles, leur statut
Core, optionnel, premium, expérimental ou prévu, ainsi que les liens vers
leur documentation.

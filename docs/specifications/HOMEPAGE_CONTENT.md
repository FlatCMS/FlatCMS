# HOMEPAGE_CONTENT — Contenu de la page d’accueil officielle FlatCMS

> **Contenu éditorial prêt à intégrer**
>
> Projet : FlatCMS  
> Page : Accueil `fr-FR`  
> URL cible : `https://flat-cms.fr/fr-FR/`  
> Version de référence : FlatCMS v1.0.0 LTS Core  
> Date de création : 8 juin 2026  
> Brief parent : `PAGE_BRIEFS.md` — `P0-HOME-FR`  
> Statut : première version rédactionnelle à relire, illustrer et intégrer

---

## 1. Objectif de la page

La page d’accueil doit permettre à un nouveau visiteur de comprendre rapidement :

- ce qu’est FlatCMS ;
- ce qui le différencie ;
- les projets auxquels il est adapté ;
- les fonctionnalités déjà disponibles ;
- son architecture technique ;
- la distinction entre le Core open source et les composants premium ;
- comment le tester, le télécharger ou consulter sa documentation.

La page doit rester compréhensible pour un décideur non technique tout en apportant suffisamment de preuves aux développeurs et intégrateurs.

---

# 2. Métadonnées SEO

## URL canonique

```text
https://flat-cms.fr/fr-FR/
```

## Balise `<title>`

```text
FlatCMS — CMS PHP open source sans base de données
```

## Meta description

```text
Découvrez FlatCMS, un CMS flat-file en PHP natif avec architecture HMVC,
autoloading PSR-4, stockage JSON, modules et gestion multilingue.
```

## Directive robots

```text
index, follow, max-image-preview:large
```

## Open Graph

### `og:title`

```text
FlatCMS — Le CMS PHP open source sans base de données
```

### `og:description`

```text
Créez et administrez des sites modernes avec un CMS PHP natif,
modulaire et multilingue, sans dépendance à un serveur SQL.
```

### `og:type`

```text
website
```

### `og:url`

```text
https://flat-cms.fr/fr-FR/
```

### `og:image`

```text
https://flat-cms.fr/assets/images/og/flatcms-home-fr-FR.webp
```

## Twitter Card

```text
summary_large_image
```

---

# 3. Hero

## Sur-titre

```text
FlatCMS v1.0.0 LTS Core
```

## H1

```text
FlatCMS, le CMS PHP open source sans base de données
```

## Introduction

```text
Créez, administrez et déployez des sites modernes avec un CMS flat-file
en PHP natif, fondé sur une architecture HMVC, l’autoloading PSR-4 et
un stockage JSON structuré.
```

## Complément

```text
FlatCMS réunit les fonctions essentielles d’un CMS dans un cœur stable,
modulaire et lisible, sans dépendre d’un serveur MySQL ou MariaDB pour
gérer les contenus du site.
```

## CTA principal

```text
Télécharger FlatCMS
```

Destination :

```text
/fr-FR/telechargement/
```

## CTA secondaire

```text
Tester la démo
```

Destination :

```text
https://demo.flat-cms.fr/
```

## Lien tertiaire

```text
Consulter la documentation
```

Destination :

```text
/fr-FR/documentation/
```

## Réassurance courte

```text
PHP 8.3+ · HMVC · PSR-4 · JSON · Multilingue · Open source
```

## Média conseillé

Une composition montrant :

- l’administration réelle de FlatCMS ;
- l’éditeur de pages ou le tableau de bord ;
- une vue frontend ;
- une représentation discrète de la structure JSON.

Le média ne doit pas remplacer l’explication textuelle.

---

# 4. Section — Un CMS sans serveur SQL

## H2

```text
Un CMS sans serveur SQL, simple à déployer
```

## Texte

```text
FlatCMS stocke ses pages, articles, catégories, utilisateurs et
configurations dans des fichiers structurés. Le site n’a donc pas besoin
d’un serveur MySQL ou MariaDB pour fonctionner dans son périmètre normal.
```

```text
Cette architecture réduit le nombre de services à installer, simplifie
les sauvegardes et facilite le déplacement d’un site entre un
environnement local, un hébergement mutualisé ou un serveur privé.
```

```text
Le code, les données et les médias restent clairement séparés. En
production, le serveur web doit exposer uniquement le dossier public/,
afin que les fichiers applicatifs et les données ne soient pas accessibles
directement depuis le Web.
```

## Trois bénéfices

### H3 — Déploiement allégé

```text
Téléchargez l’archive, configurez PHP et le document root, puis lancez
l’installateur. Aucun serveur SQL supplémentaire n’est requis.
```

### H3 — Sauvegardes lisibles

```text
Les contenus et configurations sont stockés dans une arborescence de
fichiers qui peut être sauvegardée avec les médias et le code du site.
```

### H3 — Architecture transparente

```text
Les données et composants du CMS restent identifiables dans le projet,
sans couche opaque imposée par un framework lourd.
```

## CTA contextuel

```text
Comprendre le stockage JSON de FlatCMS
```

Destination :

```text
/fr-FR/architecture/stockage-json/
```

---

# 5. Section — Fonctionnalités essentielles

## H2

```text
Les fonctions essentielles pour gérer un site
```

## Introduction

```text
FlatCMS LTS Core se concentre sur un périmètre stable : administrer les
contenus, les médias, la navigation, les utilisateurs et l’apparence du
site depuis une interface modulaire.
```

## Grille de fonctionnalités

### H3 — Pages

```text
Créez, modifiez et organisez les pages permanentes de votre site avec
des URLs propres et des métadonnées adaptées à chaque contenu.
```

Lien :

```text
/fr-FR/fonctionnalites/pages/
```

### H3 — Articles et catégories

```text
Publiez un blog, classez les articles par catégorie et gérez leur cycle
de publication depuis l’administration.
```

Lien :

```text
/fr-FR/fonctionnalites/articles/
```

### H3 — Médias

```text
Centralisez les images et fichiers utilisés par vos pages, vos articles
et vos thèmes dans une médiathèque dédiée.
```

Lien :

```text
/fr-FR/fonctionnalites/medias/
```

### H3 — Menus et footer

```text
Construisez la navigation principale et organisez les informations du
pied de page sans modifier directement les templates du thème.
```

Lien :

```text
/fr-FR/fonctionnalites/menus/
```

### H3 — Utilisateurs et authentification

```text
Gérez les comptes, les rôles et les accès aux fonctions d’administration
selon les responsabilités de chaque utilisateur.
```

Lien :

```text
/fr-FR/fonctionnalites/utilisateurs/
```

### H3 — Thèmes

```text
Séparez clairement le thème du frontend et celui de l’administration
pour faire évoluer l’apparence du site sans mélanger les responsabilités.
```

Lien :

```text
/fr-FR/fonctionnalites/themes/
```

### H3 — Multilingue

```text
Organisez les contenus et l’interface dans les locales natives de
FlatCMS : français, anglais, allemand, espagnol, italien et portugais.
```

Lien :

```text
/fr-FR/fonctionnalites/multilingue/
```

### H3 — Sauvegardes et corbeille

```text
Protégez le travail éditorial avec des fonctions de sauvegarde, de
restauration et de récupération des contenus supprimés selon les modules
activés.
```

Lien :

```text
/fr-FR/fonctionnalites/sauvegardes/
```

## CTA

```text
Découvrir toutes les fonctionnalités
```

Destination :

```text
/fr-FR/fonctionnalites/
```

---

# 6. Section — Architecture

## H2

```text
Une architecture PHP native, modulaire et compréhensible
```

## Introduction

```text
FlatCMS repose sur un cœur PHP natif et une organisation modulaire.
L’objectif n’est pas de masquer le fonctionnement du CMS, mais de proposer
une base stable, lisible et extensible.
```

## H3 — Architecture HMVC

```text
Les fonctionnalités sont organisées en modules. Chaque module peut
regrouper ses contrôleurs, ses vues, ses services, ses routes et ses
ressources autour d’un périmètre fonctionnel clairement défini.
```

## H3 — Autoloading PSR-4

```text
Les namespaces et chemins de classes suivent les conventions
d’autoloading PSR-4 utilisées par le projet, afin de conserver une
organisation cohérente du code PHP.
```

## H3 — Cœur applicatif

```text
Le runtime s’appuie notamment sur l’application, le routeur, la requête,
la réponse, les contrôleurs, les vues, les modules, les hooks et les
services.
```

## H3 — Services indépendants

```text
Les traitements transversaux sont isolés dans des services dédiés,
notamment pour les licences, les données structurées, les mises à jour
et les intégrations d’intelligence artificielle.
```

## H3 — Extensions sans modification du cœur

```text
Les modules et les hooks permettent d’ajouter des comportements sans
mélanger systématiquement les fonctionnalités avec les fondations du CMS.
```

## Schéma textuel

```text
Requête HTTP
→ Bootstrap
→ Application
→ Routeur
→ Module et contrôleur
→ Services et stockage
→ Vue
→ Réponse HTTP
```

## CTA principal

```text
Explorer l’architecture de FlatCMS
```

Destination :

```text
/fr-FR/architecture/
```

## CTA secondaire

```text
Comprendre HMVC
```

Destination :

```text
/fr-FR/architecture/hmvc/
```

---

# 7. Section — Multilingue

## H2

```text
Un socle multilingue prévu dès la conception
```

## Texte

```text
FlatCMS prend en charge six locales natives : fr-FR, en-US, de-DE,
es-ES, it-IT et pt-PT.
```

```text
Chaque version linguistique peut disposer de sa propre URL, de ses
métadonnées, de ses liens internes et de ses contenus traduits. Cette
organisation permet de construire un site international sans servir
plusieurs langues différentes sous une même adresse.
```

```text
Le futur site officiel de FlatCMS appliquera lui-même cette architecture
avec des canonicals locales, des balises hreflang réciproques et un
sélecteur de langue accessible.
```

## Liste des langues visible

```text
Français · English · Deutsch · Español · Italiano · Português
```

## CTA

```text
Découvrir la gestion multilingue
```

Destination :

```text
/fr-FR/fonctionnalites/multilingue/
```

---

# 8. Section — Builders premium

## H2

```text
Des builders visuels pour aller plus loin
```

## Introduction

```text
Le cœur open source de FlatCMS fournit les fondations du CMS. Des
composants premium peuvent compléter cette base avec des outils visuels
destinés à accélérer la construction du site.
```

## H3 — PagesBuilder

```text
Composez des pages à partir de sections et de widgets réutilisables,
sans modifier manuellement chaque template.
```

## H3 — MenuBuilder

```text
Concevez des menus avancés et organisez leur structure depuis une
interface dédiée.
```

## H3 — FooterBuilder

```text
Construisez un footer modulaire cohérent avec le thème et les contenus
du site.
```

## Note de transparence

```text
Les builders sont des composants commerciaux distincts du cœur open
source. Leur périmètre, leur licence et leur prix sont présentés
séparément.
```

## CTA principal

```text
Découvrir les builders
```

Destination :

```text
/fr-FR/fonctionnalites/builders/
```

## CTA secondaire

```text
Voir les tarifs
```

Destination :

```text
/fr-FR/tarifs/
```

---

# 9. Section — Sécurité

## H2

```text
Une séparation claire entre le Web, le code et les données
```

## Texte

```text
La configuration recommandée expose uniquement le dossier public/ au
serveur web. Les dossiers applicatifs, les configurations, les données
et les fichiers d’exécution restent en dehors du document root public.
```

```text
FlatCMS intègre des fondations pour l’authentification, les sessions,
les rôles et les contrôles d’accès. La sécurité finale dépend également
de la configuration du serveur, des permissions, des mises à jour et des
bonnes pratiques de déploiement.
```

## H3 — Document root public

```text
Le serveur Apache ou Nginx doit pointer vers public/, et non vers la
racine complète du projet.
```

## H3 — Permissions limitées

```text
Seuls les dossiers qui nécessitent une écriture doivent être accessibles
au processus PHP.
```

## H3 — Secrets hors du contenu public

```text
Les clés, jetons et paramètres sensibles ne doivent jamais être
enregistrés dans une page, un fichier public ou un dépôt accessible.
```

## CTA

```text
Consulter les recommandations de sécurité
```

Destination :

```text
/fr-FR/architecture/securite/
```

---

# 10. Section — SEO, GEO et données structurées

## H2

```text
Des contenus structurés pour le Web et les moteurs génératifs
```

## Texte

```text
FlatCMS permet d’organiser les contenus avec des URLs propres, des
métadonnées, un maillage interne, plusieurs locales et des données
structurées en JSON-LD.
```

```text
Ces fondations facilitent l’exploration et la compréhension des pages
par les moteurs de recherche. Elles peuvent également aider les systèmes
génératifs à identifier les entités, les auteurs, les sujets et les
relations entre les contenus.
```

```text
Aucun CMS ne peut garantir une position, un résultat enrichi ou une
citation par une intelligence artificielle. FlatCMS fournit une base
technique ; la visibilité dépend ensuite de la qualité, de la fiabilité,
de l’accessibilité et de l’autorité des contenus publiés.
```

## H3 — Données structurées

```text
Le service StructuredData de FlatCMS construit un graphe JSON-LD à
partir de fournisseurs dédiés au site, aux pages et aux articles.
```

## H3 — Contenu textuel accessible

```text
Les informations essentielles restent présentes dans le HTML et ne
dépendent pas uniquement d’une animation ou d’un composant visuel.
```

## H3 — Maillage et langues

```text
Les pages peuvent être reliées par sujet, type de contenu et locale afin
de former un corpus cohérent.
```

## CTA principal

```text
Découvrir les données structurées
```

Destination :

```text
/fr-FR/architecture/donnees-structurees/
```

## CTA secondaire

```text
Comprendre SEO, GEO et GIO
```

Destination :

```text
/fr-FR/blog/seo-geo-gio/
```

---

# 11. Section — Agent-ready

## H2

```text
Une architecture pensée pour les futurs agents IA
```

## Texte

```text
FlatCMS est conçu pour séparer les contenus, les services et les
fonctionnalités. Son stockage JSON, son architecture modulaire et sa
couche de services facilitent la création d’intégrations contrôlées avec
des fournisseurs d’intelligence artificielle.
```

```text
Le projet contient un socle de services IA et un module AiAgent. Les
capacités disponibles, optionnelles, premium ou encore prévues doivent
être distinguées clairement dans chaque version.
```

## Cas d’usage visés

- assistance à la rédaction ;
- amélioration de contenus ;
- traduction ;
- génération de métadonnées ;
- création assistée de structures de pages ;
- génération de médias ;
- suivi d’usage et limites.

## Contrôle humain

```text
L’intelligence artificielle assiste l’utilisateur. Elle ne remplace ni
la validation humaine, ni la sécurité, ni la vérification des faits.
```

## CTA

```text
Découvrir l’approche agent-ready
```

Destination :

```text
/fr-FR/agent-ready/
```

---

# 12. Section — Cas d’usage

## H2

```text
À quels projets FlatCMS est-il adapté ?
```

## H3 — Sites vitrines

```text
Présentez une entreprise, une activité, une association ou un projet
avec des pages, un blog, des médias, un menu et un formulaire de contact.
```

## H3 — Blogs et contenus éditoriaux

```text
Publiez des articles structurés, classés par catégorie et disponibles
dans plusieurs langues.
```

## H3 — Documentation

```text
Organisez des tutoriels, guides, références et explications dans une
architecture claire et navigable.
```

## H3 — Agences et intégrateurs

```text
Déployez un cœur PHP lisible, personnalisez les thèmes et complétez le
projet avec des modules ou des builders selon les besoins du client.
```

## H3 — Petits serveurs et environnements locaux

```text
Réduisez les dépendances d’infrastructure grâce à un runtime PHP et un
stockage par fichiers.
```

## Limite honnête

```text
FlatCMS n’a pas vocation à remplacer toutes les architectures. Un projet
transactionnel complexe, un très grand catalogue ou des besoins
relationnels intensifs peuvent nécessiter une solution reposant sur une
base de données et un écosystème différent.
```

## CTA

```text
Vérifier si FlatCMS correspond à votre projet
```

Destination :

```text
/fr-FR/pourquoi-flatcms/
```

---

# 13. Section — Comparaison

## H2

```text
Choisir un CMS adapté à son projet
```

## Texte

```text
WordPress, Grav et les autres CMS répondent à des besoins différents.
FlatCMS privilégie un cœur PHP natif, une architecture modulaire, un
stockage JSON et une dépendance réduite à l’infrastructure.
```

```text
Les comparatifs du site officiel présenteront les versions étudiées,
les critères, les avantages et les limites de chaque solution, sans
transformer les différences techniques en classement artificiel.
```

## Liens

```text
FlatCMS vs WordPress
```

Destination :

```text
/fr-FR/comparatifs/flatcms-vs-wordpress/
```

```text
FlatCMS vs Grav
```

Destination :

```text
/fr-FR/comparatifs/flatcms-vs-grav/
```

```text
Comprendre les CMS sans base de données
```

Destination :

```text
/fr-FR/comparatifs/cms-sans-base-de-donnees/
```

---

# 14. Section — Preuves et confiance

## H2

```text
Un projet documenté, versionné et vérifiable
```

## Texte

```text
FlatCMS publie son architecture, sa documentation, ses licences et ses
notes de version afin que les utilisateurs puissent vérifier le
périmètre réel du produit.
```

## Éléments de confiance

### Version stable

```text
FlatCMS v1.0.0 LTS Core
```

### Code source

```text
Dépôt GitHub officiel
```

### Documentation

```text
Installation, architecture, administration et développement
```

### Licences

```text
Core open source, composants commerciaux distincts et règles de marque
documentées
```

### Démonstration

```text
Environnement de test remis à zéro régulièrement
```

## CTA

```text
À propos de FlatCMS
```

Destination :

```text
/fr-FR/a-propos/
```

---

# 15. Section — Derniers contenus

## H2

```text
Guides, analyses et actualités FlatCMS
```

## Texte d’introduction

```text
Le blog officiel approfondit l’architecture du CMS, les pratiques de
développement, le référencement, l’intelligence artificielle et les
évolutions du projet.
```

## Contenus à mettre en avant

- Pourquoi FlatCMS a été conçu nativement agent-ready ;
- GIO — Generative Indexing Optimization ;
- FlatCMS vs WordPress ;
- Architecture HMVC et PSR-4 ;
- Installer FlatCMS en local ;
- Notes de version.

## Règle

Afficher trois à six contenus récents ou prioritaires, avec :

- titre ;
- résumé ;
- date ;
- catégorie ;
- image ;
- lien descriptif.

## CTA

```text
Consulter le blog FlatCMS
```

Destination :

```text
/fr-FR/blog/
```

---

# 16. Section — Commencer

## H2

```text
Commencer avec FlatCMS
```

## Option 1 — Télécharger

### Titre

```text
Installer FlatCMS
```

### Texte

```text
Téléchargez la version LTS, vérifiez les prérequis et suivez le guide
d’installation correspondant à votre serveur.
```

### CTA

```text
Télécharger FlatCMS
```

---

## Option 2 — Tester

### Titre

```text
Explorer la démo
```

### Texte

```text
Connectez-vous à un environnement de démonstration pour découvrir
l’administration, les contenus, les thèmes et les modules disponibles.
```

### CTA

```text
Ouvrir la démo
```

---

## Option 3 — Comprendre

### Titre

```text
Lire la documentation
```

### Texte

```text
Parcourez les guides de démarrage, l’architecture, les tutoriels et la
référence développeur.
```

### CTA

```text
Consulter la documentation
```

---

# 17. Questions fréquentes éditoriales

> Ces questions-réponses sont destinées aux visiteurs. Elles ne doivent
> pas déclencher automatiquement un balisage `FAQPage`.

## H2

```text
Questions fréquentes
```

### H3 — FlatCMS a-t-il besoin de MySQL ?

```text
Non. FlatCMS stocke ses contenus et configurations principales dans des
fichiers structurés, sans dépendre d’un serveur MySQL ou MariaDB.
```

### H3 — FlatCMS est-il open source ?

```text
Le cœur FlatCMS LTS Core est destiné à être distribué sous une licence
open source. Certains composants de la gamme, notamment des builders,
peuvent être proposés sous licence commerciale distincte.
```

### H3 — Quelle version de PHP faut-il utiliser ?

```text
Le dépôt LTS Core annonce PHP 8.3 ou une version ultérieure compatible.
La page de téléchargement et la documentation doivent préciser les
versions effectivement testées pour chaque distribution.
```

### H3 — FlatCMS peut-il fonctionner sur Apache et Nginx ?

```text
Oui, à condition de configurer correctement le document root sur
public/, les réécritures d’URL, PHP et les permissions nécessaires.
```

### H3 — FlatCMS est-il multilingue ?

```text
Oui. Le projet prévoit nativement les locales fr-FR, en-US, de-DE,
es-ES, it-IT et pt-PT.
```

### H3 — Les builders sont-ils inclus dans le Core ?

```text
Non. Le cœur open source et les composants commerciaux doivent rester
clairement distingués. Les pages de fonctionnalités et de tarifs
précisent le statut de chaque composant.
```

### H3 — FlatCMS garantit-il une meilleure position dans Google ou les moteurs IA ?

```text
Non. FlatCMS fournit des fondations techniques pour créer des contenus
accessibles, structurés et multilingues. Le classement et la citation
dépendent de nombreux facteurs externes, notamment la qualité, la
pertinence et l’autorité des contenus.
```

### H3 — Où puis-je tester FlatCMS ?

```text
La démonstration officielle est disponible sur demo.flat-cms.fr. Elle
utilise des comptes limités et peut être remise à zéro régulièrement.
```

---

# 18. CTA final

## H2

```text
Un CMS plus simple à comprendre, à déployer et à faire évoluer
```

## Texte

```text
Découvrez un cœur PHP natif conçu autour de modules, de contenus
structurés et d’une administration multilingue, sans dépendance à un
serveur SQL.
```

## CTA principal

```text
Télécharger FlatCMS
```

## CTA secondaire

```text
Tester la démo
```

## Lien tertiaire

```text
Explorer l’architecture
```

---

# 19. Footer — liens prioritaires

## Produit

- Pourquoi FlatCMS
- Fonctionnalités
- Architecture
- Agent-ready
- Roadmap

## Ressources

- Documentation
- Blog
- Démo
- Téléchargement
- GitHub

## Écosystème

- Modules
- Thèmes
- Builders
- Tarifs
- Licences

## Projet

- À propos
- Contact
- Contribuer
- Mentions légales
- Confidentialité

---

# 20. Maillage interne attendu

| Section source | Destination |
|---|---|
| Hero | Téléchargement, démo, documentation |
| Sans SQL | Stockage JSON |
| Fonctionnalités | Pages fonctionnalités |
| Architecture | Architecture, HMVC, PSR-4 |
| Multilingue | Fonctionnalité multilingue |
| Builders | Builders, tarifs |
| Sécurité | Architecture sécurité |
| SEO/GEO | Données structurées, article GEO/GIO |
| Agent-ready | Page Agent-ready |
| Cas d’usage | Pourquoi FlatCMS |
| Comparaisons | Comparatifs |
| Confiance | À propos, licences, GitHub |
| Contenus | Blog |
| Commencer | Téléchargement, démo, documentation |

---

# 21. Données structurées attendues

Le graphe de la page doit relier :

```text
Organization
WebSite
SoftwareApplication
WebPage
ImageObject
```

## Identifiants

```text
https://flat-cms.fr/#organization
https://flat-cms.fr/#website
https://flat-cms.fr/#software
https://flat-cms.fr/fr-FR/#webpage
https://flat-cms.fr/fr-FR/#primaryimage
```

## Informations à confirmer avant intégration

- identité publique de l’éditeur ;
- e-mail ;
- profils `sameAs` ;
- URL GitHub ;
- URL de téléchargement ;
- version stable ;
- URL de licence ;
- prix gratuit du Core ;
- logo définitif ;
- image Open Graph.

---

# 22. Suggestions de composants du thème

## Hero

```text
HeroProduct
```

## Bénéfices

```text
FeatureGrid
```

## Architecture

```text
ContentSplitMedia
```

## Fonctionnalités

```text
FeatureCards
```

## Langues

```text
LocaleList
```

## Builders

```text
ProductCards
```

## Cas d’usage

```text
UseCaseGrid
```

## Blog

```text
LatestPosts
```

## FAQ

```text
FaqAccordion
```

## CTA final

```text
CallToActionBanner
```

Les noms définitifs doivent correspondre aux widgets réellement disponibles dans le thème ou les builders.

---

# 23. Médias à produire

## P0 — Obligatoires

- image Open Graph française ;
- capture du tableau de bord ;
- capture de la gestion des pages ;
- capture de la médiathèque ;
- schéma simplifié de l’architecture ;
- logos PHP, HMVC, PSR-4 et JSON sous une forme respectueuse des marques.

## P1 — Recommandés

- courte vidéo de présentation ;
- capture du sélecteur multilingue ;
- capture de PagesBuilder ;
- schéma agent-ready ;
- comparaison visuelle des dossiers du projet.

## Règles

- aucun faux écran ;
- aucune fonctionnalité future représentée comme disponible ;
- textes intégrés traduits par locale ;
- dimensions documentées ;
- texte alternatif prévu.

---

# 24. Textes alternatifs suggérés

## Capture administration

```text
Tableau de bord de l’administration FlatCMS
```

## Gestion des pages

```text
Interface de gestion des pages dans FlatCMS
```

## Médiathèque

```text
Médiathèque FlatCMS affichant les images et fichiers du site
```

## Architecture

```text
Cycle d’une requête FlatCMS du routeur jusqu’à la réponse HTTP
```

## Builders

```text
Interface du PagesBuilder de FlatCMS
```

Ces textes devront être adaptés à l’image finale.

---

# 25. Éléments à ne pas publier sans validation

- nombre de téléchargements ;
- nombre d’utilisateurs ;
- benchmark ;
- taux de performance ;
- témoignages ;
- logos clients ;
- compatibilité PHP autre que celle validée ;
- date exacte de sortie ;
- prix non confirmés ;
- promesse de support ;
- note ou avis ;
- certification GEO/GIO ;
- conformité juridique ;
- partenaires.

---

# 26. Checklist éditoriale

- [ ] Le H1 est unique.
- [ ] Le positionnement principal est visible.
- [ ] La version annoncée est correcte.
- [ ] Les fonctionnalités citées existent.
- [ ] Le Core et le Premium sont distingués.
- [ ] Le stockage JSON est expliqué sans ambiguïté.
- [ ] Le document root public est mentionné correctement.
- [ ] Les six locales sont exactes.
- [ ] L’agent-ready est présenté avec ses limites.
- [ ] Aucune garantie SEO ou IA n’est formulée.
- [ ] Les liens internes pointent vers des pages prévues.
- [ ] Les CTA sont explicites.
- [ ] Les médias correspondent à la version stable.
- [ ] Les textes alternatifs sont présents.
- [ ] Les données structurées correspondent au contenu visible.
- [ ] Le contenu a été relu techniquement.
- [ ] Le contenu a été relu éditorialement.

---

# 27. Checklist d’intégration

- [ ] URL `/fr-FR/`.
- [ ] Canonique correcte.
- [ ] Attribut `<html lang="fr-FR">`.
- [ ] Groupe `hreflang`.
- [ ] Title.
- [ ] Meta description.
- [ ] Open Graph.
- [ ] Twitter Card.
- [ ] H1.
- [ ] Navigation.
- [ ] Footer.
- [ ] Images responsive.
- [ ] Liens explorables.
- [ ] JSON-LD.
- [ ] Sitemap.
- [ ] Robots `index, follow`.
- [ ] Test mobile.
- [ ] Test clavier.
- [ ] Test performance.
- [ ] Test des liens.
- [ ] Test du code HTTP.

---

# 28. Sources internes

- `README.md`
- `VERSION`
- `flatcms.json`
- `LICENSING.md`
- `COMMERCIAL_LICENSE.md`
- `TRADEMARK.md`
- `CLA.md`
- arborescence `app/`
- arborescence `data/`
- arborescence `themes/`
- service `StructuredData`
- modules de FlatCMS v1.0.0

---

# 29. Références externes

- Google Search Central — AI features and your website  
  https://developers.google.com/search/docs/appearance/ai-features

- Google Search Central — Helpful, reliable, people-first content  
  https://developers.google.com/search/docs/fundamentals/creating-helpful-content

- PHP-FIG — PSR-4  
  https://www.php-fig.org/psr/psr-4/

---

# 30. Journal des mises à jour

| Date | Version | Modification | Auteur |
|---|---:|---|---|
| 2026-06-08 | 1.0 | Première rédaction complète de l’accueil fr-FR | ChatGPT / Alain BROYE |

---

# 31. Prochaine action

Après validation et ajout dans le Drive :

```text
Créer WHY_FLATCMS_CONTENT.md
```

Ce document contiendra la rédaction complète de la page :

```text
/fr-FR/pourquoi-flatcms/
```

La page devra présenter les cas d’usage, les avantages, les limites et
un tableau d’aide à la décision sans transformer le contenu en argumentaire
commercial excessif.

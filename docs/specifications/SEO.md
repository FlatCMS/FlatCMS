# SEO — Stratégie et roadmap du futur site officiel FlatCMS

> **Document directeur**
>
> Projet : FlatCMS  
> Domaine cible : `https://flat-cms.fr`  
> Date de création : 8 juin 2026  
> Statut : version initiale à maintenir pendant la conception, la migration et les évolutions du site

---

## 1. Objectif du document

Ce document définit la stratégie SEO, l’architecture éditoriale et la roadmap de construction du futur site officiel de FlatCMS.

Le futur site devra réunir sous un même domaine :

- le site institutionnel et produit ;
- les pages de présentation et de conversion ;
- le blog officiel ;
- la documentation technique ;
- les guides d’installation et de développement ;
- la roadmap publique ;
- les pages de téléchargement et de licences ;
- les contenus multilingues.

La démonstration restera séparée sur :

```text
https://demo.flat-cms.fr
```

L’objectif principal est de faire de `flat-cms.fr` la source officielle, centrale et canonique de toutes les informations relatives au CMS FlatCMS.

---

## 2. Synthèse de l’analyse SEO actuelle

### 2.1 Points positifs

FlatCMS dispose déjà de plusieurs signaux favorables :

- le nom de marque est indexé ;
- le domaine officiel apparaît sur les recherches de marque ;
- la démonstration est accessible et indexée ;
- plusieurs pages techniques sont déjà connues des moteurs ;
- les concepts HMVC, PHP et documentation sont partiellement associés à FlatCMS ;
- le projet bénéficie d’un positionnement différenciant :
  - PHP natif ;
  - architecture HMVC ;
  - autoloading PSR-4 ;
  - stockage JSON ;
  - absence de base de données SQL ;
  - modularité ;
  - multilingue ;
  - orientation « agent-ready ».

### 2.2 Faiblesses principales

Les faiblesses observées sont les suivantes :

1. Le domaine principal est essentiellement une landing page et ne porte pas encore assez de contenu.
2. Le contenu est dispersé entre le domaine principal, le wiki et la démonstration.
3. Le nom FlatCMS entre en collision avec un ancien produit homonyme présent sur `flatcms.nl`.
4. Le nouveau wiki est moins visible que certaines anciennes URLs.
5. Plusieurs résultats utilisent des titres trop génériques.
6. Les requêtes génériques ne sont pas encore occupées par FlatCMS :
   - CMS sans base de données ;
   - CMS flat-file PHP ;
   - CMS PHP JSON ;
   - CMS léger ;
   - alternative à WordPress ;
   - CMS open source français ;
   - CMS agent-ready.
7. Les contenus de démonstration peuvent brouiller la compréhension thématique du projet.
8. Certains anciens contenus peuvent décrire une architecture obsolète ou incohérente avec la version actuelle.

### 2.3 Opportunité centrale

Le principal levier consiste à réunir le site, le blog et la documentation sous `flat-cms.fr` afin de construire une entité éditoriale unique, cohérente et clairement identifiable.

Le regroupement ne constitue pas à lui seul une garantie de meilleur classement. Il améliore toutefois fortement :

- le maillage interne ;
- la compréhension de la marque ;
- la cohérence sémantique ;
- la consolidation des contenus ;
- la maintenance des balises SEO ;
- la gestion du multilingue ;
- les conversions ;
- la mesure des performances.

---

## 3. Positionnement officiel recommandé

### 3.1 Formulation principale

```text
FlatCMS — Le CMS PHP open source sans base de données
```

### 3.2 Formulation technique

```text
FlatCMS est un CMS flat-file en PHP natif fondé sur une architecture HMVC,
l’autoloading PSR-4 et un stockage JSON.
```

### 3.3 Formulation différenciante

```text
Un CMS léger, modulaire et multilingue, conçu pour les sites modernes,
les développeurs, les agences et les futurs agents IA.
```

### 3.4 Signature de marque recommandée

Pour éviter la confusion avec les autres produits homonymes, les contenus externes et les principales pages devront employer régulièrement une signature distinctive :

```text
FlatCMS France
```

ou :

```text
FlatCMS — CMS PHP open source développé en France
```

La marque ne devra pas être remplacée par « Flat CMS » dans les titres principaux, sauf nécessité linguistique ou contexte explicatif.

---

## 4. Architecture cible du domaine

### 4.1 Structure générale

```text
https://flat-cms.fr/
├── fr-FR/
│   ├── fonctionnalites/
│   ├── architecture/
│   ├── pourquoi-flatcms/
│   ├── comparatifs/
│   ├── telechargement/
│   ├── tarifs/
│   ├── licences/
│   ├── roadmap/
│   ├── blog/
│   └── documentation/
├── en-US/
├── de-DE/
├── es-ES/
├── it-IT/
└── pt-PT/
```

### 4.2 Domaine de démonstration

```text
https://demo.flat-cms.fr/
```

La démonstration reste séparée, car elle constitue une application fonctionnelle autonome avec :

- comptes temporaires ;
- contenus fictifs ;
- remise à zéro ;
- administration accessible ;
- scénarios de test ;
- thèmes et sites exemples.

### 4.3 Domaine principal sans locale

L’URL racine :

```text
https://flat-cms.fr/
```

devra servir soit :

- de page internationale de sélection de langue ;
- de page `x-default` ;
- de page française par défaut avec sélecteur de langue clairement visible.

La redirection automatique forcée selon la langue du navigateur doit être évitée si elle empêche les moteurs ou les utilisateurs de choisir une autre version.

---

## 5. Arborescence éditoriale recommandée

### 5.1 Navigation principale

```text
Produit
Fonctionnalités
Architecture
Documentation
Blog
Tarifs
Télécharger
```

Appels à l’action :

```text
Télécharger FlatCMS
Tester la démo
```

### 5.2 Pages produit

| Page | Objectif principal |
|---|---|
| Accueil | Positionner la marque et convertir |
| Fonctionnalités | Présenter les capacités du CMS |
| Architecture | Expliquer PHP, HMVC, PSR-4 et JSON |
| Pourquoi FlatCMS | Exposer les bénéfices et cas d’usage |
| Multilingue | Présenter les six locales natives |
| Sécurité | Détailler les protections et bonnes pratiques |
| Performances | Présenter des mesures reproductibles |
| Modules | Présenter l’écosystème modulaire |
| Builders | Présenter PagesBuilder, MenuBuilder et FooterBuilder |
| Agent-ready | Expliquer la préparation aux agents IA |
| Tarifs | Présenter les offres premium |
| Télécharger | Proposer les versions et prérequis |
| Licences | Expliquer AGPL, commercial et marques |
| Roadmap | Présenter les évolutions du projet |

### 5.3 Documentation

```text
/documentation/
├── demarrage/
├── installation/
├── configuration/
├── architecture/
├── administration/
├── contenus/
├── themes/
├── modules/
├── widgets/
├── builders/
├── securite/
├── performances/
├── api-et-hooks/
├── multilingue/
├── deploiement/
└── depannage/
```

### 5.4 Blog

Le blog doit servir l’acquisition organique et non reproduire la documentation.

Catégories recommandées :

- FlatCMS ;
- CMS flat-file ;
- PHP ;
- Architecture ;
- Intelligence artificielle ;
- Sécurité ;
- Performance ;
- Tutoriels ;
- Comparatifs ;
- Open source ;
- Actualités du projet.

---

## 6. Stratégie de mots-clés

### 6.1 Priorité 1 — Identité et cœur du produit

- FlatCMS ;
- FlatCMS France ;
- FlatCMS CMS ;
- FlatCMS PHP ;
- FlatCMS v1.0.0 ;
- CMS sans base de données ;
- CMS flat-file PHP ;
- CMS PHP JSON ;
- CMS sans MySQL ;
- CMS PHP natif.

### 6.2 Priorité 2 — Architecture et développement

- architecture HMVC PHP ;
- CMS HMVC ;
- autoloading PSR-4 CMS ;
- stockage JSON CMS ;
- CMS modulaire PHP ;
- créer un module PHP CMS ;
- créer un widget CMS ;
- CMS open source PHP ;
- CMS sans framework lourd.

### 6.3 Priorité 3 — Bénéfices et alternatives

- CMS léger ;
- CMS rapide ;
- CMS sécurisé sans SQL ;
- alternative WordPress sans base de données ;
- alternative Grav CMS ;
- CMS pour site vitrine ;
- CMS pour agence web ;
- CMS pour petit hébergement ;
- CMS faible consommation ;
- CMS simple à déployer.

### 6.4 Priorité 4 — Différenciation IA

- CMS agent-ready ;
- CMS AI-ready ;
- CMS pour agents IA ;
- CMS compatible OpenAI ;
- CMS GIO ;
- Generative Indexing Optimization ;
- génération de contenu CMS ;
- CMS multilingue avec IA.

### 6.5 Règle d’utilisation

Chaque page doit posséder :

- une intention principale ;
- un mot-clé principal ;
- trois à huit expressions secondaires ;
- un titre unique ;
- un H1 unique ;
- une meta description spécifique ;
- des liens internes vers les pages complémentaires ;
- aucun bourrage de mots-clés.

Les textes doivent être écrits pour l’utilisateur et couvrir naturellement les variantes lexicales.

---

## 7. Matrice initiale des pages et titres SEO

| Page | URL proposée | Balise `<title>` recommandée |
|---|---|---|
| Accueil | `/fr-FR/` | `FlatCMS — CMS PHP open source sans base de données` |
| Fonctionnalités | `/fr-FR/fonctionnalites/` | `Fonctionnalités de FlatCMS : pages, blog, médias et modules` |
| Architecture | `/fr-FR/architecture/` | `Architecture HMVC, PSR-4 et stockage JSON de FlatCMS` |
| Pourquoi FlatCMS | `/fr-FR/pourquoi-flatcms/` | `Pourquoi choisir FlatCMS pour créer un site web ?` |
| Documentation | `/fr-FR/documentation/` | `Documentation FlatCMS — Installation et développement` |
| Installation | `/fr-FR/documentation/installation/` | `Installer FlatCMS sur Apache, Nginx, MAMP ou WAMP` |
| Modules | `/fr-FR/documentation/modules/` | `Créer et installer un module HMVC dans FlatCMS` |
| Widgets | `/fr-FR/documentation/widgets/` | `Créer un widget pour les builders de FlatCMS` |
| Blog | `/fr-FR/blog/` | `Blog FlatCMS — PHP, CMS flat-file, IA et développement web` |
| Téléchargement | `/fr-FR/telechargement/` | `Télécharger FlatCMS — CMS PHP open source` |
| Tarifs | `/fr-FR/tarifs/` | `Tarifs FlatCMS — Builders et licences commerciales` |
| Démo | `https://demo.flat-cms.fr/` | `Démo FlatCMS — Testez le CMS PHP sans base de données` |
| Comparatif WordPress | `/fr-FR/comparatifs/flatcms-vs-wordpress/` | `FlatCMS vs WordPress : quel CMS choisir ?` |
| Comparatif Grav | `/fr-FR/comparatifs/flatcms-vs-grav/` | `FlatCMS vs Grav : comparaison des CMS flat-file` |
| Agent-ready | `/fr-FR/agent-ready/` | `FlatCMS : un CMS conçu pour les agents IA` |
| Licences | `/fr-FR/licences/` | `Licences FlatCMS — Open source, commercial et marque` |

---

## 8. Contenu recommandé pour la page d’accueil

### H1

```text
FlatCMS, le CMS PHP open source sans base de données
```

### Introduction

```text
Créez des sites rapides, sécurisés et faciles à déployer avec un CMS
flat-file en PHP natif, fondé sur une architecture HMVC, l’autoloading
PSR-4 et le stockage JSON.
```

### Sections

1. Proposition de valeur.
2. Avantages du stockage sans base SQL.
3. Fonctionnalités principales.
4. Architecture PHP, HMVC, PSR-4 et JSON.
5. Builders visuels.
6. Multilingue natif.
7. Sécurité.
8. Performances mesurées.
9. Modules et extensibilité.
10. Intégration IA et approche agent-ready.
11. Comparaison avec WordPress et Grav.
12. Derniers articles.
13. Accès rapide à la documentation.
14. Démonstration.
15. Téléchargement et GitHub.
16. Questions fréquentes.
17. Newsletter.

### Appels à l’action principaux

```text
Télécharger FlatCMS
Tester la démo
Consulter la documentation
Découvrir l’architecture
```

---

## 9. Stratégie éditoriale du blog

### 9.1 Pages piliers prioritaires

1. Qu’est-ce qu’un CMS sans base de données ?
2. Comment fonctionne un CMS flat-file PHP ?
3. FlatCMS vs WordPress.
4. FlatCMS vs Grav.
5. Pourquoi utiliser JSON plutôt qu’une base SQL pour un petit site ?
6. Architecture HMVC et PSR-4 expliquée simplement.
7. Comment installer FlatCMS sur Apache et Nginx ?
8. Comment créer un module FlatCMS ?
9. Comment créer un widget pour les builders ?
10. Qu’est-ce qu’un CMS agent-ready ?
11. Comment optimiser un site pour les moteurs génératifs ?
12. CMS open source français : critères de choix.
13. Sécuriser un CMS PHP sans base de données.
14. Déployer un CMS PHP sur un hébergement mutualisé.
15. Créer un site multilingue avec FlatCMS.

### 9.2 Rythme éditorial recommandé

Phase de lancement :

- deux articles structurants par mois ;
- une mise à jour technique ou note de version par mois ;
- un tutoriel vidéo associé lorsque pertinent.

Après stabilisation :

- un article approfondi par mois ;
- une revue trimestrielle des contenus existants ;
- mise à jour systématique des tutoriels lors des changements de version.

### 9.3 Structure type d’un article

```text
H1 : réponse claire à une intention de recherche
Introduction : problème et promesse
H2 : définition ou contexte
H2 : fonctionnement
H2 : avantages
H2 : limites
H2 : exemple FlatCMS
H2 : mise en œuvre
H2 : questions fréquentes
Conclusion et appel à l’action
```

Chaque article doit contenir :

- auteur ;
- date de publication ;
- date de mise à jour ;
- sommaire ;
- liens vers la documentation ;
- liens vers une page produit ;
- sources lorsque des affirmations externes sont utilisées ;
- image Open Graph dédiée ;
- données structurées `Article` ou `BlogPosting`.

---

## 10. Documentation et SEO technique

### 10.1 Titres explicites

Éviter les titres génériques comme :

```text
Documentation - FlatCMS v1.0.0
```

Préférer :

```text
Installer FlatCMS sur Nginx
Configurer les URLs propres de FlatCMS
Comprendre l’architecture HMVC de FlatCMS
Créer un module avec autoloading PSR-4
Configurer le stockage JSON
```

### 10.2 URLs descriptives

Préférer :

```text
/fr-FR/documentation/installation/nginx/
```

Éviter :

```text
/index.php?doc=getting-started%2FINSTALLATION&p=wiki
```

### 10.3 Maillage interne

Chaque page de documentation doit comporter :

- un fil d’Ariane ;
- une navigation précédente/suivante ;
- des pages connexes ;
- un lien vers la page parent ;
- un lien vers le dépôt GitHub lorsque nécessaire ;
- un lien vers la version correspondante du produit.

### 10.4 Gestion des versions

Prévoir dès le départ une stratégie de versionnement :

```text
/documentation/lts/
/documentation/next/
```

ou :

```text
/documentation/v1/
```

Une seule version doit être déclarée canonique lorsqu’elle représente la documentation stable actuelle.

---

## 11. Stratégie multilingue

### 11.1 Locales

- `fr-FR`
- `en-US`
- `de-DE`
- `es-ES`
- `it-IT`
- `pt-PT`

### 11.2 Règles

Chaque version traduite doit avoir :

- sa propre URL ;
- son contenu réellement traduit ;
- sa balise canonique auto-référencée ;
- toutes les balises `hreflang` réciproques ;
- un lien `x-default` ;
- une navigation permettant de changer de langue ;
- une correspondance de page à page.

### 11.3 Exemple

```html
<link rel="alternate" hreflang="fr-FR"
      href="https://flat-cms.fr/fr-FR/fonctionnalites/">
<link rel="alternate" hreflang="en-US"
      href="https://flat-cms.fr/en-US/features/">
<link rel="alternate" hreflang="de-DE"
      href="https://flat-cms.fr/de-DE/funktionen/">
<link rel="alternate" hreflang="es-ES"
      href="https://flat-cms.fr/es-ES/funcionalidades/">
<link rel="alternate" hreflang="it-IT"
      href="https://flat-cms.fr/it-IT/funzionalita/">
<link rel="alternate" hreflang="pt-PT"
      href="https://flat-cms.fr/pt-PT/funcionalidades/">
<link rel="alternate" hreflang="x-default"
      href="https://flat-cms.fr/">
```

### 11.4 Traductions

Les traductions automatiques brutes ne doivent pas être publiées sans relecture.

Chaque locale doit conserver :

- un vocabulaire naturel ;
- les conventions locales ;
- des titres adaptés à la recherche réelle ;
- des slugs localisés lorsque cela reste maintenable.

---

## 12. Gestion SEO de la démonstration

### 12.1 Principe

La page d’accueil de la démo peut rester indexable si elle présente clairement FlatCMS.

Les contenus fictifs doivent être exclus de l’index afin d’éviter de brouiller le positionnement thématique du domaine.

### 12.2 Recommandation

Sur les pages de contenu de démonstration :

```html
<meta name="robots" content="noindex, follow">
```

La page d’accueil pourra utiliser :

```html
<meta name="robots" content="index, follow">
```

### 12.3 Exclusions complémentaires

- ne pas inclure les contenus fictifs dans le sitemap principal ;
- ne pas déclarer de canonique vers le blog officiel si les contenus diffèrent ;
- ne pas utiliser le fichier `robots.txt` pour bloquer les pages devant porter un `noindex`, car le moteur doit pouvoir lire la balise ;
- éviter que les thèmes exemples génèrent des milliers d’URLs indexables.

---

## 13. Migration du wiki et des anciennes URLs

### 13.1 Principe

Le contenu existant de `wiki.flat-cms.fr` doit être déplacé vers :

```text
https://flat-cms.fr/{locale}/documentation/
https://flat-cms.fr/{locale}/blog/
```

### 13.2 Table de correspondance

Créer un fichier de migration contenant :

| Ancienne URL | Nouvelle URL | Type | Statut |
|---|---|---|---|
| URL wiki | URL cible | Documentation/Blog | À faire/Fait/Testé |

### 13.3 Redirections

Chaque ancienne URL doit recevoir une redirection permanente vers son équivalent exact :

```text
301 ancienne URL → nouvelle URL
```

Ne pas rediriger toutes les pages vers l’accueil.

### 13.4 Actions de migration

- inventorier toutes les URLs indexées ;
- identifier les contenus obsolètes ;
- fusionner les doublons ;
- corriger les pages décrivant une ancienne architecture ;
- créer les nouvelles URLs ;
- poser les redirections 301 ;
- mettre à jour les liens internes ;
- générer les nouveaux sitemaps ;
- vérifier les canonicals ;
- vérifier les `hreflang` ;
- tester les codes HTTP ;
- soumettre les sitemaps ;
- surveiller les erreurs 404 ;
- conserver les redirections pendant plusieurs années.

---

## 14. Balises techniques obligatoires

Chaque page indexable doit disposer de :

```html
<title>Titre unique et explicite</title>
<meta name="description" content="Description spécifique de la page.">
<link rel="canonical" href="URL canonique absolue">
<meta name="robots" content="index, follow">
<meta property="og:title" content="Titre Open Graph">
<meta property="og:description" content="Description Open Graph">
<meta property="og:url" content="URL canonique">
<meta property="og:type" content="website">
<meta property="og:image" content="Image absolue">
<meta name="twitter:card" content="summary_large_image">
```

Pour les articles :

```html
<meta property="og:type" content="article">
<meta property="article:published_time" content="...">
<meta property="article:modified_time" content="...">
```

---

## 15. Données structurées

### 15.1 Types prioritaires

- `Organization`
- `WebSite`
- `WebPage`
- `SoftwareApplication`
- `Article`
- `BlogPosting`
- `BreadcrumbList`
- `FAQPage` uniquement lorsque le contenu visible correspond réellement
- `VideoObject` pour les tutoriels vidéo

### 15.2 Entité principale

La page d’accueil doit présenter une entité cohérente :

```json
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "FlatCMS",
  "url": "https://flat-cms.fr/",
  "logo": "https://flat-cms.fr/assets/img/logo-flatcms.png"
}
```

Une donnée structurée ne doit jamais annoncer une information absente ou trompeuse dans le contenu visible.

---

## 16. Sitemap, robots et indexation

### 16.1 Sitemaps

Prévoir :

```text
/sitemap.xml
/sitemaps/sitemap-fr-FR.xml
/sitemaps/sitemap-en-US.xml
/sitemaps/sitemap-de-DE.xml
/sitemaps/sitemap-es-ES.xml
/sitemaps/sitemap-it-IT.xml
/sitemaps/sitemap-pt-PT.xml
/sitemaps/sitemap-images.xml
/sitemaps/sitemap-videos.xml
```

Seules les URLs :

- canoniques ;
- indexables ;
- répondant en `200 OK` ;
- utiles ;

doivent apparaître dans les sitemaps.

### 16.2 Robots

Exemple de base :

```text
User-agent: *
Disallow: /admin/
Disallow: /install/
Disallow: /data/
Disallow: /storage/
Disallow: /config/

Sitemap: https://flat-cms.fr/sitemap.xml
```

Les ressources CSS, JavaScript et images nécessaires au rendu public ne doivent pas être bloquées.

---

## 17. Performance et expérience

### 17.1 Objectifs

- HTML utile disponible sans dépendre d’un rendu JavaScript complexe ;
- responsive design ;
- navigation clavier ;
- contrastes accessibles ;
- images dimensionnées ;
- formats WebP ou AVIF ;
- chargement différé hors écran ;
- cache HTTP ;
- compression Brotli ou gzip ;
- polices limitées ;
- absence de scripts tiers inutiles.

### 17.2 Core Web Vitals

Cibles recommandées au 75e percentile :

- LCP : inférieur ou égal à 2,5 secondes ;
- INP : inférieur ou égal à 200 millisecondes ;
- CLS : inférieur ou égal à 0,1.

### 17.3 Benchmarks

Toute affirmation de performance doit être accompagnée de :

- la version de FlatCMS ;
- le serveur ;
- la version PHP ;
- le matériel ;
- le protocole de test ;
- le nombre de requêtes ;
- l’état du cache ;
- la date du test.

Éviter les expressions non démontrées comme :

```text
Le CMS le plus rapide au monde
```

Préférer :

```text
Un CMS PHP ultraléger conçu pour réduire la complexité et supprimer
la dépendance à un serveur SQL.
```

---

## 18. Autorité externe et notoriété

### 18.1 Priorités

- dépôt GitHub officiel optimisé ;
- README cohérent avec le site ;
- fiche Packagist si pertinente ;
- annuaires open source sérieux ;
- communautés PHP françaises et internationales ;
- articles invités techniques ;
- comparatifs de CMS flat-file ;
- conférences, podcasts et démonstrations ;
- liens depuis les profils officiels ;
- publications LinkedIn ;
- tutoriels YouTube.

### 18.2 Cohérence de marque

Tous les profils doivent utiliser :

- le même nom ;
- le même logo ;
- la même description courte ;
- le domaine officiel ;
- les mêmes liens vers la documentation et GitHub.

---

## 19. Mesure et outils

### 19.1 Outils

- Google Search Console ;
- Bing Webmaster Tools ;
- Matomo ;
- éventuellement Google Analytics ;
- Lighthouse ;
- PageSpeed Insights ;
- Rich Results Test ;
- Schema Markup Validator ;
- outil de vérification des liens ;
- journal des redirections et erreurs 404.

### 19.2 Indicateurs

| KPI | Mesure |
|---|---|
| Pages indexées | Nombre d’URLs utiles présentes dans l’index |
| Impressions de marque | Requêtes contenant FlatCMS |
| Impressions génériques | Requêtes sans le nom FlatCMS |
| Clics organiques | Trafic issu des moteurs |
| CTR | Clics / impressions |
| Position moyenne | Par groupe de mots-clés |
| Conversions | Téléchargements, démo, newsletter |
| Couverture multilingue | Pages valides par locale |
| Erreurs 404 | URLs internes ou externes cassées |
| Core Web Vitals | URLs bonnes, à améliorer, faibles |
| Backlinks | Domaines référents qualitatifs |
| Mentions | Citations de FlatCMS sans lien |

---

## 20. Roadmap de construction

## Phase 0 — Gouvernance et cadrage

### Objectifs

- valider le positionnement ;
- figer les conventions d’URL ;
- nommer les responsables ;
- centraliser les documents ;
- définir la version de FlatCMS qui propulsera le site.

### Livrables

- [ ] Validation de ce fichier `SEO.md`
- [ ] Charte éditoriale
- [ ] Charte graphique
- [ ] Convention de nommage des médias
- [ ] Convention des slugs
- [ ] Matrice des locales
- [ ] Liste des pages obligatoires
- [ ] Tableau des anciennes URLs
- [ ] Définition des KPI

---

## Phase 1 — Architecture fonctionnelle

### Objectifs

- concevoir l’arborescence ;
- construire les menus ;
- définir les modèles de page ;
- préparer le multilingue.

### Livrables

- [ ] Sitemap visuel du futur site
- [ ] Menu principal
- [ ] Méga-menu Documentation
- [ ] Footer
- [ ] Fil d’Ariane
- [ ] Modèle de page produit
- [ ] Modèle d’article
- [ ] Modèle de documentation
- [ ] Modèle de comparatif
- [ ] Modèle de page légale
- [ ] Modèle de téléchargement
- [ ] Sélecteur de langue

---

## Phase 2 — Fondation technique SEO

### Objectifs

- rendre le site explorable et indexable ;
- automatiser les balises ;
- prévenir les duplications.

### Livrables

- [ ] URLs propres
- [ ] Balises title dynamiques
- [ ] Meta descriptions
- [ ] Canonicals
- [ ] Robots meta
- [ ] Open Graph
- [ ] Twitter Cards
- [ ] `hreflang`
- [ ] `x-default`
- [ ] Sitemap index
- [ ] Sitemaps par locale
- [ ] `robots.txt`
- [ ] Page 404
- [ ] Redirections
- [ ] Données structurées
- [ ] Pagination propre
- [ ] Gestion des archives et catégories
- [ ] Noindex de la recherche interne
- [ ] Noindex des pages techniques inutiles

---

## Phase 3 — Pages essentielles en français

### Priorité de production

1. [ ] Accueil
2. [ ] Fonctionnalités
3. [ ] Architecture
4. [ ] Pourquoi FlatCMS
5. [ ] Téléchargement
6. [ ] Documentation
7. [ ] Installation
8. [ ] Builders
9. [ ] Modules
10. [ ] Multilingue
11. [ ] Sécurité
12. [ ] Performances
13. [ ] Agent-ready
14. [ ] Tarifs
15. [ ] Licences
16. [ ] Roadmap
17. [ ] Contact
18. [ ] Mentions légales
19. [ ] Politique de confidentialité
20. [ ] Gestion des cookies

---

## Phase 4 — Migration du wiki et du blog

### Objectifs

- consolider les contenus existants ;
- préserver les signaux déjà acquis ;
- supprimer les incohérences.

### Livrables

- [ ] Export complet des URLs du wiki
- [ ] Classification : conserver, fusionner, réécrire, supprimer
- [ ] Nouvelle destination pour chaque URL
- [ ] Migration des médias
- [ ] Mise à jour des titres
- [ ] Mise à jour des liens
- [ ] Redirections 301
- [ ] Vérification des canonicals
- [ ] Vérification des dates
- [ ] Vérification des auteurs
- [ ] Vérification des six locales
- [ ] Contrôle des réponses HTTP
- [ ] Audit post-migration

---

## Phase 5 — Démonstration

### Objectifs

- conserver la démo comme outil de conversion ;
- empêcher les contenus fictifs de polluer l’index.

### Livrables

- [ ] Page d’accueil officielle de la démo
- [ ] Titre et description dédiés
- [ ] Lien canonique propre
- [ ] `noindex, follow` sur les contenus fictifs
- [ ] Exclusion des sitemaps
- [ ] Liens vers le téléchargement
- [ ] Liens vers la documentation
- [ ] Explication de la remise à zéro
- [ ] Sécurisation des comptes de test

---

## Phase 6 — Déploiement multilingue

### Ordre recommandé

1. [ ] fr-FR
2. [ ] en-US
3. [ ] de-DE
4. [ ] es-ES
5. [ ] it-IT
6. [ ] pt-PT

### Contrôles par locale

- [ ] Traductions relues
- [ ] Slugs validés
- [ ] Titles uniques
- [ ] Meta descriptions
- [ ] H1
- [ ] Menus
- [ ] Footer
- [ ] Canonicals
- [ ] `hreflang`
- [ ] Sitemap
- [ ] Images et textes alternatifs
- [ ] Liens internes
- [ ] Données structurées
- [ ] Absence de texte non traduit

---

## Phase 7 — Lancement

### Avant mise en ligne

- [ ] Sauvegarde complète
- [ ] Crawl de préproduction
- [ ] Vérification des liens
- [ ] Vérification mobile
- [ ] Vérification accessibilité
- [ ] Vérification performances
- [ ] Vérification sécurité
- [ ] Vérification des formulaires
- [ ] Vérification des e-mails
- [ ] Vérification des téléchargements
- [ ] Vérification des licences
- [ ] Vérification de la démo
- [ ] Vérification des redirections
- [ ] Vérification du sitemap
- [ ] Vérification du robots.txt

### Après mise en ligne

- [ ] Soumettre les sitemaps
- [ ] Inspecter les pages principales
- [ ] Surveiller les erreurs
- [ ] Surveiller les 404
- [ ] Surveiller les redirections
- [ ] Contrôler l’indexation
- [ ] Contrôler les données structurées
- [ ] Comparer les impressions avant/après
- [ ] Corriger rapidement les anomalies

---

## Phase 8 — Croissance organique

### Objectifs

- gagner des positions sur les requêtes génériques ;
- renforcer l’autorité ;
- développer les conversions.

### Actions continues

- [ ] Publier les pages piliers
- [ ] Produire des tutoriels techniques
- [ ] Mettre à jour les contenus
- [ ] Publier les notes de version
- [ ] Créer des vidéos
- [ ] Obtenir des mentions externes
- [ ] Développer les comparatifs
- [ ] Enrichir les FAQ visibles
- [ ] Mesurer les conversions
- [ ] Réviser les titles à faible CTR
- [ ] Consolider les contenus concurrents
- [ ] Archiver les contenus obsolètes

---

## 21. Critères de validation du futur site

Le site pourra être considéré comme prêt lorsque :

- toutes les pages essentielles existent en français ;
- aucune ancienne page importante n’est perdue ;
- les redirections sont testées ;
- les pages publiques ont un titre et un H1 uniques ;
- les canonicals sont corrects ;
- les six locales sont correctement reliées ;
- les sitemaps ne contiennent que des URLs canoniques ;
- la démo ne pollue pas l’index ;
- les données structurées sont valides ;
- la navigation est accessible ;
- les performances sont satisfaisantes ;
- Search Console ne détecte pas d’erreur bloquante ;
- les contenus obsolètes sont corrigés ou redirigés ;
- le positionnement de marque est cohérent sur tous les supports.

---

## 22. Décisions stratégiques retenues

1. `flat-cms.fr` devient la plateforme officielle centrale.
2. Le blog et la documentation sont intégrés au domaine principal.
3. `demo.flat-cms.fr` reste séparé.
4. Les contenus fictifs de la démo sont placés en `noindex`.
5. Les six locales disposent d’URLs distinctes.
6. Les anciennes URLs sont redirigées page par page.
7. La formulation principale associe FlatCMS à PHP, HMVC, PSR-4, JSON et à l’absence de base SQL.
8. La stratégie éditoriale distingue clairement blog, produit et documentation.
9. Les performances sont présentées avec des mesures reproductibles.
10. Le positionnement agent-ready devient un axe différenciant, sans remplacer le cœur de recherche actuel.

---

## 23. Sources de référence

Documentation officielle Google Search Central :

- SEO Starter Guide  
  https://developers.google.com/search/docs/fundamentals/seo-starter-guide

- Versions localisées et `hreflang`  
  https://developers.google.com/search/docs/specialty/international/localized-versions

- Migration de site avec changement d’URLs  
  https://developers.google.com/search/docs/crawling-indexing/site-move-with-url-changes

- Titres dans les résultats de recherche  
  https://developers.google.com/search/docs/appearance/title-link

- Sitemaps  
  https://developers.google.com/search/docs/crawling-indexing/sitemaps/overview

- Données structurées  
  https://developers.google.com/search/docs/appearance/structured-data/intro-structured-data

---

## 24. Journal des mises à jour

| Date | Version | Modification | Auteur |
|---|---:|---|---|
| 2026-06-08 | 1.0 | Création du document SEO et de la roadmap initiale | ChatGPT / Alain BROYE |

---

## 25. Prochaine action recommandée

Créer ensuite un document complémentaire :

```text
SITE_ARCHITECTURE.md
```

Il devra détailler :

- chaque page ;
- son objectif ;
- son audience ;
- son mot-clé principal ;
- ses mots-clés secondaires ;
- son H1 ;
- son title ;
- sa meta description ;
- ses sections H2/H3 ;
- ses liens internes ;
- son appel à l’action ;
- ses données structurées ;
- son statut de production dans les six locales.

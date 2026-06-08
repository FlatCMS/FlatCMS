# HOMEPAGE_WIREFRAME — Wireframe fonctionnel de la page d’accueil FlatCMS

> **Spécification d’intégration pour le thème officiel `flatcms`**
>
> Projet : FlatCMS  
> Page : Accueil `fr-FR`  
> URL cible : `https://flat-cms.fr/fr-FR/`  
> Date : 8 juin 2026  
> Documents parents : `HOMEPAGE_CONTENT.md`, `THEME_SPECIFICATION.md`, `DESIGN_SYSTEM.md`, `NAVIGATION_SPECIFICATION.md`, `COMPONENT_LIBRARY.md`  
> Statut : wireframe de référence pour Codex, le thème `flatcms` et PagesBuilder  
> Inspiration visuelle : rythme et sophistication d’un site technologique premium, sans reproduction du site Webby

---

# 1. Objet

Ce document transforme le contenu éditorial de la page d’accueil en un
wireframe fonctionnel et technique directement exploitable.

Il définit :

- l’ordre exact des sections ;
- le rôle de chaque section ;
- les composants utilisés ;
- les largeurs et grilles ;
- les contenus et CTA ;
- les médias à produire ;
- le comportement responsive ;
- les animations autorisées ;
- le maillage interne ;
- les contraintes SEO ;
- les exigences d’accessibilité ;
- la compatibilité avec PagesBuilder ;
- les critères d’acceptation.

La page d’accueil doit présenter FlatCMS comme un projet :

- crédible ;
- technique ;
- compréhensible ;
- open source ;
- modulaire ;
- multilingue ;
- prêt pour les usages agentiques ;
- distinct de WordPress sans communication agressive ;
- capable de proposer des composants commerciaux sans masquer le Core.

---

# 2. Principes non négociables

## 2.1 Identité originale

Le site peut reprendre de la référence Webby :

- le rythme vertical ;
- les grandes accroches ;
- l’alternance de surfaces ;
- les cartes techniques ;
- les panneaux de code ;
- les halos subtils ;
- les grands CTA.

Il ne doit pas reprendre :

- le code source ;
- les textes ;
- les illustrations ;
- les composants exacts ;
- les proportions exactes ;
- les animations identiques ;
- les couleurs de marque ;
- les dispositions pixel perfect.

## 2.2 Contenu essentiel sans JavaScript

Doivent rester visibles sans JavaScript :

- H1 ;
- proposition de valeur ;
- CTA ;
- fonctionnalités ;
- architecture ;
- Builders ;
- tarifs principaux ;
- documentation ;
- FAQ ;
- footer.

## 2.3 Aucun Builder requis

La page d’accueil officielle doit fonctionner :

- avec le thème seul ;
- avec PagesBuilder actif ;
- avec PagesBuilder inactif ;
- après expiration d’une licence PagesBuilder ;
- en cas d’indisponibilité du serveur de licences.

## 2.4 Aucun impact de licence sur le rendu

Les sections publiées restent visibles après expiration d’une licence.

Le thème ne doit jamais :

- masquer une section ;
- supprimer un widget ;
- remplacer le contenu par un message de licence ;
- faire un appel de licence dans le rendu public ;
- générer une erreur 500.

## 2.5 Performance

La page ne doit pas charger une démonstration complète de l’administration
dans un iframe.

Les interfaces doivent être représentées par :

- captures optimisées ;
- SVG ;
- HTML/CSS léger ;
- diagrammes locaux.

---

# 3. Vue globale de la page

```text
00. Skip link
01. Header
02. Hero
03. Barre de preuves rapides
04. Panneau technique principal
05. Manifeste / philosophie
06. Sans serveur SQL
07. Fonctionnalités essentielles
08. Pourquoi FlatCMS
09. Architecture
10. Multilingue
11. Builders premium
12. Démarrer en quelques minutes
13. Sécurité et contrôle
14. SEO, GEO et données structurées
15. Agent-ready
16. Documentation et communauté
17. Comparatif
18. FAQ
19. CTA final
20. Footer
```

## Longueur cible

La page est volontairement riche, mais chaque section doit rester
concentrée.

Objectif :

```text
10 à 14 minutes de lecture complète
```

Le visiteur doit comprendre la proposition de valeur dans les premières
secondes sans lire toute la page.

---

# 4. Grille générale

## Conteneur standard

```text
max-width : 1200 px
padding-inline : clamp(20px, 4vw, 48px)
```

## Conteneur large

```text
max-width : 1400 px
```

Réservé aux :

- panneaux techniques ;
- captures ;
- tableaux ;
- visualisations.

## Largeur éditoriale

```text
max-width : 760 px
```

Réservée aux :

- introductions ;
- citations ;
- textes de section ;
- FAQ.

## Grille desktop

```text
12 colonnes
gap : 24 à 32 px
```

## Mobile

```text
1 colonne
```

## Tablette

```text
6 ou 8 colonnes conceptuelles
```

---

# 5. Rythme vertical

## Espacement de section

Desktop :

```text
112 à 144 px
```

Tablette :

```text
88 à 112 px
```

Mobile :

```text
64 à 88 px
```

## Espacement interne

- titre vers introduction : 16 à 24 px ;
- introduction vers contenu : 40 à 64 px ;
- cartes : 20 à 32 px ;
- CTA : 24 à 32 px.

## Alternance des surfaces

```text
Hero                fond principal
Panneau technique   fond principal
Manifeste           surface douce
Fonctionnalités     fond principal
Pourquoi            fond élevé
Architecture        fond principal
Builders            fond élevé
Documentation       fond principal
Comparatif          fond élevé
FAQ                 fond principal
CTA final           dégradé contrôlé
```

---

# 6. Section 00 — Skip link

## Composant

```text
SkipLink
```

## Libellé

```text
Aller au contenu principal
```

## Cible

```text
#main-content
```

## Comportement

- premier élément focusable ;
- visible au focus ;
- contraste AA ;
- position fixe ou absolue maîtrisée ;
- non masqué par le header sticky.

---

# 7. Section 01 — Header

## Composant

```text
SiteHeader
```

## Hauteur

Desktop :

```text
72 à 80 px
```

Mobile :

```text
64 à 72 px
```

## Contenu desktop

### Colonne gauche

- logo rectangulaire FlatCMS ;
- lien vers `/fr-FR/`.

### Centre

Navigation :

```text
Pourquoi FlatCMS
Fonctionnalités
Architecture
Documentation
Tarifs
Blog
```

### Droite

- sélecteur de langue ;
- bascule clair/sombre ;
- CTA `Télécharger`.

## CTA

```text
Télécharger
```

Destination :

```text
/fr-FR/telechargement/
```

## Comportement sticky

État initial :

- transparent ou presque ;
- bordure subtile.

Après scroll :

- surface opaque ;
- backdrop blur facultatif ;
- bordure visible ;
- ombre très légère.

## Mobile

- logo ;
- thème ;
- bouton menu ;
- CTA téléchargement dans le panneau mobile.

## Accessibilité

Voir `NAVIGATION_SPECIFICATION.md`.

---

# 8. Section 02 — Hero

## ID

```text
hero
```

## Composants

```text
Hero
VersionPill
HeadingBlock
CTAGroup
ResponsiveImage ou ArchitecturePreview
```

## Hauteur

Desktop :

```text
min-height : calc(100svh - header)
```

sans forcer un contenu excessivement espacé.

Mobile :

```text
hauteur automatique
```

## Composition desktop

```text
12 colonnes
```

Option retenue :

- contenu centré sur 10 colonnes ;
- texte principal sur 8 à 9 colonnes ;
- média technique sous les CTA sur 12 colonnes.

La mise en page n’utilise pas un split classique 50/50 afin de conserver
l’impact typographique de la référence visuelle tout en restant originale.

## Sur-titre

```text
FlatCMS v1.0.0 LTS Core
```

Source dynamique recommandée :

```text
VERSION
```

## H1

```text
Le CMS PHP flat-file simple, rapide et agent-ready
```

Le segment :

```text
simple, rapide et agent-ready
```

peut utiliser le dégradé FlatCMS.

## Introduction

```text
Créez des sites modernes avec un CMS PHP natif, modulaire et sans serveur
SQL, fondé sur une architecture HMVC, l’autoloading PSR-4 et un stockage
JSON structuré.
```

## Complément

```text
Gardez le contrôle de vos données, de votre code et de votre déploiement,
sans dépendre d’une pile applicative lourde.
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
Explorer la documentation
```

Destination :

```text
/fr-FR/documentation/
```

## Lien tertiaire

```text
Voir la démonstration
```

Destination :

```text
https://demo.flat-cms.fr/
```

Ouvrir dans le même onglet par défaut.

## Décor

- halos indigo et cyan ;
- grille technique très légère ;
- lignes évoquant un graphe de modules ;
- bruit visuel subtil facultatif ;
- aucun canvas lourd.

## Animation

Entrée possible :

- badge ;
- H1 ;
- texte ;
- CTA ;
- média.

Durée :

```text
350 à 650 ms
```

Sans animation si :

```text
prefers-reduced-motion: reduce
```

---

# 9. Section 03 — Barre de preuves rapides

## Composant

```text
StatsBar
StatItem
```

## Position

Directement sous les CTA ou entre le Hero et le panneau technique.

## Contenu

```text
PHP 8.3+
6 locales
0 serveur SQL
AGPL-3.0-or-later
```

## Règle éditoriale

Chaque information doit rester exacte.

## Mise en page

Desktop :

```text
4 colonnes
```

Mobile :

```text
2 × 2
```

ou liste compacte.

## Style

- aucune carte lourde ;
- séparateurs verticaux ;
- texte secondaire atténué ;
- valeurs plus contrastées.

---

# 10. Section 04 — Panneau technique principal

## ID

```text
technical-overview
```

## Composants

```text
TechnicalShowcase
CodePanel
ArchitectureFlow
FlatFileDiagram
```

## Objectif

Créer le visuel signature de la page d’accueil.

## Composition desktop

```text
12 colonnes
```

- panneau gauche : 7 colonnes ;
- panneau droit : 5 colonnes.

## Panneau gauche — cycle de requête

Titre :

```text
Une architecture lisible de bout en bout
```

Flux :

```text
Request
→ Router
→ Module
→ Service
→ JSON
→ View
→ Response
```

Affichage :

- nœuds reliés ;
- état actif simulé ;
- libellés courts ;
- description textuelle sous le diagramme.

## Panneau droit — structure JSON

Onglets facultatifs :

```text
Page JSON
Module
Arborescence
```

Sans JavaScript, les trois exemples apparaissent successivement.

## Exemple conceptuel

```json
{
  "id": "home",
  "locale": "fr-FR",
  "title": "FlatCMS",
  "status": "published",
  "template": "home"
}
```

## Barre de titre

```text
flatcms / data / pages / home.json
```

## Effet visuel

- terminal premium ;
- bordure translucide ;
- halo interne discret ;
- aucun faux bouton fonctionnel ;
- bouton copier uniquement sur le code réel.

## CTA

```text
Comprendre l’architecture
```

Destination :

```text
/fr-FR/architecture/
```

---

# 11. Section 05 — Manifeste

## ID

```text
manifesto
```

## Composants

```text
QuoteBlock
AuthorMiniCard
```

## Largeur

```text
max-width : 920 px
```

centré.

## Citation

```text
« Un CMS ne devrait pas devenir plus complexe que le site qu’il doit
gérer. »
```

## Texte associé

```text
FlatCMS cherche à conserver une architecture compréhensible, des données
lisibles et des responsabilités clairement séparées.
```

## Attribution

```text
Alain BROYE
Créateur et développeur principal de FlatCMS
```

## CTA

```text
Découvrir le projet
```

Destination :

```text
/fr-FR/a-propos/
```

## Style

- très aéré ;
- aucune carte standard ;
- grande typographie ;
- trait ou halo de marque ;
- portrait facultatif uniquement si une photo officielle est disponible.

---

# 12. Section 06 — Sans serveur SQL

## ID

```text
sans-sql
```

## Composants

```text
HeadingBlock
BenefitGrid
BenefitCard
```

## Surtitre

```text
Flat-file par conception
```

## H2

```text
Un CMS sans serveur de base de données SQL
```

## Introduction

Reprendre le contenu validé de `HOMEPAGE_CONTENT.md`.

## Trois cartes

### Déploiement allégé

- moins de services à configurer ;
- archive et serveur PHP compatible ;
- document root `public/`.

### Sauvegardes lisibles

- contenus ;
- médias ;
- configuration ;
- fichiers.

### Architecture transparente

- structures JSON ;
- fichiers identifiables ;
- versionnement possible ;
- traitements explicites.

## Composition

Desktop :

```text
3 cartes égales
```

Mobile :

```text
1 colonne
```

## Média facultatif

Diagramme :

```text
Code + Data + Media = Site FlatCMS
```

## CTA

```text
Pourquoi choisir FlatCMS ?
```

Destination :

```text
/fr-FR/pourquoi-flatcms/
```

---

# 13. Section 07 — Fonctionnalités essentielles

## ID

```text
features
```

## Composants

```text
HeadingBlock
FeatureGrid
FeatureCard
```

## H2

```text
Les fonctions essentielles pour gérer un site moderne
```

## Grille

Huit cartes prioritaires :

1. Pages
2. Articles et catégories
3. Médias
4. Menus et footer
5. Utilisateurs et authentification
6. Thèmes
7. Multilingue
8. Sauvegardes et corbeille

Carte complémentaire possible :

9. Données structurées

## Colonnes

Large desktop :

```text
3 colonnes
```

ou grille asymétrique originale :

```text
première carte 2 colonnes de large
autres cartes standard
```

La grille ne doit pas reproduire exactement celle de Webby.

## Carte type

- icône locale ;
- titre ;
- description ;
- lien ;
- halo au hover ;
- bordure fine.

## Interaction

Le hover ne doit pas être nécessaire pour comprendre ou ouvrir la carte.

## CTA

```text
Voir toutes les fonctionnalités
```

Destination :

```text
/fr-FR/fonctionnalites/
```

---

# 14. Section 08 — Pourquoi FlatCMS

## ID

```text
why-flatcms
```

## Composants

```text
ImageTextSplit
BenefitList
QuickFactsPanel
```

## Composition desktop

- gauche : 7 colonnes ;
- droite : 5 colonnes.

## Colonne gauche

### H2

```text
Pourquoi FlatCMS ?
```

### Liste

- PHP natif ;
- architecture HMVC ;
- autoloading PSR-4 ;
- stockage JSON ;
- modules indépendants ;
- données contrôlées ;
- multilingue natif ;
- base agent-ready.

Chaque item contient :

- icône ;
- titre ;
- explication courte.

## Colonne droite — panneau rapide

Titre :

```text
Informations techniques
```

Contenu :

| Élément | Valeur |
|---|---|
| Version stable | 1.0.0 LTS |
| PHP minimum | 8.3 |
| Architecture | HMVC |
| Autoloading | PSR-4 |
| Stockage | JSON |
| Licence du Core | AGPL-3.0-or-later |
| Locales | 6 |

## Source

Les valeurs doivent provenir autant que possible des fichiers du projet.

## CTA

```text
Lire la présentation complète
```

Destination :

```text
/fr-FR/pourquoi-flatcms/
```

---

# 15. Section 09 — Architecture

## ID

```text
architecture
```

## Composants

```text
HeadingBlock
ArchitectureFlow
ModuleCard
```

## H2

```text
Une architecture modulaire et prévisible
```

## Introduction

```text
Le cœur orchestre la requête. Les modules portent les fonctionnalités.
Les services appliquent les traitements. Les vues produisent le rendu.
```

## Diagramme principal

```text
public/index.php
      ↓
Bootstrap
      ↓
App / Router
      ↓
Modules HMVC
      ↓
Services
      ↓
Stockage JSON
      ↓
View / Response
```

## Six cartes

- Core ;
- Modules ;
- Services ;
- Hooks ;
- Thèmes ;
- Data.

## Présentation

Desktop :

- diagramme pleine largeur ;
- cartes en 3 × 2.

Mobile :

- diagramme vertical ;
- cartes empilées.

## CTA principal

```text
Explorer l’architecture
```

Destination :

```text
/fr-FR/architecture/
```

## CTA secondaire

```text
Documentation développeur
```

Destination :

```text
/fr-FR/documentation/developpement/
```

---

# 16. Section 10 — Multilingue

## ID

```text
multilingual
```

## Composants

```text
HeadingBlock
LocaleOrbit
LocaleCard
```

## H2

```text
Six locales intégrées dès la conception
```

## Locales

```text
fr-FR
en-US
de-DE
es-ES
it-IT
pt-PT
```

## Composition

Option visuelle originale :

- logo ou nœud FlatCMS central ;
- six locales autour ;
- version statique en grille sur mobile.

## Texte

Insister sur :

- contenus localisés ;
- URLs ;
- métadonnées ;
- menus ;
- traductions ;
- `hreflang` ;
- données structurées.

## CTA

```text
Découvrir le multilingue
```

Destination :

```text
/fr-FR/fonctionnalites/multilingue/
```

## Animation

Lignes ou nœuds légers uniquement.

Aucune rotation permanente.

---

# 17. Section 11 — Builders premium

## ID

```text
builders
```

## Composants

```text
HeadingBlock
BuilderCard
PricingSummary
LicenseGuarantee
```

## Surtitre

```text
Composants premium
```

## H2

```text
Construisez visuellement les pages, menus et footers
```

## Introduction

```text
Le Core reste gratuit. Les Builders ajoutent des interfaces visuelles
spécialisées et peuvent être testés gratuitement en local.
```

## Trois cartes produit

### PagesBuilder

- pages ;
- sections ;
- widgets ;
- aperçu.

### MenuBuilder

- menus ;
- sous-menus ;
- méga-menus ;
- mobile.

### FooterBuilder

- colonnes ;
- liens ;
- coordonnées ;
- éléments légaux.

## Prix visibles

```text
29,90 € HT/an par Builder
```

## Résumé des offres

```text
Solo            29,90 €
Duo             49,90 €
Suite complète  59,90 €
```

## Mise en avant

Suite complète :

- bordure en dégradé ;
- badge `Recommandée` ;
- pas de taille disproportionnée.

## Garantie fonctionnelle

Bloc visible :

```text
Le frontend reste actif après expiration.
```

Texte :

```text
Les pages, menus et footers déjà publiés restent affichés. Le
renouvellement réactive l’édition, les mises à jour et le support.
```

## CTA principal

```text
Comparer les offres
```

Destination :

```text
/fr-FR/tarifs/
```

## CTA secondaire

```text
Découvrir les Builders
```

Destination :

```text
/fr-FR/fonctionnalites/builders/
```

---

# 18. Section 12 — Démarrer

## ID

```text
getting-started
```

## Composants

```text
HeadingBlock
StepList
CodePanel
CTAGroup
```

## H2

```text
Démarrez en quelques étapes
```

## Étapes

1. Télécharger FlatCMS
2. Extraire l’archive
3. Pointer le serveur vers `public/`
4. Lancer l’installateur
5. Créer le premier site

## Composition desktop

- gauche : étapes 5 colonnes ;
- droite : panneau commande 7 colonnes.

## Panneau

Exemple :

```bash
php -v
```

Puis :

```text
Ouvrez l’installateur dans votre environnement local.
```

Éviter de publier une commande d’installation inexistante.

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
Télécharger FlatCMS
```

Destination :

```text
/fr-FR/telechargement/
```

---

# 19. Section 13 — Sécurité et contrôle

## ID

```text
security
```

## Composants

```text
HeadingBlock
SecurityPrinciples
FeatureCard
```

## H2

```text
Une base conçue pour limiter l’exposition
```

## Quatre principes

- document root `public/` ;
- permissions limitées ;
- secrets côté serveur ;
- rôles et contrôles d’accès.

## Message de prudence

```text
Aucun CMS ne garantit une sécurité absolue. La configuration, les mises à
jour et l’exploitation restent déterminantes.
```

## CTA

```text
Consulter l’architecture de sécurité
```

Destination :

```text
/fr-FR/architecture/securite/
```

## Style

Section sobre.

Ne pas utiliser d’animation spectaculaire ou de cadenas géant.

---

# 20. Section 14 — SEO, GEO et données structurées

## ID

```text
seo-geo
```

## Composants

```text
HeadingBlock
StructuredDataPanel
BenefitGrid
```

## H2

```text
Des fondations solides pour la recherche classique et générative
```

## Trois colonnes

### SEO technique

- URLs ;
- canonicals ;
- sitemaps ;
- performances.

### Multilingue

- locales ;
- `hreflang` ;
- métadonnées ;
- équivalents.

### Données structurées

- graphe JSON-LD ;
- pages ;
- articles ;
- organisation ;
- logiciel.

## Panneau JSON-LD

Extrait local et simplifié :

```json
{
  "@type": "SoftwareApplication",
  "name": "FlatCMS",
  "applicationCategory": "ContentManagementSystem"
}
```

Ne pas publier de propriété non validée.

## Message

```text
Aucun balisage ne garantit un classement, un rich result ou une citation.
```

## CTA

```text
Comprendre l’approche SEO et GEO
```

Destination :

```text
/fr-FR/fonctionnalites/seo-geo/
```

---

# 21. Section 15 — Agent-ready

## ID

```text
agent-ready
```

## Composants

```text
HeadingBlock
AgentReadyFlow
CapabilityStatus
CTAGroup
```

## H2

```text
Préparé pour des assistants et agents contrôlés
```

## Diagramme

```text
Utilisateur
→ AiAgent
→ AIManager
→ Provider
→ Validation humaine
→ Brouillon ou action
```

## Principes

- provider abstrait ;
- outils explicites ;
- permissions ;
- sorties structurées ;
- validation ;
- coûts suivis ;
- panne sans impact sur le CMS.

## Transparence

```text
Agent-ready décrit l’architecture. Toutes les fonctions IA ne sont pas
nécessairement incluses dans le LTS Core.
```

## CTA principal

```text
Découvrir l’approche agent-ready
```

Destination :

```text
/fr-FR/agent-ready/
```

## CTA secondaire

```text
Explorer l’architecture
```

Destination :

```text
/fr-FR/architecture/
```

---

# 22. Section 16 — Documentation et communauté

## ID

```text
resources
```

## Composants

```text
HeadingBlock
ResourceGrid
ResourceCard
```

## H2

```text
Apprendre, comprendre et contribuer
```

## Cartes principales

### Documentation

```text
Installer, configurer et administrer FlatCMS.
```

### Tutoriels

```text
Construire un premier site étape par étape.
```

### Blog technique

```text
Suivre les analyses, nouveautés et choix d’architecture.
```

### GitHub

```text
Consulter le code, les releases et les contributions.
```

## Cartes secondaires

- démo ;
- roadmap ;
- contribuer ;
- support.

## Règles

- liens réels ;
- statuts exacts ;
- ne pas présenter une communauté massive si elle est encore naissante ;
- aucun compteur fictif.

---

# 23. Section 17 — Comparatif

## ID

```text
comparison
```

## Composants

```text
HeadingBlock
ComparisonTable
```

## H2

```text
Choisir une architecture adaptée au projet
```

## Comparaison proposée

Colonnes :

- FlatCMS ;
- WordPress ;
- Grav ;
- CMS headless générique.

## Critères

- serveur SQL requis ;
- stockage principal ;
- administration incluse ;
- PHP ;
- multilingue natif ;
- modularité ;
- déploiement ;
- approche agent-ready.

## Règles éditoriales

- sources ;
- date de vérification ;
- formulation neutre ;
- aucune note globale arbitraire ;
- aucun concurrent ridiculisé ;
- `Variable` lorsque la catégorie est trop large ;
- possibilité de supprimer cette section si les données ne sont pas
  suffisamment stables ou sourcées.

## Mobile

- tableau horizontal avec premier critère sticky ;
- ou cartes par critère.

---

# 24. Section 18 — FAQ

## ID

```text
faq
```

## Composants

```text
HeadingBlock
Accordion
```

## H2

```text
Questions fréquentes
```

## Questions

1. FlatCMS nécessite-t-il MySQL ?
2. Quelle version de PHP faut-il utiliser ?
3. Le Core est-il gratuit ?
4. Puis-je tester les Builders en local ?
5. Que se passe-t-il à l’expiration d’une licence ?
6. FlatCMS gère-t-il plusieurs langues ?
7. Peut-on créer ses propres modules ?
8. Le module AiAgent est-il inclus dans le Core ?

## Implémentation

```html
<details>
  <summary>...</summary>
  <div>...</div>
</details>
```

## SEO

Ne pas ajouter automatiquement `FAQPage`.

## CTA facultatif

```text
Consulter toute la documentation
```

---

# 25. Section 19 — CTA final

## ID

```text
final-cta
```

## Composants

```text
CTASection
CTAGroup
TrustItems
```

## H2

```text
Prêt à construire un site plus simple à maintenir ?
```

## Texte

```text
Téléchargez le LTS Core, testez FlatCMS localement et explorez une
architecture PHP modulaire sans serveur SQL.
```

## CTA principal

```text
Télécharger FlatCMS
```

## CTA secondaire

```text
Tester la démonstration
```

## Réassurance

```text
Core open source
Installation sans SQL
Six locales
Documentation complète
```

## Design

- dégradé indigo-violet-cyan ;
- halo maîtrisé ;
- texte blanc ;
- aucun mouvement permanent ;
- coins généreux ;
- contraste validé.

---

# 26. Section 20 — Footer

## Composant

```text
SiteFooter
```

## Colonnes desktop

### Produit

- Pourquoi FlatCMS
- Fonctionnalités
- Architecture
- Builders
- Tarifs
- Téléchargement

### Documentation

- Démarrage
- Installation
- Administration
- Développement
- Déploiement
- Dépannage

### Communauté

- Blog
- GitHub
- Contribuer
- Roadmap
- Démo
- Contact

### Ressources

- À propos
- Licences
- Sécurité
- Statut éventuel
- Changelog

## Ligne légale

- Mentions légales ;
- Confidentialité ;
- Cookies ;
- CGV lorsque publiées ;
- Résilier lorsque applicable ;
- Signaler une vulnérabilité.

## Copyright

```text
© 2026 Alain BROYE / FlatCMS.
```

À adapter après création de la structure juridique.

## Mode clair/sombre

Le footer reste visuellement sombre dans les deux modes ou utilise une
variante définie par le design system.

---

# 27. Médias nécessaires

## P0

1. Logo rectangulaire FlatCMS
2. Visualisation technique du Hero
3. Diagramme cycle de requête
4. Diagramme architecture
5. Illustrations ou captures des trois Builders
6. Diagramme agent-ready
7. Image Open Graph de la home

## P1

8. Capture administration
9. Capture médiathèque
10. Capture multilingue
11. Illustration sécurité
12. Diagramme structured data

## Règles

- formats WebP, AVIF ou SVG ;
- dimensions explicites ;
- textes traduits si intégrés dans le média ;
- contenu important aussi en HTML ;
- aucun média repris de Webby ;
- droits documentés ;
- variantes clair/sombre si nécessaire.

---

# 28. Image Open Graph

## Fichier

```text
/assets/images/og/home-flatcms-fr-FR.webp
```

## Concept

- logo FlatCMS ;
- titre court ;
- architecture modulaire ;
- fond bleu nuit ;
- lignes indigo et cyan ;
- aucun prix ;
- aucun texte trop petit.

## Taille recommandée

```text
1200 × 630 px
```

## Variantes locales

Une image par locale si le texte est traduit.

---

# 29. Comportement responsive global

## Mobile 320–479 px

- Hero en une colonne ;
- CTA empilés ;
- stats 2 × 2 ;
- panneaux techniques empilés ;
- grilles en une colonne ;
- tableaux scrollables ;
- architecture verticale ;
- footer en accordéons ou colonnes empilées ;
- pas de texte tronqué.

## Mobile large 480–767 px

- certaines grilles en 2 colonnes si lisibles ;
- CTA horizontaux seulement si l’espace le permet.

## Tablette 768–1023 px

- Hero toujours centré ;
- panneaux 1 ou 2 colonnes selon contenu ;
- fonctionnalités 2 colonnes ;
- Builders 1 ou 3 selon largeur réelle.

## Desktop 1024–1279 px

- grille complète ;
- navigation desktop ;
- contenu principal 1200 px.

## Large desktop 1280 px et plus

- conteneur large pour visualisations ;
- ne pas étirer les paragraphes ;
- limiter la largeur des cartes ;
- conserver des marges généreuses.

---

# 30. Animations

## Autorisées

- apparition légère ;
- translation de 8 à 20 px ;
- opacité ;
- halo au hover ;
- changement de bordure ;
- progression discrète d’un flux ;
- copie de code.

## Interdites

- parallaxe lourde ;
- vidéo de fond automatique ;
- texte en boucle ;
- carrousel automatique ;
- rotation permanente ;
- animation bloquant l’interaction ;
- animations sur toutes les cartes simultanément.

## Déclenchement

Intersection Observer possible, avec fallback visible immédiat.

## Réduction

Avec `prefers-reduced-motion: reduce` :

- aucun déplacement important ;
- aucun flux animé ;
- transitions quasi instantanées ;
- contenu visible.

---

# 31. Chargement

## Priorité élevée

- CSS critique ;
- logo ;
- H1 ;
- CTA ;
- police principale si locale ;
- média Hero uniquement s’il est visible immédiatement.

## Lazy loading

- images hors Hero ;
- captures Builders ;
- médias documentation ;
- diagrammes lourds.

## JavaScript

Charger uniquement :

- menu mobile ;
- thème ;
- copie de code ;
- éventuels onglets ;
- recherche ;
- animations progressives.

Les accordéons peuvent utiliser `<details>` sans JavaScript.

---

# 32. SEO

## H1

Un seul H1.

## H2

Une section principale = un H2.

## Liens

Tous les CTA importants sont des liens HTML.

## Données structurées

La home peut utiliser :

- `WebPage` ;
- `WebSite` ;
- `Organization` ou `Person` selon le statut ;
- `SoftwareApplication` ;
- `BreadcrumbList` facultatif sur la home ;
- `Offer` uniquement si les offres sont réellement publiées et visibles.

## Cohérence

Les données structurées doivent correspondre aux textes visibles.

## Contenus comparatifs

Les affirmations non stables doivent être datées et sourcées.

---

# 33. Maillage interne

| Section | Destination |
|---|---|
| Hero | Téléchargement, documentation, démo |
| Panneau technique | Architecture |
| Manifeste | À propos |
| Sans SQL | Pourquoi FlatCMS |
| Fonctionnalités | Fonctionnalités |
| Pourquoi | Pourquoi FlatCMS |
| Architecture | Architecture, documentation développeur |
| Multilingue | Fonction multilingue |
| Builders | Tarifs, pages Builders |
| Démarrage | Installation, téléchargement |
| Sécurité | Architecture sécurité |
| SEO/GEO | Fonction SEO/GEO |
| Agent-ready | Agent-ready |
| Ressources | Documentation, blog, GitHub |
| Comparatif | Pages comparatives éventuelles |
| FAQ | Documentation |
| CTA final | Téléchargement, démo |

---

# 34. Mode clair

Le mode clair conserve :

- le même ordre ;
- les mêmes composants ;
- les mêmes contrastes hiérarchiques.

Adaptations :

- fond blanc cassé ;
- surfaces grises très claires ;
- textes bleu nuit ;
- halos moins saturés ;
- panneaux de code sombres conservés ou version claire dédiée ;
- bordures visibles.

## Règle

Le mode clair n’est pas une inversion automatique.

---

# 35. Fallback sans PagesBuilder

Le thème doit fournir un template natif :

```text
templates/home.php
```

Ce template assemble les composants P0 avec les contenus issus des sources
éditoriales ou de la configuration du site.

## En cas de données absentes

- masquer uniquement le bloc facultatif ;
- ne pas afficher un composant vide ;
- conserver la structure principale ;
- journaliser les incohérences côté serveur.

---

# 36. Contrat PagesBuilder

## Sections éditables

Toutes les sections peuvent être représentées comme widgets ou groupes
de widgets, mais le thème conserve un preset officiel.

## Preset

```text
home-flatcms-v1
```

## Données

Le preset doit contenir :

- IDs stables ;
- versions ;
- contenus par locale ;
- propriétés validées ;
- ordre ;
- états.

## Verrouillage facultatif

Les zones suivantes peuvent être protégées contre une suppression
accidentelle :

- H1 ;
- CTA principal ;
- liens juridiques du footer ;
- message de licence ;
- informations techniques de version.

Le verrouillage ne doit pas empêcher un administrateur autorisé
d’effectuer une modification consciente.

---

# 37. Sécurité

- aucun HTML non nettoyé ;
- liens validés ;
- pas de script utilisateur ;
- aucun secret dans les exemples ;
- captures nettoyées ;
- formulaires protégés ;
- aucune API externe obligatoire ;
- aucun contenu privé dans le HTML ;
- CSP compatible.

---

# 38. Accessibilité

Objectif :

```text
WCAG 2.2 AA
```

## Contrôles

- skip link ;
- landmarks ;
- H1 unique ;
- hiérarchie ;
- focus ;
- clavier ;
- contrastes ;
- cibles ;
- reflow ;
- zoom ;
- alternatives ;
- erreurs ;
- animations réduites ;
- tableaux ;
- code ;
- menus ;
- accordéons.

## Ordre DOM

L’ordre DOM suit l’ordre visuel.

---

# 39. Budgets de performance

Objectifs de départ, à valider par mesure :

```text
HTML initial           < 120 Ko
CSS total critique     < 100 Ko minifié
JavaScript initial     < 100 Ko minifié
Image Hero             < 250 Ko
Poids page initial     < 1 Mo
```

Ces budgets ne sont pas des garanties de Core Web Vitals.

## Mesures

Tester :

- LCP ;
- INP ;
- CLS ;
- TTFB ;
- poids ;
- requêtes ;
- cache.

---

# 40. Tests unitaires

```text
testHomeHasSingleH1
testHomeUsesCurrentVersion
testPrimaryCtaLinksToDownload
testDocumentationCtaLinksToCurrentLocale
testFeatureCardsUsePublishedContent
testBuilderPricesMatchPricingSource
testLicenseGuaranteeIsDisplayed
testFaqWorksWithoutJavascript
testNoSectionRequiresPremiumLicenseToRender
testMissingOptionalMediaDoesNotBreakHome
testHomeDoesNotExposeSecret
```

---

# 41. Tests d’intégration

## Thème natif

1. activer le thème `flatcms` ;
2. désactiver les trois Builders ;
3. ouvrir la home ;
4. vérifier toutes les sections essentielles ;
5. tester clair/sombre ;
6. tester six locales.

## PagesBuilder

1. importer le preset ;
2. modifier une section ;
3. publier ;
4. expirer la licence ;
5. vérifier le rendu identique ;
6. renouveler ;
7. vérifier l’édition.

## Navigation

1. sans MenuBuilder ;
2. avec MenuBuilder ;
3. après expiration ;
4. sans JavaScript.

## Footer

1. sans FooterBuilder ;
2. avec FooterBuilder ;
3. après expiration ;
4. vérifier les liens légaux.

---

# 42. Tests end-to-end

## Scénario première visite

```text
Étant donné un visiteur sur la home
Quand la page charge
Alors il identifie FlatCMS comme un CMS PHP flat-file
Et il peut télécharger ou consulter la documentation
```

## Scénario mobile

```text
Étant donné une largeur de 320 px
Quand la home est affichée
Alors aucun scroll horizontal global n’apparaît
Et tous les CTA restent utilisables
```

## Scénario sans JavaScript

```text
Étant donné JavaScript désactivé
Quand la home est chargée
Alors le contenu, la navigation essentielle, la FAQ et les CTA restent disponibles
```

## Scénario licence expirée

```text
Étant donné une home construite avec PagesBuilder
Quand la licence expire
Alors toutes les sections publiées restent visibles
Et aucun bandeau d’expiration n’est injecté dans le frontend
```

## Scénario fournisseur IA indisponible

```text
Étant donné le fournisseur IA indisponible
Quand la home est chargée
Alors la section Agent-ready reste statique et fonctionnelle
Et aucun appel distant ne bloque la page
```

---

# 43. Audit demandé à Codex

## Inventaire

```text
Section
Template
Composants
Source des données
Locale
JavaScript
Média
Builder
Tests
```

## Rapport d’écarts

```text
Section
Comportement actuel
Spécification
Impact
Correction
Test associé
```

## Confirmations critiques

- [ ] Le thème ne copie pas le site Webby.
- [ ] Le H1 est unique.
- [ ] Le contenu essentiel fonctionne sans JavaScript.
- [ ] La home fonctionne sans les Builders.
- [ ] Une expiration ne modifie pas le frontend.
- [ ] Les tarifs sont issus d’une source unique.
- [ ] La version est issue du projet.
- [ ] Les six locales sont prévues.
- [ ] Les CTA sont localisés.
- [ ] Les images ont dimensions et alternatives.
- [ ] Les animations respectent la préférence utilisateur.
- [ ] Les données structurées correspondent au contenu visible.
- [ ] Aucun CDN n’est obligatoire.
- [ ] Aucun secret n’est exposé.
- [ ] Les budgets sont mesurés.

---

# 44. Critères d’acceptation

Le wireframe est considéré comme correctement intégré si :

1. la proposition de valeur est visible sans scroll important ;
2. le visiteur peut télécharger ou accéder à la documentation ;
3. le contenu suit l’ordre défini ;
4. la direction visuelle est premium et originale ;
5. les sections techniques restent compréhensibles sans illustration ;
6. les Builders sont présentés sans masquer le Core gratuit ;
7. l’expiration des licences est expliquée correctement ;
8. le responsive fonctionne dès 320 px ;
9. le clavier et les lecteurs d’écran permettent la navigation ;
10. les tests passent.

---

# 45. Checklist éditoriale

- [ ] H1 validé.
- [ ] Version validée.
- [ ] Proposition de valeur validée.
- [ ] Textes issus de `HOMEPAGE_CONTENT.md`.
- [ ] Prix validés.
- [ ] Statuts IA validés.
- [ ] Comparatif sourcé ou retiré.
- [ ] FAQ cohérente.
- [ ] Liens internes créés.
- [ ] Aucun slogan absolu non démontré.
- [ ] Auteur et rôle exacts.
- [ ] Six locales planifiées.

---

# 46. Checklist design

- [ ] Palette FlatCMS.
- [ ] Mode sombre.
- [ ] Mode clair.
- [ ] Typographie.
- [ ] Grille.
- [ ] Espacements.
- [ ] Halos subtils.
- [ ] Cartes cohérentes.
- [ ] Panneaux techniques.
- [ ] Aucun clone Webby.
- [ ] Illustrations originales.
- [ ] Responsive.
- [ ] Animations réduites.
- [ ] Contrastes.

---

# 47. Checklist technique

- [ ] Template natif.
- [ ] Preset PagesBuilder.
- [ ] Schémas versionnés.
- [ ] Fallback Builder.
- [ ] Header natif.
- [ ] Footer natif.
- [ ] CSS local.
- [ ] JS local.
- [ ] Polices locales ou système.
- [ ] Lazy loading.
- [ ] Images dimensionnées.
- [ ] Aucun appel de licence frontend.
- [ ] CSP.
- [ ] Cache.
- [ ] Tests.
- [ ] Audit Codex.

---

# 48. Sources internes

- `HOMEPAGE_CONTENT.md`
- `THEME_SPECIFICATION.md`
- `DESIGN_SYSTEM.md`
- `NAVIGATION_SPECIFICATION.md`
- `COMPONENT_LIBRARY.md`
- `PRICING_CONTENT.md`
- `AGENT_READY_CONTENT.md`
- `ABOUT_CONTENT.md`
- `404_CONTENT.md`
- `BUILDER_LICENSE_ENFORCEMENT.md`
- `VERSION`
- `README.md`

---

# 49. Journal des mises à jour

| Date | Version | Modification | Auteur |
|---|---:|---|---|
| 2026-06-08 | 1.0 | Première spécification du wireframe de la page d’accueil | ChatGPT / Alain BROYE |

---

# 50. Prochaine action

Après validation et ajout dans le Drive :

```text
Créer MEDIA_PLAN.md
```

Ce document inventoriera tous les médias nécessaires au site officiel :

- logos ;
- captures ;
- diagrammes ;
- illustrations ;
- images Open Graph ;
- vidéos ;
- formats ;
- dimensions ;
- textes alternatifs ;
- locales ;
- emplacements ;
- priorités de production.

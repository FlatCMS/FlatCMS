# COMPONENT_LIBRARY — Bibliothèque de composants du thème FlatCMS

> **Spécification fonctionnelle, visuelle et technique**
>
> Projet : FlatCMS  
> Thème : `flatcms`  
> Date : 8 juin 2026  
> Documents parents : `THEME_SPECIFICATION.md`, `DESIGN_SYSTEM.md`, `NAVIGATION_SPECIFICATION.md`  
> Statut : spécification de référence pour Codex  
> Portée : frontend officiel, documentation, blog, pages légales, erreurs et Builders

---

# 1. Objectif

Ce document définit la bibliothèque officielle des composants du thème
`flatcms`.

Chaque composant doit être :

- réutilisable ;
- indépendant du contenu métier ;
- compatible avec les six locales ;
- accessible ;
- responsive ;
- testable ;
- documenté ;
- compatible avec les thèmes clair et sombre ;
- utilisable sans JavaScript lorsque l’interaction ne l’exige pas ;
- compatible avec PagesBuilder, MenuBuilder et FooterBuilder lorsque le
  composant est exposé à un Builder ;
- doté d’un fallback lorsque le Builder ou JavaScript est indisponible.

La bibliothèque ne doit pas devenir un ensemble de widgets visuellement
incohérents.

Tous les composants utilisent :

- les tokens de `DESIGN_SYSTEM.md` ;
- les règles de navigation de `NAVIGATION_SPECIFICATION.md` ;
- les contrats du thème définis dans `THEME_SPECIFICATION.md`.

---

# 2. Principes de conception

## 2.1 Composition

Un composant doit pouvoir être composé avec d’autres composants sans
modifier leur code interne.

## 2.2 Responsabilité unique

Chaque composant possède une fonction principale clairement identifiable.

## 2.3 API explicite

Les propriétés doivent être :

- nommées ;
- documentées ;
- validées ;
- typées autant que possible ;
- limitées à une liste blanche lorsque pertinent.

## 2.4 Contenu avant décoration

Le contenu essentiel doit rester disponible même si :

- CSS ne charge pas ;
- JavaScript est désactivé ;
- une animation échoue ;
- un Builder n’est pas actif ;
- un service externe ne répond pas.

## 2.5 Accessibilité native

La structure HTML et les contrôles natifs sont prioritaires.

Ne pas recréer en JavaScript un comportement fourni correctement par :

- `<button>` ;
- `<a>` ;
- `<details>` ;
- `<summary>` ;
- `<dialog>` lorsque supporté et maîtrisé ;
- champs de formulaire natifs.

## 2.6 Variantes limitées

Une variante doit répondre à un besoin réel.

Éviter :

```text
primary
primary-soft
primary-soft-alt
primary-soft-alt-2
```

Préférer des variantes sémantiques :

```text
primary
secondary
ghost
danger
success
```

---

# 3. Arborescence recommandée

```text
themes/flatcms/
├── components/
│   ├── base/
│   ├── content/
│   ├── navigation/
│   ├── marketing/
│   ├── documentation/
│   ├── commerce/
│   ├── feedback/
│   ├── forms/
│   └── errors/
├── partials/
├── layouts/
├── templates/
└── assets/
    ├── css/components/
    └── js/components/
```

## Convention d’un composant

```text
components/<category>/<component>/
├── component.php
├── component.css
├── component.js
├── schema.json
├── README.md
└── tests/
```

Tous les fichiers ne sont pas obligatoires.

Un composant sans comportement JavaScript ne doit pas contenir de fichier
JavaScript vide.

---

# 4. Contrat commun

Chaque composant exposé à un Builder doit déclarer :

```json
{
  "id": "feature-card",
  "name": "Feature Card",
  "category": "content",
  "version": "1.0.0",
  "supports": {
    "darkMode": true,
    "responsive": true,
    "locales": true,
    "builder": true
  },
  "properties": {}
}
```

## Propriétés communes possibles

```text
id
className
ariaLabel
theme
variant
size
alignment
spacing
visibility
locale
```

## Règles

- `className` doit être filtré ;
- aucun style inline arbitraire provenant d’un utilisateur ;
- aucune balise script dans les contenus ;
- les URLs sont validées ;
- les IDs HTML sont uniques ;
- les valeurs inconnues sont rejetées ou remplacées par une valeur sûre ;
- les composants ne doivent pas accepter du HTML brut sans nettoyage.

---

# 5. Conventions CSS

## Préfixe

```text
fc-
```

Exemples :

```css
.fc-button {}
.fc-card {}
.fc-hero {}
.fc-alert {}
```

## États

```css
.is-active {}
.is-disabled {}
.is-loading {}
.has-error {}
```

## Variantes

```css
.fc-button--primary {}
.fc-card--feature {}
.fc-alert--warning {}
```

## Interdictions

- sélecteurs basés sur une structure fragile ;
- `!important` systématique ;
- couleurs codées en dur hors tokens ;
- z-index arbitraires ;
- dépendance à une classe du contenu éditorial ;
- CSS global modifiant tous les éléments sans scope.

---

# 6. Conventions JavaScript

## Principe

```text
HTML d’abord, JavaScript en amélioration progressive.
```

## Attributs

```text
data-component
data-action
data-state
```

Exemple :

```html
<div data-component="accordion">
```

## Règles

- initialisation idempotente ;
- aucun listener global inutile ;
- nettoyage des listeners ;
- respect de `prefers-reduced-motion` ;
- support clavier ;
- pas de dépendance CDN ;
- pas d’erreur globale si un composant manque ;
- aucun appel externe dans le rendu critique.

---

# 7. Composants de base

# 7.1 Button

## Identifiant

```text
button
```

## Balises

- `<button>` pour une action ;
- `<a>` pour une navigation.

Ne jamais utiliser un `<div>` cliquable.

## Variantes

```text
primary
secondary
ghost
outline
danger
```

## Tailles

```text
sm
md
lg
```

## États

- normal ;
- hover ;
- focus-visible ;
- active ;
- disabled ;
- loading.

## Propriétés

```text
label
href
type
variant
size
iconStart
iconEnd
disabled
loading
ariaLabel
```

## Accessibilité

- libellé explicite ;
- état disabled réel ;
- `aria-busy` si chargement ;
- icône décorative masquée ;
- cible tactile suffisante ;
- focus visible.

---

# 7.2 IconButton

Utilisé pour :

- menu mobile ;
- fermeture ;
- changement de thème ;
- copie ;
- navigation précédente/suivante.

## Règle

Une icône seule nécessite toujours :

```text
aria-label
```

## Taille minimale

```text
44 × 44 px recommandé
```

---

# 7.3 Link

## Variantes

```text
default
muted
accent
standalone
```

## Règles

- soulignement ou autre différence perceptible ;
- texte descriptif ;
- indication d’un lien externe si utile ;
- ne pas ouvrir un nouvel onglet sans raison ;
- si `target="_blank"`, ajouter les protections appropriées.

---

# 7.4 Badge

## Usages

- version ;
- statut ;
- catégorie ;
- premium ;
- expérimental ;
- recommandé.

## Variantes

```text
neutral
primary
success
warning
danger
info
```

## Règle

Le statut ne doit pas dépendre uniquement de la couleur.

---

# 7.5 Divider

## Variantes

```text
horizontal
vertical
withLabel
```

## Accessibilité

Décoratif :

```html
role="presentation"
```

Sémantique :

```html
<hr>
```

---

# 7.6 Surface

Conteneur visuel générique pour :

- panneau ;
- carte ;
- section ;
- encart.

## Variantes

```text
default
elevated
soft
glass
```

La variante `glass` doit rester lisible sans effet de flou.

---

# 8. Composants de contenu

# 8.1 HeadingBlock

## Usage

Titre de section accompagné d’un surtitre, d’une description et
éventuellement d’une action.

## Propriétés

```text
eyebrow
title
description
level
alignment
maxWidth
action
```

## Règles

- niveau de titre défini par le contexte ;
- style visuel indépendant du niveau HTML ;
- pas de saut arbitraire de H2 à H4.

---

# 8.2 RichText

## Usage

Contenu éditorial provenant de SunEditor ou du stockage.

## Autorisé

- paragraphes ;
- titres ;
- listes ;
- tableaux ;
- citations ;
- liens ;
- images ;
- code ;
- notes.

## Contrôles

- nettoyage HTML ;
- hiérarchie des titres ;
- liens sûrs ;
- images responsive ;
- tableaux scrollables ;
- aucun script ;
- aucun style arbitraire non filtré.

---

# 8.3 FeatureCard

## Usage

Présenter une fonctionnalité FlatCMS.

## Structure

- icône ;
- titre ;
- description ;
- lien facultatif ;
- statut facultatif.

## Variantes

```text
default
highlighted
compact
horizontal
```

## Exemple de contenu

```text
Stockage JSON
Conservez les contenus dans des fichiers structurés sans serveur SQL.
```

## États

- statique ;
- cliquable ;
- actif.

Une carte entièrement cliquable doit conserver un lien principal
accessible et ne pas imbriquer des liens incompatibles.

---

# 8.4 BenefitCard

Diffère de `FeatureCard` par son orientation utilisateur.

Exemples :

- déploiement simplifié ;
- maintenance lisible ;
- données contrôlées ;
- architecture modulaire.

---

# 8.5 StatItem

## Structure

- valeur ;
- unité ;
- libellé ;
- description facultative.

Exemples :

```text
6 locales
0 serveur SQL
PHP 8.3+
```

## Règle

Ne pas afficher une statistique non vérifiée.

---

# 8.6 QuoteBlock

## Structure

```html
<figure>
  <blockquote>...</blockquote>
  <figcaption>...</figcaption>
</figure>
```

## Variantes

```text
editorial
testimonial
manifesto
```

## Règle

Ne pas inventer un témoignage ou une attribution.

---

# 8.7 LogoCloud

## Usage

Partenaires, technologies ou compatibilités réelles.

## Règles

- logos autorisés ;
- texte alternatif ;
- contraste ;
- pas de partenariat suggéré sans accord ;
- taille homogène ;
- aucun carrousel automatique obligatoire.

---

# 8.8 ImageTextSplit

## Structure

- média ;
- contenu ;
- actions.

## Variantes

```text
media-left
media-right
stacked
```

## Responsive

Passage en pile sur les petits écrans.

---

# 9. Composants marketing

# 9.1 Hero

## Variantes

```text
home
page
documentation
pricing
article
legal
```

## Hero Home

Contient :

- badge de version ;
- H1 ;
- texte ;
- CTA principal ;
- CTA secondaire ;
- statistiques ;
- visualisation technique.

## Règles

- un seul H1 ;
- CTA visibles sans scroll excessif ;
- média non bloquant ;
- pas d’animation lourde ;
- contenu lisible à 320 px ;
- texte traduisible.

---

# 9.2 CTAGroup

## Variantes

```text
inline
stacked
centered
```

## Règles

- une action principale claire ;
- maximum recommandé : trois actions ;
- éviter deux boutons visuellement primaires ;
- ordre cohérent sur mobile.

---

# 9.3 CTASection

## Usage

Grand appel à l’action entre deux sections ou en fin de page.

## Structure

- titre ;
- texte ;
- CTA ;
- éléments de réassurance.

## Variante visuelle

```text
gradient
surface
minimal
```

---

# 9.4 ArchitectureFlow

## Usage

Afficher un flux technique :

```text
Request → Router → Module → Service → JSON → View → Response
```

## Rendu

- liste structurée en HTML ;
- diagramme visuel en CSS/SVG ;
- description textuelle ;
- aucun contenu uniquement graphique.

## Responsive

Sur mobile :

```text
flux vertical
```

---

# 9.5 CodePanel

## Usage

Afficher :

- commandes ;
- PHP ;
- JSON ;
- configuration ;
- arborescence.

## Structure

- barre de titre ;
- langage ;
- bouton copier ;
- code ;
- légende.

## Accessibilité

- `<pre><code>` ;
- bouton copier annoncé ;
- état « Copié » ;
- scroll horizontal ;
- aucun code uniquement dans une image.

---

# 9.6 TerminalPanel

Variante visuelle d’un panneau de code.

## Règle

Ne pas simuler une commande fausse ou dangereuse.

---

# 9.7 FeatureGrid

## Colonnes

```text
1 / 2 / 3 / 4 selon largeur
```

## Propriétés

```text
items
columns
gap
variant
```

## Responsive

- 1 colonne mobile ;
- 2 tablette ;
- 3 ou 4 desktop selon le contenu.

---

# 9.8 ComparisonTable

## Usage

Comparaisons produit ou architecture.

## Règles

- faits vérifiables ;
- source si nécessaire ;
- cellules compréhensibles ;
- en-têtes structurés ;
- pas de comparaison trompeuse ;
- version mobile lisible.

## Mobile

Options :

- scroll horizontal ;
- cartes par critère ;
- priorité aux colonnes essentielles.

---

# 10. Composants navigation

Les contrats détaillés restent dans `NAVIGATION_SPECIFICATION.md`.

# 10.1 SiteHeader

## Contenu

- logo ;
- navigation ;
- langue ;
- thème ;
- CTA ;
- menu mobile.

## Invariants

- utilisable sans MenuBuilder ;
- utilisable au clavier ;
- sticky non bloquant ;
- aucune disparition après expiration d’une licence.

---

# 10.2 Breadcrumbs

## Structure

```html
<nav aria-label="Fil d’Ariane">
  <ol>...</ol>
</nav>
```

## Règles

- page courante non cliquable ;
- libellés localisés ;
- longueur maîtrisée ;
- JSON-LD uniquement sur les pages indexables appropriées.

---

# 10.3 LocaleSwitcher

## Contenu

- locale active ;
- locales disponibles ;
- liens vers équivalents réels.

## Règle

Ne pas générer un lien vers une traduction inexistante.

---

# 10.4 ThemeToggle

## États

```text
light
dark
system
```

## Règles

- bouton accessible ;
- préférence persistée ;
- absence de flash important ;
- fonctionnement sans serveur externe.

---

# 10.5 Pagination

## Usage

- blog ;
- recherche ;
- listes.

## Règles

- liens HTML ;
- page courante annoncée ;
- précédent/suivant ;
- pas de bouton JavaScript exclusif ;
- URLs canoniques cohérentes.

---

# 11. Composants documentation

# 11.1 DocumentationSearch

## Fonction

Recherche locale ou serveur.

## Propriétés

```text
query
placeholder
scope
filters
```

## Règles

- label accessible ;
- résultats en `noindex` ;
- échappement ;
- limite de longueur ;
- aucun contenu privé ;
- fallback sans JavaScript.

---

# 11.2 DocumentationTypeCard

Types :

```text
Tutoriel
Guide pratique
Référence
Explication
```

## Structure

- type ;
- question d’orientation ;
- description ;
- exemples ;
- lien.

---

# 11.3 DocumentationSidebar

## Contenu

- sections ;
- page active ;
- niveaux limités ;
- version.

## Responsive

- sidebar desktop ;
- panneau repliable mobile ;
- état accessible.

---

# 11.4 TableOfContents

## Génération

À partir des H2 et H3 visibles.

## Règles

- ancres uniques ;
- texte lisible ;
- page active ;
- sticky uniquement si non gênant ;
- pas de dépendance JavaScript pour les liens.

---

# 11.5 CodeBlock

Extension de `CodePanel` pour la documentation.

## Fonctions

- copie ;
- langage ;
- nom de fichier ;
- lignes mises en évidence ;
- avertissement ;
- sortie attendue.

## Règles

- code exact ;
- environnement indiqué ;
- commandes dangereuses signalées ;
- pas de secret.

---

# 11.6 Callout

## Variantes

```text
note
tip
important
warning
danger
```

## Structure

- icône ;
- titre ;
- contenu.

## Accessibilité

Le niveau ne dépend pas uniquement de la couleur.

---

# 11.7 StepList

## Usage

Procédure ordonnée.

## Structure

- numéro ;
- titre ;
- contenu ;
- validation ;
- média facultatif.

## Règle

Utiliser une liste ordonnée sémantique lorsque l’ordre est nécessaire.

---

# 11.8 VersionBadge

Exemples :

```text
1.0 LTS
Déprécié
Préversion
```

## Règle

Ne pas afficher un statut non confirmé.

---

# 11.9 PrevNextNavigation

## Contenu

- page précédente ;
- page suivante ;
- catégorie.

## Règles

- liens réels ;
- ordre documentaire ;
- localisé ;
- masqué si absence de destination.

---

# 12. Composants blog

# 12.1 ArticleCard

## Structure

- image ;
- catégorie ;
- titre ;
- résumé ;
- auteur ;
- date ;
- temps de lecture facultatif ;
- lien.

## Règles

- un lien principal ;
- image avec dimensions ;
- résumé limité ;
- date réelle ;
- auteur réel ;
- pas de faux temps de lecture.

---

# 12.2 ArticleHero

## Contenu

- catégorie ;
- H1 ;
- chapeau ;
- auteur ;
- dates ;
- image ;
- actions.

## Règle

Afficher publication et modification uniquement si exactes.

---

# 12.3 AuthorBox

## Contenu

- nom ;
- rôle ;
- bio ;
- avatar facultatif ;
- liens vérifiés.

## Règle

Ne pas créer d’auteur fictif pour un contenu IA.

---

# 12.4 ShareLinks

## Politique

Préférer des liens de partage simples sans scripts de suivi tiers.

## Règles

- URL encodée ;
- texte localisé ;
- aucun compteur tiers ;
- aucune collecte avant clic.

---

# 12.5 RelatedArticles

## Sélection

- catégorie ;
- tags ;
- liens internes ;
- pertinence.

## Règles

- uniquement contenus publiés ;
- même locale ;
- pas de doublons ;
- pas de personnalisation intrusive.

---

# 13. Composants commerce

# 13.1 PricingCard

## Structure

- nom ;
- badge ;
- prix ;
- période ;
- description ;
- fonctionnalités ;
- CTA ;
- note fiscale.

## Variantes

```text
default
recommended
compact
```

## Règles

- prix exact ;
- HT/TTC clair ;
- renouvellement visible ;
- pas de faux prix barré ;
- économie calculée ;
- domaine et durée affichés.

---

# 13.2 PricingComparison

Tableau accessible des offres :

- Solo ;
- Duo ;
- Suite complète.

## Mobile

Cartes comparatives ou tableau scrollable.

---

# 13.3 ProductCard

Pour :

- PagesBuilder ;
- MenuBuilder ;
- FooterBuilder.

## Contenu

- nom ;
- description ;
- captures ;
- fonctions ;
- prix ;
- licence ;
- CTA.

---

# 13.4 LicenseGuarantee

## Usage

Expliquer :

```text
Le frontend reste actif après expiration.
```

## Règle

Le composant doit reprendre exactement la politique définie dans
`BUILDER_LICENSE_ENFORCEMENT.md`.

---

# 13.5 SubscriptionTimeline

Étapes :

```text
Activation
Utilisation
Rappel
Expiration
Lecture seule
Renouvellement
```

## Règle

Aucune promesse non implémentée.

---

# 14. Composants formulaires

# 14.1 FormField

## Contenu

- label ;
- contrôle ;
- aide ;
- erreur ;
- statut requis.

## Règles

- label explicite ;
- `id` unique ;
- `autocomplete` approprié ;
- erreur reliée ;
- valeur conservée après erreur ;
- aucune indication uniquement par placeholder.

---

# 14.2 TextInput

Types autorisés :

```text
text
email
url
search
password
tel
```

Chaque type doit être choisi selon la donnée.

---

# 14.3 Textarea

## Règles

- hauteur initiale suffisante ;
- redimensionnement possible ;
- limite visible si nécessaire ;
- aucun HTML brut exécuté.

---

# 14.4 Select

Préférer le contrôle natif sauf besoin complexe réel.

---

# 14.5 Checkbox

## Règles

- label complet ;
- zone cliquable ;
- état visible ;
- pas de case précochée pour un consentement.

---

# 14.6 RadioGroup

Utilisé lorsqu’un seul choix parmi plusieurs est requis.

## Structure

```html
<fieldset>
  <legend>...</legend>
</fieldset>
```

---

# 14.7 FormErrorSummary

## Usage

Résumer les erreurs après soumission.

## Comportement

- focus programmatique approprié ;
- liens vers les champs ;
- texte clair ;
- nombre d’erreurs.

---

# 14.8 SubmitButton

Extension de `Button`.

## États

- normal ;
- loading ;
- success ;
- error.

## Règle

Ne pas désactiver définitivement après une erreur réseau.

---

# 15. Composants feedback

# 15.1 Alert

## Variantes

```text
info
success
warning
error
```

## Structure

- titre ;
- contenu ;
- action facultative ;
- fermeture facultative.

## ARIA

- `role="alert"` uniquement si annonce immédiate nécessaire ;
- sinon contenu statique normal.

---

# 15.2 Toast

## Usage limité

- confirmation courte ;
- copie ;
- sauvegarde.

## Règles

- pas pour une erreur critique ;
- durée suffisante ;
- accessible ;
- ne pas voler le focus ;
- historique ou message persistant si nécessaire.

---

# 15.3 EmptyState

## Usage

- aucune recherche ;
- aucune donnée ;
- aucune traduction ;
- aucune licence.

## Structure

- titre ;
- explication ;
- action.

## Règle

Ne pas présenter une erreur technique comme un état vide.

---

# 15.4 Skeleton

## Usage

Chargement différé.

## Règles

- non nécessaire pour le contenu serveur ;
- animation réduite ;
- dimensions stables ;
- `aria-hidden="true"` ;
- état de chargement annoncé si nécessaire.

---

# 15.5 Progress

## Usage

- installation ;
- upload ;
- génération ;
- étape.

## HTML

Préférer :

```html
<progress>
```

lorsque possible.

---

# 16. Composants interactifs

# 16.1 Accordion

## Implémentation recommandée

```html
<details>
  <summary>Question</summary>
  <div>Réponse</div>
</details>
```

## Variante JS

Uniquement si un comportement exclusif est requis.

## Règles

- clavier natif ;
- état visible ;
- pas de fermeture automatique gênante ;
- contenu dans le DOM ;
- compatible FAQ éditoriale.

---

# 16.2 Tabs

## Usage

- code par environnement ;
- variantes ;
- comparaisons compactes.

## Accessibilité

Implémenter correctement :

- `tablist` ;
- `tab` ;
- `tabpanel` ;
- flèches clavier ;
- état sélectionné ;
- fallback linéaire sans JS.

## Règle

Ne pas cacher un contenu indispensable si JavaScript est absent.

---

# 16.3 Dialog

## Usage

- confirmation ;
- détails ;
- choix ponctuel.

## Règles

- focus géré ;
- fermeture clavier ;
- retour du focus ;
- titre ;
- pas de modal pour un simple message ;
- pas de publicité bloquante.

---

# 16.4 Tooltip

## Usage limité

Un tooltip ne doit pas contenir une information indispensable.

## Règles

- clavier ;
- survol ;
- focus ;
- mobile ;
- relation ARIA ;
- délai raisonnable.

---

# 16.5 CopyButton

## États

```text
Copier
Copié
Erreur
```

## Règle

Le code reste sélectionnable manuellement.

---

# 17. Composants médias

# 17.1 ResponsiveImage

## Propriétés

```text
src
srcset
sizes
alt
width
height
loading
decoding
```

## Règles

- dimensions obligatoires ;
- `alt` pertinent ;
- lazy loading hors Hero ;
- format moderne ;
- fallback ;
- aucune image distante non contrôlée.

---

# 17.2 Figure

## Structure

```html
<figure>
  <img>
  <figcaption>
</figure>
```

---

# 17.3 VideoPlayer

## Règles

- pas d’autoplay sonore ;
- contrôles ;
- sous-titres ;
- poster ;
- ratio stable ;
- consentement avant service tiers ;
- fallback texte.

---

# 17.4 Icon

## Source

Bibliothèque locale ou SVG contrôlé.

## Règles

- décorative : masquée ;
- informative : nom accessible ;
- taille cohérente ;
- couleur via token ;
- aucun SVG utilisateur non nettoyé.

---

# 18. Composants erreurs

# 18.1 ErrorPage

## Variantes

```text
404
410
500
maintenance
```

## Invariants

- statut HTTP conservé ;
- contenu minimal disponible ;
- aucun Builder requis ;
- aucun service externe requis ;
- pas de détail technique.

---

# 18.2 ErrorActions

Actions possibles :

- accueil ;
- documentation ;
- retour ;
- contact ;
- réessayer.

Le bouton « retour » ne doit pas être la seule option.

---

# 19. Composants légaux

# 19.1 LegalPageLayout

## Structure

- H1 ;
- date de mise à jour ;
- sommaire ;
- contenu ;
- liens juridiques liés.

## Règles

- largeur de lecture ;
- coordonnées copiables ;
- aucun contenu dans une image ;
- pas de sticky gênant.

---

# 19.2 LegalNotice

Encart visible en tête d’un document de travail ou non finalisé.

Exemple :

```text
Ce document est un modèle et ne doit pas être publié avant validation.
```

Cet encart ne doit pas apparaître sur une version juridiquement validée,
sauf information réellement utile.

---

# 20. Composants spécifiques FlatCMS

# 20.1 VersionPill

Affiche :

```text
FlatCMS 1.0.0 LTS
```

Données issues de la source de version, pas écrites en dur dans plusieurs
templates.

---

# 20.2 FlatFileDiagram

Présente :

```text
data/
├── pages/
├── posts/
├── media/
└── settings/
```

Rendu accessible sous forme de code ou liste.

---

# 20.3 ModuleCard

## Contenu

- nom ;
- statut ;
- catégorie ;
- description ;
- version ;
- licence ;
- lien.

## Statuts

```text
Core
Optionnel
Premium
Expérimental
Prévu
```

---

# 20.4 BuilderCard

Variante de `ProductCard` avec :

- éditeur concerné ;
- widgets ;
- licence ;
- prix ;
- mode local ;
- expiration.

---

# 20.5 AgentReadyFlow

Flux :

```text
Utilisateur
→ AiAgent
→ AIManager
→ Provider
→ Validation
→ Brouillon
```

## Règle

Toujours afficher la validation humaine.

---

# 20.6 LicenseStatusBanner

## Contextes

- administration ;
- Builder ;
- production de test.

## États

```text
valid
test
expiring
expired
service-unavailable
```

## Invariant

Ne jamais remplacer le contenu public existant.

---

# 21. Contrat PagesBuilder

## Composants exposables

- Hero ;
- HeadingBlock ;
- RichText ;
- FeatureGrid ;
- FeatureCard ;
- BenefitCard ;
- ImageTextSplit ;
- StatItem ;
- QuoteBlock ;
- CTASection ;
- FAQ/Accordion ;
- PricingCard ;
- ComparisonTable ;
- CodePanel ;
- ArchitectureFlow ;
- Spacer ;
- Divider.

## Règles

- schéma JSON versionné ;
- propriétés validées ;
- preview identique au rendu ;
- aucun contenu supprimé après expiration ;
- migration documentée ;
- fallback thème disponible ;
- contenu exportable.

---

# 22. Contrat MenuBuilder

Composants concernés :

- SiteHeader ;
- navigation principale ;
- sous-menu ;
- méga-menu ;
- menu mobile ;
- CTA ;
- LocaleSwitcher.

## Invariant

Une licence absente ou expirée ne doit jamais supprimer la navigation
publique déjà enregistrée.

---

# 23. Contrat FooterBuilder

Composants concernés :

- footer principal ;
- colonnes ;
- menus ;
- logo ;
- coordonnées ;
- newsletter ;
- réseaux sociaux ;
- liens légaux ;
- copyright.

## Invariant

Les liens légaux et le footer publié restent visibles après expiration.

---

# 24. Versioning des composants

Chaque composant exposé aux Builders doit avoir :

```text
id stable
version
schema version
migration
compatibilité minimale
```

## Exemple

```json
{
  "component": "feature-card",
  "version": "1.2.0",
  "schemaVersion": 2
}
```

## Règles

- une propriété supprimée nécessite une migration ;
- une valeur inconnue ne doit pas casser le rendu ;
- conserver un fallback ;
- documenter les changements ;
- tester les données anciennes.

---

# 25. Internationalisation

## Règles générales

- aucun texte visible codé en dur ;
- libellés dans les fichiers de langue ;
- contenus fournis par locale ;
- longueur variable ;
- pluriels ;
- dates localisées ;
- nombres et devises localisés ;
- direction de texte extensible ;
- aucun layout dépendant d’un texte court.

## Test de longueur

Tester avec des libellés plus longs, notamment en allemand.

---

# 26. Responsive

## Breakpoints de référence

Les valeurs exactes sont définies dans `DESIGN_SYSTEM.md`.

Principe :

```text
mobile-first
```

## Tests minimaux

```text
320 px
375 px
768 px
1024 px
1280 px
1440 px
```

## Règles

- aucun scroll horizontal global ;
- tableaux gérés ;
- code scrollable ;
- CTA empilés ;
- grille réduite ;
- menu remplacé par navigation mobile ;
- composants tactiles suffisants.

---

# 27. Accessibilité

Objectif :

```text
WCAG 2.2 niveau AA
```

## Contrôles

- sémantique ;
- clavier ;
- focus ;
- contraste ;
- reflow ;
- zoom ;
- messages d’erreur ;
- labels ;
- cibles ;
- animations ;
- lecteurs d’écran ;
- contenu alternatif.

## Interdictions

- `outline: none` sans remplacement ;
- texte dans une image ;
- couleur seule ;
- hover uniquement ;
- contenu caché inaccessible ;
- ordre visuel différent de l’ordre DOM sans raison.

---

# 28. Performance

## Principes

- CSS critique limité ;
- JavaScript par composant ;
- chargement conditionnel ;
- images optimisées ;
- dimensions réservées ;
- polices locales ;
- aucune dépendance lourde pour un petit composant ;
- aucune bibliothèque d’animation globale obligatoire.

## Budgets recommandés

À ajuster dans le plan de performance :

```text
CSS composant individuel : idéalement < 10 Ko minifié
JS composant individuel : idéalement < 8 Ko minifié
```

Les composants statiques ne doivent pas charger de JavaScript.

---

# 29. Sécurité

## Données

- échappement ;
- nettoyage HTML ;
- validation URL ;
- liste blanche des variantes ;
- aucune exécution de code utilisateur ;
- aucun style arbitraire ;
- pas de secrets ;
- CSP compatible.

## Liens

Refuser ou filtrer :

```text
javascript:
data: non autorisé
vbscript:
```

## SVG

Nettoyer les SVG importés ou utiliser une bibliothèque interne.

---

# 30. Documentation de chaque composant

Chaque README doit inclure :

- objectif ;
- exemple ;
- propriétés ;
- valeurs par défaut ;
- variantes ;
- HTML ;
- CSS ;
- JS ;
- accessibilité ;
- responsive ;
- limites ;
- compatibilité Builder ;
- migrations ;
- tests.

---

# 31. Aperçu des composants

Créer une page interne de type catalogue :

```text
/fr-FR/styleguide/
```

ou une route de développement protégée.

Elle doit afficher :

- toutes les variantes ;
- clair/sombre ;
- états ;
- contenu long ;
- erreurs ;
- mobile ;
- RTL futur ;
- six locales.

## Production

La route peut être :

- désactivée ;
- protégée ;
- `noindex`.

---

# 32. Tests unitaires

Exemples :

```text
testButtonRendersAsLinkWhenHrefProvided
testButtonRendersAsButtonForAction
testIconButtonRequiresAccessibleName
testHeadingLevelIsValidated
testFeatureCardEscapesContent
testAlertVariantUsesKnownValue
testCodeBlockDoesNotExposeSecret
testAccordionWorksWithoutJavascript
testTabsHaveFallbackContent
testPricingCardDisplaysBillingPeriod
testResponsiveImageRequiresDimensions
testLocaleSwitcherHidesMissingTranslation
testLicenseBannerNeverReplacesFrontendContent
```

---

# 33. Tests d’intégration

## PagesBuilder

1. insérer chaque composant exposé ;
2. enregistrer ;
3. prévisualiser ;
4. publier ;
5. changer de thème clair/sombre ;
6. changer de locale ;
7. expirer la licence ;
8. vérifier le rendu inchangé.

## MenuBuilder

1. construire le header ;
2. tester clavier ;
3. désactiver JavaScript ;
4. expirer la licence ;
5. vérifier la navigation publique.

## FooterBuilder

1. construire les colonnes ;
2. ajouter les liens légaux ;
3. tester mobile ;
4. expirer la licence ;
5. vérifier le footer.

---

# 34. Tests end-to-end

## Scénario composant statique

```text
Étant donné une page contenant une FeatureCard
Quand JavaScript est désactivé
Alors le titre, la description et le lien restent disponibles
```

## Scénario mode sombre

```text
Étant donné le thème système sombre
Quand la page est chargée
Alors les tokens sombres sont appliqués
Et le contraste reste conforme
```

## Scénario traduction longue

```text
Étant donné une locale avec un libellé long
Quand le composant est affiché sur mobile
Alors aucun texte essentiel n’est tronqué
```

## Scénario expiration Builder

```text
Étant donné un layout publié avec PagesBuilder
Quand la licence expire
Alors tous les composants restent rendus
Et aucune donnée n’est modifiée
```

---

# 35. Audit demandé à Codex

Codex doit produire :

## Inventaire

```text
Composant
Fichier
Catégorie
Utilisé par
JavaScript
Builder
Tests
Statut
```

## Écarts

```text
Composant
Problème
Impact
Règle violée
Correction
Test
```

## Confirmations critiques

- [ ] Tous les composants utilisent les tokens.
- [ ] Aucun composant essentiel ne dépend d’un CDN.
- [ ] Tous les contrôles sont accessibles au clavier.
- [ ] Les contenus essentiels fonctionnent sans JavaScript.
- [ ] Les variantes sont validées.
- [ ] Les URLs sont filtrées.
- [ ] Les six locales sont testées.
- [ ] Les modes clair et sombre sont testés.
- [ ] Les composants Builder restent rendus après expiration.
- [ ] Les pages 404/500 n’ont aucune dépendance premium.
- [ ] Aucun secret n’est présent dans les exemples.
- [ ] Les migrations de schéma sont documentées.

---

# 36. Critères d’acceptation

La bibliothèque est acceptée si :

1. les composants sont identifiés et documentés ;
2. leurs APIs sont explicites ;
3. les tokens du design system sont utilisés ;
4. les états accessibles sont implémentés ;
5. le rendu est responsive ;
6. le contenu fonctionne sans JavaScript lorsque possible ;
7. les composants Builders utilisent des schémas validés ;
8. une expiration de licence ne modifie pas le frontend ;
9. les tests automatisés passent ;
10. aucun composant ne copie directement l’identité du site Webby.

---

# 37. Composants prioritaires pour le lancement

## P0

- Button ;
- IconButton ;
- Link ;
- Badge ;
- HeadingBlock ;
- RichText ;
- Hero ;
- CTAGroup ;
- FeatureCard ;
- FeatureGrid ;
- StatItem ;
- CodePanel ;
- ArchitectureFlow ;
- SiteHeader ;
- Breadcrumbs ;
- LocaleSwitcher ;
- ThemeToggle ;
- DocumentationSidebar ;
- TableOfContents ;
- Callout ;
- StepList ;
- ArticleCard ;
- ArticleHero ;
- PricingCard ;
- PricingComparison ;
- Accordion ;
- FormField ;
- Alert ;
- ErrorPage ;
- LegalPageLayout ;
- SiteFooter.

## P1

- Tabs ;
- Dialog ;
- Tooltip ;
- LogoCloud ;
- QuoteBlock ;
- RelatedArticles ;
- SubscriptionTimeline ;
- DocumentationSearch ;
- AgentReadyFlow ;
- FlatFileDiagram.

## P2

- composants avancés issus des besoins réels ;
- animations complexes ;
- visualisations interactives ;
- widgets marketplace.

---

# 38. Checklist de release

- [ ] IDs stables.
- [ ] Schémas validés.
- [ ] README présents.
- [ ] Tokens utilisés.
- [ ] Dark mode.
- [ ] Light mode.
- [ ] Six locales.
- [ ] Mobile 320 px.
- [ ] Clavier.
- [ ] Lecteur d’écran.
- [ ] Contrastes.
- [ ] Réduction des animations.
- [ ] Sans JavaScript.
- [ ] CSP.
- [ ] Aucun CDN obligatoire.
- [ ] Aucun secret.
- [ ] PagesBuilder.
- [ ] MenuBuilder.
- [ ] FooterBuilder.
- [ ] Expiration de licence.
- [ ] 404 et 500.
- [ ] Tests automatiques.
- [ ] Documentation à jour.

---

# 39. Sources internes

- `THEME_SPECIFICATION.md`
- `DESIGN_SYSTEM.md`
- `NAVIGATION_SPECIFICATION.md`
- `HOMEPAGE_CONTENT.md`
- `DOCUMENTATION_CONTENT.md`
- `PRICING_CONTENT.md`
- `AGENT_READY_CONTENT.md`
- `CONTACT_CONTENT.md`
- `404_CONTENT.md`
- `BUILDER_LICENSE_ENFORCEMENT.md`
- widgets existants de PagesBuilder ;
- composants réels du thème.

---

# 40. Références externes

- W3C — WCAG 2.2  
  https://www.w3.org/TR/WCAG22/

- WAI-ARIA Authoring Practices Guide  
  https://www.w3.org/WAI/ARIA/apg/

- MDN — HTML elements reference  
  https://developer.mozilla.org/en-US/docs/Web/HTML/Reference/Elements

- MDN — Using media queries for accessibility  
  https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_media_queries/Using_media_queries_for_accessibility

Les références externes encadrent l’accessibilité et les comportements
standards. Le contrat des composants FlatCMS reste défini par ce document
et par le code du thème.

---

# 41. Journal des mises à jour

| Date | Version | Modification | Auteur |
|---|---:|---|---|
| 2026-06-08 | 1.0 | Première spécification de la bibliothèque de composants | ChatGPT / Alain BROYE |

---

# 42. Prochaine action

Après validation et ajout dans le Drive :

```text
Créer HOMEPAGE_WIREFRAME.md
```

Ce document traduira `HOMEPAGE_CONTENT.md`, `THEME_SPECIFICATION.md` et
cette bibliothèque en un wireframe précis :

- ordre des sections ;
- composants utilisés ;
- largeur ;
- contenu ;
- médias ;
- comportement responsive ;
- animations ;
- maillage ;
- critères d’acceptation.

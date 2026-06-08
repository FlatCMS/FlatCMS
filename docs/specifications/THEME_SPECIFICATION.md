# THEME_SPECIFICATION — Thème officiel `flatcms`

> **Spécification fonctionnelle, visuelle et technique destinée à Codex**
>
> Projet : FlatCMS  
> Thème : `flatcms`  
> Type : thème frontend officiel du futur site `https://flat-cms.fr`  
> Version initiale : `1.0.0`  
> Date : 8 juin 2026  
> Référence visuelle : capture de la page d’accueil de Webby fournie par Alain BROYE  
> Statut : spécification de conception — à implémenter, tester et valider  
> Priorité : critique pour la construction du site officiel

---

## 1. Objet

Le thème `flatcms` doit devenir le thème frontend officiel du site de la marque FlatCMS.

Il doit servir à publier :

- la page d’accueil ;
- les pages institutionnelles ;
- les pages Fonctionnalités et Architecture ;
- la documentation ;
- le blog ;
- les pages des Builders ;
- les tarifs ;
- les licences ;
- les pages juridiques ;
- les pages de contact et d’erreur ;
- les contenus multilingues.

Le thème s’inspire de la logique visuelle et du rythme du site Webby présenté dans la capture fournie : esthétique SaaS/tech sombre, typographie forte, panneaux techniques, grilles de cartes, halos subtils et grandes respirations verticales.

Il ne doit pas reproduire Webby à l’identique.

Le résultat doit être :

- original ;
- immédiatement identifiable comme FlatCMS ;
- cohérent avec la couleur officielle `#4F46E5` ;
- juridiquement distinct ;
- compatible avec l’architecture et les Builders de FlatCMS ;
- performant et accessible.

---

# 2. Principes non négociables

## 2.1 Identité originale

Le thème peut reprendre des principes généraux de composition :

- Hero centré ;
- grand titre en dégradé ;
- navigation compacte ;
- panneaux techniques ;
- grilles de fonctionnalités ;
- alternance de surfaces sombres ;
- tableau comparatif ;
- accordéon FAQ ;
- CTA final en dégradé.

Il ne doit pas reprendre à l’identique :

- le logo ;
- les textes ;
- les icônes ;
- les illustrations ;
- les proportions exactes ;
- les animations spécifiques ;
- les compositions pixel perfect ;
- le code CSS ou JavaScript du site de référence.

## 2.2 Contenu essentiel sans JavaScript

Sans JavaScript, le visiteur doit encore pouvoir :

- lire les pages ;
- utiliser la navigation principale ;
- suivre les liens ;
- consulter les tarifs ;
- lire les FAQ ouvertes dans le HTML ou via un composant natif ;
- remplir les formulaires essentiels ;
- télécharger FlatCMS ;
- accéder aux pages juridiques.

JavaScript apporte seulement une amélioration progressive.

## 2.3 Aucune dépendance CDN obligatoire

Le thème ne doit pas dépendre en production de :

- Tailwind chargé depuis un CDN ;
- Bootstrap chargé depuis un CDN ;
- Google Fonts obligatoire ;
- bibliothèque d’icônes distante ;
- script d’animation distant ;
- service tiers requis pour le rendu.

Les assets nécessaires doivent être locaux, versionnés et optimisés.

## 2.4 Compatibilité sans Builder

Toutes les pages du site doivent pouvoir être rendues sans PagesBuilder, MenuBuilder ou FooterBuilder.

Les Builders apportent une interface de composition, pas une dépendance d’exécution obligatoire pour le thème.

## 2.5 Rendu préservé

Une licence Builder absente ou expirée ne doit jamais modifier le rendu public du thème.

Référence obligatoire :

```text
BUILDER_LICENSE_ENFORCEMENT.md
```

---

# 3. Positionnement visuel

## 3.1 Direction générale

```text
Premium technologique
Minimalisme sombre
Architecture lisible
Contrastes nets
Indigo officiel
Accents violet et cyan
Effets lumineux maîtrisés
Grandes respirations
Interfaces techniques mises en scène
```

## 3.2 Sensation recherchée

Le site doit inspirer :

- simplicité ;
- maîtrise technique ;
- fiabilité ;
- modernité ;
- rapidité ;
- transparence ;
- précision ;
- autonomie.

Il ne doit pas donner une impression :

- de template SaaS générique ;
- de surenchère néon ;
- de jeu vidéo ;
- de page crypto ;
- de framework JavaScript lourd ;
- de copie de Webby ;
- de landing page exclusivement commerciale.

---

# 4. Arborescence du thème

```text
themes/flatcms/
├── assets/
│   ├── css/
│   │   ├── tokens.css
│   │   ├── reset.css
│   │   ├── base.css
│   │   ├── typography.css
│   │   ├── layout.css
│   │   ├── components.css
│   │   ├── utilities.css
│   │   ├── dark.css
│   │   ├── light.css
│   │   ├── print.css
│   │   └── pages/
│   ├── js/
│   │   ├── app.js
│   │   ├── navigation.js
│   │   ├── theme-toggle.js
│   │   ├── disclosure.js
│   │   ├── copy-code.js
│   │   ├── search.js
│   │   └── motion.js
│   ├── img/
│   │   ├── brand/
│   │   ├── illustrations/
│   │   ├── screenshots/
│   │   ├── diagrams/
│   │   └── og/
│   ├── icons/
│   └── fonts/
├── components/
│   ├── alert.php
│   ├── badge.php
│   ├── breadcrumb.php
│   ├── button.php
│   ├── card.php
│   ├── code-panel.php
│   ├── comparison-table.php
│   ├── cta-banner.php
│   ├── feature-card.php
│   ├── hero.php
│   ├── icon.php
│   ├── locale-switcher.php
│   ├── pricing-card.php
│   ├── search-form.php
│   ├── section-heading.php
│   ├── stat.php
│   └── status-badge.php
├── layouts/
│   ├── default.php
│   ├── landing.php
│   ├── documentation.php
│   ├── article.php
│   ├── legal.php
│   ├── error.php
│   └── minimal.php
├── partials/
│   ├── head.php
│   ├── header.php
│   ├── navigation.php
│   ├── mobile-navigation.php
│   ├── footer.php
│   ├── skip-link.php
│   ├── breadcrumbs.php
│   ├── cookie-settings.php
│   └── structured-data.php
├── templates/
│   ├── home.php
│   ├── page.php
│   ├── documentation-index.php
│   ├── documentation-page.php
│   ├── blog-index.php
│   ├── blog-post.php
│   ├── pricing.php
│   ├── contact.php
│   ├── search.php
│   ├── 404.php
│   └── 500.php
├── builder/
│   ├── widget-mappings.php
│   ├── section-presets.php
│   └── compatibility.php
├── config/
│   ├── theme.php
│   ├── navigation.php
│   ├── components.php
│   └── assets.php
├── languages/
│   ├── fr-FR.json
│   ├── en-US.json
│   ├── de-DE.json
│   ├── es-ES.json
│   ├── it-IT.json
│   └── pt-PT.json
├── screenshots/
│   ├── screenshot.webp
│   └── thumbnail.webp
└── manifest.json
```

La structure exacte doit respecter le contrat réel des thèmes FlatCMS.

---

# 5. Manifeste conceptuel

```json
{
  "id": "flatcms",
  "name": "FlatCMS Official",
  "version": "1.0.0",
  "type": "frontend",
  "description": "Thème officiel du site FlatCMS.",
  "author": "Alain BROYE",
  "license": "À CONFIRMER",
  "requires": {
    "flatcms": ">=1.0.0",
    "php": ">=8.3"
  },
  "locales": [
    "fr-FR",
    "en-US",
    "de-DE",
    "es-ES",
    "it-IT",
    "pt-PT"
  ],
  "supports": {
    "darkMode": true,
    "lightMode": true,
    "pagesBuilder": true,
    "menuBuilder": true,
    "footerBuilder": true,
    "structuredData": true,
    "responsiveImages": true
  }
}
```

Les propriétés doivent être adaptées au schéma de manifeste officiel.

---

# 6. Design tokens

## 6.1 Couleurs sombres

```css
:root {
  --fc-color-bg: #070A12;
  --fc-color-bg-alt: #0A0F1A;
  --fc-color-surface: #0D121C;
  --fc-color-surface-raised: #111827;
  --fc-color-surface-soft: #151C2C;

  --fc-color-primary: #4F46E5;
  --fc-color-primary-hover: #4338CA;
  --fc-color-primary-light: #818CF8;
  --fc-color-violet: #7C3AED;
  --fc-color-cyan: #22D3EE;
  --fc-color-green: #22C55E;
  --fc-color-amber: #F59E0B;
  --fc-color-red: #EF4444;

  --fc-color-text: #F8FAFC;
  --fc-color-text-soft: #CBD5E1;
  --fc-color-text-muted: #94A3B8;
  --fc-color-text-subtle: #64748B;

  --fc-color-border: rgba(148, 163, 184, 0.14);
  --fc-color-border-strong: rgba(148, 163, 184, 0.26);
  --fc-color-focus: #A5B4FC;
}
```

## 6.2 Mode clair

```css
[data-theme="light"] {
  --fc-color-bg: #FFFFFF;
  --fc-color-bg-alt: #F8FAFC;
  --fc-color-surface: #FFFFFF;
  --fc-color-surface-raised: #F8FAFC;
  --fc-color-surface-soft: #EEF2FF;

  --fc-color-text: #0F172A;
  --fc-color-text-soft: #334155;
  --fc-color-text-muted: #64748B;
  --fc-color-text-subtle: #94A3B8;

  --fc-color-border: rgba(15, 23, 42, 0.12);
  --fc-color-border-strong: rgba(15, 23, 42, 0.22);
}
```

## 6.3 Dégradés

```css
--fc-gradient-brand: linear-gradient(
  90deg,
  #A5B4FC 0%,
  #4F46E5 42%,
  #7C3AED 72%,
  #22D3EE 100%
);

--fc-gradient-cta: linear-gradient(
  120deg,
  rgba(79, 70, 229, 0.30),
  rgba(124, 58, 237, 0.24),
  rgba(34, 211, 238, 0.12)
);
```

## 6.4 Couleurs fonctionnelles

Les états ne doivent jamais dépendre uniquement de la couleur.

```text
Succès       vert + icône + texte
Attention    ambre + icône + texte
Erreur       rouge + icône + texte
Information  indigo ou cyan + icône + texte
```

---

# 7. Typographie

## 7.1 Police

Priorité : police locale variable avec excellente lisibilité et licence compatible.

Pile de secours :

```css
font-family: Inter, ui-sans-serif, system-ui, -apple-system,
  BlinkMacSystemFont, "Segoe UI", sans-serif;
```

Une alternative comme `Manrope` peut être évaluée pour les titres, sans dépendance distante.

## 7.2 Échelle

```css
--fc-font-size-xs: 0.75rem;
--fc-font-size-sm: 0.875rem;
--fc-font-size-base: 1rem;
--fc-font-size-lg: 1.125rem;
--fc-font-size-xl: 1.25rem;
--fc-font-size-2xl: 1.5rem;
--fc-font-size-3xl: 1.875rem;
--fc-font-size-4xl: 2.25rem;
--fc-font-size-5xl: 3rem;
--fc-font-size-6xl: 3.75rem;
--fc-font-size-7xl: 4.5rem;
```

## 7.3 Titres fluides

```css
.hero-title {
  font-size: clamp(2.5rem, 7vw, 5.5rem);
  line-height: 0.98;
  letter-spacing: -0.045em;
}

.section-title {
  font-size: clamp(2rem, 4vw, 3.5rem);
  line-height: 1.05;
  letter-spacing: -0.035em;
}
```

## 7.4 Longueur de lecture

Contenu courant :

```css
max-width: 72ch;
```

Introduction Hero :

```css
max-width: 62ch;
```

---

# 8. Espacements, rayons et ombres

## 8.1 Échelle d’espacement

```css
--fc-space-1: 0.25rem;
--fc-space-2: 0.5rem;
--fc-space-3: 0.75rem;
--fc-space-4: 1rem;
--fc-space-5: 1.25rem;
--fc-space-6: 1.5rem;
--fc-space-8: 2rem;
--fc-space-10: 2.5rem;
--fc-space-12: 3rem;
--fc-space-16: 4rem;
--fc-space-20: 5rem;
--fc-space-24: 6rem;
--fc-space-32: 8rem;
```

## 8.2 Sections

Desktop :

```css
padding-block: clamp(5rem, 9vw, 9rem);
```

Mobile : minimum `4rem`.

## 8.3 Rayons

```css
--fc-radius-sm: 0.5rem;
--fc-radius-md: 0.75rem;
--fc-radius-lg: 1rem;
--fc-radius-xl: 1.5rem;
--fc-radius-pill: 999px;
```

## 8.4 Ombres

Les ombres restent discrètes.

```css
--fc-shadow-card: 0 16px 50px rgba(0, 0, 0, 0.20);
--fc-shadow-glow: 0 0 60px rgba(79, 70, 229, 0.16);
```

---

# 9. Grille et conteneurs

## Conteneur principal

```css
.container {
  width: min(100% - 2rem, 76rem);
  margin-inline: auto;
}
```

## Conteneur large

```css
.container-wide {
  width: min(100% - 2rem, 88rem);
  margin-inline: auto;
}
```

## Grille de référence

- mobile : 1 colonne ;
- tablette : 2 colonnes ;
- desktop : 12 colonnes logiques ;
- grille de cartes : 3 colonnes ;
- grandes cartes produit : 3 colonnes ;
- contenu + panneau : 7 / 5 ou 6 / 6.

## Ruptures proposées

```css
--fc-breakpoint-sm: 36rem;
--fc-breakpoint-md: 48rem;
--fc-breakpoint-lg: 64rem;
--fc-breakpoint-xl: 80rem;
```

Les composants doivent rester robustes entre les ruptures, sans dépendre uniquement de valeurs fixes.

---

# 10. Header

## 10.1 Desktop

Structure :

```text
Logo FlatCMS
Navigation principale
Sélecteur de langue
Bascule clair/sombre
CTA Télécharger FlatCMS
```

## 10.2 Navigation

```text
Pourquoi FlatCMS
Fonctionnalités
Architecture
Documentation
Tarifs
Blog
```

## 10.3 Comportement

- header sticky ;
- hauteur compacte ;
- fond légèrement translucide après scroll ;
- `backdrop-filter` seulement comme amélioration ;
- bordure inférieure subtile ;
- logo toujours lisible ;
- focus clavier visible ;
- aucun menu au survol uniquement.

## 10.4 Mobile

- bouton avec nom accessible ;
- panneau latéral ou plein écran ;
- fermeture au clavier ;
- touche Échap ;
- focus contenu dans le panneau ;
- retour du focus au bouton ;
- sous-menus en disclosures ;
- aucun défilement du fond lorsque le menu est ouvert.

## 10.5 MenuBuilder

Si MenuBuilder est actif, il peut fournir la structure avancée.

Le thème doit conserver un rendu standard fonctionnel lorsque MenuBuilder :

- est absent ;
- est désactivé ;
- a une licence expirée ;
- rencontre une erreur.

---

# 11. Hero de la page d’accueil

## Sur-titre

```text
FlatCMS v1.0.0 LTS Core
```

## H1 proposé

```text
Le CMS PHP flat-file simple, rapide et agent-ready
```

Le groupe central peut recevoir le dégradé de marque.

## Introduction

```text
Créez des sites modernes avec un CMS PHP natif, modulaire et sans
serveur SQL, fondé sur HMVC, PSR-4 et un stockage JSON structuré.
```

## CTA

```text
Télécharger FlatCMS
Explorer la documentation
```

## Statistiques

```text
PHP 8.3+
6 locales
0 serveur SQL
AGPL-3.0-or-later
```

## Décor

- halos indigo diffus ;
- trame ou grille très légère ;
- lignes techniques ;
- aucune vidéo automatique ;
- aucune animation lourde ;
- aucun texte intégré à une image.

---

# 12. Panneau technique principal

Le panneau remplace le terminal de la référence par une représentation propre à FlatCMS.

## Contenu

Colonne A : cycle de requête.

```text
Request
→ Router
→ Module
→ Service
→ JSON
→ View
→ Response
```

Colonne B : extrait PHP ou arborescence.

```php
$response = $router->dispatch($request);
return $response->send();
```

## Présentation

- fenêtre technique sombre ;
- onglets `Cycle`, `Module`, `Data` ;
- syntaxe locale ;
- bouton Copier accessible ;
- contenu disponible sans JavaScript ;
- animation d’apparition facultative ;
- aucune animation de frappe obligatoire.

---

# 13. Citation manifeste

## Texte proposé

```text
« Un CMS ne devrait pas devenir plus complexe que le site qu’il doit gérer. »
```

## Contenu associé

- portrait ou avatar sobre d’Alain BROYE ;
- nom ;
- rôle : auteur et mainteneur principal ;
- lien vers À propos.

## Présentation

- section pleine largeur ;
- fond légèrement différencié ;
- guillemets graphiques discrets ;
- pas de faux témoignage ;
- pas de notation fictive.

---

# 14. Grille des fonctions essentielles

## Cartes

```text
Pages
Articles
Médias
Menus
Footer
Thèmes
Multilingue
Modules HMVC
Données structurées
```

## Carte type

- icône locale ;
- titre H3 ;
- 2 ou 3 lignes ;
- lien explicite ;
- bordure subtile ;
- fond élevé ;
- halo limité au hover ;
- transformation maximale `translateY(-2px)` ;
- aucun mouvement si `prefers-reduced-motion: reduce`.

---

# 15. Section « Pourquoi FlatCMS ? »

## Composition

Desktop : liste de bénéfices à gauche, panneau technique à droite.

### Bénéfices

```text
Sans serveur SQL
PHP natif
Architecture lisible
Stockage JSON
Déploiement simple
Contrôle des données
```

### Panneau rapide

```text
Version stable       1.0.0 LTS
PHP minimum          8.3
Architecture         HMVC
Autoloading          PSR-4
Stockage             JSON
Licence du Core      AGPL-3.0-or-later
Locales              6
```

Les valeurs doivent provenir d’une source unique de configuration lorsqu’elles sont dynamiques.

---

# 16. Section Architecture

## Diagramme

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

## Cartes

```text
Core
Modules
Services
Hooks
Thèmes
Data
```

## Règles

- diagramme en HTML/CSS ou SVG accessible ;
- description textuelle ;
- couleurs non indispensables à la compréhension ;
- version mobile verticale ;
- aucun canvas opaque aux lecteurs d’écran.

---

# 17. Section Builders premium

## Produits

```text
PagesBuilder
MenuBuilder
FooterBuilder
```

## Tarifs

```text
Builder Solo       29,90 € HT/an
Bundle Duo         49,90 € HT/an
Suite complète     59,90 € HT/an
```

## Carte produit

- badge Premium ;
- icône ;
- titre ;
- résumé ;
- fonctionnalités ;
- prix seul ;
- lien détail ;
- CTA ;
- statut réel.

## Suite complète

- visuellement recommandée ;
- contraste accessible ;
- pas de fausse urgence ;
- réduction réelle ;
- mention `1 domaine de production / 12 mois`.

## Expiration

Afficher clairement :

```text
Le frontend et les contenus publiés restent fonctionnels après expiration.
```

---

# 18. Section démarrage

## Étapes

```text
1. Télécharger
2. Configurer public/
3. Lancer l’installateur
4. Créer le site
```

## Panneau de commande

Exemple contextuel, sans inventer une commande d’installation inexistante.

Le panneau peut afficher :

```text
Document root → /path/to/flatcms/public
Installateur  → index.php?step=1
```

## CTA

```text
Lire le guide d’installation
Télécharger FlatCMS
```

---

# 19. Section Agent-ready

## Schéma

```text
Utilisateur
→ AiAgent
→ AIManager
→ Provider
→ Validation
→ Brouillon ou action autorisée
```

## Principes affichés

- providers interchangeables ;
- outils contrôlés ;
- permissions ;
- validation humaine ;
- secrets côté serveur ;
- FlatCMS fonctionne sans IA.

## Message de transparence

```text
Agent-ready décrit une architecture préparée. Toutes les fonctions IA ne
sont pas nécessairement incluses dans le LTS Core.
```

---

# 20. Documentation et communauté

## Cartes principales

```text
Documentation
Tutoriels
Blog technique
GitHub
```

## Cartes secondaires possibles

```text
Démo
Roadmap
Contribuer
Signaler un problème
```

## Règles

- liens réels ;
- pas de compteur communautaire fictif ;
- pas de nombre de téléchargements non vérifié ;
- pas de témoignage inventé ;
- GitHub identifié comme service externe.

---

# 21. Tableau comparatif

## Critères proposés

```text
Serveur SQL requis
Stockage de contenu
Administration incluse
Multilingue
Déploiement
Extensibilité
Licence
Architecture agent-ready
```

## Comparés possibles

```text
FlatCMS
WordPress
Grav
CMS headless générique
```

## Règles éditoriales

- données sourcées ;
- date de vérification ;
- formulations nuancées ;
- pas de gagnant artificiel ;
- `Variable` lorsque la catégorie est trop large ;
- lien vers une page comparative détaillée ;
- tableau responsive avec en-têtes accessibles.

---

# 22. FAQ

## Questions de départ

- FlatCMS nécessite-t-il MySQL ?
- Quelle version de PHP faut-il utiliser ?
- Le LTS Core est-il gratuit ?
- Puis-je tester les Builders en local ?
- Que se passe-t-il à l’expiration ?
- FlatCMS est-il multilingue ?
- Puis-je développer mes modules ?
- AiAgent est-il inclus dans le Core ?

## Implémentation

Préférer :

```html
<details>
  <summary>Question</summary>
  <div>Réponse</div>
</details>
```

ou un composant accessible équivalent.

## Règles

- fonctionnement sans JS ;
- état clavier ;
- focus visible ;
- animation désactivable ;
- aucune FAQ JSON-LD systématique.

---

# 23. CTA final

## Titre

```text
Prêt à construire un site plus simple à maintenir ?
```

## CTA

```text
Télécharger FlatCMS
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

- panneau large ;
- gradient de marque ;
- contraste AA minimum ;
- fond décoratif non bloquant ;
- aucune fausse urgence.

---

# 24. Footer

## Colonnes

### Produit

```text
Pourquoi FlatCMS
Fonctionnalités
Architecture
Tarifs
Téléchargement
```

### Documentation

```text
Démarrage
Installation
Administration
Développement
Dépannage
```

### Communauté

```text
GitHub
Contribuer
Roadmap
Blog
Contact
```

### Ressources

```text
Licences
Sécurité
Statut
Presse
Marque
```

## Ligne juridique

```text
Mentions légales
Confidentialité
Gérer mes cookies
Licences
CGV
Résilier votre contrat
Sécurité
```

## FooterBuilder

Le footer standard doit fonctionner sans FooterBuilder.

FooterBuilder peut remplacer ou enrichir la composition, sans rendre les liens juridiques dépendants d’une licence active.

---

# 25. Templates

## `home.php`

Ordre initial :

1. Header
2. Hero
3. Panneau technique
4. Citation manifeste
5. Fonctionnalités
6. Pourquoi FlatCMS
7. Architecture
8. Builders
9. Démarrage
10. Agent-ready
11. Documentation et communauté
12. Comparatif
13. FAQ
14. CTA final
15. Footer

## `page.php`

- Hero interne compact ;
- fil d’Ariane ;
- sommaire optionnel ;
- contenu ;
- contenus associés ;
- CTA final.

## `documentation-page.php`

- navigation latérale ;
- fil d’Ariane ;
- titre ;
- métadonnées de version ;
- sommaire ;
- contenu ;
- précédent/suivant ;
- signalement d’erreur.

## `blog-post.php`

- catégorie ;
- H1 ;
- résumé ;
- auteur ;
- dates ;
- image ;
- contenu ;
- sources ;
- partage non intrusif ;
- articles liés.

## `legal.php`

- largeur de lecture maîtrisée ;
- aucune animation nécessaire ;
- date de mise à jour ;
- sommaire ;
- impression propre.

## `error.php`

Conforme à `404_CONTENT.md` et aux futurs contrats 500.

---

# 26. Composants

## Boutons

Variantes :

```text
primary
secondary
ghost
link
danger
```

États :

```text
default
hover
focus
active
disabled
loading
```

Hauteur tactile minimale recommandée : `44px`.

## Badges

```text
LTS
Core
Premium
Optionnel
Expérimental
Prévu
Nouveau
```

Le texte reste toujours visible.

## Cartes

Variantes :

```text
feature
product
documentation
article
stat
comparison
```

Une carte entière ne doit pas créer des liens imbriqués invalides.

## Alertes

```text
info
success
warning
error
legal
security
```

## Code

- langage indiqué ;
- bouton Copier ;
- lignes longues scrollables ;
- contraste ;
- pas de hauteur fixe coupant le contenu ;
- contenu lisible sans coloration.

## Tableaux

- `caption` ;
- `th` corrects ;
- scroll horizontal contrôlé ;
- première colonne sticky uniquement si accessible ;
- alternative mobile si nécessaire.

---

# 27. PagesBuilder

## Compatibilité

Le thème doit fournir des styles pour les widgets officiels :

```text
heading
text
image
button
hero
feature-grid
content-split-media
stats-section
testimonial-cards
faq-accordion
pricing-plans
video-player
logo-cloud
carousel
contact
newsletter
spacer
divider
```

## Contrat de widget

Chaque widget doit :

- utiliser les tokens du thème ;
- respecter la largeur du conteneur ;
- fonctionner en clair et sombre ;
- avoir un rendu sans données ;
- valider ses champs ;
- générer du HTML sémantique ;
- éviter les styles inline non nécessaires ;
- rester utilisable sans animation ;
- ne pas charger un asset plusieurs fois.

## Presets

Le thème peut fournir des presets :

```text
flatcms-hero
flatcms-feature-grid
flatcms-architecture
flatcms-pricing
flatcms-agent-ready
flatcms-final-cta
```

Un preset n’est pas une donnée obligatoire pour le rendu.

---

# 28. MenuBuilder

Le thème doit fournir :

- styles des niveaux ;
- méga-menu ;
- groupes ;
- icônes ;
- descriptions ;
- CTA ;
- navigation mobile ;
- focus clavier ;
- `aria-expanded` ;
- fermeture Échap ;
- clic extérieur comme amélioration.

## Interdictions

- sous-menu uniquement au hover ;
- tabulation derrière un panneau fermé ;
- menu impossible à fermer ;
- HTML différent dépourvu de fallback ;
- liens juridiques uniquement dans un méga-menu.

---

# 29. FooterBuilder

Le thème doit définir des emplacements :

```text
footer-intro
footer-product
footer-documentation
footer-community
footer-resources
footer-legal
footer-bottom
```

## Invariants

Toujours rendre accessibles :

- mentions légales ;
- confidentialité ;
- cookies ;
- licences ;
- contact ;
- sécurité.

L’administrateur ne doit pas pouvoir supprimer accidentellement tous les accès juridiques sans avertissement.

---

# 30. Mode sombre et clair

## Stratégie

Ordre :

1. préférence enregistrée ;
2. préférence système ;
3. sombre par défaut pour l’identité du site.

## Stockage

Une préférence locale strictement nécessaire peut être stockée sans profilage.

## Prévention du flash

Appliquer le thème choisi avant le rendu visuel, avec un script minimal local dans le `<head>` si nécessaire.

## Contrôle

- bouton accessible ;
- nom dynamique ;
- icône + texte accessible ;
- aucune perte d’information ;
- diagrammes et screenshots adaptés.

---

# 31. Animations

## Autorisées

- apparition légère ;
- halo ;
- transition de bordure ;
- déplacement de 2 à 8 pixels ;
- ouverture de panneau ;
- progression non essentielle.

## Durées

```text
rapide : 120 à 180 ms
standard : 180 à 260 ms
entrée de section : 300 à 500 ms maximum
```

## Réduction du mouvement

```css
@media (prefers-reduced-motion: reduce) {
  *, *::before, *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    scroll-behavior: auto !important;
    transition-duration: 0.01ms !important;
  }
}
```

## Interdictions

- parallaxe forte ;
- texte qui clignote ;
- animation de fond permanente coûteuse ;
- déplacement indispensable à la compréhension ;
- autoplay vidéo ;
- curseur personnalisé ;
- scroll hijacking.

---

# 32. Accessibilité

Objectif : conformité WCAG 2.2 niveau AA.

## Exigences

- lien d’évitement ;
- landmarks ;
- H1 unique ;
- ordre de titres ;
- navigation clavier ;
- focus visible ;
- contraste ;
- zoom à 200 % et plus ;
- cible tactile ;
- formulaires avec labels ;
- erreurs reliées ;
- messages de statut ;
- menus accessibles ;
- langue ;
- alternatives d’images ;
- tableaux structurés ;
- réduction du mouvement ;
- authentification accessible.

## Tests

- clavier seul ;
- VoiceOver sur macOS/iOS ;
- NVDA sur Windows si possible ;
- axe ou équivalent ;
- zoom ;
- contraste ;
- orientation mobile.

---

# 33. Performance

## Objectifs de terrain

À mesurer sur appareils réels et connexions représentatives.

Cibles initiales :

```text
LCP ≤ 2,5 s au 75e percentile
INP ≤ 200 ms au 75e percentile
CLS ≤ 0,1 au 75e percentile
```

## Budgets initiaux

Page d’accueil hors médias éditoriaux lourds :

```text
CSS compressé          ≤ 80 Ko
JavaScript initial     ≤ 100 Ko
Police initiale        ≤ 120 Ko
Image Hero             ≤ 250 Ko
Requêtes initiales     ≤ 35
```

Les budgets sont des seuils de conception, pas une garantie de terrain.

## Stratégies

- CSS critique raisonnable ;
- JS différé ;
- modules ES si utiles ;
- images `srcset` et `sizes` ;
- dimensions réservées ;
- WebP/AVIF avec fallback selon support ;
- lazy loading hors écran ;
- préchargement limité ;
- cache long des assets hashés ;
- aucune bibliothèque lourde pour un accordéon ;
- aucun slider automatique en Hero.

---

# 34. SEO technique

Le thème doit fournir des emplacements pour :

- title ;
- meta description ;
- canonical ;
- hreflang ;
- robots ;
- Open Graph ;
- données structurées ;
- breadcrumbs ;
- dates ;
- auteur ;
- images sociales.

## Règles

- aucune balise SEO dupliquée ;
- aucune valeur codée en dur par locale ;
- un H1 unique ;
- liens HTML réels ;
- navigation accessible aux crawlers ;
- contenu essentiel dans le HTML initial ;
- pas de texte critique uniquement rendu côté client ;
- pages 404 en vrai 404.

---

# 35. Données structurées

Le thème rend les graphes fournis par `StructuredDataManager`.

Il ne doit pas créer des entités contradictoires dans chaque template.

Types possibles selon page :

```text
Organization
WebSite
SoftwareApplication
WebPage
TechArticle
BlogPosting
BreadcrumbList
ImageObject
Product
Offer
ContactPage
```

Le contenu visible et le JSON-LD doivent rester cohérents.

---

# 36. Internationalisation

## Locales

```text
fr-FR
en-US
de-DE
es-ES
it-IT
pt-PT
```

## Règles

- aucune chaîne UI importante codée en français ;
- textes dans les fichiers de langue ;
- dates localisées ;
- nombres localisés ;
- prix affichés selon contexte ;
- attribut `lang` ;
- liens vers équivalents réels ;
- textes longs allemands testés ;
- boutons non dimensionnés par largeur fixe ;
- interface compatible avec traductions plus longues.

Le thème n’a pas à supporter RTL dans la v1, mais ne doit pas rendre son ajout impossible.

---

# 37. Images et médias

## Images

- formats modernes ;
- texte alternatif ;
- dimensions ;
- ratios cohérents ;
- art direction si nécessaire ;
- aucun étirement ;
- placeholders sans CLS.

## Captures de l’administration

- version indiquée ;
- données privées masquées ;
- cohérence clair/sombre ;
- légende ;
- fichier source conservé.

## Vidéo

- pas d’autoplay sonore ;
- poster local ;
- commandes ;
- sous-titres ;
- transcription lorsque pertinente ;
- chargement différé.

---

# 38. Icônes

## Stratégie

- SVG locaux ;
- sprite ou composants ;
- `currentColor` ;
- taille cohérente ;
- aucun chargement Font Awesome CDN.

## Accessibilité

Décorative :

```html
aria-hidden="true"
```

Informative : nom accessible ou texte visible.

---

# 39. Formulaires

Le thème doit couvrir :

- contact ;
- recherche ;
- newsletter ;
- connexion éventuelle ;
- checkout externe ou intégré.

## Exigences

- labels ;
- aide ;
- requis ;
- erreurs ;
- focus ;
- état disabled ;
- confirmation ;
- autocomplete ;
- tailles tactiles ;
- validation serveur.

Le thème ne doit jamais afficher un message de succès si le backend a échoué.

---

# 40. Pages juridiques

Le layout `legal.php` doit :

- présenter un sommaire ;
- conserver les paragraphes lisibles ;
- éviter les cartes décoratives inutiles ;
- afficher la date ;
- permettre l’impression ;
- lier les documents connexes ;
- ne pas cacher un texte derrière un accordéon obligatoire.

---

# 41. Erreurs 404 et 500

## 404

Conforme à :

```text
404_CONTENT.md
```

## 500

- vrai statut 500 ;
- message générique ;
- aucun détail ;
- identifiant d’incident facultatif ;
- lien accueil ;
- fallback autonome ;
- pas de dépendance à une API.

Les pages d’erreur ne doivent pas nécessiter un Builder.

---

# 42. Sécurité frontend

- échappement contextuel ;
- pas de `innerHTML` non maîtrisé ;
- CSP compatible ;
- aucune clé dans JS ;
- intégrité et provenance des assets ;
- formulaire CSRF côté application ;
- liens externes sécurisés selon contexte ;
- aucun script inline dynamique inutile ;
- pas d’évaluation de code ;
- pas de contenu utilisateur interprété comme script.

---

# 43. Politique d’assets

## Nommage

```text
flatcms.[hash].css
flatcms.[hash].js
home.[hash].js
```

## Cache

Assets hashés : cache long et immutable.

HTML : cache adapté au contenu et à l’authentification.

## Chargement

Chaque template charge uniquement les bundles nécessaires.

Exemple : le JS du comparatif ou du code copier ne doit pas être imposé à une page légale si absent.

---

# 44. Impression

`print.css` doit :

- masquer navigation et CTA non utiles ;
- afficher URL des liens si pertinent ;
- conserver titres ;
- éviter les fonds très sombres ;
- améliorer les pages documentation et juridique ;
- ne pas couper les blocs de code de manière illisible.

---

# 45. Configuration administrable

Options possibles :

```text
logo clair
logo sombre
favicon
couleur primaire
mode par défaut
CTA header
liens sociaux
footer
image OG par défaut
activation des animations
largeur de contenu
```

## Règles

- valeurs validées ;
- pas de CSS arbitraire non sécurisé par défaut ;
- valeurs de secours ;
- aperçu ;
- restauration des valeurs officielles ;
- options documentées.

L’identité officielle doit rester la configuration par défaut.

---

# 46. Compatibilité navigateurs

Support cible : deux dernières versions stables des principaux navigateurs modernes.

- Chrome ;
- Edge ;
- Firefox ;
- Safari ;
- Safari iOS ;
- Chrome Android.

Les fonctions avancées doivent avoir un fallback.

Exemple :

```css
@supports (backdrop-filter: blur(12px)) { ... }
```

---

# 47. Tests visuels

## Viewports minimum

```text
320 × 568
375 × 812
390 × 844
768 × 1024
1024 × 768
1280 × 800
1440 × 900
1920 × 1080
```

## Vérifications

- pas de débordement horizontal ;
- navigation ;
- tableaux ;
- code ;
- titres allemands ;
- cartes ;
- CTA ;
- mode clair ;
- mode sombre ;
- zoom ;
- orientation paysage.

---

# 48. Tests fonctionnels

```text
testThemeLoadsWithoutBuilders
testThemeLoadsWithPagesBuilder
testThemeLoadsWithMenuBuilder
testThemeLoadsWithFooterBuilder
testExpiredBuilderLicenseDoesNotBreakFrontend
testHeaderNavigationIsKeyboardAccessible
testMobileMenuTrapsAndRestoresFocus
testThemeTogglePersistsPreference
testReducedMotionDisablesDecorativeAnimations
testDocumentationLayoutHasValidHeadingOrder
testLegalLayoutPrintsCorrectly
test404Returns404
test500Returns500
testLocaleStringsExist
testStructuredDataRendersOnce
testAssetsAreLocalAndVersioned
```

---

# 49. Audit Lighthouse et terrain

Automatiser des audits de laboratoire sans les confondre avec les données de terrain.

Pages à tester :

- accueil ;
- fonctionnalités ;
- documentation ;
- article ;
- tarifs ;
- contact ;
- page légale ;
- 404.

Catégories :

- performance ;
- accessibilité ;
- bonnes pratiques ;
- SEO.

Les seuils ne doivent pas conduire à masquer de vrais problèmes sous une note moyenne.

---

# 50. Audit demandé à Codex

Codex doit produire :

## 50.1 Plan d’implémentation

```text
Phase
Fichiers
Composants
Dépendances
Tests
Risques
```

## 50.2 Inventaire des contrats FlatCMS

- manifeste de thème ;
- chargement des assets ;
- vues ;
- layouts ;
- localisation ;
- menus ;
- footer ;
- StructuredData ;
- PagesBuilder ;
- MenuBuilder ;
- FooterBuilder.

## 50.3 Écarts

Pour chaque adaptation nécessaire :

```text
Contrat actuel
Besoin du thème
Écart
Correction
Compatibilité
Test
```

## 50.4 Confirmation finale

- [ ] aucune copie de code Webby ;
- [ ] thème original ;
- [ ] tous les assets locaux ;
- [ ] fonctionnement sans Builders ;
- [ ] rendu préservé après expiration ;
- [ ] mode clair et sombre ;
- [ ] six locales ;
- [ ] navigation clavier ;
- [ ] réduction du mouvement ;
- [ ] budgets de performance ;
- [ ] données structurées centralisées ;
- [ ] 404 réelle ;
- [ ] pages juridiques lisibles ;
- [ ] aucun secret frontend.

---

# 51. Critères d’acceptation

Le thème est accepté uniquement si :

1. son identité est clairement FlatCMS et non Webby ;
2. toutes les pages P0 peuvent être intégrées ;
3. le site fonctionne sans Builder premium ;
4. les trois Builders sont compatibles ;
5. une expiration ne casse aucun rendu ;
6. le mode sombre et le mode clair sont complets ;
7. les six locales sont fonctionnelles ;
8. le contenu essentiel fonctionne sans JavaScript ;
9. la navigation est utilisable au clavier ;
10. les pages respectent les budgets validés ;
11. les statuts HTTP sont exacts ;
12. les tests automatiques passent ;
13. aucune dépendance CDN obligatoire n’existe ;
14. le package du thème est documenté et versionné.

---

# 52. Livrables attendus

```text
themes/flatcms/
THEME_README.md
THEME_CHANGELOG.md
THEME_TEST_PLAN.md
THEME_ACCESSIBILITY_REPORT.md
THEME_PERFORMANCE_REPORT.md
THEME_SCREENSHOTS/
```

Captures obligatoires :

- accueil desktop sombre ;
- accueil desktop clair ;
- accueil mobile ;
- documentation ;
- article ;
- tarifs ;
- page légale ;
- 404 ;
- menu mobile ;
- méga-menu.

---

# 53. Sources internes

- `HOMEPAGE_CONTENT.md`
- `WHY_FLATCMS_CONTENT.md`
- `FEATURES_CONTENT.md`
- `ARCHITECTURE_CONTENT.md`
- `DOCUMENTATION_CONTENT.md`
- `INSTALLATION_CONTENT.md`
- `DOWNLOAD_CONTENT.md`
- `LICENSING_CONTENT.md`
- `PRICING_CONTENT.md`
- `AGENT_READY_CONTENT.md`
- `ABOUT_CONTENT.md`
- `CONTACT_CONTENT.md`
- `LEGAL_NOTICE_CONTENT.md`
- `PRIVACY_CONTENT.md`
- `404_CONTENT.md`
- `BUILDER_LICENSE_ENFORCEMENT.md`
- capture Webby fournie par Alain BROYE.

---

# 54. Références externes

- W3C — Web Content Accessibility Guidelines 2.2  
  https://www.w3.org/TR/WCAG22/

- MDN — `prefers-reduced-motion`  
  https://developer.mozilla.org/en-US/docs/Web/CSS/@media/prefers-reduced-motion

- web.dev — Core Web Vitals  
  https://web.dev/articles/vitals

- Google Search Central — JavaScript SEO basics  
  https://developers.google.com/search/docs/crawling-indexing/javascript/javascript-seo-basics

Les références encadrent l’accessibilité, les mouvements, les métriques de terrain et l’indexabilité. Elles ne définissent pas l’identité propre de FlatCMS.

---

# 55. Journal des mises à jour

| Date | Version | Modification | Auteur |
|---|---:|---|---|
| 2026-06-08 | 1.0 | Première spécification complète du thème officiel `flatcms` | ChatGPT / Alain BROYE |

---

# 56. Prochaine étape

Après validation et ajout dans le Drive :

```text
Créer DESIGN_SYSTEM.md
```

Ce document détaillera les tokens et les états de chaque composant, puis :

```text
Créer NAVIGATION_SPECIFICATION.md
Créer COMPONENT_LIBRARY.md
Créer HOMEPAGE_WIREFRAME.md
```

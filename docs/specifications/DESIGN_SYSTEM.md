# DESIGN_SYSTEM — Système de design du thème officiel FlatCMS

> Projet : FlatCMS  
> Thème : `flatcms`  
> Site cible : `https://flat-cms.fr`  
> Date : 8 juin 2026  
> Statut : spécification design à transmettre à Codex  
> Document parent : `THEME_SPECIFICATION.md`

---

## 1. Objectif

Ce document définit le système de design du thème officiel `flatcms`.

Il sert de contrat visuel pour :

- la page d’accueil ;
- les pages marketing ;
- la documentation ;
- le blog ;
- les pages légales ;
- les pages d’erreur ;
- les composants utilisés par PagesBuilder, MenuBuilder et FooterBuilder.

Le système doit permettre de créer un site moderne, premium, lisible,
accessible et cohérent avec l’identité technique de FlatCMS.

Le thème peut s’inspirer du rythme visuel observé sur Webby, mais il ne doit jamais copier ses textes, son code, ses composants exacts, ses couleurs exactes ou ses compositions pixel perfect.

---

## 2. Principes visuels

Le design officiel de FlatCMS repose sur six principes :

1. **Clarté technique** : chaque section doit expliquer rapidement une idée.
2. **Sobriété premium** : peu d’effets, mais des contrastes et espacements maîtrisés.
3. **Identité FlatCMS** : indigo officiel, bleu nuit, JSON, modules, architecture, agents IA.
4. **Lisibilité** : typographie confortable, textes courts, hiérarchie nette.
5. **Accessibilité** : contraste, focus, clavier, responsive et réduction des animations.
6. **Performance** : CSS et JavaScript limités, aucun framework lourd obligatoire.

---

## 3. Palette de couleurs

### 3.1 Mode sombre principal

```css
:root,
[data-theme="dark"] {
  --fc-bg: #070A12;
  --fc-bg-soft: #0B1020;
  --fc-surface: #0D121C;
  --fc-surface-2: #111827;
  --fc-surface-3: #151C2C;

  --fc-primary: #4F46E5;
  --fc-primary-hover: #6366F1;
  --fc-primary-soft: rgba(79, 70, 229, 0.16);

  --fc-violet: #7C3AED;
  --fc-cyan: #06B6D4;
  --fc-pink: #C026D3;
  --fc-green: #22C55E;
  --fc-amber: #F59E0B;
  --fc-red: #EF4444;

  --fc-text: #F8FAFC;
  --fc-text-soft: #CBD5E1;
  --fc-text-muted: #94A3B8;
  --fc-text-faint: #64748B;

  --fc-border: rgba(148, 163, 184, 0.16);
  --fc-border-strong: rgba(148, 163, 184, 0.28);

  --fc-shadow-soft: 0 18px 60px rgba(0, 0, 0, 0.28);
  --fc-shadow-card: 0 12px 40px rgba(0, 0, 0, 0.22);
}
```

### 3.2 Mode clair

```css
[data-theme="light"] {
  --fc-bg: #F8FAFC;
  --fc-bg-soft: #EEF2FF;
  --fc-surface: #FFFFFF;
  --fc-surface-2: #F1F5F9;
  --fc-surface-3: #E2E8F0;

  --fc-primary: #4F46E5;
  --fc-primary-hover: #4338CA;
  --fc-primary-soft: rgba(79, 70, 229, 0.10);

  --fc-violet: #7C3AED;
  --fc-cyan: #0891B2;
  --fc-pink: #A21CAF;
  --fc-green: #16A34A;
  --fc-amber: #D97706;
  --fc-red: #DC2626;

  --fc-text: #0F172A;
  --fc-text-soft: #334155;
  --fc-text-muted: #64748B;
  --fc-text-faint: #94A3B8;

  --fc-border: rgba(15, 23, 42, 0.12);
  --fc-border-strong: rgba(15, 23, 42, 0.22);

  --fc-shadow-soft: 0 18px 60px rgba(15, 23, 42, 0.10);
  --fc-shadow-card: 0 12px 40px rgba(15, 23, 42, 0.08);
}
```

### 3.3 Dégradés

Dégradé de marque :

```css
--fc-gradient-brand: linear-gradient(90deg, #818CF8 0%, #4F46E5 42%, #7C3AED 72%, #22D3EE 100%);
```

Dégradé CTA :

```css
--fc-gradient-cta: radial-gradient(circle at top left, rgba(79, 70, 229, 0.42), transparent 32%), linear-gradient(135deg, #111827, #17132F 50%, #08111F);
```

Dégradé de halo :

```css
--fc-gradient-glow: radial-gradient(circle, rgba(79, 70, 229, 0.28), transparent 60%);
```

---

## 4. Règles de contraste

Objectif minimal : **WCAG 2.2 AA**.

Exigences :

- texte normal : ratio minimum 4.5:1 ;
- grands titres : ratio minimum 3:1 ;
- icônes informatives : ratio minimum 3:1 ;
- bordures de champs : état focus visible ;
- boutons : contraste suffisant entre texte et fond ;
- ne jamais utiliser la couleur seule pour indiquer un état.

Les couleurs d’accent peuvent servir aux icônes, badges et halos, mais le texte long doit utiliser `--fc-text`, `--fc-text-soft` ou `--fc-text-muted` selon le contexte.

---

## 5. Typographie

### 5.1 Police recommandée

Le thème doit fonctionner avec une pile système performante :

```css
--fc-font-sans: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
--fc-font-mono: "JetBrains Mono", "SFMono-Regular", Consolas, "Liberation Mono", monospace;
```

Si Inter ou JetBrains Mono sont embarquées localement, elles doivent être servies en `woff2`, avec `font-display: swap`.

Aucune police ne doit être chargée depuis un CDN externe par défaut.

### 5.2 Échelle typographique

```css
--fc-text-xs: 0.75rem;
--fc-text-sm: 0.875rem;
--fc-text-base: 1rem;
--fc-text-lg: 1.125rem;
--fc-text-xl: 1.25rem;
--fc-text-2xl: 1.5rem;
--fc-text-3xl: 1.875rem;
--fc-text-4xl: 2.25rem;
--fc-text-5xl: 3rem;
--fc-text-6xl: 3.75rem;
--fc-text-7xl: 4.5rem;
```

### 5.3 Titres

H1 desktop :

```css
font-size: clamp(2.75rem, 6vw, 5.5rem);
line-height: 0.95;
letter-spacing: -0.06em;
font-weight: 800;
```

H1 mobile :

```css
font-size: clamp(2.25rem, 13vw, 3.5rem);
line-height: 1;
letter-spacing: -0.05em;
```

H2 :

```css
font-size: clamp(2rem, 4vw, 3.5rem);
line-height: 1.05;
letter-spacing: -0.045em;
font-weight: 760;
```

H3 :

```css
font-size: clamp(1.25rem, 2vw, 1.75rem);
line-height: 1.2;
letter-spacing: -0.025em;
font-weight: 700;
```

Paragraphes :

```css
font-size: 1rem;
line-height: 1.7;
color: var(--fc-text-muted);
```

---

## 6. Grille et espacements

### 6.1 Conteneurs

```css
--fc-container-sm: 720px;
--fc-container-md: 960px;
--fc-container-lg: 1120px;
--fc-container-xl: 1240px;
```

Classe conceptuelle :

```css
.fc-container {
  width: min(100% - 2rem, var(--fc-container-xl));
  margin-inline: auto;
}
```

### 6.2 Espacements

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

Sections principales :

```css
.fc-section {
  padding-block: clamp(4rem, 8vw, 7.5rem);
}
```

Hero :

```css
.fc-hero {
  padding-top: clamp(7rem, 12vw, 11rem);
  padding-bottom: clamp(4rem, 8vw, 7rem);
}
```

---

## 7. Rayons, bordures et ombres

```css
--fc-radius-xs: 0.375rem;
--fc-radius-sm: 0.5rem;
--fc-radius-md: 0.75rem;
--fc-radius-lg: 1rem;
--fc-radius-xl: 1.25rem;
--fc-radius-2xl: 1.5rem;
--fc-radius-full: 999px;
```

Cartes :

```css
border: 1px solid var(--fc-border);
border-radius: var(--fc-radius-xl);
background: linear-gradient(180deg, rgba(255,255,255,0.035), rgba(255,255,255,0.015));
box-shadow: var(--fc-shadow-card);
```

En mode clair, les surfaces doivent rester plus plates afin d’éviter un rendu trop chargé.

---

## 8. Boutons

### 8.1 Bouton primaire

```css
.fc-btn-primary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 44px;
  padding: 0.75rem 1.1rem;
  border-radius: var(--fc-radius-full);
  background: var(--fc-primary);
  color: #fff;
  font-weight: 700;
  text-decoration: none;
}

.fc-btn-primary:hover {
  background: var(--fc-primary-hover);
}
```

### 8.2 Bouton secondaire

```css
.fc-btn-secondary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 44px;
  padding: 0.75rem 1.1rem;
  border-radius: var(--fc-radius-full);
  border: 1px solid var(--fc-border-strong);
  background: rgba(255,255,255,0.04);
  color: var(--fc-text);
  font-weight: 700;
  text-decoration: none;
}
```

### 8.3 Bouton ghost

```css
.fc-btn-ghost {
  color: var(--fc-text-soft);
  text-decoration: none;
  font-weight: 650;
}

.fc-btn-ghost:hover {
  color: var(--fc-text);
}
```

### 8.4 États obligatoires

Tous les boutons doivent avoir :

- état `hover` ;
- état `focus-visible` ;
- état `disabled` ;
- taille de cible minimale 44 × 44 px ;
- libellé explicite ;
- contraste AA.

---

## 9. Badges

Badges utilisés pour :

- version ;
- statut ;
- premium ;
- open source ;
- LTS ;
- recommandé ;
- expérimental.

Structure :

```html
<span class="fc-badge fc-badge--primary">FlatCMS v1.0 LTS</span>
```

CSS conceptuel :

```css
.fc-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  min-height: 28px;
  padding: 0.3rem 0.65rem;
  border-radius: var(--fc-radius-full);
  border: 1px solid var(--fc-border);
  background: var(--fc-primary-soft);
  color: var(--fc-text-soft);
  font-size: var(--fc-text-xs);
  font-weight: 700;
}
```

---

## 10. Cartes

### 10.1 Carte standard

Structure :

```html
<article class="fc-card">
  <div class="fc-card__icon" aria-hidden="true"></div>
  <h3 class="fc-card__title">Titre</h3>
  <p class="fc-card__text">Description courte.</p>
</article>
```

Règles :

- titre clair ;
- description courte ;
- icône optionnelle ;
- lien si toute la carte est cliquable ;
- focus visible ;
- pas de carte cliquable imbriquant plusieurs liens.

### 10.2 Carte premium

Utilisée pour les Builders et offres payantes.

Éléments :

- badge ;
- prix ;
- durée ;
- liste d’inclusions ;
- CTA ;
- note sur l’expiration sans coupure frontend.

---

## 11. Panneaux de code et terminal

FlatCMS doit utiliser des panneaux originaux montrant :

- arborescence JSON ;
- cycle de requête ;
- configuration Nginx ;
- exemple PHP ;
- structure de module.

Structure :

```html
<figure class="fc-code-panel">
  <figcaption>Cycle de requête FlatCMS</figcaption>
  <pre><code>Request → Router → Module → Service → JSON → View</code></pre>
</figure>
```

Règles :

- `pre` scrollable horizontalement ;
- contraste suffisant ;
- pas de petite police illisible ;
- bouton copier accessible si présent ;
- langage indiqué ;
- pas de contenu essentiel uniquement coloré.

---

## 12. Tableaux

Les tableaux sont utilisés pour :

- comparaison ;
- tarifs ;
- fonctionnalités ;
- statuts ;
- architecture.

Règles :

- en-têtes `th` ;
- légende ou titre visible ;
- scroll horizontal sur mobile ;
- cellules lisibles ;
- pas de tableau géant illisible ;
- alternatives textuelles pour tableaux complexes.

Classe :

```css
.fc-table-wrap {
  overflow-x: auto;
  border: 1px solid var(--fc-border);
  border-radius: var(--fc-radius-xl);
}
```

---

## 13. FAQ et accordéons

Règles :

- utiliser un vrai bouton ;
- état `aria-expanded` ;
- contenu associé par `aria-controls` ;
- accessible clavier ;
- pas d’ouverture automatique envahissante ;
- fonctionnement sans animation obligatoire.

Structure :

```html
<div class="fc-accordion">
  <button class="fc-accordion__trigger" aria-expanded="false" aria-controls="faq-1">
    FlatCMS nécessite-t-il MySQL ?
  </button>
  <div id="faq-1" class="fc-accordion__panel" hidden>
    <p>Non, FlatCMS utilise un stockage flat-file JSON.</p>
  </div>
</div>
```

---

## 14. Header

Le header doit être :

- compact ;
- sticky optionnel ;
- lisible sur fond sombre et clair ;
- compatible mobile ;
- accessible clavier ;
- indépendant de MenuBuilder pour le rendu minimal.

Navigation desktop :

```text
Pourquoi FlatCMS
Fonctionnalités
Architecture
Documentation
Tarifs
Blog
```

Actions :

```text
Démo
Télécharger
```

Éléments :

- logo ;
- navigation ;
- sélecteur de langue ;
- toggle clair/sombre ;
- CTA ;
- menu mobile.

---

## 15. Menu mobile

Règles :

- bouton avec `aria-expanded` ;
- panneau accessible ;
- fermeture par `Esc` ;
- focus géré correctement ;
- scroll verrouillé si panneau plein écran ;
- liens suffisamment grands ;
- langue et thème accessibles ;
- aucun contenu caché indispensable.

---

## 16. Footer

Colonnes recommandées :

```text
Produit
Documentation
Communauté
Ressources
```

Liens juridiques :

```text
Mentions légales
Confidentialité
Licences
CGV
Contact
Sécurité
```

Règles :

- lien vers le téléchargement ;
- lien vers la documentation ;
- lien vers GitHub ;
- liens légaux permanents ;
- copyright prudent ;
- pas de promesse juridique non validée.

---

## 17. Sections de page d’accueil

Ordre recommandé :

1. Header
2. Hero
3. Indicateurs techniques
4. Panneau architecture/code
5. Manifeste FlatCMS
6. Fonctionnalités essentielles
7. Pourquoi FlatCMS
8. Architecture
9. Builders premium
10. Démarrer en quelques minutes
11. Agent-ready
12. Documentation et communauté
13. Comparatif
14. FAQ
15. CTA final
16. Footer

Chaque section doit pouvoir être désactivée ou adaptée sans casser la page.

---

## 18. Design du Hero

Objectif : impact immédiat, sans surcharger.

Éléments :

- badge version ;
- H1 centré ;
- accent en dégradé ;
- paragraphe court ;
- deux CTA ;
- indicateurs techniques ;
- halo discret ;
- panneau visuel sous le texte.

H1 proposé :

```text
Le CMS PHP flat-file simple, rapide et agent-ready
```

Les mots `flat-file` ou `agent-ready` peuvent porter le dégradé de marque.

---

## 19. États interactifs

Tous les éléments interactifs doivent gérer :

- `hover` ;
- `focus-visible` ;
- `active` ;
- `disabled` ;
- `aria-current` pour la navigation ;
- `aria-expanded` pour les menus ;
- `aria-selected` pour les onglets.

Focus :

```css
:focus-visible {
  outline: 3px solid rgba(99, 102, 241, 0.75);
  outline-offset: 3px;
}
```

Ne jamais supprimer le focus sans alternative visible.

---

## 20. Animations

Animations autorisées :

- fade léger ;
- translation courte ;
- halo subtil ;
- hover de carte ;
- ouverture d’accordéon ;
- apparition au scroll si non bloquante.

Interdictions :

- animations indispensables à la compréhension ;
- parallaxe agressive ;
- mouvement permanent ;
- déplacement horizontal important ;
- animation sur gros blocs de texte ;
- effets rendant le site lent.

Réduction obligatoire :

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

---

## 21. Responsive

Approche mobile-first.

Breakpoints conceptuels :

```css
--fc-bp-sm: 640px;
--fc-bp-md: 768px;
--fc-bp-lg: 1024px;
--fc-bp-xl: 1280px;
```

Règles :

- une colonne sur mobile ;
- cartes en grille à partir de tablette ;
- header mobile dédié ;
- tableaux scrollables ;
- blocs de code scrollables ;
- CTA empilés sur mobile ;
- espacement vertical réduit mais confortable.

---

## 22. Accessibilité

Objectif : WCAG 2.2 AA.

Checklist :

- HTML sémantique ;
- H1 unique ;
- hiérarchie H2/H3 ;
- landmarks ;
- liens descriptifs ;
- labels ;
- focus visible ;
- navigation clavier ;
- contraste AA ;
- tailles de cible suffisantes ;
- textes alternatifs ;
- pas d’information par couleur seule ;
- pas de piège clavier ;
- support `prefers-reduced-motion` ;
- zoom 200 % sans perte majeure.

---

## 23. Performance

Budgets recommandés :

```text
CSS critique initial : < 35 Ko gzip
CSS total thème : < 90 Ko gzip
JavaScript initial : < 35 Ko gzip
JavaScript total thème : < 80 Ko gzip
Image hero : < 180 Ko si raster
Illustrations secondaires : < 120 Ko chacune
Police locale : woff2 uniquement
```

Règles :

- aucun Tailwind CDN ;
- aucun framework JS lourd obligatoire ;
- images optimisées ;
- `loading="lazy"` hors hero ;
- dimensions explicites ;
- CSS critique ;
- scripts différés ;
- pas de dépendance externe pour le rendu principal.

---

## 24. Images et icônes

Formats :

- SVG pour icônes et diagrammes simples ;
- WebP ou AVIF pour visuels raster ;
- PNG uniquement si nécessaire ;
- pas de WebP avec faux fond transparent si PNG/SVG est requis.

Règles :

- icônes originales ou sous licence compatible ;
- textes importants en HTML ;
- versions localisées si texte dans image ;
- alt adapté ;
- pas de chargement externe par défaut.

---

## 25. Mode clair et sombre

Le mode sombre est l’identité principale du site.

Le mode clair doit exister pour :

- accessibilité ;
- préférences utilisateur ;
- lisibilité documentaire ;
- captures ;
- cohérence avec certains environnements.

Règles :

- suivre `prefers-color-scheme` si aucun choix utilisateur ;
- mémoriser le choix ;
- ne pas provoquer de flash violent ;
- tester tous les composants dans les deux modes ;
- ne pas inverser les images de marque de façon destructrice.

---

## 26. Compatibilité PagesBuilder

PagesBuilder doit pouvoir utiliser :

- sections ;
- cards ;
- hero ;
- CTA ;
- FAQ ;
- pricing ;
- feature grid ;
- code panel ;
- architecture diagram ;
- testimonial ;
- comparison table.

Règle : le rendu public d’un contenu construit doit rester fonctionnel même si la licence expire, conformément à `BUILDER_LICENSE_ENFORCEMENT.md`.

---

## 27. Compatibilité MenuBuilder

Le thème doit fournir :

- header minimal sans MenuBuilder ;
- emplacements MenuBuilder ;
- rendu desktop ;
- rendu mobile ;
- méga-menu ;
- fallback si MenuBuilder indisponible.

Règle : un menu déjà publié reste affiché après expiration d’une licence.

---

## 28. Compatibilité FooterBuilder

Le thème doit fournir :

- footer natif minimal ;
- emplacements FooterBuilder ;
- colonnes ;
- liens légaux ;
- CTA final ;
- fallback si FooterBuilder indisponible.

Règle : un footer déjà publié reste affiché après expiration d’une licence.

---

## 29. Pages légales

Les pages légales doivent privilégier :

- lisibilité ;
- largeur contenue ;
- titres clairs ;
- tableaux responsive ;
- liens visibles ;
- absence d’animation inutile ;
- impression correcte.

Layout recommandé :

```text
Header simple
Titre
Sommaire optionnel
Contenu long
Liens associés
Footer juridique
```

---

## 30. Documentation

La documentation doit proposer :

- sommaire latéral sur desktop ;
- sommaire repliable sur mobile ;
- blocs de code ;
- alertes ;
- tableaux ;
- navigation précédent/suivant ;
- version ;
- date de mise à jour ;
- lien de signalement.

Composants :

```text
DocsLayout
DocsSidebar
DocsToc
DocsCodeBlock
DocsCallout
DocsPrevNext
DocsVersionBadge
```

---

## 31. Blog

Le blog doit proposer :

- liste d’articles ;
- carte article ;
- image ;
- catégorie ;
- date ;
- auteur ;
- résumé ;
- pagination ;
- page article ;
- articles liés ;
- partage sobre.

Les images doivent respecter les formats existants de FlatCMS.

---

## 32. Pages d’erreur

Le thème doit fournir :

- 404 ;
- 500 ;
- maintenance ;
- accès refusé.

La page 404 doit respecter `404_CONTENT.md` : vrai statut HTTP 404, pas de redirection automatique vers l’accueil.

La page 500 doit être sobre et ne jamais exposer une stack trace en production.

---

## 33. Formulaires

Éléments :

- input ;
- textarea ;
- select ;
- checkbox ;
- radio ;
- message d’erreur ;
- message d’aide ;
- bouton ;
- état succès ;
- état chargement.

Règles :

- label visible ;
- champ obligatoire indiqué ;
- message d’erreur relié ;
- validation serveur ;
- focus visible ;
- `autocomplete` adapté ;
- pas de placeholder comme label unique.

---

## 34. Alertes et callouts

Types :

```text
info
success
warning
danger
premium
experimental
legal
```

Structure :

- icône ;
- titre ;
- message ;
- lien optionnel.

Ne jamais dépendre uniquement de la couleur.

---

## 35. Fichiers du thème

Arborescence recommandée :

```text
themes/flatcms/
├── assets/
│   ├── css/
│   │   ├── tokens.css
│   │   ├── base.css
│   │   ├── layout.css
│   │   ├── components.css
│   │   ├── utilities.css
│   │   └── theme.css
│   ├── js/
│   │   ├── theme-toggle.js
│   │   ├── navigation.js
│   │   ├── accordion.js
│   │   └── reveal.js
│   ├── img/
│   ├── icons/
│   └── fonts/
├── components/
├── layouts/
├── partials/
├── templates/
├── languages/
├── screenshots/
└── manifest.json
```

---

## 36. Manifest conceptuel

```json
{
  "name": "flatcms",
  "title": "FlatCMS Official Theme",
  "version": "1.0.0",
  "type": "frontend",
  "author": "Alain BROYE",
  "license": "LicenseRef-FlatCMS-Theme-Official",
  "supports": {
    "darkMode": true,
    "lightMode": true,
    "responsive": true,
    "locales": ["fr-FR", "en-US", "de-DE", "es-ES", "it-IT", "pt-PT"],
    "pagesBuilder": true,
    "menuBuilder": true,
    "footerBuilder": true
  }
}
```

La licence exacte du thème doit être validée avant publication.

---

## 37. Sécurité frontend

Règles :

- échapper les sorties ;
- pas de HTML brut non validé ;
- pas de JS inline obligatoire ;
- CSP compatible ;
- pas de CDN obligatoire ;
- pas de clé API frontend ;
- pas d’injection de contenu externe sans validation ;
- champs et URLs échappés.

---

## 38. Tests demandés à Codex

Codex doit vérifier :

- mode sombre ;
- mode clair ;
- responsive ;
- navigation clavier ;
- contraste ;
- performance ;
- absence de CDN obligatoire ;
- compatibilité Builders ;
- pages longues ;
- tableaux ;
- blocs de code ;
- formulaire ;
- 404 ;
- six locales.

Tests minimaux :

```text
testThemeLoadsWithoutBuilderLicenses
testDarkModeTokensExist
testLightModeTokensExist
testNavigationKeyboardAccessible
testMobileMenuAriaExpanded
testAccordionAriaExpanded
testNoTailwindCdnInProduction
testNoExternalFontByDefault
test404FallbackDoesNotDependOnBuilder
testPagesBuilderContentRendersAfterLicenseExpiration
testMenuBuilderContentRendersAfterLicenseExpiration
testFooterBuilderContentRendersAfterLicenseExpiration
```

---

## 39. Critères d’acceptation

Le design system est accepté si :

1. tous les tokens sont centralisés ;
2. les deux modes clair et sombre fonctionnent ;
3. les composants essentiels utilisent les tokens ;
4. les contrastes respectent WCAG 2.2 AA ;
5. les interactions clavier sont possibles ;
6. les animations respectent `prefers-reduced-motion` ;
7. les pages longues restent lisibles ;
8. les Builders peuvent réutiliser les composants ;
9. le site reste performant ;
10. aucun élément ne copie Webby à l’identique.

---

## 40. Prochaine étape

Après validation :

```text
Créer NAVIGATION_SPECIFICATION.md
```

Ce document détaillera :

- header desktop ;
- menu mobile ;
- méga-menu ;
- footer ;
- sélecteur de langue ;
- CTA ;
- accessibilité ;
- comportement avec MenuBuilder et FooterBuilder.

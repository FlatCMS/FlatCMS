# FlatCMS Spring Cleanup Audit

Date: 2026-06-02
Repository: `/Applications/MAMP/htdocs/FlatCMS-LTS-Core`

## Current Size Snapshot

- Working repository size: `103 MB`
- Installable zip snapshot (without `.git`, `node_modules`, `vendor`, `storage/cache`): `40 MB`

## Largest Weight Sources

### Package-Level Directories

- `app`: `37 MB`
- `app/Modules`: `30 MB`
- `public`: `11 MB`
- `public/assets`: `6.6 MB`
- `app/Extensions`: `5.6 MB`
- `themes`: `4.9 MB`

### Largest Individual Files And Assets

- `app/Modules/Install/Seeds/demo/assets/public/uploads/images/demo-post-workshop-tools.png`: `2.6 MB`
- `public/assets/dists/suneditor/suneditor.min.js`: `2.5 MB`
- `app/Modules/Install/Seeds/demo/assets/public/uploads/images/demo-post-wabi-sabi.png`: `2.5 MB`
- `app/Modules/Install/Seeds/demo/assets/public/uploads/images/demo-post-calm-interiors.png`: `2.2 MB`
- `app/Modules/Install/Seeds/demo/assets/public/uploads/images/demo-atelier-contact-banner.png`: `2.2 MB`
- `app/Modules/Install/Seeds/demo/assets/public/uploads/images/demo-atelier-workshop.png`: `2.1 MB`
- `app/Modules/Install/Seeds/demo/assets/public/uploads/images/demo-atelier-collections-banner.png`: `2.1 MB`
- `app/Modules/Install/Seeds/demo/assets/public/uploads/images/demo-post-tea-rituals.png`: `2.0 MB`
- `app/Modules/Install/Seeds/demo/assets/public/uploads/images/demo-post-tableware.png`: `2.0 MB`
- `app/Modules/Install/Seeds/demo/assets/public/uploads/images/demo-atelier-portrait.png`: `1.9 MB`
- `app/Modules/Install/Seeds/demo/assets/public/uploads/images/demo-atelier-home-hero.png`: `1.9 MB`
- `app/Modules/Install/Seeds/demo/assets/public/uploads/images/demo-atelier-craftsmanship-banner.png`: `1.8 MB`
- `app/Modules/Install/Seeds/demo/assets/public/uploads/images/demo-post-handmade.png`: `1.7 MB`
- `themes/admin/default/screenshot.png`: `1.5 MB`
- `public/themes/admin/default/assets/screenshot.png`: `1.5 MB`
- `themes/admin/admin-modern-pro/screenshot.png`: `1.2 MB`
- `public/themes/admin/admin-modern-pro/assets/screenshot.png`: `1.2 MB`
- `app/Extensions/PagesBuilder/Assets/js/pages-builder.js`: `1.0 MB`
- `public/assets/images/admin/ai-agent/icon-dark.png`: `984 KB`
- `themes/frontend/default/screenshot.png`: `864 KB`
- `public/themes/frontend/default/assets/screenshot.png`: `864 KB`
- `public/assets/images/admin/ai-agent/icon-light.png`: `848 KB`
- `themes/frontend/modern-pro/screenshot.png`: `820 KB`
- `public/themes/frontend/modern-pro/assets/screenshot.png`: `820 KB`

## Technical Debt Hotspots

### PagesBuilder

- `app/Extensions/PagesBuilder/Assets/js/pages-builder.js`: `1.0 MB`
- `app/Extensions/PagesBuilder/Assets/css/pages-builder.css`: `256 KB`
- duplicated logic across widget renderers, preview handlers and published asset copies
- local fallbacks and legacy branches added during migration
- missing cleanup comments on non-obvious rendering contracts

### Install / Demo Payload

- demo image seed pack is one of the heaviest contributors to distribution size
- likely highest-impact package reduction target without touching runtime behavior

### Theme Asset Duplication

- screenshots exist in both source theme folders and published `public/themes/*`
- these are useful for previews/manager UI but should be reviewed for packaging strategy

### Rich Text Vendor Payload

- `suneditor.min.js` is a heavy fixed asset
- must be evaluated only after verifying whether it is mandatory in every shipped build

## Spring Cleanup Order

### Pass 1: PagesBuilder Scope Lock

Status: `In progress`

- inventory dead code and duplicated code in `PagesBuilder`
- identify widget-local code that should be shared
- identify shared code that should be pushed back widget-local
- do not touch validated rendering contracts during inventory

### Pass 2: Remove HTML Widget

Status: `Done`

- saved page inventory found no explicit widget blocks `type: "html"` in current builder page data
- widget was removed from registry, admin locales, shared runtime and widget sources
- removal completed without content migration because current builder data inventory stayed clean
- mandatory regression-check after shared file cleanup:
  - `Carousel`
  - `Hero`
  - `SnapCards`
  - `FeatureGrid`

### Pass 3: PagesBuilder Bundle Slimming

Status: `Pending`

- delete dead branches in `pages-builder.js`
- remove duplicated preview helpers where safe
- reduce duplicated CSS rules between widget copies and shared published copies where architecture allows
- add short comments only on contracts/invariants, not trivial code

### Pass 4: Install Package Weight Reduction

Status: `Pending`

- review demo seed images for compression / optional packaging
- review screenshot duplication strategy
- review admin AI illustration payload
- separate runtime-critical assets from showcase/demo assets

### Pass 5: Module And Core Audit

Status: `In progress`

- audit `Contact`, `Pages`, `Menu`, `Themes`, `Settings`
- only after `PagesBuilder` debt is reduced
- no cleanup in core before evidence-based inventory

## Rules For Cleanup Sessions

- smallest possible write scope
- delete dead code instead of stacking more fallbacks
- no speculative refactor without measured gain
- every shared `PagesBuilder` edit requires regression check on:
  - `Carousel`
  - `Hero`
  - `SnapCards`
  - `FeatureGrid`
- comments only where contract or invariant is not obvious

## Inline CSS/JS Inventory Boundary

### What The Strict Gate Currently Catches

- inline `<style>` tags in PHP
- inline executable `<script>` tags in PHP
- `style=` attributes in PHP-rendered HTML
- `on*=` inline handlers in PHP-rendered HTML
- obvious hardcoded user-facing text in PHP

Current status at audit time: `PASS`

Manual confirmation with the stricter regex inventory:

- no executable inline `<script>` blocks in PHP views
- no inline `<style>` blocks in PHP views
- no `style=` attributes in rendered PHP templates
- remaining `<script type="application/json">` usages are data payloads, not executable inline JS

### What The Strict Gate Does Not Catch Today

- CSS declaration strings assembled in PHP and returned as widget runtime CSS payloads
- JS-like string payload generation inside PHP when not emitted as inline `<script>`

### Confirmed Debt Found In PagesBuilder

Several widget renderers still generate CSS declarations from PHP, for example:

- `app/Extensions/PagesBuilder/Widgets/Carousel/Renderer.php`
- `app/Extensions/PagesBuilder/Widgets/ContactSection/Renderer.php`
- `app/Extensions/PagesBuilder/Widgets/NewsletterSection/Renderer.php`
- `app/Extensions/PagesBuilder/Widgets/Image/Renderer.php`
- `app/Extensions/PagesBuilder/Widgets/PricingPlans/Renderer.php`
- `app/Extensions/PagesBuilder/Widgets/FeatureGrid/Renderer.php`
- `app/Extensions/PagesBuilder/Widgets/Hero/Renderer.php`

This is not inline CSS in rendered HTML, so the current gate passes, but it is still structural debt and must be reduced during cleanup.

### Important Clarification

- utility classes present in installer/admin PHP views are not inline CSS by themselves
- the real problem to clean is CSS rule construction embedded in PHP renderer logic

## Cleanup Progress Log

### 2026-06-02 - First Renderer Dedup Pass

Scope:

- `app/Extensions/PagesBuilder/Support/AbstractWidgetRenderer.php`
- `app/Extensions/PagesBuilder/Widgets/Spacer/Renderer.php`
- `app/Extensions/PagesBuilder/Widgets/Divider/Renderer.php`
- `app/Extensions/PagesBuilder/Widgets/Button/Renderer.php`
- `app/Extensions/PagesBuilder/Widgets/Newsletter/Renderer.php`

What changed:

- extracted shared renderer helpers into `AbstractWidgetRenderer`
- centralized:
  - block id sanitization
  - block selector building
  - toggle normalization
  - color normalization
  - border-style normalization
  - shadow preset normalization
  - bounded integer normalization
  - shadow preset to CSS value mapping
- removed duplicated helper implementations from the first four lightweight renderers

Why this pass first:

- smallest risk surface
- no contract change in preview/front
- establishes the cleanup pattern before heavier widgets

Validation:

- `php -l` on all touched PHP files: `PASS`
- strict gate: `PASS`
- shared-support regression spot check:
  - `Carousel`: present
  - `Hero`: present
  - `SnapCards`: present
  - `FeatureGrid`: present
- `Button` front page `/fr-FR/page/boutons`: `200`

### 2026-06-02 - Second Renderer Dedup Pass

Scope:

- `app/Extensions/PagesBuilder/Support/AbstractWidgetRenderer.php`
- `app/Extensions/PagesBuilder/Widgets/Image/Renderer.php`
- `app/Extensions/PagesBuilder/Widgets/ContactSection/Renderer.php`
- `app/Extensions/PagesBuilder/Widgets/NewsletterSection/Renderer.php`
- `app/Extensions/PagesBuilder/Widgets/Button/Renderer.php`

What changed:

- centralized typography helpers in `AbstractWidgetRenderer`
- added shared methods for:
  - text font preset rule generation
  - text font normalization
  - text size normalization
- switched `Image` to shared block selector / block id helpers and removed duplicated design normalizers
- replaced duplicated `toggle`, `color`, `text font`, `text size` and `font rule` helpers in `ContactSection` and `NewsletterSection` with thin wrappers over the shared base
- removed duplicated typography helpers from `Button` and `Image`

Why this pass stayed safe:

- no widget contract change
- no preview/front structure change
- only helper indirection and selector assembly cleanup
- one regression on `/fr-FR/page/image` was detected during the pass and resolved by synchronizing the target test runtime before closure

Validation:

- `php -l` on all touched PHP files: `PASS`
- strict gate: `PASS`
- shared-support regression spot check:
  - `Carousel`: present
  - `Hero`: present
  - `SnapCards`: present
  - `FeatureGrid`: present
- front pages:
  - `/fr-FR/page/image`: `200`

### 2026-06-05 - Admin Stabilization Mini Debug Pass

Scope:

- `app/Modules/Settings/Controllers/AdminController.php`
- `app/Modules/Auth/Services/AuthService.php`
- `public/assets/js/admin/guided-tour.js`
- `themes/admin/admin-modern-pro/views/partials/scripts.php`
- `themes/admin/default/views/partials/scripts.php`
- `app/Modules/Menu/Assets/js/menu.js`
- `app/Modules/Modules/Controllers/AdminController.php`
- `app/Modules/Modules/Views/admin/index.php`
- `app/Modules/Modules/Assets/js/modules.js`

What changed:

- fixed the admin guided tour reset flow so `Reactiver pour ma prochaine connexion` no longer depends on JSON request parsing
- switched guided-tour frontend requests to form-encoded POST with CSRF token
- added a one-login force flag so the tour can relaunch on next login even when the global tour switch is off
- cache-busted the admin guided-tour asset includes in both shipped admin themes
- corrected the floating right accordion sizing in `Menu` so available item groups no longer open with a large empty area below short lists
- changed module installation flow so a freshly installed official or third-party module returns to `admin/modules` in the disabled lane, already expanded and ready to activate

Why this pass matters:

- closes three admin UX regressions found during clean install validation on Apache and Nginx
- keeps the write scope limited to module-owned files and frontend admin assets
- provides a documented stabilization checkpoint before the heavier cleanup passes continue

Validation:

- `php -l` on touched PHP files: `PASS`
- `node --check` on touched admin JS files: `PASS`
- strict gate: `PASS`
- Apache sync spot-check:
  - guided tour assets: synced
  - menu accordion script: synced
  - modules install flow files: synced
  - `/fr-FR/page/section-contact`: `200`
  - `/fr-FR/page/section-newsletter`: `200`

### 2026-06-02 - Third Renderer Dedup Pass

Scope:

- `app/Extensions/PagesBuilder/Support/AbstractWidgetRenderer.php`
- `app/Extensions/PagesBuilder/Widgets/StatsSection/Renderer.php`
- `app/Extensions/PagesBuilder/Widgets/Heading/Renderer.php`

What changed:

- added shared widget-surface `Design` CSS builder to `AbstractWidgetRenderer`
- moved repeated `Heading` and `StatsSection` design-surface CSS assembly onto the shared helper
- replaced duplicated `toggle`, `color`, `font`, `size`, `font rule`, `border style` and `shadow preset` helpers in this lot with base calls
- switched `Heading` text-style selector assembly to the shared block selector helper

Why this pass stayed safe:

- no change to HTML structures
- no change to inspector contracts
- only replacement of repeated normalization and scoped CSS assembly

Validation:

- `php -l` on all touched PHP files: `PASS`
- strict gate: `PASS`
- shared-support regression spot check:
  - `Carousel`: present
  - `Hero`: present
  - `SnapCards`: present
  - `FeatureGrid`: present
- front pages:
  - `/fr-FR/page/titre`: `200`
- `StatsSection` renderer smoke check via direct render call: `PASS`

### 2026-06-02 - Fourth Renderer Dedup Pass

Scope:

- `app/Extensions/PagesBuilder/Widgets/Contact/Renderer.php`
- `app/Extensions/PagesBuilder/Widgets/VideoPlayer/Renderer.php`
- `app/Extensions/PagesBuilder/Support/AbstractWidgetRenderer.php`

What changed:

- switched `Contact` to shared toggle/color/border/shadow helpers
- switched `Contact` widget-scoped design selector to the shared block selector helper
- switched `VideoPlayer` to shared:
  - toggle
  - color
  - typography font/size/font-rule helpers
  - border style / shadow preset helpers
  - widget `Design` CSS builder
  - block id / block selector helpers
- removed duplicated widget-surface `Design` CSS assembly from `VideoPlayer`

Why this pass stayed safe:

- no player logic change
- no shortcode/form fallback logic change
- no widget HTML structure change
- only helper consolidation and scoped CSS assembly cleanup

Validation:

- `php -l` on all touched PHP files: `PASS`
- strict gate: `PASS`
- shared-support regression spot check:
  - `Carousel`: present
  - `Hero`: present
  - `SnapCards`: present
  - `FeatureGrid`: present
- front pages:
  - `/fr-FR/page/contact`: `200`
  - `/fr-FR/page/nouvelle-page`: `200`
- runtime markers confirmed:
  - `flatcms-contact-native`
  - `pb-contact-widget-has-design`
  - `pb-video-player-shell`
  - `pb-video-player-design-overlay`

### 2026-06-02 - Fifth Renderer Dedup Pass

Scope:

- `app/Extensions/PagesBuilder/Support/AbstractWidgetRenderer.php`
- `app/Extensions/PagesBuilder/Widgets/FaqAccordion/Renderer.php`

What changed:

- added shared helpers for:
  - text list normalization
  - icon class sanitization
  - text size CSS rule generation
- switched `FaqAccordion` to shared:
  - toggle
  - color
  - text font/size/list helpers
  - icon sanitization
  - text font/size rule helpers
  - border style / shadow preset helpers
  - widget `Design` CSS builder
  - block id / block selector helpers
- removed duplicated widget-surface `Design` CSS assembly from `FaqAccordion`

Why this pass stayed safe:

- no accordion markup change
- no open/close behavior change
- only helper consolidation and scoped CSS assembly cleanup

Validation:

- `php -l` on all touched PHP files: `PASS`
- strict gate: `PASS`
- shared-support regression spot check:
  - `Carousel`: present
  - `Hero`: present
  - `SnapCards`: present
  - `FeatureGrid`: present
- `FaqAccordion` renderer smoke check via direct render call: `PASS`
- runtime marker on `/fr-FR/page/nouvelle-page`:
  - `pb-faq-accordion`

### 2026-06-02 - Sixth Renderer Dedup Pass

Scope:

- `app/Extensions/PagesBuilder/Widgets/TestimonialCards/Renderer.php`
- `app/Extensions/PagesBuilder/Support/AbstractWidgetRenderer.php`

What changed:

- switched `TestimonialCards` to shared:
  - toggle
  - color
  - text font/size/list helpers
  - icon sanitization
  - text font/size rule helpers
  - border style / shadow preset helpers
  - widget `Design` CSS builder
  - block id / block selector helpers
- removed duplicated widget-surface `Design` CSS assembly from `TestimonialCards`

Why this pass stayed safe:

- no change to card rendering structure
- no change to the testimonial rail / cloud logic
- only helper consolidation and scoped CSS assembly cleanup

Validation:

- `php -l` on all touched PHP files: `PASS`
- strict gate: `PASS`
- shared-support regression spot check:
  - `Carousel`: present
  - `Hero`: present
  - `SnapCards`: present
  - `FeatureGrid`: present
- front page:
  - `/fr-FR/page/cartes-temoignages`: `200`
- runtime markers confirmed:
  - `pb-testimonial-card`
  - `pb-testimonial-cloud`

### 2026-06-02 - Seventh Renderer Dedup Pass

Scope:

- `app/Extensions/PagesBuilder/Support/AbstractWidgetRenderer.php`
- `app/Extensions/PagesBuilder/Widgets/Hero/Renderer.php`

What changed:

- added shared helpers for:
  - alignment normalization
  - heading tag normalization
  - media fit normalization
  - URL sanitization
  - short rich-text flattening
- switched `Hero` to shared:
  - toggle
  - align
  - heading tag
  - media fit
  - color
  - border style / shadow preset helpers
  - text font/size/list helpers
  - icon sanitization
  - block id helper
  - URL sanitization
  - short rich-text normalization
- removed dead local helper code from `Hero`

Why this pass stayed safe:

- no change to hero markup structure
- no CTA contract change
- no media rendering change
- only helper consolidation and local dead-code removal

Validation:

- `php -l` on all touched PHP files: `PASS`
- strict gate: `PASS`
- shared-support regression spot check:
  - `Carousel`: present
  - `Hero`: present
  - `SnapCards`: present
  - `FeatureGrid`: present
- front page:
  - `/fr-FR/page/nouvelle-page`: `200`
- runtime markers confirmed:
  - `fc-carousel-wrapper`
  - `fc-hero-content`
  - `pb-snap-cards`
  - `pb-feature-grid`

### 2026-06-02 - Eighth Renderer Dedup Pass

Scope:

- `app/Extensions/PagesBuilder/Widgets/PricingPlans/Renderer.php`

What changed:

- switched `PricingPlans` to shared:
  - toggle
  - align
  - color
  - border style / shadow preset helpers
  - text font/size/list helpers
  - icon sanitization
  - URL sanitization
  - block id / block selector helpers
  - widget `Design` CSS builder
- removed duplicated widget-surface `Design` CSS assembly from `PricingPlans`
- removed dead local helpers no longer used after the shared helper switch

Why this pass stayed safe:

- no change to pricing card markup
- no change to billing toggle behavior
- no change to CTA rendering contract
- only helper consolidation and dead-code removal

Validation:

- `php -l` on touched PHP files: `PASS`
- strict gate: `PASS`
- front page:
  - `/fr-FR/page/plans-tarifaires`: `200`
- runtime markers confirmed:
  - `pb-pricing-plans-title`
  - `pb-pricing-plan-feature-text`
  - `pb-pricing-plan-cta`
- validated widget spot check still clean:
  - `Carousel`: present
  - `Hero`: present
  - `SnapCards`: present
  - `FeatureGrid`: present

### 2026-06-02 - Ninth Renderer Dedup Pass

Scope:

- `app/Extensions/PagesBuilder/Widgets/LogoCloud/Renderer.php`

What changed:

- switched `LogoCloud` to shared:
  - toggle
  - align
  - color
  - border style / shadow preset helpers
  - text font/size/list helpers
  - icon sanitization
  - URL sanitization
  - block id / block selector helpers
  - widget `Design` CSS builder
- removed duplicated widget-surface `Design` CSS assembly from `LogoCloud`
- removed dead local helper code no longer needed after the shared helper switch

Why this pass stayed safe:

- no change to `classic / cloud4 / cloud6 / cloud7` rendering branches
- no change to item parsing or link behavior
- only helper consolidation and dead-code removal

Validation:

- `php -l` on touched PHP files: `PASS`
- strict gate: `PASS`
- no dedicated front page found in current dataset for `LogoCloud`
- runtime marker confirmed on `/fr-FR/page/nouvelle-page`:
  - `pb-logo-cloud`
- validated widget spot check still clean:
  - `Carousel`: present
  - `Hero`: present
  - `SnapCards`: present
  - `FeatureGrid`: present

### 2026-06-02 - Tenth Renderer Dedup Pass

Scope:

- `app/Extensions/PagesBuilder/Widgets/ContentSplitMedia/Renderer.php`

What changed:

- switched `ContentSplitMedia` to shared:
  - toggle
  - align
  - media fit
  - color
  - border style / shadow preset helpers
  - text font/size/list helpers
  - icon sanitization
  - URL sanitization
  - block id / block selector helpers
  - widget `Design` CSS builder
- removed duplicated widget-surface `Design` CSS assembly from `ContentSplitMedia`
- kept widget-specific helpers only for:
  - vertical align
  - media kind / position / ratio / preload
  - feature list alignment specifics

Why this pass stayed safe:

- no change to image/video/placeholder rendering branches
- no change to CTA rendering contract
- no change to section structure or alignment logic
- only helper consolidation and local dead-code removal

Validation:

- `php -l` on touched PHP files: `PASS`
- strict gate: `PASS`
- front page:
  - `/fr-FR/page/contenu-media`: `200`
- runtime markers confirmed:
  - `pb-content-split-media`
- validated widget spot check still clean:
  - `Carousel`: present
  - `Hero`: present
  - `SnapCards`: present
  - `FeatureGrid`: present

### 2026-06-02 - Regression Fix After Tenth Pass

Scope:

- `app/Extensions/PagesBuilder/Widgets/ContentSplitMedia/Renderer.php`
- `app/Extensions/PagesBuilder/Widgets/Image/Renderer.php`
- `app/Extensions/PagesBuilder/Widgets/Button/Renderer.php`

What broke:

- `ContentSplitMedia` still called a removed local `$normalizeToggle` closure for video options.
- `Image` and `Button` still declared private helpers with names now provided by `AbstractWidgetRenderer`, which caused PHP visibility fatals.

What changed:

- replaced the stale `ContentSplitMedia` toggle calls with `self::normalizeToggle(...)`
- removed duplicate helper methods from `Image` and `Button`
- kept explicit center fallbacks for the `Image` widget where center is the widget contract

Validation:

- `php -l` on touched PHP files: `PASS`
- strict gate: `PASS`
- front pages:
  - `/fr-FR/page/contenu-media`: `200`
  - `/fr-FR/page/image`: `200`
  - `/fr-FR/page/boutons`: `200`
  - `/fr-FR/page/nouvelle-page`: `200`
- runtime markers confirmed:
  - `pb-content-split-media`
  - `fc-image-block`
  - `fc-widget-button`

### 2026-06-02 - Eleventh Renderer Dedup Pass

Scope:

- `app/Extensions/PagesBuilder/Widgets/Carousel/Renderer.php`

What changed:

- switched `Carousel` to shared:
  - toggle
  - align
  - color
  - border style / shadow preset helpers
  - text font/size/list helpers
  - icon sanitization
  - block id / block selector helpers
- kept widget-specific helpers only for:
  - autoplay delay normalization
  - indicator style normalization
  - arrow style normalization
  - carousel shadow value mapping, to avoid changing the validated visual contract

Why this pass stayed safe:

- no change to slide parsing
- no change to media rendering
- no change to CTA rendering
- no change to carousel controls or runtime data attributes

Validation:

- `php -l` on touched PHP files: `PASS`
- strict gate: `PASS`
- front pages:
  - `/fr-FR/page/carrousel`: `200`
  - `/fr-FR/page/nouvelle-page`: `200`
  - `/fr-FR/page/page-widgets`: `200`
- runtime markers confirmed:
  - `fc-carousel-wrapper`
  - `fc-carousel-slide`
  - `fc-carousel-control`
  - `fc-carousel-indicator`
  - `fc-carousel-caption-btn`
- validated widget spot check still clean:
  - `Carousel`: present
  - `Hero`: present
  - `SnapCards`: present
  - `FeatureGrid`: present

### 2026-06-03 - Twelfth Renderer Dedup Pass

Scope:

- `app/Extensions/PagesBuilder/Widgets/FeatureGrid/Renderer.php`

What changed:

- switched `FeatureGrid` to shared:
  - toggle
  - align
  - color
  - border style / shadow preset helpers
  - text font/size/list helpers
  - icon sanitization
  - URL sanitization
  - block id / block selector helpers
- kept widget-specific helpers only for:
  - feature grid variant normalization
  - button variant normalization
  - button target normalization
  - feature grid shadow value mapping, to avoid changing the validated visual contract

Why this pass stayed safe:

- no change to repeater parsing
- no change to item/card HTML structure
- no change to CTA rendering
- no change to empty-card fallback

Validation:

- `php -l` on touched PHP files: `PASS`
- strict gate: `PASS`
- front pages:
  - `/fr-FR/page/grille-d-avantages`: `200`
  - `/fr-FR/page/nouvelle-page`: `200`
  - `/fr-FR/page/page-widgets`: `200`
- runtime markers confirmed:
  - `pb-feature-grid`
  - `pb-feature-item`
  - `pb-feature-item-cta`
- validated widget spot check still clean:
  - `Carousel`: present
  - `Hero`: present
  - `SnapCards`: present
  - `FeatureGrid`: present

### 2026-06-03 - Thirteenth Renderer Dedup Pass

Scope:

- `app/Extensions/PagesBuilder/Widgets/SnapCards/Renderer.php`

What changed:

- switched `SnapCards` to shared:
  - toggle
  - align
  - color
  - border style / shadow preset helpers
  - text font/size/list helpers
  - icon sanitization
  - URL sanitization
  - block id / block selector helpers
- kept widget-specific helpers only for:
  - snap card variant normalization
  - snap card shadow value mapping, to avoid changing the validated visual contract

Why this pass stayed safe:

- no change to repeater parsing
- no change to media rendering
- no change to CTA HTML structure
- no change to scroll/card runtime data attributes

Validation:

- `php -l` on touched PHP files: `PASS`
- front pages:
  - `/fr-FR/page/cartes-scroll`: `200`
  - `/fr-FR/page/nouvelle-page`: `200`
  - `/fr-FR/page/page-widgets`: `200`
- runtime markers confirmed:
  - `pb-snap-cards`
  - `pb-snap-card`
  - `pb-snap-card-link`
  - `btn btn-primary`

### 2026-06-03 - Fourteenth Renderer Dedup And Regression Pass

Scope:

- `app/Extensions/PagesBuilder/Widgets/StatsSection/Renderer.php`
- `app/Extensions/PagesBuilder/Widgets/Contact/Renderer.php`
- `app/Extensions/PagesBuilder/Widgets/FaqAccordion/Renderer.php`
- `app/Extensions/PagesBuilder/Widgets/VideoPlayer/Renderer.php`
- `app/Extensions/PagesBuilder/Widgets/TestimonialCards/Renderer.php`
- `app/Extensions/PagesBuilder/Widgets/ContactSection/Renderer.php`
- `app/Extensions/PagesBuilder/Widgets/NewsletterSection/Renderer.php`
- `app/Extensions/PagesBuilder/Widgets/Heading/Renderer.php`
- `app/Extensions/PagesBuilder/Widgets/Text/Renderer.php`
- `app/Extensions/PagesBuilder/Widgets/Newsletter/Renderer.php`
- `app/Extensions/PagesBuilder/Widgets/Hero/Renderer.php`
- `app/Extensions/PagesBuilder/Widgets/ContactSection/Assets/js/contact-section-preview.js`
- `app/Extensions/PagesBuilder/Assets/js/contact-section-preview.js`

What changed:

- removed remaining local aliases that simply proxied shared renderer primitives
- replaced remaining manual widget-scoped selectors with `blockSelector(...)`
- kept widget-specific helpers only where they encode a real widget contract:
  - variants
  - columns
  - ratings
  - media preload / skin / fitting
  - rich text media normalization
  - form catalog fallback behavior
- fixed the `ContactSection` regression detected during the pass:
  - centered key-point lists now follow the same validated contract as `ContentSplitMedia` and `NewsletterSection`
  - the list is centered as a block
  - bullets stay visually attached to their text
  - text remains left-aligned inside the centered list block

Validation:

- `php -l` on touched PHP files: `PASS`
- `node --check` on touched preview JS files: `PASS`
- front pages:
  - `/fr-FR/page/nouvelle-page`: `200`
  - `/fr-FR/page/page-widgets`: `200`
  - `/fr-FR/page/contact`: `200`
  - `/fr-FR/page/contenu-media`: `200`
  - `/fr-FR/page/image`: `200`
  - `/fr-FR/page/boutons`: `200`
  - `/fr-FR/page/cartes-scroll`: `200`
  - `/fr-FR/page/carrousel`: `200`
  - `/fr-FR/page/grille-d-avantages`: `200`
  - `/fr-FR/page/titre`: `200`
  - `/fr-FR/page/texte`: `200`
- CLI render smoke checks:
  - `/fr-FR/page/nouvelle-page`: `EXIT 0`
  - `/fr-FR/page/contact`: `EXIT 0`
- validated widget markers confirmed:
  - `Carousel`: present
  - `Hero`: present
  - `SnapCards`: present
  - `FeatureGrid`: present
  - `ContentSplitMedia`: present
  - `ContactSection`: present
  - `NewsletterSection`: present
  - `FaqAccordion`: present
  - `StatsSection`: present
  - `Text`: present

### 2026-06-03 - Fifteenth PagesBuilder JS Cleanup Pass

Scope:

- `app/Extensions/PagesBuilder/Assets/js/pages-builder.js`

What changed:

- removed legacy internal preview branches for official widgets now served by autonomous `preview_handler` assets:
  - `heading`
  - `text`
  - `image`
  - `button`
  - `hero`
  - `snap_cards`
  - `feature_grid`
  - `newsletter`
  - `contact`
  - `spacer`
  - `divider`
- removed orphaned internal preview functions for:
  - `Hero`
  - `SnapCards`
  - `FeatureGrid`
- removed 20 unused helper/function declarations left behind by the autonomous preview migration:
  - first cascade: `readBoolStorage`, `addSourceItem`, `openBlockBoxEditor`, `setSectionColumns`, `shouldMergeContentNavigationTabs`, `resolveInspectorFieldStepPriority`, `hasInspectorNavigationFields`, `buildInspectorDensityHint`, `formatLinkLine`, `syncInlineSunEditorTextarea`, `normalizeFeatureGridGridVariant`, `enhanceInlineSunEditorColors`
  - second cascade: `setInspectorDensity`, `ensureBoxEditor`, `findSunEditorToolbarCommandModule`, `applySunEditorToolbarColor`, `createSunEditorInlineColorControl`
  - third cascade: `buildBoxModelInput`, `updateBlockBoxSetting`, `resetBlockBoxEditor`
- kept legacy preview branches for non-official/older block types where no autonomous handler is guaranteed
- kept shared SnapCards preview initialization because the autonomous handler still emits the same interactive preview data hooks
- synchronized the final `pages-builder.js` cleanup to TEST

Why this pass stayed safe:

- every official widget definition declares a `preview_handler`
- widget preview assets are loaded before `pages-builder.js` in the admin edit view
- no inspector contract change
- no frontend renderer change
- no widget-local preview asset change
- dead-function scan returned: `no unused function declarations detected`
- final `pages-builder.js` size:
  - LTS: `973121` bytes
  - TEST: `973121` bytes

Validation:

- `node --check` on LTS `pages-builder.js`: `PASS`
- `node --check` on TEST `pages-builder.js`: `PASS`
- `php -l` on LTS `PageBuilderContactFormCatalogService.php`: `PASS`
- `php -l` on TEST `PageBuilderContactFormCatalogService.php`: `PASS`
- strict gate: `PASS`
- CLI render smoke check:
  - `/fr-FR/page/nouvelle-page`: `EXIT 0`
- front pages:
  - `/fr-FR/page/nouvelle-page`: `200`
  - `/fr-FR/page/page-widgets`: `200`
  - `/fr-FR/page/carrousel`: `200`
  - `/fr-FR/page/cartes-scroll`: `200`
  - `/fr-FR/page/grille-d-avantages`: `200`
  - `/fr-FR/page/contact`: `200`
  - `/fr-FR/page/image`: `200`
  - `/fr-FR/page/boutons`: `200`
- validated widget markers confirmed:
  - `Carousel`: present
  - `Hero`: present
  - `SnapCards`: present
  - `FeatureGrid`: present
  - `ContactSection`: present
  - `NewsletterSection`: present

### 2026-06-03 - Sixteenth PagesBuilder CSS Cleanup Pass

Scope:

- `app/Extensions/PagesBuilder/Services/PageBuilderRenderService.php`
- `app/Extensions/PagesBuilder/Assets/css/widgets/hero.css`

What changed:

- removed the legacy duplicated global asset `css/widgets/hero.css`
- changed the Hero fallback runtime asset from `css/widgets/hero.css` to the canonical `css/hero.css`
- synchronized the service change and deleted the duplicated asset in TEST

Why this pass stayed safe:

- `css/widgets/hero.css` and `css/hero.css` had identical SHA-256 content
- `css/widgets/hero.css` had only one source reference, inside `PageBuilderRenderService`
- after the patch, no source reference to `css/widgets/hero.css` remains in LTS or TEST
- the front HTML for `/fr-FR/page/nouvelle-page` no longer references the old path and still references `css/hero.css`

CSS duplication decision:

- widget-local CSS copies under `Widgets/<Widget>/Assets/css/` were not removed in this pass
- even when identical to `Assets/css/...`, those files may be required by future signed widget packaging
- the current runtime still publishes and serves module assets from `app/Extensions/PagesBuilder/Assets/`
- a later packaging-focused pass should decide whether to make widget-local assets canonical before deleting either side

Validation:

- `php -l` on LTS `PageBuilderRenderService.php`: `PASS`
- `php -l` on TEST `PageBuilderRenderService.php`: `PASS`
- strict gate: `PASS`
- CLI render smoke check:
  - `/fr-FR/page/nouvelle-page`: `EXIT 0`
- front pages:
  - `/fr-FR/page/nouvelle-page`: `200`
  - `/fr-FR/page/page-widgets`: `200`
  - `/fr-FR/page/contact`: `200`
  - `/fr-FR/page/boutons`: `200`

### 2026-06-03 - Seventeenth PagesBuilder Legacy Widget JS Asset Cleanup Pass

Scope:

- `app/Extensions/PagesBuilder/Assets/js/widgets/heading-preview.js`
- `app/Extensions/PagesBuilder/Assets/js/widgets/hero-preview.js`
- `app/Extensions/PagesBuilder/Assets/js/widgets/image-preview.js`
- `app/Extensions/PagesBuilder/Assets/js/widgets/text-preview.js`

What changed:

- removed the obsolete `Assets/js/widgets/*` preview handlers
- kept the canonical active preview handlers in `Assets/js/*`
- synchronized the deletions to TEST

Why this pass stayed safe:

- no source reference to `js/widgets/` remained before deletion
- widget definitions load canonical paths such as `js/hero-preview.js`, `js/image-preview.js`, `js/text-preview.js`, and `js/heading-preview.js`
- canonical handlers passed `node --check`
- widget-local JS copies under `Widgets/<Widget>/Assets/js/` were kept for packaging autonomy

Validation:

- `node --check` on canonical LTS handlers: `PASS`
- strict gate: `PASS`
- CLI render smoke check:
  - `/fr-FR/page/nouvelle-page`: `EXIT 0`
- front HTML check:
  - no `js/widgets/` reference on `/fr-FR/page/nouvelle-page`
- front pages:
  - `/fr-FR/page/nouvelle-page`: `200`
  - `/fr-FR/page/page-widgets`: `200`
  - `/fr-FR/page/contact`: `200`
  - `/fr-FR/page/image`: `200`
  - `/fr-FR/page/titre`: `200`
  - `/fr-FR/page/texte`: `200`

## Immediate Next Action

Renderer cleanup pass and shared `pages-builder.js` dead-code cleanup are complete for the duplicated shared helper layer.

Next cleanup should stay outside this renderer pass and be scoped separately:

- packaging-aware CSS asset deduplication, only after deciding whether widget-local assets become canonical
- continue only with widget-scoped fixes unless a shared defect is proven

# FLATCMS BUILDER PHASE 0 RUNTIME BLUEPRINT

Status: technical execution blueprint for premium builder runtime preparation in
`FlatCMS LTS Core`.
Last update: 2026-05-20

See also:

- [FLATCMS_BUILDER_WIDGET_DISTRIBUTION.md](/Applications/MAMP/htdocs/FlatCMS-LTS-Core/FLATCMS_BUILDER_WIDGET_DISTRIBUTION.md)
  for the product-side catalog doctrine that must constrain future runtime
  discovery and exposure.

## 1. Purpose

This document defines the concrete `Phase 0` work required before migrating
`PagesBuilder`, `MenuBuilder`, or `FooterBuilder` as premium extensions on top
of `FlatCMS LTS Core`.

It is not a product pitch.

It is an execution contract for preparing the runtime so that premium builders
can exist without contaminating the LTS core.

## 2. Goal

Before any builder migration starts, the LTS runtime must be able to support
premium extensions through generic contracts only.

The target is:

- builders live in `app/Extensions/`
- builders remain optional
- builders can be enabled or disabled safely
- builders can register routes, hooks, assets, renderers, and widgets
- the LTS core remains fully operational when all builders are absent

## 3. Current LTS Runtime Reality

The LTS core already contains a partial extension-ready foundation.

### 3.1 What already exists

Current runtime capabilities already visible in code:

- [ModuleManager.php](/Applications/MAMP/htdocs/FlatCMS-LTS-Core/app/Core/ModuleManager.php)
  scans both:
  - `app/Modules`
  - `app/Extensions`
- [App.php](/Applications/MAMP/htdocs/FlatCMS-LTS-Core/app/Core/App.php)
  already loads:
  - extension routes from `Config/routes.php`
  - extension listeners from `Hooks/listeners.php`
- [Hook.php](/Applications/MAMP/htdocs/FlatCMS-LTS-Core/app/Core/Hook.php)
  already loads hook definitions from enabled extensions
- [I18n.php](/Applications/MAMP/htdocs/FlatCMS-LTS-Core/app/Core/I18n.php)
  already resolves locale trees from `app/Extensions/<Extension>/Languages/`
- [View.php](/Applications/MAMP/htdocs/FlatCMS-LTS-Core/app/Core/View.php)
  already resolves view paths in `app/Extensions/<Extension>/Views/`

Conclusion:

- `Phase 0` is not starting from zero
- the LTS core is already extension-aware
- the missing work is to harden, normalize, and constrain this behavior for
  premium builders

## 4. Current Gaps

Even though the runtime already sees `app/Extensions`, it is not yet ready for
premium builders as a disciplined product lane.

Main gaps:

1. manifest contract is still module-centric
2. there is no explicit extension manifest doctrine
3. there is no generic asset publication or collection contract
4. there is no generic renderer resolution contract for builder enrichment
5. there is no generic builder widget discovery contract
6. there is no formal shared licensing infrastructure for premium builders
7. there is no explicit post-enable or post-disable lifecycle contract
8. there is no migration pilot protocol encoded in the runtime doctrine

## 5. Mandatory Phase 0 Deliverables

`Phase 0` is complete only when the following deliverables exist.

### 5.1 Extension manifest contract

The runtime must support a stable manifest contract for extensions.

Target:

- keep backward compatibility with `module.json` if needed
- define the premium canonical target as `extension.json`
- normalize manifest loading in one place

Minimum manifest shape:

```json
{
  "name": "PagesBuilder",
  "key": "pages-builder",
  "type": "builder",
  "tier": "premium",
  "version": "1.0.0",
  "enabled": false,
  "official": true,
  "requires": {
    "flatcms": ">=1.0.0"
  },
  "routes": "Config/routes.php",
  "hooks": "Hooks/listeners.php",
  "widgets_path": "Widgets"
}
```

Rules:

- the core must not branch on the builder name
- the manifest must describe capabilities, not trigger product-specific logic
- manifest loading must stay generic for any extension, not just builders

### 5.2 Extension classification

The runtime must classify loaded units explicitly:

- `module`
- `extension`
- `builder`

Current note:

- [ModuleManager.php](/Applications/MAMP/htdocs/FlatCMS-LTS-Core/app/Core/ModuleManager.php)
  already exposes `location`
- this should be hardened with a normalized `type` and optional `tier`

Why:

- admin discovery
- future marketplace differentiation
- future premium gating without name-based branching

### 5.3 Generic route and hook loading contract

Current route and listener loading already works, but it is still implicit.

The runtime contract must explicitly define:

- what file path is loaded
- when it is loaded
- in what order
- how failure is reported

Required target:

- routes come from manifest-declared or convention-safe paths
- listeners come from manifest-declared or convention-safe paths
- no builder route enters `config/routes.php`
- no builder listener enters `config/hooks.php`

### 5.4 Generic asset contract

This is currently the biggest missing runtime piece.

The core needs a generic rule for extension-owned assets.

Target:

- extension assets stay inside the extension source tree
- public publication target stays isolated under:
  - `public/assets/extensions/<extension-key>/`

Required runtime behavior:

- declare asset roots
- resolve published URLs without core-global mixing
- avoid injecting builder assets into core admin/frontend bundles

`Phase 0` does not need a full build system.

It does need a stable publication and lookup contract.

### 5.5 Generic renderer resolution contract

This is the most important contract for future builders.

The runtime must be able to ask:

- `is there an external renderer able to enrich this canonical entity?`

without asking:

- `is PagesBuilder enabled?`

Target contract examples:

- `ContentRendererInterface`
- `ExtensionRendererInterface`
- `RenderableContract`

Required behavior:

- core standard renderer remains canonical fallback
- extension renderers may enrich or override through generic resolution
- renderer resolution must work with no builder installed

### 5.6 Generic widget discovery contract

Builder widgets must stay builder-owned, but the runtime still needs a generic
discovery contract.

Target:

- discover extension widgets through manifest capability or convention
- never hardcode widget names in core runtime
- keep business ownership in the builder

What `Phase 0` must define:

- widget root resolution
- metadata contract
- safe absence behavior
- activation and deactivation behavior

### 5.7 Shared licensing infrastructure

Premium builders need licensing support, but not duplicate license systems.

`Phase 0` must define:

- one shared license infrastructure
- one shared validation flow
- one shared secure storage rule
- builder-specific gating only at the policy layer

Rules:

- published content must not break because authoring UI is gated
- standard runtime must not depend on a premium license

### 5.8 Lifecycle contract

The runtime must define what happens when an extension is:

- discovered
- enabled
- disabled
- invalid
- missing dependencies

Required behavior:

- no fatal error when a builder is absent
- no frontend crash when a builder is disabled
- explicit failure state when an extension manifest is invalid
- symmetric activation/deactivation expectations

## 6. Concrete Runtime Work Items

This is the exact implementation order I recommend.

### Step 1 — Harden manifest loading

Primary target:

- [ModuleManager.php](/Applications/MAMP/htdocs/FlatCMS-LTS-Core/app/Core/ModuleManager.php)

Tasks:

- support a normalized extension manifest contract
- decide `module.json` only vs dual `module.json`/`extension.json`
- expose:
  - `location`
  - `type`
  - `tier`
  - `official`
  - capability metadata

Constraint:

- no builder names in runtime logic

### Step 2 — Formalize generic route and hook loading

Primary target:

- [App.php](/Applications/MAMP/htdocs/FlatCMS-LTS-Core/app/Core/App.php)
- [Hook.php](/Applications/MAMP/htdocs/FlatCMS-LTS-Core/app/Core/Hook.php)

Tasks:

- codify load order
- codify manifest-driven or convention-driven lookup
- codify failure behavior for invalid extensions

Constraint:

- preserve current working behavior for standard modules

### Step 3 — Define extension asset publication contract

Primary target:

- generic runtime service or helper, not builder-specific code

Tasks:

- define extension public asset target
- define URL resolution contract
- define publication strategy

Constraint:

- no pollution of core global asset bundles

### Step 4 — Introduce generic renderer resolution

Primary target:

- new generic runtime contract layer

Tasks:

- define renderer interface
- define resolver entrypoint
- keep standard rendering as fallback

Constraint:

- zero builder names in core renderer logic

### Step 5 — Define widget discovery contract

Primary target:

- generic extension widget discovery only

Tasks:

- resolve builder-owned widget roots
- define metadata discovery
- define safe failure behavior

Constraint:

- no widget migration yet
- only runtime preparation

### Step 6 — Define shared premium licensing foundation

Primary target:

- shared service layer

Tasks:

- define storage
- define validation flow
- define gating semantics

Constraint:

- no duplicated builder-local license systems

## 7. Explicit Non-Goals For Phase 0

The following are not `Phase 0`:

- rebuilding `PagesBuilder`
- migrating builder widgets
- rewriting page authoring UX
- moving existing builder data
- implementing mega-menu logic
- implementing footer builder rendering

`Phase 0` is runtime preparation only.

## 8. First Pilot After Phase 0

The first migration pilot must be `PagesBuilder`.

Why:

- it validates the richest combination of:
  - canonical page ownership
  - enrichment data
  - widgets
  - rendering
  - licensing
  - activation/deactivation

Success criteria for the pilot:

- page standard CRUD still works with no builder
- builder authoring can be enabled
- builder authoring can be disabled
- frontend fallback remains stable
- no builder name is introduced in core runtime files

Only after that:

- `MenuBuilder`
- then `FooterBuilder`

## 9. Mandatory Validation Questions Before Any Builder Coding

Before implementing any premium builder lane, the answer must be:

1. Does the runtime already provide a generic extension contract for this need?
2. If not, can one be added generically without naming a builder?
3. Does the patch keep `FlatCMS LTS Core` fully operational with all builders
   absent?
4. Does the patch avoid builder names in core runtime files?
5. Does the patch preserve canonical ownership in the standard module?

If one answer is `no`, stop and redesign first.

## 10. Success Definition

`Phase 0` is successful when this sentence becomes true:

`FlatCMS LTS Core can host premium builders through generic extension contracts,
without learning the builders' product names and without losing its standard
runtime autonomy.`

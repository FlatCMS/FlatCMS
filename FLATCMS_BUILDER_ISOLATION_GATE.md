# FLATCMS BUILDER ISOLATION GATE

Status: canonical premium builder isolation doctrine for `FlatCMS LTS Core`.
Last update: 2026-05-20

See also:

- [FLATCMS_BUILDER_WIDGET_DISTRIBUTION.md](/Applications/MAMP/htdocs/FlatCMS-LTS-Core/FLATCMS_BUILDER_WIDGET_DISTRIBUTION.md)
  for the closed-catalog rule, signed widget distribution classes, and
  customer-specific widget exposure policy.

## 1. Purpose

This document defines the mandatory architecture rule for premium builders in
relation to `FlatCMS LTS Core`.

Its role is to prevent:

- reintroducing premium authoring lanes into the LTS core
- leaking builder-specific logic into standard modules
- mixing canonical core data with builder enrichment
- turning builder widgets into core runtime dependencies
- patching the core "just to make a builder work"

If implementation and this document disagree, implementation must change.

## 2. Non-Negotiable Summary

`FlatCMS LTS Core` must remain:

- stable
- deliverable
- testable
- maintainable
- operational without any premium builder installed

Mandatory consequences:

- premium builders live in `app/Extensions/`
- the core must never know a builder by its product name
- the core may expose generic extension contracts
- canonical content remains owned by the standard modules
- builders store enrichment only
- disabling a builder must not break published standard runtime behavior

## 3. Official Scope

This doctrine applies whenever work touches:

- `Pages` / `Menu` / `Footer`
- premium builder lanes derived from those domains
- builder widgets
- builder rendering
- builder data storage
- builder activation, deactivation, or licensing
- generic extension points required to support premium builders

## 4. Directory And Ownership Rules

### 4.1 LTS core zones

Canonical LTS zones:

- `app/Core/`
- `app/Modules/`
- `app/Services/`
- `config/`
- `data/core/`
- `themes/`
- `public/`

These zones are owned by the LTS core runtime and standard modules.

### 4.2 Premium builder zones

Premium builders must live in:

- `app/Extensions/PagesBuilder/`
- `app/Extensions/MenuBuilder/`
- `app/Extensions/FooterBuilder/`

Builder widgets must live in:

- `app/Extensions/PagesBuilder/Widgets/`
- `app/Extensions/MenuBuilder/Widgets/`
- `app/Extensions/FooterBuilder/Widgets/`

Premium builders must not live in:

- `app/Modules/PagesBuilder/`
- `app/Modules/MenuBuilder/`
- `app/Modules/FooterBuilder/`

## 5. Core Knowledge Rule

The correct rule is not:

- `the core never knows builders`

The correct rule is:

- `the core never knows a builder by its product name`
- `the core may expose generic extension contracts`

Allowed examples:

- generic extension discovery
- generic route collection
- generic hook registration
- generic asset publication or collection
- generic renderer resolution
- generic widget registry contracts

Forbidden examples:

- `if ($builderEnabled) { ... }`
- `if ($module === 'PagesBuilder') { ... }`
- `PagesBuilder::...`
- `MenuBuilder::...`
- `FooterBuilder::...`
- builder names hardcoded in core runtime files

## 6. Generic Core Changes Are Allowed Only Under Strict Conditions

By default, the following zones are protected:

- `app/Core/`
- `app/Bootstrap/`
- `config/app.php`
- `config/routes.php`
- `config/hooks.php`
- `index.php`
- `public/index.php`

They may be modified only if all of the following are true:

1. the change is generic
2. the change is stable
3. the change is useful to more than one extension or future lane
4. the change does not mention any builder by name
5. the change keeps the LTS core functional when all builders are absent

If one of these conditions fails, the patch is invalid.

## 7. Required Runtime Contracts

Premium builders are only viable if the LTS core exposes generic extension
contracts.

The target runtime capability set is:

- extension manifest discovery
- extension activation and deactivation
- generic route provider loading
- generic hook or listener loading
- generic admin/frontend asset collection
- generic renderer resolution
- generic widget discovery inside extensions

Acceptable examples of generic contracts:

- `ExtensionManifestInterface`
- `ExtensionRouteProviderInterface`
- `ExtensionHookProviderInterface`
- `ExtensionAssetProviderInterface`
- `ContentRendererInterface`
- `ExtensionRendererInterface`
- `RenderableContract`

The exact class names may differ, but the principle is mandatory:

- builder support must flow through generic contracts
- never through builder-specific core branching

## 8. Licensing Rule

Premium builders must not duplicate ad hoc license logic in each builder.

Correct target:

- shared licensing infrastructure
- builder-level gating on top of that infrastructure

Mandatory consequences:

- the authoring UI may be gated
- canonical content reading and standard runtime must not be gated
- published output must survive license loss where the data remains valid

Forbidden target:

- a different homegrown license system per builder

## 9. Data Ownership Rule

Canonical content remains owned by the standard core domains:

- `Pages` owns page data
- `Menu` owns menu data
- `Footer` owns footer data

Premium builders store enrichment only.

Allowed builder data locations:

- `data/extensions/pages-builder/`
- `data/extensions/menu-builder/`
- `data/extensions/footer-builder/`

Allowed data contract:

- reference canonical entities by stable IDs
- store enrichment metadata separately

Forbidden:

- canonical duplication
- builder-only truth replacing core truth
- storing builder enrichment in `data/core/`

## 10. Widget Ownership Rule

If a widget exists only for a premium builder, it belongs to that builder.

Allowed:

- `app/Extensions/PagesBuilder/Widgets/Hero/`
- `app/Extensions/MenuBuilder/Widgets/MegaMenuColumn/`
- `app/Extensions/FooterBuilder/Widgets/FooterNewsletter/`

Forbidden:

- placing builder widgets in `app/Modules/`
- placing builder widgets in `app/Core/`
- making the LTS core depend on a builder widget to render standard content

Important distinction:

- widget business ownership stays in the builder
- shared authoring primitives may still live in a generic shared layer such as
  `BuilderCore` if they are genuinely cross-builder and builder-agnostic

## 11. Routes, Hooks, And Assets

### 11.1 Routes

Builder routes must be declared inside the builder:

- `app/Extensions/PagesBuilder/Config/routes.php`
- `app/Extensions/MenuBuilder/Config/routes.php`
- `app/Extensions/FooterBuilder/Config/routes.php`

`config/routes.php` must not contain builder-specific routes.

### 11.2 Hooks

Builder listeners must be declared inside the builder:

- `app/Extensions/PagesBuilder/Hooks/listeners.php`
- `app/Extensions/MenuBuilder/Hooks/listeners.php`
- `app/Extensions/FooterBuilder/Hooks/listeners.php`

`config/hooks.php` must not contain builder-specific hooks or builder names.

If a hook is missing, only a generic hook may be introduced.

Acceptable examples:

- `extension.routes.collect`
- `extension.assets.collect`
- `content.renderer.resolve`
- `admin.sidebar.extend`

Forbidden examples:

- `pagesbuilder.render`
- `menubuilder.load`
- `footerbuilder.render`

### 11.3 Assets

Builder assets must remain builder-owned.

Allowed source zones:

- `app/Extensions/*Builder/Assets/`

Allowed public publication target:

- `public/assets/extensions/pages-builder/`
- `public/assets/extensions/menu-builder/`
- `public/assets/extensions/footer-builder/`

Forbidden:

- mixing builder assets into core global admin/frontend bundles

## 12. Rendering Rule

The LTS core renders the standard runtime.

A premium builder may register an external renderer through a generic contract.

Mandatory consequences:

- no builder condition in `Pages` frontend runtime
- no builder condition in `Menu` frontend runtime
- no builder condition in `Footer` frontend runtime
- renderer resolution must stay generic

If standard rendering needs extensibility, introduce a generic extension point,
never a builder-specific condition.

## 13. Protected Standard Modules

The following standard modules are protected from premium contamination:

- `app/Modules/Pages/`
- `app/Modules/Menu/`
- `app/Modules/Footer/`
- `app/Modules/Settings/`
- `app/Modules/Themes/`
- `app/Modules/Media/`
- `app/Modules/Auth/`
- `app/Modules/Core/`

They may only be modified for a generic reason that remains useful without any
builder installed.

## 14. Migration Strategy

Premium builder migration must not be big-bang.

Mandatory sequence:

### Phase 0 — Runtime preparation

Before migrating any builder:

1. define or validate generic extension contracts
2. validate extension discovery
3. validate route and hook loading
4. validate asset publication strategy
5. validate generic renderer resolution
6. validate shared licensing infrastructure

Execution reference:

- [FLATCMS_BUILDER_PHASE0_RUNTIME_BLUEPRINT.md](/Applications/MAMP/htdocs/FlatCMS-LTS-Core/FLATCMS_BUILDER_PHASE0_RUNTIME_BLUEPRINT.md)

### Phase 1 — `PagesBuilder` pilot

`PagesBuilder` is the pilot migration.

Reasons:

- it is the richest builder lane
- it validates widgets, rendering, enrichment, and fallback behavior

No migration of `MenuBuilder` or `FooterBuilder` should begin before the
`PagesBuilder` pilot is stable.

### Phase 2 — `MenuBuilder`

Migrate only after the pilot contracts are proven.

### Phase 3 — `FooterBuilder`

Migrate last, using the already validated runtime contracts.

## 15. Quality Gate

Release or merge only if all checks pass:

- the LTS core boots with all builders absent
- admin standard modules remain functional
- standard frontend remains functional
- canonical CRUD remains operational
- builders can be disabled without fatal errors
- builder data stays outside `data/core/`
- no builder names are introduced in core runtime files
- no inline CSS, JS, handlers, or hardcoded user text are introduced

## 16. Decision Rule

When choosing where to fix a builder problem, the order is:

1. fix inside `app/Extensions/*Builder/`
2. fix inside `app/Extensions/*Builder/Widgets/`
3. fix builder data in `data/extensions/*`
4. fix builder assets
5. use an existing generic core contract
6. propose a minimal generic extension point
7. ask for architectural validation

Never choose:

- patch the LTS core with builder-specific logic

## 17. Mandatory Analysis Template Before Coding

Before coding a premium builder patch, the report must state:

```md
## Builder Isolation Analysis

### Patch goal
...

### Touched files
- [EXTENSION BUILDER] ...
- [BUILDER WIDGET] ...
- [DATA EXTENSION] ...

### Touched core or standard-module files
None.
```

If a core or standard-module file is touched:

```md
### Generic justification
- why the change is generic
- why it does not name a builder
- why it remains useful without builders
```

And always:

```md
### LTS independence
- Does `FlatCMS LTS Core` remain functional if all builders are removed? OUI
- Does the patch introduce a builder name into core runtime files? NON
```

## 18. Final Rule

The target is not:

- `make builders run at any cost`

The target is:

- `make premium builders run without teaching the LTS core their product names`

That is the isolation contract.

# FLATCMS CCTP

Status: canonical, versioned source of truth for `FlatCMS LTS Core`.
Last update: 2026-05-17

## 1. Purpose

This document defines the mandatory technical and product contract for the LTS
core line of FlatCMS.

Its role is to prevent:

- local reinterpretations
- architectural drift
- duplicated primitives
- unstable labels
- surprising behavior
- reintroduction of removed lanes into the stable core

If implementation and this document disagree, implementation must change.

## 2. Source Hierarchy

Mandatory order:

1. this file
2. `FLATCMS_AI_ARCHITECTURE.md`
3. `FLATCMS_BUILDER_ISOLATION_GATE.md` when the task touches premium builders
   or builder/core boundaries
4. `AGENTS.md`
5. `FLATCMS_ROADMAP.md`
6. validated code already present in the repository
7. local Codex skills

Local skills are executors of doctrine. They are not the doctrine itself.

## 3. Hard Gates

Before writing code:

1. verify PSR-4 compatibility
2. verify HMVC compliance
3. verify no new layer introduced
4. verify no dependency added
5. verify naming convention
6. verify JSON flat-file compatibility

If any gate fails:

`STOP and ask for confirmation.`

Mandatory technical gate before implementation and before commit/push:

```bash
bash /Users/alain/.codex/skills/flatcms-inline-zero-tolerance/scripts/strict_gate.sh
```

Never commit with a non-zero gate.

## 4. Product Philosophy

FlatCMS LTS Core must feel like it was designed by one disciplined team.

The system must preserve:

1. architectural continuity
2. code coherence
3. stabilized conventions
4. predictable behavior
5. durable runtime simplicity

Operational consequences:

- one concept keeps one name everywhere
- one problem gets one solution everywhere
- one control keeps one visual contract everywhere
- the core owns canonical data
- public runtime must stay understandable without hidden authoring layers

## 5. Architecture

### 5.1 Mandatory layout

- `app/Core/` = transversal runtime contracts and low-level services
- `app/Modules/<ModuleName>/` = official business domains
- `app/Extensions/<ExtensionName>/` = third-party modules only
- `data/` = canonical flat-file data
- `public/` = controlled public assets and entry points

### 5.2 Boundaries

- never reintroduce removed authoring or commerce lanes into this repository
- never duplicate canonical data ownership to bypass weak architecture
- never statically couple unstable product lanes into the core runtime

## 6. Core System

### 6.1 Service discipline

Services must be:

- explicit
- idempotent
- testable
- UI-light

Forbidden:

- service renders a view
- service emits inline HTML/JS/CSS
- service carries hardcoded user-facing text

### 6.2 Data ownership

Each domain must have one owner for canonical data.

Examples:

- `Pages` owns page data
- `Menu` owns menu data
- `Footer` owns footer data
- `Posts` owns post data

## 7. Routing

Routes must be:

- declarative
- readable
- predictable
- side-effect free

Rules:

- every module declares its own routes
- admin routes live under `/admin/...`
- public routes keep stable semantics
- localized routes follow the existing locale strategy

## 8. Loaders

A loader must load a contract, not a surprise.

Rules:

- stable load order
- idempotent behavior
- explicit failure when a critical dependency is missing
- clean deactivation when an extension is invalid

Forbidden:

- uncontrolled auto-discovery
- mutating core behavior from a template during boot
- parallel language trees for the same feature owner

## 9. Cache

Cache accelerates. It does not decide.

Rules:

- business truth remains outside cache
- cache keys are named by domain and purpose
- invalidation must map to a clear business event
- stale cache must never make an interface incoherent

## 10. UI, Labels, And i18n

Rules:

- no hardcoded user-facing text in PHP, services, or controllers
- all UI strings must live in module locale JSON
- one user action keeps one label everywhere when the meaning is the same

Validation rule:

- keep `fr-FR`, `en-US`, `de-DE`, `es-ES`, `it-IT`, `pt-PT` synchronized when
  the feature is shipped to those locales

## 11. Frontend And Themes

Rules:

- shipped themes must render the standard runtime only
- frontend assets must not depend on removed authoring layers
- preview-only styling must not leak into the LTS frontend runtime

## 12. Security

Rules:

- secrets never live in versioned data
- auth, permissions, CSRF, and upload handling must stay explicit
- new capabilities must default to least privilege

## 13. AI Rule

FlatCMS is agent-ready, but the PHP core is not an agent runtime.

Mandatory policy:

- use `Responses API` for new OpenAI work
- treat `Assistants API` as legacy for new FlatCMS work
- do not embed the `Agents SDK` directly in the HMVC PHP core
- keep advanced orchestration behind a runtime boundary if needed later

## 14. Delivery Rule

Before claiming work is ready:

1. syntax-check touched PHP
2. syntax-check touched JS
3. validate touched JSON
4. run the strict gate
5. report files changed, architecture impact, and residual risk

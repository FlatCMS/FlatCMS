# FlatCMS LTS Core Agent Rules

This repository is the source tree for `FlatCMS LTS Core`.

## Repository Scope

This repo carries only the stable FlatCMS core line:

- native PHP HMVC runtime
- installer runtime
- stable admin and frontend themes
- auth, pages, posts, comments, contact, media, menus, footer
- users, settings, languages, themes, trash, backups, hooks
- analytics and structured data when they are added here

This repo must stay free of removed product lanes and abandoned authoring
systems.

## Mandatory Read Order

1. `FLATCMS_CCTP.md`
2. `FLATCMS_AI_ARCHITECTURE.md` when the task touches AI/OpenAI/agent logic
3. `FLATCMS_ROADMAP.md`

## Hard Gate

Run before implementation and before commit/push:

```bash
bash /Users/alain/.codex/skills/flatcms-inline-zero-tolerance/scripts/strict_gate.sh
```

If the gate fails:

`STOP and ask for confirmation.`

## Scope Discipline

- protect validated behavior first
- keep the smallest possible write scope
- prefer rollback over stacking patches on regressions

## Separation Rules

- no inline CSS
- no inline JS
- no inline handlers
- no hardcoded user-facing text outside i18n

## LTS Product Rule

This repository is a stable core line, not a feature incubator.

- prefer removing unstable coupling over hiding it behind flags
- install/runtime stability has priority over feature growth
- do not reintroduce removed authoring, commerce, or experimental dependencies

## AI Rule

FlatCMS is agent-ready, but the PHP core is not an agent runtime.

Mandatory policy:

- use `Responses API` for new OpenAI work
- treat `Assistants API` as legacy for new FlatCMS work
- do not embed the `Agents SDK` directly in the HMVC PHP core
- keep advanced orchestration behind a runtime boundary if needed later

## Delivery Rule

Before claiming work is ready:

1. syntax-check touched PHP
2. syntax-check touched JS
3. validate touched JSON
4. run the strict gate
5. report files changed, architecture impact, and residual risk

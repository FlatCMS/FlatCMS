# FLATCMS LTS CORE ROADMAP

Status: versioned operational roadmap for the `FlatCMS LTS Core` line.
Last update: 2026-05-20

## Usage

This file is the sequencing reference for ongoing work in this repository.

Rules:

1. apply `AGENTS.md` and `FLATCMS_CCTP.md` first
2. keep this repo focused on the stable core line
3. do not reintroduce removed lanes or dormant dependencies here
4. when a phase item is validated as finished, update this roadmap immediately

## Priority 1 - LTS Baseline Stabilization

- [x] Create a clean repository cut for the stable core line
- [x] Keep `Comments` in the shipped scope
- [x] Remove the non-core physical lanes from the repo cut
- [x] Remove packaging scripts from the source tree
- [x] Remove the extra frontend theme not kept in LTS
- [x] Stabilize the installer entry on Apache root docroot deployments
- [x] Remove frontend/runtime handoffs to deleted lanes
- [x] Lock the footer back to the native core renderer
- [x] Keep only the standard menu lane in public runtime
- [x] Make step 9 demo content opt-in and remove the hard root-item cap from the standard menu lane
- [x] Replace the generic demo seed pack with a classic artisan showcase scenario and local media set
- [x] Remove removed-lane dependencies from helpers, licensing, update catalogs, seeds, and docs

## Priority 2 - Core Release Readiness

- [x] Finish `Analytics` settings
- [x] Add `Matomo` configuration
- [x] Add `Google Analytics` configuration
- [x] Review mobile-first frontend behavior across shipped themes
- [x] Stabilize `Themes` module behavior for the LTS scope
- [x] Add menu item translations in the standard menu lane

## Priority 3 - SEO / AI-Ready Core

- [x] Add minimal `StructuredData` V1 architecture
- [x] Ship `WebSite`
- [x] Ship `Organization`
- [x] Ship `WebPage`
- [x] Ship `Article/BlogPosting`
- [x] Ship `BreadcrumbList`

## Priority 4 - Public Release Hardening

- [x] Run a final clean install smoke test on public Apache hosting
- [x] Run a final clean install smoke test on public Nginx hosting
- [x] Verify demo seed parity after public install:
  - homepage routing
  - translated locales
  - Terra Nōra footer
  - contact form fields
  - promo banner
- [x] Freeze the release notes for the first public `FlatCMS LTS Core`
- [ ] Prepare the public distribution package/runtime checklist for release

## Priority 5 - GEO / GIO Baseline

- [x] Extend `StructuredData` beyond the minimal graph into a true LTS GEO baseline
- [x] Ship `ContactPage`
- [x] Add a public `llms.txt` route
- [x] Add a public `sitemap.xml` route
- [x] Verify parity between `robots.txt`, `sitemap.xml`, breadcrumbs, and JSON-LD
- [x] Define the editorial entity contract for GEO / GIO:
  - organization
  - website
  - page
  - article
  - contact
- [x] Document what remains intentionally out of scope for LTS GEO / GIO:
  - product schemas
  - software application schemas
  - local business variants

## Priority 6 - Agent-Ready Core Foundation

- [x] Audit the current `Settings > Integrations` OpenAI/chatbot surface against the real LTS backend
- [x] Remove or hard-gate any legacy OpenAI UI that has no stable backend contract
- [x] Create the `app/Services/AI/Contracts` foundation
- [x] Create normalized DTOs:
  - `AiRequest`
  - `AiResponse`
  - `AiUsage`
  - `AiToolCall`
  - `AiToolResult`
  - `AiRefusal`
- [x] Create `AIManager`
- [x] Create `OpenAiResponsesProvider`
- [x] Keep the integration strictly `Responses API` first
- [x] Add `.env.local`-based provider configuration loading
- [x] Add a minimal provider health/configuration check in admin
- [x] Define the initial FlatCMS tool registry skeleton
- [x] Keep advanced orchestration outside the HMVC PHP core

## Priority 7 - Post-LTS Decision Gate

- [ ] Decide what remains in `FlatCMS LTS Core` versus what moves to future premium/product lines
- [ ] Re-scope any future builder, marketplace, or agent product work outside this repository
- [ ] Freeze the doctrine for `FlatCMS LTS Core v1.0.0`

# FLATCMS GEO / GIO BASELINE

Status: canonical GEO / GIO baseline for `FlatCMS LTS Core`.
Last update: 2026-05-19

## 1. Purpose

This document defines the public machine-readable content contract for the LTS
line.

For FlatCMS, `GEO / GIO` means:

- clean public entities
- explicit discoverable URLs
- consistent structured metadata
- stable signals for search engines, LLM crawlers, and answer engines

It does not mean:

- autonomous AI SEO editing
- commerce schema sprawl in core
- premium feature leakage into the LTS line

## 2. LTS baseline

The LTS GEO / GIO baseline must expose:

- `robots.txt`
- `llms.txt`
- `sitemap.xml`
- JSON-LD structured data
- canonical localized URLs
- breadcrumbs that match the public navigation model

The baseline must stay:

- deterministic
- schema-driven
- locale-aware
- safe for Apache and Nginx deployments

## 3. Parity rules

Public machine-readable outputs must stay aligned.

### 3.1 robots.txt

`/robots.txt` must:

- allow public crawling
- block admin and private runtime paths
- advertise the public sitemap

### 3.2 llms.txt

`/llms.txt` must:

- point to canonical public entrypoints
- repeat the access boundaries already enforced in `robots.txt`
- avoid private/admin paths

### 3.3 sitemap.xml

`/sitemap.xml` must list only:

- published pages
- published posts
- active blog categories
- localized home/blog/contact entrypoints when they exist

It must never list:

- admin URLs
- draft content
- removed modules
- private runtime files

### 3.4 JSON-LD

JSON-LD must use:

- absolute public URLs
- the same canonical locale-aware routes exposed in the sitemap
- breadcrumb items that match the real public path hierarchy

## 4. Editorial entity contract

The LTS line recognizes these public entities:

### 4.1 Organization

Represents the site owner or editorial brand.

Required public signals:

- `@type = Organization`
- stable `@id`
- public `url`
- public `name`
- optional `description`
- optional `logo`

### 4.2 WebSite

Represents the site as a published web property.

Required public signals:

- `@type = WebSite`
- stable `@id`
- public `url`
- public `name`
- optional `description`
- `publisher -> Organization`

### 4.3 WebPage

Represents published editorial pages.

Required public signals:

- `@type = WebPage`
- stable `@id`
- public `url`
- `name`
- optional `description`
- `inLanguage`
- `isPartOf -> WebSite`
- `breadcrumb -> BreadcrumbList`

### 4.4 BlogPosting

Represents published blog articles.

Required public signals:

- `@type = BlogPosting`
- stable `@id`
- `headline`
- `description`
- `url`
- `datePublished`
- `dateModified`
- `inLanguage`
- `publisher -> Organization`
- `author -> Organization` for the LTS baseline
- optional `image`

### 4.5 ContactPage

Represents the public contact page.

Required public signals:

- `@type = ContactPage`
- stable `@id`
- public `url`
- `name`
- optional `description`
- `inLanguage`
- `isPartOf -> WebSite`
- `about -> Organization`
- `mainEntity -> Organization`
- `breadcrumb -> BreadcrumbList`

## 5. Locale policy

The LTS line is locale-aware.

Rules:

- localized home URLs are first-class public entities
- localized page/post/category URLs must be canonical for that locale
- fallback rendering may exist for UX continuity, but sitemap and JSON-LD
  should prefer the canonical localized public URL

## 6. Out of scope for LTS

The following schemas and GEO/GIO expansions are intentionally excluded from
the LTS line:

- `Product`
- `Offer`
- `AggregateRating`
- `SoftwareApplication`
- `LocalBusiness`
- marketplace-specific entities
- store-specific entities
- agent-authored SEO mutations

These belong to future product lines or premium modules, not to the LTS core.

## 7. Implementation map

Current LTS implementation anchors:

- `app/Modules/Settings/Controllers/FrontController.php`
  - `robots()`
  - `llms()`
  - `sitemap()`
- `app/Services/StructuredData/`
  - `StructuredDataManager`
  - `SchemaGraphBuilder`
  - `Providers/SiteSchemaProvider`
  - `Providers/PageSchemaProvider`
  - `Providers/PostSchemaProvider`

## 8. Relationship to agent-ready architecture

`GEO / GIO` and `agent-ready` are complementary but different.

- `GEO / GIO` governs public discoverable content and metadata
- `agent-ready` governs the future internal AI foundation and provider/tool
  contracts

The LTS core must keep both layers explicit and independent.

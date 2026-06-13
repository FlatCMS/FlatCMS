# FLATCMS BUILDER WIDGET DISTRIBUTION

Status: canonical widget distribution doctrine for premium FlatCMS builders.
Last update: 2026-06-01

## 1. Purpose

This document defines how widget catalogs must evolve for premium builders in
`FlatCMS LTS Core`.

It exists to solve one specific product reality:

- some clients buy a builder and use the standard widget catalog only
- some clients buy a builder but also need premium shared widgets or private
  client widgets

This doctrine protects that model without opening FlatCMS builders to
third-party widget ecosystems.

## 2. Product Rule

FlatCMS builders are not an open marketplace.

Non-negotiable rule:

- third-party developers must not be able to add widgets to official FlatCMS
  builders just by dropping folders or uploading arbitrary packages

Therefore:

- automatic folder existence must never be treated as a publication rule
- widget visibility must never be driven by filesystem presence alone
- package trust must be enforced before a widget becomes available in a builder

## 3. Client Typologies To Support

### 3.1 Standard builder clients

These clients buy a builder and use the validated official catalog only.

They must receive:

- the official builder widgets
- official builder updates
- no premium private widgets
- no customer-specific widgets

### 3.2 Premium or custom builder clients

These clients also buy a builder, but need additional capabilities such as:

- premium shared widgets
- customer-specific widgets
- one-off widgets such as `NW_Carousel`

They must receive:

- the official builder widgets
- the subset of signed premium widgets they are entitled to use
- optionally, private signed widgets reserved for their project only

## 4. Distribution Classes

Additional builder widgets must be classified into one of these classes.

### 4.1 `official-core`

Meaning:

- validated official widget
- part of the standard builder catalog
- visible to every customer entitled to the builder

Examples:

- `Hero`
- `Carousel`
- `LogoCloud`

### 4.2 `official-premium`

Meaning:

- validated official FlatCMS widget
- distributed by FlatCMS only
- not necessarily visible to every builder customer
- may target a premium segment, a business offer, or a privileged group

Examples:

- a shared advanced commerce slider
- a sector-specific premium widget pack

### 4.3 `official-private-client`

Meaning:

- validated and signed by FlatCMS
- distributed only to one client or an explicit allowlist of clients
- hidden from all other builder customers

Example:

- `NW_Carousel`

## 5. Loading Rule

The correct rule is not:

- `if the folder exists, the widget is active`

The correct rule is:

1. the package is discovered as an extension through generic runtime contracts
2. the package is signed by FlatCMS
3. the package declares the target builder explicitly
4. the package distribution class is valid
5. license and client scope checks pass
6. only then may the widget enter the builder catalog

This means widget discovery may stay automatic at runtime, but widget exposure
must remain filtered and controlled.

## 6. Channels

Two distribution channels are allowed.

### 6.1 Official update channel

Use for:

- `official-core`
- `official-premium`
- optional delivery of `official-private-client`

Rules:

- delivered by FlatCMS only
- signed packages only
- versioned and update-safe

### 6.2 Signed upload channel

Use for:

- `official-private-client`
- optional controlled delivery of `official-premium`

Rules:

- upload must not bypass signature verification
- upload must not bypass builder entitlement checks
- upload must not make the widget globally visible by default

Upload is a transport mechanism, not a trust decision.

## 7. Registry Rule

Builder registries must become closed catalogs with controlled extension lanes.

Expected registry behavior:

1. load the official builder baseline
2. load additional signed extension widgets targeting the builder
3. filter them by distribution class
4. filter them by license and client scope
5. expose only the allowed widgets in the builder UI

Important:

- hidden or unauthorized widgets must not appear in the widget picker
- unauthorized widgets must not become active because a folder exists on disk
- the official builder catalog must remain deterministic

## 8. Required Metadata For Additional Widget Packs

Additional widget packages should declare metadata equivalent to:

```json
{
  "name": "NWCarouselPack",
  "key": "nw-carousel-pack",
  "type": "builder-extension",
  "tier": "premium",
  "official": true,
  "signature_required": true,
  "target_builders": ["pages"],
  "distribution": "official-private-client",
  "visibility": "hidden",
  "client_scope": {
    "mode": "allowlist",
    "clients": ["client_nw"]
  }
}
```

The exact shape may evolve, but the required semantics are stable:

- official origin
- mandatory signature
- explicit builder target
- explicit distribution class
- explicit visibility rule
- explicit client scope rule

## 9. Anti-Patterns

The following targets are forbidden.

- `folder exists => widget visible`
- unsigned upload => widget active
- private client widget mixed into the global standard catalog
- third-party widget becoming visible in an official builder
- builder registry logic driven only by filename or folder name
- customer-specific branching hardcoded in the core runtime

## 10. Example: `NW_Carousel`

`NW_Carousel` must not be treated as a standard official widget.

Correct treatment:

- signed by FlatCMS
- distributed as `official-private-client` or `official-premium`
- loaded through extension discovery
- exposed only to the entitled customer set

Incorrect treatment:

- copied directly into the standard builder catalog for all customers
- exposed because the widget folder exists
- installed without package trust verification

## 11. Recommended Implementation Sequence

Future sessions should implement this in three passes.

### Pass A - Registry classification

Add explicit registry support for:

- `official-core`
- `official-premium`
- `official-private-client`

without opening builder catalogs to arbitrary discovered widgets.

### Pass B - Entitlement filtering

Add filtering rules based on:

- builder license entitlement
- premium offer entitlement
- optional client allowlist

### Pass C - Delivery channels

Finalize:

- official signed update delivery
- signed upload delivery
- publication, activation, and visibility symmetry

## 12. Final Rule

FlatCMS builders must remain closed, signed, and curated.

The runtime may discover extension widgets automatically.

The product must expose them only when:

- FlatCMS trusts the package
- the builder is an allowed target
- the customer is entitled to the widget

This is the only acceptable direction for future premium and private widget
distribution.

# FLATCMS AI ARCHITECTURE

Status: canonical FlatCMS AI architecture annex.
Last architecture update: 2026-05-19
Last validated against official OpenAI docs: 2026-05-13

## 1. Purpose

This document defines the official AI architecture target for FlatCMS.

FlatCMS must be:

- Responses-first
- provider-ready
- tool-ready
- structured-output-ready
- agent-ready
- GEO / GIO-compatible

FlatCMS must not become:

- tightly coupled to one SDK runtime
- frontend-secret dependent
- prompt-magic driven
- unsafe by default

### 1.1 LTS interpretation

For `FlatCMS LTS Core`, `agent-ready` means foundation-ready.

It does not mean:

- ship a full chatbot product in core
- ship a full agent orchestration runtime in PHP
- promise autonomous publishing or autonomous site editing

For LTS, the official target is:

- a clean provider abstraction
- normalized AI request/response contracts
- safe configuration boundaries
- future tool-calling compatibility
- future GEO / GIO compatibility at the content and metadata layer

## 2. Official OpenAI Posture For FlatCMS

Validated direction from official OpenAI docs on 2026-05-13:

- `Responses API` is recommended for new projects
- `Assistants API` is legacy
- `Agents SDK` is for applications that own orchestration, tool execution,
  approvals, and state
- tool usage, remote MCP, structured outputs, and multi-turn state are part of
  the current OpenAI surface, not a future-only concept

FlatCMS consequence:

- PHP core must integrate through `Responses`
- advanced agent runtime remains optional and external to the PHP core

## 3. Core Decisions

### 3.1 Responses-first

The canonical API surface for new FlatCMS AI work is:

`POST /v1/responses`

Do not design new FlatCMS AI features around:

- `v1/assistants`
- chat-completions-specific contracts
- frontend direct API calls

### 3.2 No Agents SDK inside the PHP core

For FlatCMS v1.x:

- do not integrate the OpenAI Agents SDK directly into the HMVC PHP core
- do not make the CMS boot depend on a Node or Python agent runtime

Reason:

- FlatCMS must stay simple, modular, and operable in MAMP/PHP-native contexts
- the CMS core needs provider abstraction, not orchestrator lock-in

### 3.3 Agent-ready means architecture-ready

Agent-ready does not mean "ship the whole SDK in core".

It means FlatCMS must already be able to support:

- tool calling
- structured outputs
- refusal handling
- multi-turn continuation
- provider swapping
- future remote MCP wiring
- future advanced orchestration

FlatCMS LTS consequence:

- the core may expose an AI foundation before it exposes a public AI product
- any admin OpenAI surface must be backed by the canonical foundation
- legacy or speculative UI must be removed or hard-gated until backend parity
  exists

## 4. Target Architecture

### 4.1 AI foundation layer

Canonical target:

```txt
app/
└── Services/
    └── AI/
        ├── Contracts/
        │   ├── AiProviderInterface.php
        │   └── AiToolInterface.php
        ├── Providers/
        │   └── OpenAiResponsesProvider.php
        ├── DTO/
        │   ├── AiRequest.php
        │   ├── AiMessage.php
        │   ├── AiUsage.php
        │   ├── AiToolCall.php
        │   ├── AiToolResult.php
        │   └── AiRefusal.php
        ├── Responses/
        │   └── AiResponse.php
        ├── Tools/
        │   ├── ToolRegistry.php
        │   └── FlatCms/
        ├── Exceptions/
        │   ├── AiException.php
        │   ├── AiConfigurationException.php
        │   ├── AiProviderException.php
        │   └── AiRateLimitException.php
        └── AIManager.php
```

### 4.2 Scope of `app/Services/AI/`

Allowed responsibilities:

- provider calls
- request normalization
- response normalization
- tool registry
- structured output handling
- refusal handling
- usage accounting
- rate-limit and timeout handling

Forbidden responsibilities:

- article business logic
- page business logic
- builder business logic
- media business logic
- chatbot UI rendering

Business modules consume the AI layer. They do not define it.

### 4.3 Business module boundary

The product module remains separate:

```txt
app/Modules/AiAgent/
```

`AiAgent` must never call OpenAI directly.

It must always go through:

`AIManager`

### 4.4 LTS admin surface discipline

The LTS core must not ship misleading AI settings.

Rules:

- do not keep a visible OpenAI or chatbot settings surface without a stable
  backend contract
- do not expose provider toggles that map to no runtime capability
- do not expose model or prompt controls that bypass the canonical AI manager
- if a future AI product module exists, it must consume the same foundation
  contract instead of adding a parallel OpenAI integration

## 5. Provider Contract

The provider interface must not stay chat-centric.

Preferred contract:

```php
public function respond(AiRequest $request): AiResponse;
public function isConfigured(): bool;
public function getProviderName(): string;
```

Recommended optional capability probes:

```php
public function supportsTools(): bool;
public function supportsStructuredOutputs(): bool;
public function supportsConversationState(): bool;
```

Avoid `chat()` as the canonical verb. It reflects the old surface too
strongly.

## 6. OpenAI Provider Contract

`OpenAiResponsesProvider` must:

- call `/v1/responses`
- read secrets from `.env.local`
- load the effective model
- support `instructions`
- support `input`
- support `previous_response_id`
- support `tools`
- support `tool_choice`
- support `text.format`
- normalize tool calls and `call_id`
- normalize refusals
- normalize usage
- normalize provider errors

## 7. Required Response Normalization

FlatCMS must not depend directly on raw OpenAI payload shapes in business
modules.

The normalized `AiResponse` should expose at least:

- `responseId`
- `provider`
- `model`
- `outputText`
- `outputItems`
- `toolCalls`
- `refusal`
- `usage`
- `rawMetadata`

### Refusal handling

This is mandatory.

Structured output requests can still produce a refusal that does not follow the
requested JSON schema. Therefore:

- refusal detection must happen before schema validation
- business modules must receive a typed refusal state
- refusal must never be mistaken for invalid builder JSON

## 8. Tool Strategy

FlatCMS must be tool-ready now, even if all tools are not exposed on day one.

### 8.1 FlatCMS tool rules

Tools must be:

- explicit
- whitelisted
- permission-aware
- logged without secrets
- deterministic enough to validate

### 8.2 OpenAI tool surface

The architecture must allow:

- custom function tools
- hosted tools when strategically useful
- remote MCP tools later

### 8.3 Tool anti-patterns

Do not:

- let the model write directly into canonical JSON without validation
- let the model publish content automatically
- expose destructive tools without approval rules

## 9. Structured Outputs

For FlatCMS builders and content workflows:

- never ask the model for free HTML as the canonical output
- always prefer validated JSON contracts
- validate output server-side against FlatCMS schemas
- keep a manual approval step before insertion/publishing

Builder consequence:

- AI can propose section JSON
- FlatCMS validates it
- user previews it
- user confirms insertion

## 10. Conversation State

The architecture must be stateful by design.

Prepare for:

- `response_id`
- `previous_response_id`
- provider-side usage tracking
- optional prompt caching metadata
- instruction swapping between turns

This does not require exposing full conversation mechanics in V1 UI, but the
backend contract must not block them.

## 11. Configuration

Secrets belong only in:

`.env.local`

Recommended OpenAI env naming:

```env
OPENAI_API_KEY=
OPENAI_API_BASE_URL=https://api.openai.com/v1
OPENAI_RESPONSES_MODEL=gpt-5.4-mini
OPENAI_TIMEOUT=20
OPENAI_MAX_OUTPUT_TOKENS=800
OPENAI_SLOW_LOG_THRESHOLD_MS=3000
OPENAI_RATE_LIMIT_PER_MINUTE=10
```

The default model choice above is a FlatCMS recommendation, not a hard-coded
OpenAI truth.

## 12. Safe Settings JSON

Allowed in JSON settings:

- provider selection
- feature toggles
- non-secret model overrides
- UX copy
- human validation flags

Forbidden in JSON settings:

- API keys
- raw provider secrets
- approval bypass flags
- hidden production prompts without governance

## 13. Security Rules

Never:

- expose API keys in frontend code
- call OpenAI directly from frontend views
- store provider secrets in JSON
- log raw secrets
- auto-publish AI output without explicit validation

Always:

- protect endpoints with CSRF where relevant
- validate roles and permissions
- rate-limit AI endpoints
- bound prompt size and uploaded file size
- sanitize and validate outputs before persistence

## 14. GEO / GIO relation

`GEO / GIO` is not a replacement for the AI foundation.

For FlatCMS LTS, `GEO / GIO` belongs to the structured content and discoverable
metadata layer that makes the CMS legible to search engines, LLM crawlers, and
answer engines.

The LTS GEO / GIO baseline should converge:

- `StructuredData` / JSON-LD
- `robots.txt`
- `llms.txt`
- `sitemap.xml`
- breadcrumbs
- canonical content entities

The core entity contract for LTS should remain explicit:

- `Organization`
- `WebSite`
- `WebPage`
- `Article` / `BlogPosting`
- `ContactPage`

What stays intentionally out of scope for the LTS line:

- product commerce schemas
- software application schemas
- local business variants
- autonomous agent SEO editing

Agent-ready and GEO / GIO therefore complement each other:

- AI foundation prepares future generation, analysis, and tool use
- GEO / GIO prepares stable machine-readable public content
- both must stay schema-driven, explicit, and validated

Reference:

- see `FLATCMS_GEO_GIO.md` for the LTS public entity and parity contract

## 15. Runtime Boundary For Future Agents

If advanced orchestration becomes necessary later:

```txt
FlatCMS PHP
    -> AIManager
    -> optional runtime adapter
    -> external Node/Python agent runtime
    -> OpenAI Agents SDK
```

The PHP core remains the product system of record.

The external runtime becomes optional orchestration infrastructure, not a
foundational requirement for the CMS.

## 16. Human Workflow

Mandatory workflow:

1. user request
2. AI generation
3. preview
4. human validation
5. insertion or execution

Never skip human validation for canonical content creation or publication in
FlatCMS v1.x.

## 17. Operational Recommendation

For the official FlatCMS launch:

- ship the provider foundation first
- ship limited business use cases second
- ship advanced orchestration later

Recommended early sequence:

1. foundation `app/Services/AI/`
2. OpenAI Responses provider
3. minimal configuration and health check
4. admin settings integration only after backend parity exists
5. test connection flow
6. limited content assistant workflows
7. optional tool registry exposure
8. builder JSON generation with validation if that product line returns later
9. optional advanced agent runtime later

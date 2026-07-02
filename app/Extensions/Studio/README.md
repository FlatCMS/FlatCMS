# FlatCMS Studio

Experimental FlatCMS extension located in `app/Extensions/Studio`.

## Installation

1. Ensure `Studio` exists in `app/Extensions/Studio`.
2. Enable the extension from the FlatCMS admin if needed.
3. Open `/admin/studio`.

## Admin URL

- `/admin/studio`

## Public Assets

Studio assets are exposed through:

- `public/assets/extensions/studio/`

Asset URLs are resolved through `module_asset('Studio', ...)`.

## Persistence

Studio stores one JSON document per existing FlatCMS page:

- `data/extensions/studio/pages/{page-id}.json`

## Current Status

- Experimental extension
- Canvas-first admin experience
- Existing FlatCMS pages can be selected as Studio sources
- Admin preview is rendered through the active frontend theme
- No AI integration in this phase

## Current Limits

- Page-oriented phase only
- Frontend publishing is still preview-oriented, not final public rendering
- JSON schema may still evolve
- Navigation and mega-menu authoring remain exploratory

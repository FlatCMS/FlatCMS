# StudioFlatCMS

Official FlatCMS module foundation for the future unified Studio builder.

Current scope:

- signed native module skeleton in `app/Modules/StudioFlatCMS/`
- admin entrypoint at `/admin/studio-flatcms`
- JSON persistence in `data/modules/StudioFlatCMS/pages/`
- visual shell with top bar, left rail, central canvas, and right inspector
- structured starter document with `header`, `main`, optional `aside`, and `footer`

Current limits:

- no production frontend takeover yet
- no native source adapters yet
- no real drag and drop yet
- preview action is intentionally deferred

This module is the clean restart path for FlatCMS Studio and must coexist with
the legacy experimental extension during the transition period.

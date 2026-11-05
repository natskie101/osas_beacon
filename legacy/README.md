# Legacy static portal

This folder preserves the original static BEACON OSAS pages, assets and QA
harness for reference.

The live application is served from `public/` and replaces these URLs with
server-rendered PHP pages. Old bookmarks such as:

- `/index.html`
- `/coordinator/dashboard.html`
- `/student/announcements.html`

are redirected by `App\Http\Controllers\LegacyRedirectController` to the
matching `/admin/*` or `/student/*` route after authentication.

Do not use this folder as the web document root. See `docs/DEPLOYMENT.md`.

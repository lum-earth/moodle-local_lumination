# Changelog

## v0.5.0 (2026-10-22)

- Works with the new Lumination AI Provider (Moodle 5.0+). When the provider is installed and enabled, the Course Generator uses its API key and base URL, and the settings page says so. Without it, nothing changes.
- The usage dashboard shows actions logged by other Lumination plugins with their own labels.
- A clear message when the API takes too long to answer, instead of a connection error.
- Course short names keep accented letters as plain ones ("Prevención" gives PREVENCION) instead of dropping them.
- The upload form accepts PDF, DOCX and TXT, the formats the API reads. Old .doc files are no longer offered.
- README rewritten with current pricing ($0.02 per lesson, $20 of free credits for new accounts) and how to get a key.

## v0.4.0 (2026-10-22)

- Fixes "HTTP 401: Invalid API key" on sites that first installed v0.3.0. That release saved the staging server as the API base URL, and later upgrades kept it. The upgrade now switches a saved staging URL to production (`https://ai-tutor.ai/api/v1`).
- Spaces, line breaks, invisible characters (non-breaking or zero-width spaces) and quotes pasted with the API key are now removed. They made the API reject the key.
- New "Test connection" button on the settings page. It checks the base URL and the key without spending credits, and shows the start, end and length of the key Moodle is using so you can compare it with your dashboard. It warns when the key is not an AI Tutor key (`sk-tutor-...`) or looks incomplete, for example when it was copied while shortened in the dashboard.
- Clearer errors. Each message says which server was called, what to check (key, base URL or credits) and the request ID to quote to support.
- Courses are now generated in the background (Moodle cron), with a progress page. Large courses no longer hit web server or proxy timeouts, and you can leave the page while lessons are written.
- If the API rejects the key or the credits run out partway through, generation stops calling the API, and the progress page says why. It also reports how many lessons were left with placeholder text.
- Temporary API errors (rate limits, gateway errors) are retried automatically. A failed status check while a lesson is generating no longer loses that lesson.
- Updated the cost guide in the API key help to about $0.02 per lesson.

## v0.3.5 (2026-07-15)

- Bundled Spanish (es) translation (`lang/es/local_lumination.php`) so Spanish sites get a translated interface immediately. (Translations normally go through AMOS/lang.moodle.org, but the plugins directory to Marketplace migration has that import pipeline paused, so the translation is shipped in-plugin for now.)

## v0.3.4 (2026-07-11)

- Language file cleanup for AMOS: removed PHP string concatenation so the English strings parse as a pure data file, and dropped two unused strings (`setting_apibaseurl`, `setting_apibaseurl_desc`). This lets translations be contributed at lang.moodle.org.

## v0.3.3 (2026-07-11)

- Added pricing and free-credit info to the API key setting help: about $0.007 per lesson (~$0.20 per course), $20 of free credits for new accounts (around 100 courses), with self-service signup at ai-tutor.ai.

## v0.3.2 (2026-07-10)

- Display name is now "Lumination AI Course Generator" (the component name `local_lumination` is unchanged).
- Code style: build the code-fence regex without literal backticks, to satisfy the Moodle coding style checker.

## v0.3.1 (2026-07-08)

- Default API base URL now points to production (`https://ai-tutor.ai/api/v1`). Override it per site via the API base URL setting.
- Raise the PHP execution time limit during course creation (large courses generate many lessons and could otherwise time out).
- Updated the API key setting help: register at ai-tutor.ai, create a key at the API dashboard (new accounts get $20 of free usage).

## v0.3.0 (2026-07-08)

- Migrated to the AI Tutor API v1. The base URL is now a configurable admin setting, defaulting to the staging server (`https://stage.ai-tutor.ai/api/v1`).
- The course outline is now produced by the synchronous `/course/guide` endpoint, which returns a structured, editable outline (replacing the agent-chat markdown parsing).
- The reviewed outline is saved back to the guide via `PUT /course/guide/{id}`, and the Moodle course is built directly from the edited outline so your edits are respected exactly.
- Lesson content is generated via the asynchronous `/tutor` endpoint (submit + poll).
- Removed the `material-to-text` and `process-material` steps (no longer part of the API); documents are sent inline as base64.
- Course generation now takes a single source document.
- Auth header changed to `x-api-key`; usage logging reads `input_tokens`/`output_tokens`.

## v0.1.0 (2026-02-16)

- Initial alpha release.
- AI Course Generator: upload documents, generate outline, review/edit, create Moodle course.
- Text extraction via Lumination API material-to-text endpoint.
- Outline generation via Lumination agent chat endpoint.
- Lesson content generation via Lumination agent chat endpoint.
- Lesson title deduplication (prompt instruction + heading stripping safety net).
- Course creation with sections (modules) and page activities (lessons).
- Interactive outline editor with add/remove modules and lessons.
- Loading overlay during course generation.
- Multi-language support (English, Spanish, French, German, Portuguese, Italian, Dutch).
- API usage tracking: token counts and credits logged per API call.
- Admin usage dashboard with summary cards, daily/action/user breakdown tables.
- Navigation integration: nav drawer, site admin, category settings.
- Privacy API implementation.
- CI pipeline: GitHub Actions with PHP 8.1/8.2/8.3, PostgreSQL, Moodle 4.5.

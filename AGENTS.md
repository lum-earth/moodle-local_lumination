# Lumination AI Course Generator for Moodle (local_lumination)

Rules for coding agents in this repository. Keep this file short and true; update a fact in the same change that makes it untrue.

## What this is

A Moodle local plugin that turns a PDF, Word or text document into a Moodle course: `POST /course/guide` builds an editable outline, then each lesson is written through `/tutor` in a background adhoc task, with a progress page. It calls the AI Tutor API by Lumination AI.

Part of the Lumination AI plugins for Moodle. On Moodle 5.0+ the hub is `lum-earth/moodle-aiprovider_lumination`: when it is installed and enabled, `api_client` uses its key and base URL; otherwise this plugin's own settings. This plugin also supports Moodle 4.4 and 4.5, where the hub cannot exist, so never require the hub.

- Code: `classes/` (`api_client`, `api_exception`, `course_generator`, `task/generate_course`, `usage_logger`, forms, privacy), pages at the root (`course_generator.php`, `review_outline.php`, `progress.php`, `usage.php`, `test_connection.php`), `db/`, `lang/en` and `lang/es`, `tests/`.
- The API is async: POST a job, poll `GET /requests/{id}`. A 401, 402 or 403 (`api_exception::is_fatal()`) stops further API calls.

## Checks

CI (`.github/workflows/ci.yml`, moodle-plugin-ci) must pass: lint, code checker and PHPDoc with zero warnings, validate, savepoints, PHPUnit. Local dev in the parent workspace: Moodle 4.5 on port 8080 (copy the plugin into `moodle/local/lumination`) and Moodle 5.2 on port 8081 (this folder is mounted). Upgrade with `admin/cli/upgrade.php --non-interactive`.

## Moodle coding rules that CI enforces

- `defined('MOODLE_INTERNAL') || die();` only in files with side effects (`version.php`, `db/*.php`, `lib.php`, `settings.php`, form files that `require_once` formslib). Never in class files, tests or pages that load `config.php`.
- Multi-line `if` conditions: first expression on the line after `(`, closing `)` on its own line.
- Multi-line calls: one argument per line, closing parenthesis on its own line.
- Test classes are `final`. `else if`, not `elseif`. Inline comments end with `.`, `!` or `?`. Lines up to 132 characters (lang files excepted).
- No empty `catch` blocks (use `unset($e);`); no `debugging()` in code that tests run. No backticks in strings (`chr(96)`).
- `\moodleform` and `curl` are not autoloaded: `require_once` formslib and `$CFG->libdir . '/filelib.php'` first.
- SQL must run on PostgreSQL and MySQL: no `FROM_UNIXTIME()`; group days with `FLOOR(timecreated / 86400)`.
- Lang files are plain `$string[...] = '...';` lines, sorted, no concatenation (AMOS). Keep `lang/es` in step with `lang/en`.
- New database fields need `db/install.xml`, an upgrade step with a savepoint, a version bump, and the privacy provider.

## Boundaries

- Never push, move or delete a Git tag, and never publish a release; the maintainer does that.
- Ask before calls to the real AI Tutor API (they spend credits) and before any other networked side effect.
- Never commit a key, a password or a personal path.
- Listing names and descriptions follow the Marketplace guide in the maintainer's workspace (`docs/marketplace-listing-guide.md`); keep "Course Generator" in the display name.

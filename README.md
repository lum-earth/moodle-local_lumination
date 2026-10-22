# Lumination AI Course Generator for Moodle

Generate a complete Moodle course from a PDF or Word document with AI. This plugin is also the hub of the Lumination AI plugins for Moodle: it holds the API key and the connection that add-ons use.

## Features

**AI Course Generator.** Upload a PDF, Word or text file and get a structured Moodle course with sections and lesson pages.

1. **Upload** a source document (PDF, DOC, DOCX or TXT).
2. **Review** the AI-generated outline and edit it (add or remove modules and lessons).
3. **Create** the course. Lessons are written in the background through Moodle's cron, and a progress page shows how far it has got.

Also included:
- A usage dashboard with tokens and credits per day, action and user.
- A Test connection button that checks the API key and base URL without spending credits.
- English and Spanish interface.

## API key and pricing

The plugin uses the AI Tutor API by Lumination AI, so it needs an AI Tutor API key (it starts with `sk-tutor-`).

1. Register at https://ai-tutor.ai and create a key at https://ai-tutor.ai/dashboard/api.
2. Paste it in **Site administration > Plugins > Local plugins > Lumination AI Course Generator**.
3. Click **Test connection**.

Each generated lesson costs about $0.02, roughly $0.30 for a 20-lesson course. New accounts get $20 of free credits. Prices and credit details are at https://ai-tutor.ai/api/documentation.

## Requirements

- Moodle 4.4 or later
- PHP 8.1 or later
- Moodle cron running (courses are generated in the background)
- An AI Tutor API key

## Installation

1. Download the ZIP from the Moodle Marketplace.
2. Go to **Site administration > Plugins > Install plugins** and upload it.
3. Add your API key in the plugin settings and click **Test connection**.

The Course Generator appears in the navigation drawer and under the plugin's settings category. Administrators can switch it off in the settings if they only use add-ons.

## Building an add-on

Other Lumination plugins (add-ons) reuse this plugin's API key and connection. An add-on declares the dependency in its `version.php`:

```php
$plugin->dependencies = ['local_lumination' => 2026102201]; // 0.5.0 or later.
```

These classes are the public API for add-ons. Their public methods keep working across 0.x releases; anything else may change.

- `\local_lumination\api_client`: `new api_client()` uses the site's key and base URL. `post()`, `get()`, `put()` for synchronous calls, `run($path, $data)` to submit an async job and wait for the result, `is_configured()`, `check_connection()`.
- `\local_lumination\api_exception`: thrown by `api_client` on any failure. `is_fatal()` is true when the key is rejected or out of credits (stop calling the API), `$httpcode` and `$requestid` identify the call. `getMessage()` is ready to show to an administrator.
- `\local_lumination\usage_logger::log($action, $response, $courseid)`: records tokens and credits from a job envelope. Add-ons pass `$action` as `<component>:<action>` (for example `block_luminationtutor:answer`) and define the label `action_<action>` in their own lang file; the usage dashboard picks it up.
- `\local_lumination\suite::get_addons()`: installed add-ons, listed on the settings page.

## License

GNU GPL v3 or later, https://www.gnu.org/copyleft/gpl.html

## Support

Report bugs at https://github.com/Borjablm/moodle-local_lumination/issues or write to hello@ai-tutor.ai.

# Lumination AI Course Generator for Moodle

Generate a complete Moodle course from a PDF or Word document with AI. Part of the Lumination AI plugins for Moodle.

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

The Course Generator appears in the navigation drawer and under the plugin's settings category.

## With the Lumination AI Provider (Moodle 5.0+)

The Lumination AI Provider (`aiprovider_lumination`) connects Moodle's own AI features to the same AI Tutor account and holds the API key for all Lumination AI plugins. When it is installed with an enabled instance, the Course Generator uses its key and base URL, and its own key fields are ignored. Installing the provider on a site where the Course Generator already has a key creates the provider instance from that key.

Source: https://github.com/lum-earth/moodle-aiprovider_lumination

## License

GNU GPL v3 or later, https://www.gnu.org/copyleft/gpl.html

## Support

Report bugs at https://github.com/Borjablm/moodle-local_lumination/issues or write to hello@ai-tutor.ai.

<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Course generator for the Lumination plugin.
 *
 * Orchestrates the two-step course workflow: extract an editable guide (outline)
 * from an uploaded document, then build a Moodle course from the reviewed outline.
 *
 * @package    local_lumination
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lumination;

defined('MOODLE_INTERNAL') || die();

require_once($GLOBALS['CFG']->dirroot . '/course/lib.php');
require_once($GLOBALS['CFG']->dirroot . '/course/modlib.php');
require_once($GLOBALS['CFG']->dirroot . '/lib/resourcelib.php');

/**
 * Orchestrates the two-step course generation workflow.
 *
 * 1. extract_guide(): upload a document to POST /course/guide and return an
 *    editable outline (modules + lessons).
 * 2. The user reviews/edits the outline.
 * 3. create_moodle_course_from_outline(): build a Moodle course from the edited
 *    outline, generating each lesson's content via the AI Tutor /tutor endpoint,
 *    and map modules to sections and lessons to page activities.
 *
 * @package    local_lumination
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_generator {
    /** @var api_client The Lumination API client instance. */
    private api_client $api;

    /**
     * Constructor.
     *
     * @param api_client|null $api Optional API client instance for dependency injection.
     */
    public function __construct(?api_client $api = null) {
        $this->api = $api ?? new api_client();
    }

    /**
     * Extract an editable course guide (outline) from an uploaded document.
     *
     * Calls the synchronous POST /course/guide endpoint, which returns a
     * structured, editable outline immediately (no polling).
     *
     * @param \stored_file $file The uploaded source document (PDF/DOCX/TXT).
     * @param string $title Optional course title hint.
     * @param string $instructions Optional extra constraints (audience, tone, scope).
     * @param string $language Language code (e.g. 'en').
     * @return array ['guide_uuid' => string, 'title' => string, 'outline' => array, 'objectives' => array]
     *               where 'outline' is ['title' => string, 'modules' => [['title', 'description', 'lessons' => [['title']]]]].
     * @throws \moodle_exception If the API does not return a guide.
     */
    public function extract_guide(
        \stored_file $file,
        string $title = '',
        string $instructions = '',
        string $language = 'en'
    ): array {
        $data = [
            'file_b64' => base64_encode($file->get_content()),
            'file_name' => $file->get_filename(),
        ];
        if (!empty($title)) {
            $data['title'] = $title;
        }
        if (!empty($instructions)) {
            $data['instructions'] = $instructions;
        }
        if (!empty($language)) {
            $data['language_code'] = $language;
        }

        // POST /course/guide is synchronous -- it returns the guide directly.
        $result = $this->api->post('/course/guide', $data);
        usage_logger::log('generate_outline', $result);

        if (empty($result['guide_uuid']) || empty($result['guide'])) {
            throw new \moodle_exception('errornocontent', 'local_lumination');
        }

        return [
            'guide_uuid' => $result['guide_uuid'],
            'title' => $result['title'] ?? ($result['guide']['course_title'] ?? $title),
            'outline' => $this->guide_to_outline($result['guide'], $title),
            'objectives' => $result['guide']['learning_objectives'] ?? [],
        ];
    }

    /**
     * Convert an AI Tutor guide structure into the plugin's outline shape.
     *
     * @param array $guide The 'guide' object from POST /course/guide.
     * @param string $fallbacktitle Title to use if the guide has none.
     * @return array ['title' => string, 'modules' => [['title', 'description', 'lessons' => [['title']]]]]
     */
    private function guide_to_outline(array $guide, string $fallbacktitle): array {
        $modules = [];
        foreach (($guide['course_structure'] ?? []) as $module) {
            $lessons = [];
            foreach (($module['lesson_names'] ?? []) as $lessonname) {
                $lessons[] = ['title' => $lessonname];
            }
            $modules[] = [
                'title' => $module['module_name'] ?? '',
                'description' => $module['module_description'] ?? '',
                'lessons' => $lessons,
            ];
        }

        return [
            'title' => $guide['course_title'] ?? $fallbacktitle,
            'modules' => $modules,
        ];
    }

    /**
     * Persist the user's edited outline back to the course guide (best-effort).
     *
     * Saves the reviewed structure via PUT /course/guide/{guide_uuid}. Failures
     * never block Moodle course creation, which is built from the edited outline
     * directly.
     *
     * @param string $guideuuid The guide UUID from extract_guide().
     * @param array $modules The edited modules from the review form.
     * @param string $title The (possibly edited) course title.
     * @return void
     */
    public function save_guide(string $guideuuid, array $modules, string $title): void {
        if (empty($guideuuid)) {
            return;
        }

        // Convert the edited outline back into the guide's course_structure shape.
        $structure = [];
        foreach ($modules as $module) {
            $lessonnames = [];
            foreach (($module['lessons'] ?? []) as $lesson) {
                $lessonnames[] = $lesson['title'] ?? '';
            }
            $structure[] = [
                'module_name' => $module['title'] ?? '',
                'lesson_names' => $lessonnames,
            ];
        }

        try {
            // Preserve the guide's other fields; overwrite only title and structure.
            $current = $this->api->get('/course/guide/' . rawurlencode($guideuuid));
            $guide = $current['guide'] ?? [];
            $guide['course_title'] = $title;
            $guide['course_structure'] = $structure;
            $this->api->put('/course/guide/' . rawurlencode($guideuuid), [
                'guide' => $guide,
                'title' => $title,
            ]);
        } catch (\Exception $e) {
            debugging('Lumination save_guide failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
        }
    }

    /**
     * Build a Moodle course from a reviewed outline.
     *
     * Each module becomes a course section and each lesson becomes a mod_page
     * activity whose HTML content is generated via the AI Tutor /tutor endpoint.
     *
     * If a lesson fails, it gets placeholder content and the rest continue. If the API
     * rejects the key or runs out of credits, no further lessons are requested: the
     * remaining ones get placeholders and the reason is returned in lumination_error.
     *
     * @param array $modules Edited modules, each ['title', 'description', 'lessons' => [['title']]].
     * @param string $title The full name for the Moodle course.
     * @param int $categoryid Moodle course category ID.
     * @param array $objectives Course learning objectives used to ground lesson content.
     * @param string $language Language code for generated content (e.g. 'en').
     * @param callable|null $progress Called as $progress($course, $done, $failed) once the
     *                                course exists and after each lesson.
     * @return \stdClass The created Moodle course with lumination_sections, lumination_activities,
     *                   lumination_failed (lessons left as placeholders) and lumination_error.
     */
    public function create_moodle_course_from_outline(
        array $modules,
        string $title,
        int $categoryid,
        array $objectives = [],
        string $language = 'en',
        ?callable $progress = null
    ): \stdClass {
        global $DB;

        // Generating each lesson is a separate API round-trip, so a large course
        // can take many minutes. This runs from cron, so lift the time limit.
        \core_php_time_limit::raise();

        // Ensure valid category -- fall back to first available.
        if (empty($categoryid) || !$DB->record_exists('course_categories', ['id' => $categoryid])) {
            $firstcat = $DB->get_record('course_categories', [], 'id', IGNORE_MULTIPLE);
            $categoryid = $firstcat ? $firstcat->id : 1;
        }

        $shortname = $this->generate_unique_shortname($title);

        $courseobj = new \stdClass();
        $courseobj->fullname = $title;
        $courseobj->shortname = $shortname;
        $courseobj->category = $categoryid;
        $courseobj->format = 'topics';
        $courseobj->numsections = count($modules);
        $courseobj->visible = 1;
        $courseobj->enablecompletion = 1;

        $course = create_course($courseobj);
        $activitycount = 0;
        $failedcount = 0;
        $fatalerror = '';
        if ($progress) {
            $progress($course, 0, 0);
        }

        foreach ($modules as $i => $module) {
            $sectionnumber = $i + 1;
            $moduletitle = $module['title'] ?? 'Module ' . ($i + 1);

            $section = $DB->get_record(
                'course_sections',
                [
                    'course' => $course->id,
                    'section' => $sectionnumber,
                ]
            );
            if ($section) {
                course_update_section(
                    $course,
                    $section,
                    [
                        'name' => $moduletitle,
                        'summary' => $module['description'] ?? '',
                    ]
                );
            }

            $lessons = $module['lessons'] ?? [];
            foreach ($lessons as $lesson) {
                $lessontitle = $lesson['title'] ?? 'Lesson';

                $lessoncontent = null;
                if ($fatalerror === '') {
                    try {
                        $lessoncontent = $this->generate_lesson_content(
                            $lessontitle,
                            $moduletitle,
                            $title,
                            $objectives,
                            $language,
                            (int) $course->id
                        );
                    } catch (api_exception $e) {
                        if ($e->is_fatal()) {
                            $fatalerror = $e->getMessage();
                        }
                    } catch (\Exception $e) {
                        // Any other failure affects this lesson only; it gets a placeholder below.
                        unset($e);
                    }
                }
                if ($lessoncontent === null) {
                    $failedcount++;
                    $lessoncontent = '<p><em>Content for "' . htmlspecialchars($lessontitle)
                        . '" will be added. Edit this page to complete.</em></p>';
                }

                $this->add_page_activity($course, $sectionnumber, $lessontitle, $lessoncontent);
                $activitycount++;
                if ($progress) {
                    $progress($course, $activitycount, $failedcount);
                }
            }
        }

        rebuild_course_cache($course->id, true);

        $course->lumination_sections = count($modules);
        $course->lumination_activities = $activitycount;
        $course->lumination_failed = $failedcount;
        $course->lumination_error = $fatalerror;

        return $course;
    }

    /**
     * Count the lessons in an outline.
     *
     * @param array $modules Modules, each with a 'lessons' array.
     * @return int Total number of lessons.
     */
    public static function count_lessons(array $modules): int {
        $total = 0;
        foreach ($modules as $module) {
            $total += count($module['lessons'] ?? []);
        }
        return $total;
    }

    /**
     * Generate HTML content for a single lesson via the AI Tutor /tutor endpoint.
     *
     * The lesson and module titles come from the document-derived guide, so the
     * generated content stays on-topic. Course learning objectives are included
     * for extra grounding.
     *
     * @param string $lessontitle The lesson title.
     * @param string $moduletitle The parent module title (for context).
     * @param string $coursetitle The course title (for context).
     * @param array $objectives Course learning objectives (for grounding).
     * @param string $language Language code for the generated content (e.g. 'en').
     * @param int|null $courseid Moodle course the lesson belongs to (for usage logging).
     * @return string|null Generated HTML content, or null if the API returned no content.
     * @throws \moodle_exception If the API call fails.
     */
    private function generate_lesson_content(
        string $lessontitle,
        string $moduletitle,
        string $coursetitle,
        array $objectives,
        string $language = 'en',
        ?int $courseid = null
    ): ?string {
        $objtext = '';
        if (!empty($objectives)) {
            $objtext = "Course learning objectives:\n- " . implode("\n- ", array_slice($objectives, 0, 8)) . "\n\n";
        }

        $prompt = "You are a course content writer. Write educational content for one lesson of an online course.\n\n"
            . "Course: {$coursetitle}\n"
            . "Module: {$moduletitle}\n"
            . "Lesson: {$lessontitle}\n"
            . "Language: {$language}\n\n"
            . $objtext
            . "Write the lesson content in HTML. Do NOT include the lesson title as a heading -- "
            . "it is already displayed separately by the platform.\n"
            . "Start directly with the content. Include:\n"
            . "- Clear explanations of the lesson topic\n"
            . "- Key concepts highlighted with <strong> tags\n"
            . "- Organised with <h3> subheadings where appropriate\n"
            . "- 300-600 words\n"
            . "Return only the HTML content, with no code fences.";

        $job = $this->api->run('/tutor', ['message' => $prompt]);
        usage_logger::log('generate_lesson', $job, $courseid);

        $content = $job['result']['reply'] ?? '';
        if (empty($content) || !is_string($content)) {
            return null;
        }

        // Strip a leading markdown/HTML heading that duplicates the lesson title.
        $content = preg_replace('/^\s*#{1,4}\s+.*?\n+/', '', $content, 1);
        $content = preg_replace('/^\s*<h[1-4][^>]*>.*?<\/h[1-4]>\s*/i', '', $content, 1);
        // Strip surrounding triple-backtick code fences if the model added them.
        $fence = str_repeat(chr(96), 3);
        $content = preg_replace('/^\s*' . $fence . '(?:html)?\s*/i', '', $content);
        $content = preg_replace('/\s*' . $fence . '\s*$/', '', $content);
        return trim($content);
    }

    /**
     * Add a Page activity (mod_page) to a course section.
     *
     * @param \stdClass $course The Moodle course object to add the activity to.
     * @param int $sectionnumber The section number within the course (1-based).
     * @param string $name The display name for the page activity.
     * @param string $content The HTML content for the page body.
     * @return void
     */
    private function add_page_activity(
        \stdClass $course,
        int $sectionnumber,
        string $name,
        string $content
    ): void {
        global $DB;

        $moduleid = $DB->get_field('modules', 'id', ['name' => 'page']);

        $moduleinfo = new \stdClass();
        $moduleinfo->modulename = 'page';
        $moduleinfo->module = $moduleid;
        $moduleinfo->course = $course->id;
        $moduleinfo->section = $sectionnumber;
        $moduleinfo->name = $name;
        $moduleinfo->intro = '';
        $moduleinfo->introformat = \FORMAT_HTML;
        $moduleinfo->content = $content;
        $moduleinfo->contentformat = \FORMAT_HTML;
        $moduleinfo->display = \RESOURCELIB_DISPLAY_OPEN;
        $moduleinfo->printintro = 0;
        $moduleinfo->printheading = 1;
        $moduleinfo->printlastmodified = 0;
        $moduleinfo->visible = 1;
        $moduleinfo->visibleoncoursepage = 1;
        $moduleinfo->timemodified = time();

        // Required for add_moduleinfo but we handle defaults.
        $moduleinfo->cmidnumber = '';
        $moduleinfo->groupmode = 0;
        $moduleinfo->groupingid = 0;
        $moduleinfo->availability = null;
        $moduleinfo->completion = 0;

        add_moduleinfo($moduleinfo, $course);
    }

    /**
     * Generate a unique short name for a Moodle course.
     *
     * @param string $title The course title to derive the shortname from.
     * @return string A unique shortname that does not conflict with existing courses.
     */
    private function generate_unique_shortname(string $title): string {
        global $DB;

        // Create base shortname from title; accented letters become plain ones ("Prevención" gives PREVENCION).
        $ascii = \core_text::specialtoascii($title);
        $base = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $ascii), 0, 15));
        if (empty($base)) {
            $base = 'LUM';
        }

        $shortname = $base;
        $counter = 1;
        while ($DB->record_exists('course', ['shortname' => $shortname])) {
            $shortname = $base . '-' . $counter;
            $counter++;
        }

        return $shortname;
    }
}

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
 * Course Generator - Step 2: Review the outline and create the Moodle course.
 *
 * Persists the reviewed outline back to the course guide, then builds the Moodle
 * course from the edited outline, generating each lesson's content via the API.
 *
 * URL: /local/lumination/review_outline.php
 *
 * @package    local_lumination
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

require_login();
$context = context_system::instance();
require_capability('local/lumination:generatecourse', $context);
if (!\local_lumination\suite::course_generator_enabled()) {
    throw new moodle_exception('coursegeneratordisabled', 'local_lumination');
}

$PAGE->set_url(new moodle_url('/local/lumination/review_outline.php'));
$PAGE->set_context($context);
$PAGE->set_title(get_string('outlinereview', 'local_lumination'));
$PAGE->set_heading(get_string('outlinereview', 'local_lumination'));
$PAGE->set_pagelayout('standard');

// Retrieve data from session cache.
$cache = cache::make('local_lumination', 'outline');
$cached = $cache->get('data');

$outline = $cached['outline'] ?? null;
$guideuuid = $cached['guide_uuid'] ?? '';
$objectives = $cached['objectives'] ?? [];
$title = $cached['title'] ?? '';
$categoryid = $cached['categoryid'] ?? 1;
$language = $cached['language'] ?? 'en';

if (empty($outline)) {
    redirect(new moodle_url('/local/lumination/course_generator.php'));
}

$form = new \local_lumination\form\review_outline_form(
    null,
    [
        'outline' => $outline,
        'guide_uuid' => $guideuuid,
        'title' => $title,
        'categoryid' => $categoryid,
        'document_uuids' => [],
        'language' => $language,
    ]
);

if ($form->is_cancelled()) {
    $cache->delete('data');
    redirect(new moodle_url('/local/lumination/course_generator.php'));
}

if ($data = $form->get_data()) {
    $api = new \local_lumination\api_client();
    $generator = new \local_lumination\course_generator($api);

    $editedmodules = $form->get_edited_outline();
    $coursetitle = $data->title;
    $categoryid = $data->categoryid;

    // Persist the reviewed outline back to the course guide (best-effort).
    $generator->save_guide($guideuuid, $editedmodules, $coursetitle);

    // Lessons are generated in the background; progress.php follows the job.
    $jobid = \local_lumination\task\generate_course::queue(
        $editedmodules,
        $coursetitle,
        (int) $categoryid,
        $objectives,
        $language
    );

    $cache->delete('data');
    redirect(new moodle_url('/local/lumination/progress.php', ['id' => $jobid]));
}

// Prepare JS strings for the AMD outline editor.
$jsparams = [
    'strings' => [
        'module' => get_string('modulename', 'local_lumination'),
        'lesson' => get_string('lessonname', 'local_lumination'),
        'addmodule' => get_string('addmodule', 'local_lumination'),
        'removemodule' => get_string('removemodule', 'local_lumination'),
        'addlesson' => get_string('addlesson', 'local_lumination'),
        'removelesson' => get_string('removelesson', 'local_lumination'),
        'generatingcourse' => get_string('generatingcourse', 'local_lumination'),
        'generatingcourse_desc' => get_string('generatingcourse_desc', 'local_lumination'),
    ],
];

$PAGE->requires->js_call_amd('local_lumination/outline_editor', 'init', [$jsparams]);

// Display the review form.
echo $OUTPUT->header();
echo html_writer::tag('p', get_string('outlinereview_desc', 'local_lumination'));
$form->display();
echo $OUTPUT->footer();

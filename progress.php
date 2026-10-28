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
 * Progress page for a background course generation job.
 *
 * @package    local_lumination
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);

require_login();
$context = context_system::instance();
require_capability('local/lumination:generatecourse', $context);

$job = $DB->get_record('local_lumination_jobs', ['id' => $id], '*', MUST_EXIST);
if ($job->userid != $USER->id && !has_capability('moodle/site:config', $context)) {
    throw new moodle_exception('nopermissions', 'error', '', get_string('coursegenerator', 'local_lumination'));
}

$PAGE->set_url(new moodle_url('/local/lumination/progress.php', ['id' => $id]));
$PAGE->set_context($context);
$PAGE->set_title(get_string('jobprogress', 'local_lumination'));
$PAGE->set_heading(get_string('jobprogress', 'local_lumination'));
$PAGE->set_pagelayout('standard');

$finished = in_array($job->status, ['completed', 'failed'], true);
if (!$finished) {
    $PAGE->set_periodic_refresh_delay(5);
}

$a = (object) [
    'name' => format_string($job->coursename),
    'done' => $job->lessonsdone,
    'total' => $job->lessonstotal,
    'failed' => $job->lessonsfailed,
];

echo $OUTPUT->header();

if ($job->status === 'queued') {
    echo $OUTPUT->notification(get_string('jobqueued', 'local_lumination', $a), 'info');
    // Adhoc tasks only run when Moodle's cron runs.
    if (time() - $job->timecreated > 2 * MINSECS) {
        echo $OUTPUT->notification(get_string('jobwaitingcron', 'local_lumination'), 'warning');
    }
} else if ($job->status === 'running') {
    echo $OUTPUT->notification(get_string('jobrunning', 'local_lumination', $a), 'info');
    $percent = $job->lessonstotal ? round(100 * $job->lessonsdone / $job->lessonstotal) : 0;
    echo html_writer::div(
        html_writer::div('', 'progress-bar', [
            'role' => 'progressbar',
            'style' => "width: {$percent}%",
            'aria-valuenow' => $percent,
            'aria-valuemin' => 0,
            'aria-valuemax' => 100,
        ]),
        'progress mb-3'
    );
} else if ($job->status === 'completed') {
    $type = $job->lessonsfailed ? 'warning' : 'success';
    $string = $job->lessonsfailed ? 'jobcompletedwithfailures' : 'jobcompleted';
    echo $OUTPUT->notification(get_string($string, 'local_lumination', $a), $type);
} else {
    echo $OUTPUT->notification(get_string('jobfailed', 'local_lumination', $a), 'error');
}

// The reason generation stopped early, or why the whole job failed.
if (!empty($job->message)) {
    echo $OUTPUT->notification(s($job->message), 'error');
}

if ($finished && $job->courseid && $DB->record_exists('course', ['id' => $job->courseid])) {
    echo html_writer::link(
        new moodle_url('/course/view.php', ['id' => $job->courseid]),
        get_string('viewcourse', 'local_lumination'),
        ['class' => 'btn btn-primary mt-3']
    );
} else if (!$finished) {
    echo html_writer::tag('p', get_string('jobcanleave', 'local_lumination'));
}

echo $OUTPUT->footer();

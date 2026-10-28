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
 * Adhoc task that builds a Moodle course from a reviewed outline.
 *
 * @package    local_lumination
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lumination\task;

use local_lumination\course_generator;

/**
 * Adhoc task that builds a Moodle course from a reviewed outline.
 *
 * Generating every lesson takes several minutes, longer than many web servers
 * and proxies allow for one request, so the work runs from cron. Progress is
 * written to local_lumination_jobs and shown on progress.php.
 *
 * Custom data: jobid, modules, title, categoryid, objectives, language.
 *
 * @package    local_lumination
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class generate_course extends \core\task\adhoc_task {
    /**
     * Queue a course generation job for the current user.
     *
     * @param array $modules Edited modules, each ['title', 'description', 'lessons' => [['title']]].
     * @param string $title The full name for the Moodle course.
     * @param int $categoryid Moodle course category ID.
     * @param array $objectives Course learning objectives.
     * @param string $language Language code for generated content.
     * @return int The job ID, for progress.php.
     */
    public static function queue(
        array $modules,
        string $title,
        int $categoryid,
        array $objectives,
        string $language
    ): int {
        global $DB, $USER;

        $now = time();
        $jobid = $DB->insert_record('local_lumination_jobs', (object) [
            'userid' => $USER->id,
            'coursename' => \core_text::substr($title, 0, 255),
            'status' => 'queued',
            'lessonstotal' => course_generator::count_lessons($modules),
            'lessonsdone' => 0,
            'lessonsfailed' => 0,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        $task = new self();
        $task->set_userid($USER->id);
        $task->set_custom_data([
            'jobid' => $jobid,
            'modules' => $modules,
            'title' => $title,
            'categoryid' => $categoryid,
            'objectives' => $objectives,
            'language' => $language,
        ]);
        \core\task\manager::queue_adhoc_task($task);

        return $jobid;
    }

    /**
     * Do not retry a failed job: a retry would create a second course and spend credits again.
     *
     * @return bool False.
     */
    public function retry_until_success(): bool {
        return false;
    }

    /**
     * Build the course, recording progress on the job record.
     *
     * @return void
     */
    public function execute() {
        global $DB;

        $data = $this->get_custom_data();
        $job = $DB->get_record('local_lumination_jobs', ['id' => $data->jobid]);
        if (!$job || $job->status !== 'queued') {
            return;
        }

        // Custom data comes back as stdClass; the generator expects arrays.
        $modules = json_decode(json_encode($data->modules), true);
        $objectives = json_decode(json_encode($data->objectives), true);

        $api = new \local_lumination\api_client();
        if (!$api->is_configured()) {
            $this->update_job($job, [
                'status' => 'failed',
                'message' => get_string('errornoapi', 'local_lumination'),
            ]);
            return;
        }

        $this->update_job($job, ['status' => 'running']);

        try {
            $generator = new course_generator($api);
            $course = $generator->create_moodle_course_from_outline(
                $modules,
                $data->title,
                (int) $data->categoryid,
                $objectives,
                $data->language,
                function ($course, $done, $failed) use ($job) {
                    $this->update_job($job, [
                        'courseid' => $course->id,
                        'lessonsdone' => $done,
                        'lessonsfailed' => $failed,
                    ]);
                }
            );
            $this->update_job($job, [
                'status' => 'completed',
                'courseid' => $course->id,
                'lessonsdone' => $course->lumination_activities,
                'lessonsfailed' => $course->lumination_failed,
                'message' => $course->lumination_error,
            ]);
        } catch (\Throwable $e) {
            $this->update_job($job, [
                'status' => 'failed',
                'message' => $e->getMessage(),
            ]);
            mtrace('Lumination course generation failed: ' . $e->getMessage());
        }
    }

    /**
     * Write changed fields to the job record.
     *
     * @param \stdClass $job The job record (updated in place).
     * @param array $fields Fields to change.
     * @return void
     */
    private function update_job(\stdClass $job, array $fields): void {
        global $DB;

        foreach ($fields as $name => $value) {
            $job->$name = $value;
        }
        $job->timemodified = time();
        $DB->update_record('local_lumination_jobs', $job);
    }
}

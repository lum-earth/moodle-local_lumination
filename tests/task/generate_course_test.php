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
 * Unit tests for the generate_course adhoc task.
 *
 * @package    local_lumination
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @category   test
 */

namespace local_lumination\task;

/**
 * Tests for {@see \local_lumination\task\generate_course}.
 *
 * @covers \local_lumination\task\generate_course
 */
final class generate_course_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Build a small outline.
     *
     * @return array Modules with lessons.
     */
    private function outline(): array {
        return [
            [
                'title' => 'Module',
                'description' => '',
                'lessons' => [['title' => 'One'], ['title' => 'Two']],
            ],
        ];
    }

    /**
     * Test that queue() records a job and queues one adhoc task for the user.
     */
    public function test_queue_creates_job_and_task(): void {
        global $DB, $USER;

        $this->setAdminUser();
        $category = $this->getDataGenerator()->create_category();

        $jobid = generate_course::queue($this->outline(), 'Queued Course', $category->id, [], 'en');

        $job = $DB->get_record('local_lumination_jobs', ['id' => $jobid], '*', MUST_EXIST);
        $this->assertSame('queued', $job->status);
        $this->assertEquals(2, $job->lessonstotal);
        $this->assertEquals($USER->id, $job->userid);
        $this->assertSame('Queued Course', $job->coursename);

        $tasks = \core\task\manager::get_adhoc_tasks(generate_course::class);
        $this->assertCount(1, $tasks);
        $task = reset($tasks);
        $this->assertEquals($USER->id, $task->get_userid());
        $this->assertEquals($jobid, $task->get_custom_data()->jobid);
    }

    /**
     * Test that the job fails cleanly, without calling the API, when no key is set.
     */
    public function test_execute_without_api_key_marks_job_failed(): void {
        global $DB;

        $this->setAdminUser();
        unset_config('apikey', 'local_lumination');
        $category = $this->getDataGenerator()->create_category();
        $jobid = generate_course::queue($this->outline(), 'No Key Course', $category->id, [], 'en');

        $tasks = \core\task\manager::get_adhoc_tasks(generate_course::class);
        $task = reset($tasks);
        $task->execute();

        $job = $DB->get_record('local_lumination_jobs', ['id' => $jobid], '*', MUST_EXIST);
        $this->assertSame('failed', $job->status);
        $this->assertSame(get_string('errornoapi', 'local_lumination'), $job->message);
        $this->assertFalse($DB->record_exists('course', ['fullname' => 'No Key Course']));
    }
}

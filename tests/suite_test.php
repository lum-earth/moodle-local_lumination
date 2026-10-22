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
 * Unit tests for the suite class.
 *
 * @package    local_lumination
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @category   test
 */

namespace local_lumination;

/**
 * Tests for {@see \local_lumination\suite}.
 *
 * @covers \local_lumination\suite
 */
final class suite_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Test that the Course Generator is on by default and can be switched off.
     */
    public function test_course_generator_enabled(): void {
        unset_config('enablecoursegenerator', 'local_lumination');
        $this->assertTrue(suite::course_generator_enabled());

        set_config('enablecoursegenerator', 0, 'local_lumination');
        $this->assertFalse(suite::course_generator_enabled());

        set_config('enablecoursegenerator', 1, 'local_lumination');
        $this->assertTrue(suite::course_generator_enabled());
    }

    /**
     * Test that no add-ons are reported when none depend on this plugin.
     */
    public function test_get_addons_empty(): void {
        $this->assertSame([], suite::get_addons());
    }
}

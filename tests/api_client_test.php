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
 * Unit tests for the api_client class.
 *
 * @package    local_lumination
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @category   test
 */

namespace local_lumination;

/**
 * Tests for {@see \local_lumination\api_client}.
 *
 * @covers \local_lumination\api_client
 */
final class api_client_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Test that is_configured() returns false when no API key is set.
     */
    public function test_is_configured_returns_false_when_empty(): void {
        unset_config('apikey', 'local_lumination');

        $client = new api_client();
        $this->assertFalse($client->is_configured());
    }

    /**
     * Test that is_configured() returns true when the API key is present.
     */
    public function test_is_configured_returns_true_when_set(): void {
        set_config('apikey', 'test-key-abc123', 'local_lumination');

        $client = new api_client();
        $this->assertTrue($client->is_configured());
    }

    /**
     * Test that whitespace pasted around the key is ignored.
     */
    public function test_key_is_trimmed(): void {
        set_config('apikey', "  \n", 'local_lumination');
        $this->assertFalse((new api_client())->is_configured());

        set_config('apikey', " sk-tutor-abc123\n", 'local_lumination');
        $client = new api_client();
        $this->assertTrue($client->is_configured());
        $this->assertTrue($client->has_valid_key_format());
    }

    /**
     * Test that keys without the AI Tutor prefix are flagged.
     */
    public function test_has_valid_key_format_rejects_old_keys(): void {
        $client = new api_client('lum_live_abc123');
        $this->assertFalse($client->has_valid_key_format());
    }

    /**
     * Test the base URL default, trimming and host extraction.
     */
    public function test_base_url(): void {
        unset_config('baseurl', 'local_lumination');
        $client = new api_client('sk-tutor-x');
        $this->assertSame(api_client::DEFAULT_BASE_URL, $client->base_url());
        $this->assertSame('ai-tutor.ai', $client->host());

        set_config('baseurl', ' https://example.com/api/v1/ ', 'local_lumination');
        $this->assertSame('https://example.com/api/v1', $client->base_url());
        $this->assertSame('example.com', $client->host());
    }

    /**
     * Test that HTTP codes map to the right message and fatal flag.
     *
     * @covers \local_lumination\api_exception
     */
    public function test_api_exception_messages(): void {
        $e = new api_exception(401, 'Invalid API key', 'ai-tutor.ai', 'moodle-abc');
        $this->assertSame('errorapikey', $e->errorcode);
        $this->assertTrue($e->is_fatal());
        $this->assertStringContainsString('ai-tutor.ai', $e->getMessage());
        $this->assertStringContainsString('moodle-abc', $e->getMessage());

        $e = new api_exception(402, 'insufficient_credits', 'ai-tutor.ai', 'moodle-abc');
        $this->assertSame('errorapicredits', $e->errorcode);
        $this->assertTrue($e->is_fatal());

        $e = new api_exception(500, 'boom', 'ai-tutor.ai', 'moodle-abc');
        $this->assertSame('errorapihttp', $e->errorcode);
        $this->assertFalse($e->is_fatal());

        $e = new api_exception(0, 'Job failed', 'ai-tutor.ai', 'moodle-abc');
        $this->assertSame('errorapijob', $e->errorcode);

        $e = new api_exception(0, 'Could not resolve host', 'ai-tutor.ai', 'moodle-abc', true);
        $this->assertSame('errorapiconnect', $e->errorcode);
        $this->assertFalse($e->is_fatal());
    }
}

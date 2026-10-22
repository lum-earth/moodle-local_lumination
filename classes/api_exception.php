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
 * Exception thrown when an AI Tutor API call fails.
 *
 * @package    local_lumination
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lumination;

/**
 * Exception thrown when an AI Tutor API call fails.
 *
 * Carries the HTTP status, the API host and the X-REQUEST-ID sent with the call,
 * so the message shown to the admin says where the request went and support can
 * find it in the server logs.
 *
 * @package    local_lumination
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api_exception extends \moodle_exception {
    /** @var int HTTP status code, or 0 for a connection error or a failed job. */
    public int $httpcode;

    /** @var string The X-REQUEST-ID header sent with the failing call. */
    public string $requestid;

    /**
     * Constructor.
     *
     * @param int $httpcode HTTP status code (0 for a connection error or a failed job).
     * @param string $detail Error detail from the API response or curl.
     * @param string $host Host the request was sent to.
     * @param string $requestid The X-REQUEST-ID header sent with the call.
     * @param bool $connectionerror True if the API could not be reached at all.
     */
    public function __construct(
        int $httpcode,
        string $detail,
        string $host,
        string $requestid,
        bool $connectionerror = false
    ) {
        $this->httpcode = $httpcode;
        $this->requestid = $requestid;

        $a = (object) [
            'code' => $httpcode,
            'detail' => $detail,
            'host' => $host,
            'requestid' => $requestid,
        ];
        if ($connectionerror) {
            $errorcode = 'errorapiconnect';
        } else if ($httpcode === 401 || $httpcode === 403) {
            $errorcode = 'errorapikey';
        } else if ($httpcode === 402) {
            $errorcode = 'errorapicredits';
        } else if ($httpcode === 0) {
            $errorcode = 'errorapijob';
        } else {
            $errorcode = 'errorapihttp';
        }
        parent::__construct($errorcode, 'local_lumination', '', $a);
    }

    /**
     * Whether retrying other calls with the same key is pointless (key rejected or no credits).
     *
     * @return bool True for HTTP 401, 402 and 403.
     */
    public function is_fatal(): bool {
        return in_array($this->httpcode, [401, 402, 403], true);
    }
}

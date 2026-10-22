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
 * Central HTTP client for all Lumination API calls.
 *
 * @package    local_lumination
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lumination;

/**
 * Central HTTP client for all Lumination API calls.
 *
 * Reads base_url and api_key from plugin settings. Uses Moodle's curl class
 * to make authenticated requests to the Lumination API.
 *
 * Public API for Lumination add-ons (see README.md, "Building an add-on"): keep
 * public method signatures backwards compatible.
 *
 * @package    local_lumination
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class api_client {
    /** @var string Default AI Tutor API base URL (production). Includes the /api/v1 prefix. */
    public const DEFAULT_BASE_URL = 'https://ai-tutor.ai/api/v1';

    /** @var string Prefix of every AI Tutor API key. */
    public const KEY_PREFIX = 'sk-tutor-';

    /** @var int Seconds between polls when waiting for an async job. */
    private const POLL_INTERVAL = 3;

    /** @var int Maximum seconds to wait for an async job to complete. */
    private const POLL_MAX_WAIT = 600;

    /** @var int[] Seconds to wait before each retry of a transient failure. */
    private const RETRY_DELAYS = [2, 5];

    /** @var int Upper bound for a server-provided Retry-After, in seconds. */
    private const RETRY_AFTER_MAX = 30;

    /** @var string The API key for authentication. */
    private string $apikey;

    /** @var int Request timeout in seconds. */
    private int $timeout;

    /**
     * Constructor.
     *
     * @param string|null $apikey The API key, or null to read from plugin settings.
     * @param int $timeout Request timeout in seconds.
     */
    public function __construct(?string $apikey = null, int $timeout = 120) {
        $this->apikey = self::clean_key((string) ($apikey ?? get_config('local_lumination', 'apikey')));
        $this->timeout = $timeout;
    }

    /**
     * Remove characters that get pasted along with a key and make the API reject it.
     *
     * Copying from a web page or an email can add spaces, line breaks, non-breaking
     * or zero-width spaces and quotes. PHP's trim() misses the Unicode ones, and the
     * API answers "Invalid API key". Keys never contain any of these characters.
     *
     * @param string $key The key as saved in the settings.
     * @return string The cleaned key.
     */
    public static function clean_key(string $key): string {
        $key = preg_replace('/[\s\x{00A0}\x{200B}-\x{200D}\x{2060}\x{FEFF}]+/u', '', $key) ?? $key;
        return trim($key, "\"'");
    }

    /**
     * A safe description of the key in use, for comparing with the dashboard.
     *
     * @return string For example 'sk-tutor-...1a2b (57 characters)'.
     */
    public function key_hint(): string {
        $length = \core_text::strlen($this->apikey);
        $start = \core_text::substr($this->apikey, 0, \core_text::strlen(self::KEY_PREFIX));
        $end = $length > 16 ? \core_text::substr($this->apikey, -4) : '';
        return get_string('keyhint', 'local_lumination', (object) [
            'start' => $start,
            'end' => $end,
            'length' => $length,
        ]);
    }

    /**
     * Whether the key looks complete: the AI Tutor prefix followed by 48 letters and digits.
     *
     * A key copied while masked in the dashboard ("..." or bullets) or cut short fails this.
     *
     * @return bool True if the key has the expected shape.
     */
    public function has_complete_key(): bool {
        return (bool) preg_match('/^' . preg_quote(self::KEY_PREFIX, '/') . '[A-Za-z0-9]{48}$/', $this->apikey);
    }

    /**
     * Resolve the API base URL from settings, falling back to the production default.
     *
     * The base URL includes the /api/v1 prefix; endpoints are short paths like '/tutor'.
     *
     * @return string Base URL with no trailing slash.
     */
    public function base_url(): string {
        $configured = trim((string) get_config('local_lumination', 'baseurl'));
        return rtrim($configured !== '' ? $configured : self::DEFAULT_BASE_URL, '/');
    }

    /**
     * Host part of the base URL, used in error messages.
     *
     * @return string The host name.
     */
    public function host(): string {
        return parse_url($this->base_url(), PHP_URL_HOST) ?: $this->base_url();
    }

    /**
     * Check if the API is configured with an API key.
     *
     * @return bool True if the API key is set.
     */
    public function is_configured(): bool {
        return !empty($this->apikey);
    }

    /**
     * Whether the configured key has the AI Tutor key format (sk-tutor-...).
     *
     * Keys from the old Lumination API do not, and are rejected by AI Tutor.
     *
     * @return bool True if the key starts with the AI Tutor prefix.
     */
    public function has_valid_key_format(): bool {
        return strpos($this->apikey, self::KEY_PREFIX) === 0;
    }

    /**
     * Make a POST request with a JSON body.
     *
     * @param string $path API path starting with '/' (e.g. /course/guide).
     * @param array $data Request body data to be JSON-encoded.
     * @return array Decoded JSON response.
     * @throws api_exception If the request fails or returns an error status.
     */
    public function post(string $path, array $data = []): array {
        return $this->handle_response($this->send('POST', $path, $data));
    }

    /**
     * Make a PUT request with a JSON body.
     *
     * @param string $path API path starting with '/' (e.g. /course/guide/{uuid}).
     * @param array $data Request body data to be JSON-encoded.
     * @return array Decoded JSON response.
     * @throws api_exception If the request fails or returns an error status.
     */
    public function put(string $path, array $data = []): array {
        return $this->handle_response($this->send('PUT', $path, $data));
    }

    /**
     * Make a GET request.
     *
     * @param string $path API path starting with '/' (e.g. /requests/{id}).
     * @param array $params Query parameters to append to the URL.
     * @return array Decoded JSON response.
     * @throws api_exception If the request fails or returns an error status.
     */
    public function get(string $path, array $params = []): array {
        if (!empty($params)) {
            $path .= '?' . http_build_query($params);
        }
        return $this->handle_response($this->send('GET', $path));
    }

    /**
     * Submit an asynchronous job and block until it completes.
     *
     * The AI Tutor API is asynchronous: a POST to a tool endpoint returns a
     * request_id, and the result is fetched by polling GET /requests/{id} until
     * status is 'completed' or 'failed'. This returns the completed job envelope
     * (with 'result', 'conversation_id', 'input_tokens', etc.).
     *
     * @param string $path Tool path (e.g. /tutor, /homework, /course).
     * @param array $data Request body.
     * @return array The completed job envelope.
     * @throws api_exception If submission fails, the job fails, or it times out.
     */
    public function run(string $path, array $data = []): array {
        $submit = $this->post($path, $data);

        // Some responses may already be terminal.
        $status = $submit['status'] ?? '';
        $requestid = $submit['request_id'] ?? '';
        if ($status === 'failed') {
            throw new api_exception(0, $submit['error'] ?? 'Job failed', $this->host(), $requestid);
        }
        if ($status === 'completed' || empty($requestid)) {
            return $submit;
        }

        $deadline = time() + self::POLL_MAX_WAIT;
        while (time() < $deadline) {
            $this->pause(self::POLL_INTERVAL);
            $job = $this->get('/requests/' . rawurlencode($requestid));
            $status = $job['status'] ?? '';
            if ($status === 'completed') {
                return $job;
            }
            if ($status === 'failed') {
                throw new api_exception(0, $job['error'] ?? 'Job failed', $this->host(), $requestid);
            }
        }

        throw new api_exception(
            0,
            'no result after ' . self::POLL_MAX_WAIT . ' seconds',
            $this->host(),
            $requestid
        );
    }

    /**
     * Check that the API is reachable and accepts the configured key, without spending credits.
     *
     * Looks up a request ID that does not exist. The API authenticates every call
     * first, so a rejected key gets 401 and an accepted key gets a JSON 404.
     *
     * @return array ['ok' => bool, 'message' => string] with a message ready to show the admin.
     */
    public function check_connection(): array {
        $res = $this->send('GET', '/requests/moodle-connection-check');
        $a = (object) [
            'host' => $this->host(),
            'code' => $res['code'],
            'requestid' => $res['requestid'],
        ];

        if ($res['errno']) {
            $a->detail = $res['error'];
            return ['ok' => false, 'message' => get_string('errorapiconnect', 'local_lumination', $a)];
        }
        if (in_array($res['code'], [401, 402, 403], true)) {
            $e = new api_exception($res['code'], $this->error_detail($res), $a->host, $res['requestid']);
            return ['ok' => false, 'message' => $e->getMessage()];
        }
        // Any other JSON answer means the key got past authentication.
        if ($res['json'] !== null && $res['code'] < 500) {
            return ['ok' => true, 'message' => get_string('testconnection_ok', 'local_lumination', $a)];
        }
        $a->detail = $this->error_detail($res);
        return ['ok' => false, 'message' => get_string('testconnection_notapi', 'local_lumination', $a)];
    }

    /**
     * Send one API call, retrying transient failures.
     *
     * GET calls are retried on connection errors and HTTP 429/502/503/504. POST and
     * PUT are retried only on 429 and 503, where the API has not started the work,
     * so a retry cannot run (and charge) the same job twice.
     *
     * @param string $method HTTP method.
     * @param string $path API path starting with '/'.
     * @param array|null $data Request body for POST/PUT.
     * @return array ['code', 'body', 'json', 'requestid', 'errno', 'error'] for the last attempt.
     */
    private function send(string $method, string $path, ?array $data = null): array {
        $retryable = $method === 'GET' ? [0, 429, 502, 503, 504] : [429, 503];
        $attempt = 0;
        while (true) {
            $res = $this->send_once($method, $path, $data);
            $code = $res['errno'] ? 0 : $res['code'];
            if (!in_array($code, $retryable, true) || $attempt >= count(self::RETRY_DELAYS)) {
                return $res;
            }
            $delay = self::RETRY_DELAYS[$attempt];
            if (is_numeric($res['retryafter']) && (int) $res['retryafter'] > 0) {
                $delay = min((int) $res['retryafter'], self::RETRY_AFTER_MAX);
            }
            $this->pause($delay);
            $attempt++;
        }
    }

    /**
     * Send a single HTTP request to the API.
     *
     * @param string $method HTTP method.
     * @param string $path API path starting with '/'.
     * @param array|null $data Request body for POST/PUT.
     * @return array ['code', 'body', 'json', 'requestid', 'errno', 'error', 'retryafter'].
     */
    protected function send_once(string $method, string $path, ?array $data = null): array {
        global $CFG;
        // The curl class is not autoloaded; cron and the test page do not load it otherwise.
        require_once($CFG->libdir . '/filelib.php');

        $url = $this->base_url() . $path;
        $requestid = 'moodle-' . uniqid();

        $curl = new \curl();
        $curl->setHeader('x-api-key: ' . $this->apikey);
        $curl->setHeader('X-REQUEST-ID: ' . $requestid);
        $curl->setopt([
            'CURLOPT_TIMEOUT' => $this->timeout,
            'CURLOPT_CONNECTTIMEOUT' => 10,
        ]);

        if ($method === 'GET') {
            $body = $curl->get($url);
        } else {
            $curl->setHeader('Content-Type: application/json');
            // Passing CURLOPT_CUSTOMREQUEST in the per-request options ensures it is
            // applied together with the request body (setting it separately gets
            // overridden by CURLOPT_POST inside post()).
            $options = $method === 'POST' ? [] : ['CURLOPT_CUSTOMREQUEST' => $method];
            $body = $curl->post($url, json_encode($data ?? []), $options);
        }

        $retryafter = null;
        foreach ((array) $curl->getResponse() as $name => $value) {
            if (strcasecmp((string) $name, 'Retry-After') === 0) {
                $retryafter = is_array($value) ? end($value) : $value;
            }
        }

        $body = (string) $body;
        $json = json_decode($body, true);
        return [
            'code' => (int) ($curl->get_info()['http_code'] ?? 0),
            'body' => $body,
            'json' => is_array($json) ? $json : null,
            'requestid' => $requestid,
            'errno' => (int) $curl->get_errno(),
            'error' => (string) $curl->error,
            'retryafter' => $retryafter,
        ];
    }

    /**
     * Turn a send() result into decoded JSON, or throw.
     *
     * @param array $res Result of send().
     * @return array Decoded JSON response data.
     * @throws api_exception If the connection failed, HTTP status is not 2xx, or JSON is invalid.
     */
    private function handle_response(array $res): array {
        if ($res['errno']) {
            throw new api_exception(0, $res['error'], $this->host(), $res['requestid'], true);
        }
        if ($res['code'] < 200 || $res['code'] >= 300) {
            throw new api_exception($res['code'], $this->error_detail($res), $this->host(), $res['requestid']);
        }
        if ($res['json'] === null) {
            throw new api_exception($res['code'], 'Invalid JSON response', $this->host(), $res['requestid']);
        }
        return $res['json'];
    }

    /**
     * Pull a readable error detail out of a response.
     *
     * @param array $res Result of send().
     * @return string The API's error or detail field, or the start of the raw body.
     */
    private function error_detail(array $res): string {
        $json = $res['json'] ?? [];
        foreach (['error', 'detail', 'message'] as $field) {
            if (!empty($json[$field]) && is_string($json[$field])) {
                return $json[$field];
            }
        }
        $text = trim(strip_tags($res['body']));
        return $text === '' ? 'no response body' : \core_text::substr($text, 0, 200);
    }

    /**
     * Wait between polls and retries. Separate so tests can skip the wait.
     *
     * @param int $seconds Seconds to wait.
     * @return void
     */
    protected function pause(int $seconds): void {
        sleep($seconds);
    }
}

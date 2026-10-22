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
 * Check that the AI Tutor API accepts the configured key, without spending credits.
 *
 * @package    local_lumination
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_lumination_testconnection');

$api = new \local_lumination\api_client();
$settingsurl = new moodle_url('/admin/settings.php', ['section' => 'local_lumination']);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('testconnection', 'local_lumination'));

if ($api->uses_hub()) {
    echo html_writer::tag('p', get_string('testconnection_fromhub', 'local_lumination'));
}
echo html_writer::tag('p', get_string('testconnection_baseurl', 'local_lumination', s($api->base_url())));

if (!$api->is_configured()) {
    echo $OUTPUT->notification(get_string('errornoapi', 'local_lumination'), 'error');
} else {
    echo html_writer::tag('p', get_string('testconnection_key', 'local_lumination', s($api->key_hint())));
    if (!$api->has_valid_key_format()) {
        echo $OUTPUT->notification(get_string('testconnection_keyformat', 'local_lumination'), 'warning');
    } else if (!$api->has_complete_key()) {
        echo $OUTPUT->notification(get_string('testconnection_keyincomplete', 'local_lumination'), 'warning');
    }
    $result = $api->check_connection();
    echo $OUTPUT->notification(s($result['message']), $result['ok'] ? 'success' : 'error');
}

echo html_writer::link($settingsurl, get_string('testconnection_back', 'local_lumination'), ['class' => 'btn btn-secondary mt-3']);
echo $OUTPUT->footer();

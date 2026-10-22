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
 * Admin settings for the Lumination AI plugin.
 *
 * @package    local_lumination
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Create a settings category for Lumination.
    $ADMIN->add(
        'localplugins',
        new admin_category(
            'local_lumination_cat',
            get_string('pluginname', 'local_lumination')
        )
    );

    // Settings page.
    $settings = new admin_settingpage(
        'local_lumination',
        get_string('pluginname', 'local_lumination')
    );

    // When the Lumination AI Provider supplies the key, say so above the fields it overrides.
    if ($ADMIN->fulltree && \local_lumination\api_client::hub_config()) {
        $settings->add(
            new admin_setting_heading(
                'local_lumination/hubinuse',
                get_string('hubinuse', 'local_lumination'),
                get_string('hubinuse_desc', 'local_lumination', (new moodle_url('/admin/settings.php', [
                    'section' => 'aiprovider',
                ]))->out())
            )
        );
    }

    $settings->add(
        new admin_setting_configpasswordunmask(
            'local_lumination/apikey',
            get_string('setting_apikey', 'local_lumination'),
            get_string('setting_apikey_desc', 'local_lumination'),
            ''
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'local_lumination/baseurl',
            get_string('setting_baseurl', 'local_lumination'),
            get_string('setting_baseurl_desc', 'local_lumination'),
            'https://ai-tutor.ai/api/v1',
            PARAM_URL
        )
    );

    $settings->add(
        new admin_setting_description(
            'local_lumination/testconnection',
            get_string('testconnection', 'local_lumination'),
            html_writer::link(
                new moodle_url('/local/lumination/test_connection.php'),
                get_string('testconnection_link', 'local_lumination'),
                ['class' => 'btn btn-secondary']
            )
        )
    );

    $ADMIN->add('local_lumination_cat', $settings);

    // Connection check, linked from the settings page.
    $ADMIN->add(
        'local_lumination_cat',
        new admin_externalpage(
            'local_lumination_testconnection',
            get_string('testconnection', 'local_lumination'),
            new moodle_url('/local/lumination/test_connection.php'),
            'moodle/site:config'
        )
    );

    // Direct link to the Course Generator tool.
    $ADMIN->add(
        'local_lumination_cat',
        new admin_externalpage(
            'local_lumination_coursegen',
            get_string('coursegenerator_nav', 'local_lumination'),
            new moodle_url('/local/lumination/course_generator.php'),
            'local/lumination:generatecourse'
        )
    );

    // Direct link to the Usage Dashboard.
    $ADMIN->add(
        'local_lumination_cat',
        new admin_externalpage(
            'local_lumination_usage',
            get_string('usage_nav', 'local_lumination'),
            new moodle_url('/local/lumination/usage.php'),
            'local/lumination:viewusage'
        )
    );
}

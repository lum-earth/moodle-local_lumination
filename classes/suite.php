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
 * The Lumination AI plugin suite: this plugin is the hub, other plugins are add-ons.
 *
 * @package    local_lumination
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lumination;

/**
 * The Lumination AI plugin suite: this plugin is the hub, other plugins are add-ons.
 *
 * Add-ons declare local_lumination in $plugin->dependencies and reuse its API key,
 * api_client, api_exception and usage_logger (see README.md, "Building an add-on").
 *
 * @package    local_lumination
 * @copyright  2026 Lumination AI <https://lumination.ai>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class suite {
    /**
     * Whether the Course Generator is switched on in the plugin settings.
     *
     * Sites that use only Lumination add-ons can hide it. On by default, including
     * on sites that upgraded before the setting existed.
     *
     * @return bool True if the Course Generator pages and menu entries are available.
     */
    public static function course_generator_enabled(): bool {
        $value = get_config('local_lumination', 'enablecoursegenerator');
        return $value === false || (bool) $value;
    }

    /**
     * Installed add-ons: plugins that declare a dependency on local_lumination.
     *
     * @return array Component name => display name, sorted by display name.
     */
    public static function get_addons(): array {
        $addons = [];
        foreach (\core_plugin_manager::instance()->other_plugins_that_require('local_lumination') as $component) {
            $addons[$component] = get_string_manager()->string_exists('pluginname', $component)
                ? get_string('pluginname', $component)
                : $component;
        }
        \core_collator::asort($addons);
        return $addons;
    }
}

<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * User import settings page
 *
 * @package    local_userimport
 * @copyright  2022 Yury Aliakhnovich yureyal@gmail.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

    $context = context_system::instance();
if (has_capability('local/userimport:manage', $context)) {
    $ADMIN->add('localplugins', new admin_category('local_userimport', get_string('pluginname', 'local_userimport')));

    $settings = new admin_settingpage('local_userimport_settings',  get_string('settings', 'local_userimport'));

    $settings->visiblename = get_string('settings', 'local_userimport');

    $settings->add(new admin_setting_heading(
                                        'local_userimport/showinnavigation',
                                        '',
                                         new lang_string('description', 'local_userimport'
                                        )));

    $settings->add(new admin_setting_heading(
        'local_userimport/endpoint',
        new lang_string('menusettings', 'local_userimport'),
        ''
    ));


    $defaultsetting = 'https://portal-dis.kpfu.ru/e-ksu/api_json.get_assigned_students';
    $settings->add(new admin_setting_configtext('local_userimport/endpoint', get_string('endpoint', 'local_userimport'),
        '', $defaultsetting, PARAM_RAW));



    $ADMIN->add('local_userimport', $settings);
    // Clear '$settings' to prevent adding again our site category.
    $settings = null;

    // Add options.
    $ADMIN->add('local_userimport',
        new admin_externalpage(
            'local_userimport_import',
            get_string('importpage', 'local_userimport'),
            new moodle_url($CFG->wwwroot.'/local/userimport/import.php')
        )
    );

}

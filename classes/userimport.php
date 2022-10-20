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
 *
 * User Import.
 *
 * @package   local_userimport
 * @copyright  2016 onwards Antonello Moro {http://antonellomoro.it}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_userimport;

defined('MOODLE_INTERNAL') || die();

/**
 * File and directory manager
 *
 * @package    local_userimport
 * @copyright  2022 onwards Yury Aliakhnovich yureyal@gmail.com
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class userimport
{

    public function insert_users($data)
    {
     global $DB;

        $manual = enrol_get_plugin('manual');
        $enrol = $DB->get_record('enrol', array('enrol' => 'manual', 'courseid' => $data->courseid, 'status' => 0));
        $coursename = $DB->get_field('course', 'fullname', array('id' => $data->courseid));

        $gruups = array();
        if ($groups_ = $this->send_webhook($data)) {
            $groups = $groups_;
        }

        if(!empty($groups['groups'])){
            foreach($groups as $group){
                foreach($group as $students) {
                    echo '<hr>';
                    if(!$groupid = $DB->get_field('groups', 'id',['courseid'=>$data->courseid, 'name'=>$students['group_number']])){
                        $groupdata = new \stdClass();
                        $groupdata->courseid    = $data->courseid;
                        $groupdata->name        = $students['group_number'];
                        $groupid = groups_create_group($groupdata);
                    }
                    foreach($students['students'] as $student) {

                        $name  = explode(" ", $student['stud_name']);
                        $user = new \stdClass();
                        $user->username  = mb_strtolower($student['stud_login']);
                        $user->email     = $student['stud_mail'];
                        $user->lastname  = $name[0];
                        $user->firstname = $name[1];
                        $user->firstname .= (isset($name[2])) ? ' '.$name[2] : '' ;
                        $report = $student['stud_name'];
                        $usercheck = $DB->get_record('user', ['email'=>$user->email]);
                        if(!$usercheck){
                            $userid = user_create_user($user, false, false);
                            $report .= ' - '. get_string('useradded', 'local_userimport');
                        } elseif ($usercheck->deleted == 1){
                            $DB->delete_records('user', ['id'=>$usercheck->id]);
                           $userid = user_create_user($user, false, false);
                            $report .= ' - '. get_string('useradded', 'local_userimport');
                        } elseif($usercheck->suspended == 1){
                            $usercheck->suspended = 0;
                            $DB->update_record('user', $usercheck);
                            $userid = $usercheck->id;
                            $report .= ' - '. get_string('userupdated', 'local_userimport');
                        } else {
                            $userid = $usercheck->id;
                            $report .= ' - '. get_string('userexists', 'local_userimport');
                        }

                        if ($enrol) {
                            $manual->enrol_user($enrol, $userid, 5, time());
                            $report .= ' - '. get_string('enrolledtocourse', 'local_userimport').': '.$coursename;
                            groups_add_member($groupid, $userid);
                            $report .= ' - '. get_string('addedtogroup', 'local_userimport').': '.$students['group_number'];
                        }

                        echo $report.'<hr>';
                    }

                }
            }
        } else {
            echo '<hr>'.get_string('nodata', 'local_userimport').'<hr>';
            return;
        }


        return $gruups;
    }


    public function send_webhook($data)
    {
        global $CFG;

        $config = get_config('local_userimport');

        if (isset($config->endpoint) && !empty($config->endpoint)) {
            $url = $config->endpoint;
            $str_data = "p_course={$data->p_course}";
            $str_data .= "&p_year={$data->p_year}";
            $str_data .= "&p_semester={$data->p_semester}";

            $defaults = array(
                CURLOPT_URL => $url,
                CURLOPT_HTTPHEADER => array('Accept: application/json'),
                CURLOPT_RETURNTRANSFER => true,    // return web page
                CURLOPT_HEADER => false,            // header
                CURLOPT_FOLLOWLOCATION => true,     // follow redirects
                CURLOPT_MAXREDIRS => 10,            // stop after 10 redirects
                CURLOPT_ENCODING => "UTF-8",             // handle compressed
                CURLOPT_CONNECTTIMEOUT => 240,      // time-out on connect
                CURLOPT_TIMEOUT => 240,             // time-out on response
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $str_data
            );

            $ch = curl_init();
            curl_setopt_array($ch, ($defaults));
            $exec = curl_exec($ch);
            $exec = mb_convert_encoding($exec,"UTF-8", "windows-1251");
            $result = json_decode($exec, true);

            $info = curl_getinfo($ch);
            $statuscode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close($ch);

            if ($statuscode !== 200 || empty($result) || (!$result)) {
                behat_error(BEHAT_EXITCODE_REQUIREMENT, $url . ' is not available, ensure you specified ' .
                    'correct url and that the server is set up and started.' . PHP_EOL . ' More info in ' .
                    behat_is_requested_url($url . PHP_EOL));
            }

            return $result;
        } else {
            print_error(get_string('endpointnotset', 'local_userimport'));
        }
    }
}
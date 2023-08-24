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
 * superframe view page
 *
 * @package    block_superframe
 * @copyright  Daniel Neis <danielneis@gmail.com>
 * Modified for use in MoodleBites for Developers Level 1 by Richard Jones & Justin Hunt
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_superframe_renderer extends plugin_renderer_base {

    public function display_view_page($url, $width, $height, $courseid, $blockid) {

        global $USER;
        // Show Modal after clicking the about link:
        $this->page->requires->js_call_amd('block_superframe/modal_amd', 'init',
            array('data' =>
                array(
                    'title' => get_string('about', 'block_superframe'),
                    'body' => get_string('modalbody', 'block_superframe'),
                    'footer' => get_string('modalfooter', 'block_superframe')
                )
            )
        );
        $data = new stdClass();

        // Page heading and iframe data.
        $data->heading = get_string('pluginname', 'block_superframe');
        $data->url = $url;
        $data->height = $height;
        $data->width = $width;
        $data->backtocourseurl = new moodle_url('/course/view.php', ['id' => $courseid]);
        $data->backtocourseurltext = get_string('backtocourseurltext', 'block_superframe');

        // Add the user data.
        $data->fullname = fullname($USER);

        // Text for the links and the size parameter.
        $strings = array();
        $strings['custom'] = get_string('custom', 'block_superframe');
        $strings['small'] = get_string('small', 'block_superframe');
        $strings['medium'] = get_string('medium', 'block_superframe');
        $strings['large'] = get_string('large', 'block_superframe');

        // Create the data structure for the links.
        $links = array();
        $link = new moodle_url('/blocks/superframe/view.php', ['courseid' => $courseid,
            'blockid' => $blockid]);

        foreach ($strings as $key => $string) {
            $links[] = ['link' => $link->out(false, ['size' => $key]), 'text' => $string];
        }

        $data->linkdata = $links;

        // Start output to browser.
        echo $this->output->header();

        // Render the data in a Mustache template.
        echo $this->render_from_template('block_superframe/frame', $data);

        // Finish the page.
        echo $this->output->footer();
    }

    public function fetch_block_content($blockid, $courseid) {
        global $USER;
        $data = new stdClass();
        $name = $USER->firstname.' '.$USER->lastname;

        // Data for displaying a popup after clicking on the welcome message in the block:
        $this->page->requires->js_call_amd('block_superframe/test_amd', 'init', ['name' => $name]);
        $data->headingclass = 'block_superframe_heading';

        $data->courseid = $courseid; // For the generation of links to user profiles in the user list.
        $data->baseuserurl = new moodle_url('/user/view.php'); // Also for link generation.

        $data->welcome = get_string('welcomeuser', 'block_superframe', $name);
        $context = \context_block::instance($blockid);
        // Display Link (given the viewing user has the necessary capability):
        if (has_capability('block/superframe:seeviewpagelink', $context)) {
            $data->url = new moodle_url('/blocks/superframe/view.php', ['blockid' => $blockid, 'courseid' => $courseid]);
            $data->text = get_string('viewlink', 'block_superframe');
        }

        // Display a list of users who are enrolled in the course (given the necessary capabilities of the viewer):
        if (has_capability('block/superframe:seeuserlist', $context)) {
            $data->students = array_values(self::get_course_users($courseid));
        }

        // Add a link to the popup page.
        $data->popurl = new moodle_url('/blocks/superframe/block_data.php');
        $data->poptext = get_string('poptext', 'block_superframe');

        // Add a link to the table editing page.
        $data->tableurl = new moodle_url('/blocks/superframe/tablemanager.php', ['courseid' => $courseid]);
        $data->tabletext = get_string('tabletext', 'block_superframe');

        // RETURN the data for a Mustache template (different to display_view_page)!
        return $this->render_from_template('block_superframe/block_content', $data);
    }

    // Helper function to display a list of users who are enrolled in the course:
    private static function get_course_users($courseid) {
        global $DB;

        $sql = "SELECT u.id, u.firstname, u.lastname ";
        $sql .= "FROM {course} c ";
        $sql .= "JOIN {context} x ON c.id = x.instanceid ";
        $sql .= "JOIN {role_assignments} r ON r.contextid = x.id ";
        $sql .= "JOIN {user} u ON u.id = r.userid ";
        $sql .= "WHERE c.id = :courseid ";
        $sql .= "AND r.roleid = :roleid";

        // In real world query should check users are not deleted/suspended.
        $records = $DB->get_records_sql($sql, ['courseid' => $courseid, 'roleid' => 5]);

        return $records;
    }

    /**
     * Function to display a table of records
     * @param array the records to display.
     * @return none.
     */
    public function display_block_table($records) {
        // Prepare the data for the template.
        $table = new stdClass();

        // Table headers.
        $table->tableheaders = [
            get_string('blockid', 'block_superframe'),
            get_string('blockname', 'block_superframe'),
            get_string('course', 'block_superframe'),
            get_string('catname', 'block_superframe'),
        ];

        // Data rows.
        $table->tabledata = array_values($records);

        // Start output to browser.
        echo $this->output->header();

        // Call our template to render the data.
        echo $this->render_from_template('block_superframe/block_data', $table);

        // Finish the page.
        echo $this->output->footer();
    }
}

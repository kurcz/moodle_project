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
 * List of all pages in course
 *
 * @package    mod
 * @subpackage project
 * @copyright  2016 onwards Jeffrey Kurcz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/mod/project/locallib.php');
//require_once($CFG->libdir.'/completionlib.php');

$id      = required_param('id', PARAM_INT); // History Module ID
$p	  = optional_param('p', 0, PARAM_INT); //Project ID
$d	  = optional_param('d', 0, PARAM_INT);

if ($p) {
    if (!$project = $DB->get_record('project', array('id'=>$p))) {
        print_error('invalidaccessparameter');
    }
    $cm = get_coursemodule_from_instance('project', $project->id, $project->course, false, MUST_EXIST);

} else {
    if (!$cm = get_coursemodule_from_id('project', $id)) {
        print_error('invalidcoursemodule');
    }
}
if($d && $id) {
	$DB->delete_records('project_history_imp_summary', array('id'=>$id));
	$DB->delete_records('project_history_imp_detail', array('message_id'=>$id));
	redirect("view.php?id=".$cm->id);
}

$history_summary = $DB->get_record('project_history_imp_summary', array('id'=>$id), '*', MUST_EXIST);
$history = $DB->get_records('project_history_imp_detail', array('message_id'=>$id));

$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/project:view', $context);

$PAGE->set_url('/mod/project/history_view.php', array('id' => $cm->id));

$options = empty($project->displayoptions) ? array() : unserialize($project->displayoptions);

$PAGE->set_title($course->shortname.': '.$project->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_activity_record($project);

/// Check to see if groups are being used here
$groupmode = groups_get_activity_groupmode($cm);
$currentgroup = groups_get_activity_group($cm, true);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string('Chat History'), 2);


$html = "<img src='pix\\".$history_summary->method.".png' width='16px' heigh='16px' />  ".userdate($history_summary->date)."<br /><br />";
//$html .= $history->content;

if($history_summary->method=="Skype"){
	$html .= '<table><tr><td>Time</td><td>User</td><td>Message</td></tr>';
	foreach($history as $record){
		//$student_name = getStudentName($record->user);
		$html .= '<tr><td>'.userdate($record->time, get_string('strftimedatetimeshort', 'langconfig')).'</td><td>'.$record->user.'</td><td>'.$record->message.'</td></tr>';
	}

	$html .= '</table>';
}//end if Method is Skype

if($history_summary->method=="Email"){
	$html .= '<table><tr><td>Time</td><td>User</td><td>Message</td></tr>';
	foreach($history as $record){
		//$student_name = getStudentName($record->user);
		$html .= '<tr><td>'.userdate($record->time, get_string('strftimedatetimeshort', 'langconfig')).'</td><td>'.$record->user.'</td><td>'.$record->message.'</td></tr>';
	}

	$html .= '</table>';
}//end if Method is Skype

$html .= "<a href='?d=1&id=".$id."&p=".$p."'>Delete this conversation</a>";

$content = $html;
//$content = format_text($content, $project->contentformat, $formatoptions);
echo $OUTPUT->box($content, "generalbox center clearfix");

add_to_log($course->id, 'project', 'history view', 'history_view.php?id='.$cm->id, $id);
//$strlastmodified = get_string("lastmodified");
//echo "<div class=\"modified\">$strlastmodified: ".userdate($project->timemodified)."</div>";

echo $OUTPUT->footer();

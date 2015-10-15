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
 * project module version information
 *
 * @package    mod
 * @subpackage project
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot.'/mod/project/locallib.php');

//require_once($CFG->libdir.'/completionlib.php');

$cmid      = required_param('cmid', PARAM_INT); // History Module ID
$p	  = optional_param('p', 0, PARAM_INT); //Project ID

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
$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/project:view', $context);

$PAGE->set_url('/mod/project/workload_distribution.php', array('id' => $cm->id));

$options = empty($project->displayoptions) ? array() : unserialize($project->displayoptions);

/// Check to see if groups are being used here
$groupmode = groups_get_activity_groupmode($cm);
$currentgroup = groups_get_activity_group($cm, true);

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string('Workload Distribution'), 2);
echo $OUTPUT->heading(format_string(getGroupName($currentgroup)), 4);

//$html = "<img src='pix\\".$history_summary->method.".png' width='16px' heigh='16px' />  ".userdate($history_summary->date)."<br /><br />";

$member_rank = RankMembersTasksDistribution($currentgroup);
//echo AlertMembersTasksDistribution($member_rank);
arsort($member_rank); //Order the Rank by number of hours

//Get the total number of hours based on each student
$total_hours = array_sum($member_rank);
if($total_hours>0){
$equal_hours = $total_hours/count($member_rank);

$html = "Individual Recommended Hours: ".round($equal_hours,2)." Hours<br /><br />";

//If large variance occurs, display column for table
if(AlertWorkloadDistribution($currentgroup))
	$html .= "<table style='border:1px solid black;'><tr style='background-color:lightgrey;'><th>Member</th><th>Assigned Hours</th><th>% of Workload</th><th>Variance</th></tr>";
else
	$html .= "<table style='border:1px solid black;'><tr style='background-color:lightgrey;'><th>Member</th><th>Assigned Hours</th><th>% of Workload</th><th></th></tr>";
	
foreach($member_rank as $key=>$member){
	if($key==$USER->id) //Bold the current student
		$html .= "<tr><td><b>".studentidToLMS_Name($key)."</b></td>";
	else
		$html .= "<tr><td>".studentidToLMS_Name($key)."</td>";
	$html .= "<td style='text-align:center;'>".$member."</td>";
	$html .= "<td style='text-align:center;'''>".round($member/$total_hours*100,2)."</td>";
	if(!empty(MemberWorkloadDistribution($member, $equal_hours))) //If distribution occus, display variance and icon.
		$html .= "<td style='text-align:center;color:crimson;'>".MemberWorkloadDistribution($member, $equal_hours)."  <img src='pix\alert_icon.png'' width='12px' height='12px'/></td>";
	else
		$html .= "<td></td>";
	$html .= "</tr>";
}

$html .= "<td><u>Total</u></td><td style='text-align:center;'>".$total_hours."</td><td style='text-align:center;'>".round($total_hours/$total_hours*100,2)."</td><td></td></tr>";
$html .= "</table>";
}
else //else, no tasks are created, display generic message.
	$html = "There are currently no tasks created. Please create a task and view workload distribution again.";
$content = $html;
//$content = format_text($content, $project->contentformat, $formatoptions);
echo $OUTPUT->box($content, "generalbox center clearfix");

add_to_log($course->id, 'project', 'workload dist', 'workload_distribution.php?id='.$cmid, $p);

echo $OUTPUT->footer();

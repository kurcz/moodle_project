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

//Set URL based on course id
$PAGE->set_url('/mod/project/workload_distribution.php', array('id' => $cm->id));

$options = empty($project->displayoptions) ? array() : unserialize($project->displayoptions);

/// Check to see if groups are being used here
$groupmode = groups_get_activity_groupmode($cm);
$currentgroup = groups_get_activity_group($cm, true);

$groups = $DB->get_records('groups', array('courseid'=>$course->id));
$group_members = $DB->get_records('groups_members', array('groupid'=>$currentgroup));

//Display some headers
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string('Group Comparison'), 2);
echo $OUTPUT->heading(format_string(getGroupName($currentgroup)), 4);

$html = "<table><tr><th>Group</th><th>% Complete</th></tr>";
//Iterate all groups in the course, then find all the tasks and add up their associated percentages and divide by the total number of tasks
$group_rank = array();
foreach($groups as $group){ //Iterate through each group in the course
	$hours_complete = $total_hours = $start = $end = 0; //Initalize all variables to 0.
	$tasks = $DB->get_records('project_task', array('group_id'=>$group->id), '', 'id,name,hours,progress,start_date,end_date');
	foreach($tasks as $task){
		//Find the earliest start time, not set OR new start is sooner
		if($start==0 || $task->start_date<$start){
			$start = $task->start_date;
		}
		//Find the latest end time, not set OR later end time
		if($end==0 || $task->end_date>$end){
			$end = $task->end_date;
		}
		$hours_complete += $task->hours * ($task->progress/100); //Hours complete for a group are the hours complete of their current progress
		$total_hours += $task->hours;
	}
	//$group_rank[$group->id][0] = $group->name; //Store Group Name
	$group_rank[$group->id] = round($hours_complete/$total_hours*100); //Store rounded hours of the group progress.
	$group_start[$group->id] = $start;
	$group_end[$group->id] = $end;
			
}
echo "<br />";

arsort($group_rank); //Sort the groups by highest progress
//Relist groups based on their progress
foreach($group_rank as $key=>$sorted_group){
	//Find how many days between the first task and the last task
	$total_days = ($group_end[$key] - $group_start[$key])/(60*60*24);
	//Find how many days into the project a group is
	$days_in = floor((time() - $group_start[$key])/(60*60*24));
	//Find out what the percentage of time done is.
	$time_done = round(($days_in / $total_days)*100,0);

	if($time_done > $sorted_group){ //If more time has been completed then group progress, bad standing alert
		$progress_bar_colour = "#f00"; //Red
	}
	else { //Otherwise more work has been done than time, good standing alert
		$progress_bar_colour = "#0f0"; //Green
	}
	
	if($key==$currentgroup) //If the student viewing page is in the group, bold their name.
		$html .= "<tr><td><b>".getGroupName($key)."<b></td><td>";
	else //Otherwise, just display the group name
		$html .= "<tr><td>".getGroupName($key)."</td><td>";
	$html .= '<div style="border: solid 1px;width: 300px;height: 10px;">';
	$html .= '<div style="background-color:'.$progress_bar_colour.';width:'.$sorted_group .'%; height:10px;">&nbsp;</div>'; //Green Progress bar to indicate complete
	$html .= '<div style="position: relative;top:-10px;text-align:center;font-size:10px;font-weight:bold;">Progress: '.$sorted_group .'% </div>';
}

$html .= "</table>";

$content = $html;
echo $OUTPUT->box($content, "generalbox center clearfix");

add_to_log($course->id, 'project', 'group compare', 'group_compare.php?id='.$cmid, $p);

echo $OUTPUT->footer();

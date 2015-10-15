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

$id      = optional_param('id', 0, PARAM_INT); // Course Module ID
$p       = optional_param('p', 0, PARAM_INT);  // project instance ID
$inpopup = optional_param('inpopup', 0, PARAM_BOOL);

if ($p) {
    if (!$project = $DB->get_record('project', array('id'=>$p))) {
        print_error('invalidaccessparameter');
    }
    $cm = get_coursemodule_from_instance('project', $project->id, $project->course, false, MUST_EXIST);

} else {
    if (!$cm = get_coursemodule_from_id('project', $id)) {
        print_error('invalidcoursemodule');
    }
    $project = $DB->get_record('project', array('id'=>$cm->instance), '*', MUST_EXIST);
}

$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/project:view', $context);

$PAGE->set_url('/mod/project/view.php', array('id' => $cm->id));

$options = empty($project->displayoptions) ? array() : unserialize($project->displayoptions);

if ($inpopup and $project->display == RESOURCELIB_DISPLAY_POPUP) {
    $PAGE->set_projectlayout('popup');
    $PAGE->set_title($course->shortname.': '.$project->name);
    $PAGE->set_heading($course->fullname);
} else {
    $PAGE->set_title($course->shortname.': '.$project->name);
    $PAGE->set_heading($course->fullname);
    $PAGE->set_activity_record($project);
}
$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->jquery_plugin('ui-css');

/// Check to see if groups are being used here
$groupmode = groups_get_activity_groupmode($cm);
$currentgroup = groups_get_activity_group($cm, true);

// url parameters
$params = array();
if ($currentgroup) {
    $groupselect = " AND groupid = '$currentgroup'";
    $groupparam = "_group{$currentgroup}";
    $params['groupid'] = $currentgroup;
} else {
    $groupselect = "";
    $groupparam = "";
}

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($project->name), 2);
if (!empty($options['printintro'])) {
    if (trim(strip_tags($project->intro))) {
        echo $OUTPUT->box_start('mod_introbox', 'projectintro');
        echo format_module_intro('project', $project, $cm->id);
        echo $OUTPUT->box_end();
    }
}

//$content = file_rewrite_pluginfile_urls($project->content, 'pluginfile.php', $context->id, 'mod_project', 'content', $project->revision);
$formatoptions = new stdClass;
$formatoptions->noclean = true;
$formatoptions->overflowdiv = true;
$formatoptions->context = $context;


$members = array();
$tasks = array();
$members = getGroupMembers($currentgroup); //Get group members of the group, ID's and last access

$tasks = getGroupsTasks($currentgroup); 
$history = getGroupChatHistory($currentgroup); //Get Group chat history
$html = ""; // Initiate blank HTML to create the screen.

//Alert Section - When loading the module, check for any alerts for the user/group
$html .= checkAlerts($USER->id, $currentgroup);

//Draw the screen
//Left side is the progrect side, Start with listing all the tasks
$html .= "<table border=1 width=80%><tr><td style='vertical-align:top;'><table><tr><td><u>List of Tasks</u><br /><br /><a href='task_edit.php?cmid=".$id."'>+ NEW TASK</a><br /><br />";
	foreach($tasks as $task){
		$name = getStudentName($task->members); //Get users assigned to the task

		//If the task is complete, display a checkmark		
		if($task->progress == 100) { 
			$html .= "<img src='pix\Check_mark.png'' width='12px' height='12px' />";
		}
		//Display the task name, link and edit 
		$html .= " Task: <a href='task_view.php?cmid=".$id."&id=".$id."&t=".$task->id."'>".$task->name ."</a> <a href='task_edit.php?cmid=".$id."&id=".$id."&t=".$task->id."'><img src='pix\settings.png'' width='12px' height='12px' /></a><br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;For: ";
			//Find and display all the users assigned to the task
			$name_size = count($name);
			$name_count = 0;
			foreach($name as $assigned_name){
				$html .= "<b>".$assigned_name->username."</b>"; //Display the name
				if(($name_size-1) != $name_count)
					$html .= ", "; //Add a nice comma
				$name_count++; //Increase the counter
			}
		$html .= " <br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Due: ".userdate($task->end_date, get_string('strftimedate', 'langconfig'));
		//Create the progress bar using DIV tags to show quick view.
		$html .= '<div style="border: solid 1px;width: 300px;height: 12px;">';
		
		//Determine if task is on time and bar colour.
		$total_days = floor(($task->end_date - $task->start_date)/(60*60*24));  //Find the total number of days for the task
		$time_done = floor((time() - $task->start_date)/(60*60*24)); //Find how many days into the task.
		$percentage_done = round($time_done / $total_days,2)*100; //Get the %
		if($percentage_done <= $task->progress && time() < $task->end_date || $task->progress==100){
			$progress_bar_colour = '#0f0'; //Green
		} else {
			$progress_bar_colour = '#f00'; //Red
		}
		$html .= '<div style="background-color: '.$progress_bar_colour.';width:'.$task->progress .'%; height:12px;">&nbsp;</div>'; //Green Progress bar to indicate complete
		$html .= '<div style="position: relative;top:-11px;text-align:center;font-size:10px;font-weight:bold;">Progress: '.$task->progress.'%</div></div><br />';
		
	} //end foreach loop

//Display Workload distribution link and possible alert.
$workload_alert = AlertWorkloadDistribution($currentgroup);
$html .= "<br/> <a href='workload_distribution.php?cmid=".$id."&p=".$project->id."' style='a:link{color: #f00;}' >Workload Distribution</a>"; //Display link
if($workload_alert){
	$html .= " <img src='pix\alert_icon.png'' width='12px' height='12px'/>"; //If alert is true, lets display an icon for attention
}

//Display Project Comparison with other groups in the course
$html .= "<br/> <a href='group_compare.php?cmid=".$id."&p=".$project->id."'>Progress Comparison with other Groups</a>";
$html .= "</table></td><td style='vertical-align:top;'>";

//Right side of the screen for communication
//List all the group members, and recent activity.
$html .= "<table><tr><td><u>Group Members</u></td><td><u>Last Online</u></td></tr>"; 
	for($i = 0; $i< count($members); $i++){
		//Messaging link
		$sendmessageurl = new moodle_url('/message/index.php', array('id' => $members[$i][0]));
			if ($course->id) {
				$sendmessageurl->param('viewing', MESSAGE_VIEW_COURSE. $course->id);
			}
			if($USER->id != $members[$i][0]) { //IF user is not the current user, display message icon
				$messagelink = "<a href='".$sendmessageurl."'><img src='pix\message.png'' width='12px' height='12px' /></a>";
			}
			else { //Otherwise do not display this icon.
				$messagelink = "";
			}
	
		if((time() - $members[$i][2]) <= 100){ //If user has been active in the past 100 seconds, they are online.
			$html .= "<tr><td><a href='../../user/view.php?id=".$members[$i][0]."&course=".$course->id."'>".$members[$i][1]."</a> ". $messagelink."</td><td><font style='color:green;font-weight:bold;'>Online Now</font></td></tr>";
		}
		else { //Otherwise display the last time they were active.
			if($members[$i][2] == 0)
				$html .= "<tr><td><a href='../../user/view.php?id=".$members[$i][0]."&course=".$course->id."'>".$members[$i][1]."</a> ". $messagelink."</td><td>Never</td></tr>";				
			else
				$html .= "<tr><td><a href='../../user/view.php?id=".$members[$i][0]."&course=".$course->id."'>".$members[$i][1]."</a> ". $messagelink."</td><td>".userdate($members[$i][2], get_string('strftimedatetimeshort', 'langconfig'))."</td></tr>";
		}
	}
	//More communication links, forums, chats, imports
	$html .= "</table><table><tr><td><u>Communication Tools</u></td></tr>";
	
	if($chat = $DB->get_record('course_modules', array('module'=>4, 'course'=>$COURSE->id, 'groupmode'=>1), 'id,instance')){
    if (! $chat = $DB->get_record('chat', array('id'=>$chat->instance))) {
        print_error('invalidid', 'chat');
    }
	if (has_capability('mod/chat:chat', $context)) {
		$params['id'] = $chat->id;
		$chattarget = new moodle_url("/mod/chat/gui_$CFG->chat_method/index.php", $params);
		$html .= '<tr><td><img src="..\..\mod\chat\pix\icon.png" width="16px" height="16px"> ';
		$html .= $OUTPUT->action_link($chattarget, $chat->name, new popup_action('click', $chattarget, "chat{$course->id}_{$chat->id}{$groupparam}", array('height' => 500, 'width' => 700))); //Create a link with a popout window for the chat.
		$html .= '</td></tr>';
		
	}//End if user can chat
}//End if chat is setup for course.

//If Forums are setup for Groups, display links to them.
$html .= displayForums();
	
//Display the previous imported communication history
$html .="</table><table><tr><td><u>Communication History</u><br/>";
	foreach($history as $history_item) { //Iterate through each imported type and display icon, link with time.
		$html .= "<img src='pix\\".$history_item->method.".png' width='16px' heigh='16px' /> <a href='history_view.php?id=".$history_item->id."&p=".$project->id."'>".userdate($history_item->date, get_string('strftimedatetime', 'langconfig'))."</a> <br />";
	}
	$html .= "<br/> <a href='history_import.php?cmid=".$id."'>+ Import</a>"; //Display a link to import more conversations

	$html .= "<br /><br />";

$html .= "</table></table>";

$content = $html;
//$content = format_text($content, $project->contentformat, $formatoptions);
echo $OUTPUT->box($content, "generalbox center clearfix");

add_to_log($course->id, 'project', 'view', 'view.php?id='.$cm->id, $project->id);
//$strlastmodified = get_string("lastmodified");
//echo "<div class=\"modified\">$strlastmodified: ".userdate($project->timemodified)."</div>";

echo $OUTPUT->footer();

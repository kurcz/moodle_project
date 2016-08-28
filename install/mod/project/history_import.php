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
require_once($CFG->dirroot.'/mod/project/edit_form.php');
require_once($CFG->dirroot.'/mod/project/locallib.php');
//require_once($CFG->libdir.'/completionlib.php');

$cmid       = required_param('cmid', PARAM_INT);  // Project Module ID
$mapped = optional_param('mapped', 0, PARAM_RAW);
//$id      = optional_param('id', 0, PARAM_INT); // Course Module ID

$cm = get_coursemodule_from_id('project', $cmid, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id'=>$cm->course), '*', MUST_EXIST);
$project = $DB->get_record('project', array('id'=>$cm->instance), '*', MUST_EXIST);

require_login($course, false, $cm);

$currentgroup = groups_get_activity_group($cm, true);
$members = getGroupMembers($currentgroup);
$project->currentgroup = $currentgroup;

require_course_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/project:view', $context);

$PAGE->set_url('/mod/project/history_import.php', array('cmid' => $cmid));

$PAGE->set_title($course->shortname.': '.$project->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_activity_record($project);

$history = new stdClass();
$summary = new stdClass();
$history->id         = null;
$history->cmid = $cm->id;

$mform = new history_import_form(null, array('project'=>$project, 'history'=>$history));
if(isset($_POST['map_users'])){
	//var_dump($_POST);break;
	foreach($_POST as $key=>$value){
		if((substr($key,0,11) == 'member_map-') ){
			$member_map = substr($key, 12);//Get the student id from the end of the key value
			$student = $DB->get_record('project_user_mapping', array('user_id'=>$member_map)); //Get student information from the user_map
			$student->skype = preg_replace('/_/', ' ', $value); //Replace any _ with a space
			if(!empty($student->skype)){
			//Update the skype field with the skype username
			$DB->set_field('project_user_mapping', 'skype', $student->skype, array('user_id'=>$student->user_id,'course_id'=>$student->course_id, 'group_id'=>$student->group_id));
			//update meetings attended to the first one.
			$DB->set_field('project_user_mapping', 'meetings_attended', 1, array('user_id'=>$student->user_id,'course_id'=>$student->course_id, 'group_id'=>$student->group_id));
			}//end if skype name empty
		}//end if
	}//end for each loop
	redirect("view.php?id=$cm->id");
}
// If data submitted, then process and store.
if ($mform->is_cancelled()) {
	redirect("view.php?id=$cm->id");
} else if ($data = $mform->get_data()) {
    if ($data->id) {
        // store the files
        $data->timemodified = time();
        //$data = file_postupdate_standard_editor($data, 'content', $options, $context, 'mod_project', 'task', $data->id);
        //$DB->update_record('project_task', $data);
        //$DB->set_field('project', 'revision', $project->revision+1, array('id'=>$project->id));

        //add_to_log($course->id, 'course', 'update mod', '../mod/project/view.php?id='.$cm->id, 'project '.$project->id);

    } else {
        // adding new history
        $data->historyid        = $history->id;

		$summary->project_id = $project->id;
		$summary->group_id = $project->currentgroup;
		$summary->date = time();
		$methods = array('Skype', 'Email');
		$summary->method = $methods[$data->method];

		//insert summary record
		$data->id = $DB->insert_record('project_history_imp_summary', $summary);

		//If History import is Skype
		if($summary->method=="Skype"){
		//Create a multidim array for each record in history
		$names_unique = array();
		$names_unique[''] = "";
		$last_record = array();

		//Remove SYSTEM User
		if(($key = array_search('SYSTEM', $names_unique)) !== false) {
			unset($names_unique[$key]);
		}

		foreach(preg_split("/((\r?\n)|(\r\n?))/", $data->history) as $line){ //Iterate through each line imported by skype
			//echo $line."<br/>";
			preg_match_all("/\[([^\]|\|]*)/", $line, $time); //Seperate the time from the line ( Between [ and ] ) REGEX: /\[([^\]]*)\]/
			$line = preg_replace("/\[([^\]]*)\]/", "", $line);
			if(!empty($time[1][0])){
				$time = strtotime($time[1][0]); //Save the time as a unix timestamp
			} else {
				$time = $last_record['time'];
			}

			$history->message_id = $data->id; $history->time = $time;
			//echo $data->id."  ".$time." ";

			if(substr($line,1,3)!="***" && substr($line, -3)!="***"){  //Check if message is not a system message
				preg_match("/\s(.*?):/", $line, $name); //Separate the users from the line ( Between ] and : )
				$line = preg_replace("/\s(.*?):/", "", $line);
			}
			else{
				$name[1] = "SYSTEM";
			}

			if(!empty($name[1])){
				$history->user = $name[1];
			} else {
				$history->user = $last_record['user'];
			}
			$history->message = $line;

			//Add user to small array if they are not yet in
			if(!in_array($history->user ,$names_unique)){
				$names_unique[$history->user] = $history->user ;
			}

			//Store last records of time and user incase missing from text.
			$last_record['time']=$time;
			$last_record['user']=$history->user;

		//print_r($history);break;
		$history->id = $DB->insert_record('project_history_imp_detail', $history);
		}//end for each loop

		//Remove the SYSTEM user since it will never be assigned.
		if(($key = array_search("SYSTEM", $names_unique)) !== false) {
			unset($names_unique[$key]);
		}//end if

		//Add 1 meeting attended for each user who attended a meeting, and 1 for the whole group
		foreach($names_unique as $key=>$user){
			$attended = $DB->get_record('project_user_mapping', array('skype'=>$user), 'id,group_id,meetings_attended');
			if(!$attended)
				continue;
			if(!empty($user)){
			$inc = $attended->meetings_attended;
			$DB->set_field('project_user_mapping', 'meetings_attended', ++$inc, array('skype'=>$user));
			}
		}

		$mapped = true;
		}//End if method is skype
		//If history method is email.
		elseif($summary->method=="Email") {

		$history->message_id = $data->id;
		$history->time = time();
		$history->message = $data->history;
		$history->id = $DB->insert_record('project_history_imp_detail', $history);
		//var_dump($history);

		}
        //add_to_log($course->id, 'course', 'update mod', '../mod/project/view.php?id='.$cm->id, 'project '.$project->id);
    }


	//redirect("history_import.php?cmid=$cm->id&mapped=true");

}

if($mapped){
	//Check if names are in mapped table.
	//Get mapped users
	$mapped_users = $DB->get_records('project_user_mapping', array('course_id'=>$course->id,'group_id'=>$currentgroup));

	$history->groupid = $currentgroup;
	$history->method = 'Skype';
	//If no users are returned, array is empty, fill it with group members id's.
	if(empty($mapped_users)){
		$fillempty = new stdClass();
		$fillempty->course_id = $course->id;
		$fillempty->group_id = $currentgroup;
		foreach($members as $member){
			$fillempty->user_id = $member[0];
			$fillempty->id = $DB->insert_record('project_user_mapping', $fillempty);
		}
		$mapped_users = $DB->get_records('project_user_mapping', array('course_id'=>$course->id,'group_id'=>$currentgroup)); //Recall the users
	}

	$unmapped = 0;
	foreach($mapped_users as $user){
		if(empty($user->skype)){
			$unmapped++;
		}
	}

	//Increament Total Meetings for all users after skype has been imported
	$total_meetings = $DB->get_records('project_user_mapping', array('group_id'=>$currentgroup), null, 'group_id,meetings_total', 0, 1);
	$inc_total =  $total_meetings[$currentgroup]->meetings_total;
	$DB->set_field('project_user_mapping', 'meetings_total', ++$inc_total, array('group_id'=>$currentgroup));
	//reset users meeting alert
	$DB->set_field('project_user_mapping', 'meeting_alert', 0, array('group_id'=>$currentgroup));

	//If all all mapped, redirect page, skipping form
	if(!$unmapped){
		redirect('view.php?id='.$cmid);
	}

	$user_map = array(); //Create the array
	$user_map[0] = ""; //Add Blank option
	foreach($mapped_users as $member){ //Fill the array with usernames
		$user_map[$member->user_id] = studentidToName($member->user_id);
	}//end for each

	$mapped_form = new history_map_users(null, array('group_members'=>$user_map,'convo_members'=>$names_unique,'history'=>$history));


}

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string('Chat History Import'), 2);

if($mapped){
$mapped_form->display();
}
$mform->display();

//$strlastmodified = get_string("lastmodified");
//echo "<div class=\"modified\">$strlastmodified: ".userdate($project->timemodified)."</div>";

echo $OUTPUT->footer();

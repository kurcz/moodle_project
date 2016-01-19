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
require_once($CFG->dirroot.'/mod/project/edit_form.php');
require_once($CFG->dirroot.'/mod/project/locallib.php');
//require_once($CFG->libdir.'/completionlib.php');

$cmid       = required_param('cmid', PARAM_INT);  // Project Module ID
$id      = optional_param('id', 0, PARAM_INT); // Course Module ID
$taskid        = optional_param('t', 0, PARAM_INT); //Task ID  

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

$PAGE->set_url('/mod/project/task_edit.php', array('cmid' => $cmid));

$PAGE->set_title($course->shortname.': '.$project->name);
$PAGE->set_heading($course->fullname);
$PAGE->set_activity_record($project);

if ($taskid) {
    $task = $DB->get_record('project_task', array('id'=>$taskid), '*', MUST_EXIST);
} else {
    $task = new stdClass();
    $task->id         = null;
}
$task->cmid = $cm->id;


//$options = array('noclean'=>true, 'subdirs'=>true, 'maxfiles'=>-1, 'maxbytes'=>0, 'context'=>$context);

$mform = new task_edit_form(null, array('task'=>$task, 'project'=>$project, 'members'=>$members));
// If data submitted, then process and store.
if ($mform->is_cancelled()) {
    if (empty($tasks->id)) {
        redirect("view.php?id=$cm->id");
    } else {
        redirect("view.php?id=$cm->id&taskid=$task->id");
    }
} else if ($data = $mform->get_data()) {
	//Create a string off all the members selected seperated by comma's to be stored in the members field.
	$memberslist = ""; //Create an empty string
	$num_of_members = count($data->members); //Find the number of members assigned
	$members_position = 1; //Set the first position
	foreach($data->members as $key => $value){ //Iterate through the array
		if($members_position < $num_of_members){ //If there are more members to go through add a comma
			$memberslist .= $key.",";
		}
		else { //otherwise we are at the end.
			$memberslist .= $key;
		}
		$members_position++;
	}
	$data->members = $memberslist; //Set new members property to overwrite the array to store.

    if ($data->id) {
        // store the files
        $data->timemodified = time();
        //$data = file_postupdate_standard_editor($data, 'content', $options, $context, 'mod_project', 'task', $data->id);
        $DB->update_record('project_task', $data);
        //$DB->set_field('project', 'revision', $project->revision+1, array('id'=>$project->id));

		//store the files
		/*if(!empty($data->userfile)){
			$file->itemid = $data->userfile;
			$file->task_id = $data->id;
			print_r($file);
			$DB->insert_record('project_submitted_files', $file);
		}*/
		
		if(!empty($data->comments)){
			$comment = new stdClass();
			$comment->time = time();
			$comment->task_id = $data->id;
			$comment->student_id = $USER->id;
			$comment->comment = $data->comments;

			$DB->insert_record('project_feedback', $comment);
			add_to_log($cm->course, 'project', 'comment', 'task_edit.php?id='.$cm->id, 'project '.$project->id);
		}
		
		
        add_to_log($cm->course, 'course', 'update task', 'task_edit.php?task='.$data->id, 'progress: '.$data->progress);
        $params = array(
            'context' => $context,
            'objectid' => $data->id
        );

    } else {
        // adding new task
        $data->taskid        = $task->id;
        $data->hidden        = 0;
        $data->timecreated   = time();
        $data->timemodified  = time();
        $data->importsrc     = '';
        $data->content       = '';          // updated later
        $data->contentformat = FORMAT_HTML; // updated later

		$data->id = $DB->insert_record('project_task', $data);

        // store the files
        //$data = file_postupdate_standard_editor($data, 'content', $options, $context, 'mod_project', 'task', $data->id);
        //$DB->update_record('project_task', $data);
		
		//store the files
		/*if(!empty($data->userfile)){
			$file->task_id = $data->id;
			$file->itemid = $data->userfile;
			print_r($file);
			$DB->insert_record('project_submitted_files', $file);
		}*/

        add_to_log($cm->course, 'project', 'add task', 'task_edit.php?id='.$cm->id, 'project '.$project->id);
        $params = array(
            'context' => $context,
            'objectid' => $data->id
        );
    }
	
	//Fill the progress table for previous cohorts, based on groups progressions.
	//Build an object with the current work done to be inserted into the database
	$previous = new stdClass();
	$previous->group_id = $currentgroup;
	
	$progress = explode('/', getCurrentGroupProgress($currentgroup)); //Get the current group progress that is returned by "work/time", seperate the two variables
	//Get the last value stored to find the missing progress values
	//if($count = $DB->count_records('project_previous_cohorts') != 0)
	$last_progress = $DB->get_record_sql('SELECT progress_percentage,time_percentage FROM mdl_project_previous_cohorts WHERE group_id = :group_id  ORDER BY progress_percentage DESC LIMIT 1', array('group_id' => $currentgroup));
	
	//Is this the first insert, or an update?
	if($progress[1] != $last_progress->time_percentage){ //If there a difference, does not currently exist, we insert
	//Find the incremental value if there is a gap in the work done table. 
	//This is the difference in work, divided by the difference in time since last updated.
	//echo "( ".($progress[0])." - ".$last_progress->progress_percentage.") / (".($progress[1]-1)." - ".$last_progress->time_percentage." )<br />";
	$increment = round(($progress[0]-$last_progress->progress_percentage) / (($progress[1])-$last_progress->time_percentage),2);
	$work_value = $last_progress->progress_percentage; //Set the first value
	
	for($i=$last_progress->time_percentage+1; $i < $progress[1]; $i++){
		$work_value += $increment; //Update the value with increments for each time missing.
		$previous->progress_percentage = $work_value;
		$previous->time_percentage = $i;
		$DB->insert_record('project_previous_cohorts', $previous);
	}
	
	//Set actual progress based on input
	$previous->progress_percentage = $progress[0];
	$previous->time_percentage = $progress[1];
	$DB->insert_record('project_previous_cohorts', $previous);

	}//end if
	else { //it already exists, so lets update it
		$previous->progress_percentage = $progress[0];
		$previous->time_percentage = $progress[1];
		
		$DB->set_field('project_previous_cohorts', 'progress_percentage', $previous->progress_percentage, array('time_percentage'=>$previous->time_percentage ) );
	} //end else
	
    redirect("view.php?id=$cm->id");
}

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string('Task Editing'), 2);

$mform->display();

//$strlastmodified = get_string("lastmodified");
//echo "<div class=\"modified\">$strlastmodified: ".userdate($project->timemodified)."</div>";

echo $OUTPUT->footer();

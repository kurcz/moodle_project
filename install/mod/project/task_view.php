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


$options = array('noclean'=>true, 'subdirs'=>true, 'maxfiles'=>-1, 'maxbytes'=>0, 'context'=>$context);

$mform = new task_view_form(null, array('task'=>$task, 'project'=>$project, 'members'=>$members));
// If data submitted, then process and store.
if ($mform->is_cancelled()) {
    if (empty($tasks->id)) {
        redirect("view.php?id=$cm->id");
    } else {
        redirect("view.php?id=$cm->id&taskid=$task->id");
    }
} else if ($data = $mform->get_data()) {
    if ($data->id) {
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
			add_to_log($cm->course, 'project', 'comment', 'task_edit.php?id='.$cm->id, 'project '.$project->id.' task: '.$comment->task_id);
		}
		
		
        //add_to_log($course->id, 'course', 'update mod', '../mod/project/view.php?id='.$cm->id, 'project '.$project->id);
        $params = array(
            'context' => $context,
            'objectid' => $data->id
        );

    } 
    redirect("view.php?id=$cm->id");
}

echo $OUTPUT->header();
echo $OUTPUT->heading(format_string('Task View: '.$task->name), 2);

$mform->display();

add_to_log($course->id, 'project', 'task view', 'task_view.php?id='.$cm->id, $taskid);
//$strlastmodified = get_string("lastmodified");
//echo "<div class=\"modified\">$strlastmodified: ".userdate($project->timemodified)."</div>";

echo $OUTPUT->footer();

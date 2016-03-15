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
 * Chapter edit form
 *
 * @package    mod_project
 * @copyright  2004-2010 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;
 
require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/mod/project/lib.php');

class task_edit_form extends moodleform {

    function definition() {
        global $CFG, $DB;

		$task = $this->_customdata['task'];
		$project = $this->_customdata['project'];
		$members = $this->_customdata['members'];
		
        $mform = $this->_form;
		
		$predefined_tasks = $DB->get_records('project_predefined_tasks', array('course_id'=>$project->course));
		
		/**Predefined Tasks Section**/
		if(!isset($task->name) && count($predefined_tasks)>0 && isset($_GET['pre'])){
		$mform->addElement('header', 'general', 'Predefined Tasks');
		$mform->addElement('html', 'Please select one of the following predefined tasks, or create one of your own. Hover over the link for the task description.<br /><br />');
		//List all the predefined tasks
		foreach($predefined_tasks as $pre_task){
			$mform->addElement('html', '<a href="?cmid='.$task->cmid.'&pre='.$pre_task->id.'" >'.$pre_task->name.'</a><br />');
			}
			$mform->addElement('html', '<a href="?cmid='.$task->cmid.'&pre=0" title="Create a Custom Task">Custom Task...</a><br />');
		}
		
		//Set a task if a predefined task has been selected
		if(isset($_GET['pre']) && is_numeric($_GET['pre'])){ //If a predefined task number has been set, set it up as a task to be displayed.
			$task = $DB->get_record('project_predefined_tasks', array('id'=>$_GET['pre']));
			$task->cmid = $_GET['cmid'];
		}
		
		/**Task creation form*/
        $mform->addElement('header', 'general', get_string('tasks', 'mod_project'));
		if(!isset($task->name) && count($predefined_tasks)>0 && !isset($_GET['pre'])){ //If no tasks are selected, and there are predefined tasks, show a message for users to select.
		$mform->addElement('html', 'Create a new task from scratch or <a href="?cmid='.$task->cmid.'&pre=select" >select one of '.count($predefined_tasks).' predefined tasks</a>.<br /><br />');
		}
		//$mform->addElement('select', 'predefined', 'Predefined Tasks', $predef_task, $attributes);
		//$mform->setDefault('predefined', '0');
        $mform->addElement('text', 'name', get_string('tasksname', 'mod_project'), array('size'=>'30'));

		$mform->setType('name', PARAM_RAW);
        $mform->addRule('name', null, 'required', null, 'client');

        //$mform->addElement('editor', 'description', get_string('description', 'mod_project'), null);
		$mform->addElement('textarea', 'description', get_string('description', 'mod_project'), 'wrap="virtual" rows="5" cols="50" maxlength="250"');
        $mform->setType('description', PARAM_RAW);
		
		$mform->addElement('date_selector', 'start_date', get_string('task_start_date', 'mod_project'));
		$mform->setType('start_date', PARAM_RAW);
		
		$mform->addElement('date_selector', 'end_date', get_string('task_end_date', 'mod_project'));
		$mform->setType('end_date', PARAM_RAW);
		
		
		$memberslist = array();
		foreach($members as $member){
			$memberslist[] =& $mform->createElement('checkbox', $member[0], '', $member[1]);
		}
		
		$mform->addGroup($memberslist, 'members', 'Assigned Members', array(' '), true);
		//$mform->addRule('members', 'At least one member must be selected', 'required', null, 'client'); //Removed to add it in the validation section.
		if(!empty($task->members)){
			$assigned_members = explode(",", $task->members);
			foreach($members as $member){
				if(in_array($member[0], $assigned_members)){
					$mform->setDefault('members['.$member[0].']', true);
				}
			}
		}
		
		$mform->addGroupRule('members', 'Please assigned at least one member', 'required', null, 1);
		
		$mform->addElement('text', 'hours', get_string('hours', 'mod_project'), array('size'=>'2', 'maxlength'=>'4'));
        $mform->setType('hours', PARAM_INT);
		$mform->setDefault('hours', '0');
		$mform->addRule('hours', null, 'numeric', null, 'client');
		
		$mform->addElement('text', 'progress', get_string('progress', 'mod_project'), array('size'=>'2', 'maxlength'=>'3'));
        $mform->setType('progress', PARAM_INT);
		$mform->setDefault('progress', '0');
		$mform->addRule('progress', null, 'numeric', null, 'client');
		
		$mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);

        $mform->addElement('hidden', 'project_id', $project->id);
        $mform->setType('project_id', PARAM_INT);
		
        $mform->addElement('hidden', 'group_id', $project->currentgroup);
        $mform->setType('group_id', PARAM_INT);		
		

		$this->method = 'edit';
        $this->add_action_buttons(true);
		
        // set the defaults
        $this->set_data($task);

		if(isset($task->name)){
			$mform->addElement('html', '<a href=task_edit.php?d=1&id='.$task->id.'&cmid='.$task->cmid.'>Delete Task</a>');
		}
    }

    function definition_after_data(){
        $mform = $this->_form;
    }
	
	function validation($data, $files) {
		global $CFG;
		$errors = parent::validation($data, $files);

		//Check to make sure the start date is not less than right now
		//Removed Nov 17 - Due to an issue with editing tasks at a later date.
		/*if ($data['start_date'] < time()) {
			$errors['start_date'] = get_string('starttime_error', 'mod_project');
		}*/
		
		//Check to make sure the start date is not later than the end date
		if ($data['start_date'] >= $data['end_date'] ) {
			$errors['start_date'] = get_string('endlessthanstart_error', 'mod_project');
		}
		
		///Check to verify progress is not > 100%
		if(isset($data['progress'])){
		if ($data['progress'] > 100) {
			$errors['progress'] = get_string('maxprogress_error', 'mod_project');
		}
		}
		return $errors;
	}
	
}

class predefined_tasks_edit_form extends moodleform {

    function definition() {
        global $CFG, $DB;

		$task = $this->_customdata['task'];

        $mform = $this->_form;
		
		/**Task creation form*/
        $mform->addElement('header', 'general', get_string('tasks', 'mod_project'));
        $mform->addElement('text', 'name', get_string('tasksname', 'mod_project'), array('size'=>'30'));
        
		$mform->setType('name', PARAM_RAW);
        $mform->addRule('name', null, 'required', null, 'client');

        //$mform->addElement('editor', 'description', get_string('description', 'mod_project'), null);
		$mform->addElement('textarea', 'description', get_string('description', 'mod_project'), 'wrap="virtual" rows="5" cols="50" maxlength="250"');
        $mform->setType('description', PARAM_RAW);
			
		$mform->addElement('text', 'hours', get_string('hours', 'mod_project'), array('size'=>'2', 'maxlength'=>'4'));
        $mform->setType('hours', PARAM_INT);
		$mform->setDefault('hours', '0');
		$mform->addRule('hours', null, 'numeric', null, 'client');

		$mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);

        $this->add_action_buttons(true);

        // set the defaults
        $this->set_data($task);
    }

    function definition_after_data(){
        $mform = $this->_form;
    }
		
}


class task_view_form extends moodleform {

    function definition() {
        global $CFG, $DB;

		$task = $this->_customdata['task'];
		$project = $this->_customdata['project'];
		$members = $this->_customdata['members'];
		//$attachmentoptions = $this->_customdata['attachmentoptions'];
		
        $mform = $this->_form;
		
		if(!empty($task->id)){
        $mform->addElement('header', 'general', get_string('tasks', 'mod_project'));
		$mform->addElement('html', 'Task Name: '.$task->name.'<br />Task Description: '.$task->description);
		}
		//TODO: Add Status Information
		if(isset($_GET['t'])){ //If Task already exists give status information
			$mform->addElement('header', 'status', get_string('task_status', 'mod_project'));
			
			$total_days = floor(($task->end_date - $task->start_date)/(60*60*24));  //Find the number of days
			$recommended_daily = ceil(1/$total_days*100); //Find how much % to do in a day
			$time_done = ceil((time() - $task->start_date)/(60*60*24)); //Find how many days into the task.
			$percentage_done = round($time_done / $total_days,2)*100; //Get the %
			$time_left = $task->end_date - time(); //Find the Time left
			
			//We only need to estimate the date of completion if they are not already done the task.
			if($task->progress!=100) {
				if($task->progress != 0){ //Calculate estimated due date, divide current progress by the amount of time into the task, then divide the remaining progress by that result
					$estimated_days = ceil((100-$task->progress)/round($task->progress/$time_done,2));
					$estimated_date = userdate(time() + ($estimated_days*(60*60*24)), get_string('strftimedateshort', 'langconfig')); //Display the date nicely
				}
				else //Cannot divide by zero progress, so we will set due date as estimated date.
					$estimated_date = userdate($task->end_date, get_string('strftimedateshort', 'langconfig'));;
			}
			
			if($time_left < 0) {$time_left = 0; $recommended_daily = 100- $task->progress; }
			$days_left = floor($time_left / (60*60*24));
			
			//Determine Status Message and Progress bar colour
			if($percentage_done <= $task->progress && time() < $task->end_date || $task->progress==100){
				$progress_bar_colour = '#0f0';
				if($task->progress == 100)
					$standing = 'Task Completed. Good Work!';
				else
					$standing = 'Good Standing. On track to finish on time.';
			} else {
				$progress_bar_colour = '#f00';
				$standing = 'Bad Standing. Not on track to finish on time.';
			}
			
			//Display elements on the webpage.
			$mform->addElement('html', '<div style="border: solid 1px;width: 300px;height: 10px;"><div style="background-color: '.$progress_bar_colour.';width:'.$task->progress .'%; height:10px;">&nbsp;</div><div style="position: relative;top:-10px;text-align:center;font-size:10px;font-weight:bold;">Progress: '.$task->progress.'%</div></div>');
			$mform->addElement('html', '<br />Deadline: '.userdate($task->end_date).'<br />Days In: <b>'.$time_done.'</b> <br />Time Left: <b>'.$days_left.'</b> days <br >Recommended Daily Progress: <b>'. $recommended_daily.'%</b><br />');
			if($task->progress!=100) 
				$mform->addElement('html', 'Estimated date of completion: '.$estimated_date.'<br />');
			
			$mform->addElement('html', '<br />'.$standing);
			$mform->setExpanded('status');
		}
		
		//TODO: Add File Picker to Tasks
		$mform->addElement('header', 'files', get_string('files', 'mod_project'));
		//$mform->addElement('filepicker', 'userfile', get_string('file'), null, array('accepted_types' => '*'));
		$mform->setExpanded('files');
		$mform->addElement('filemanager', 'attachment_filemanager', get_string('Attachments', 'mod_project'), null, array('accepted_types' => '*'));
        //$mform->addHelpButton('attachment_filemanager', 'attachment', 'project');
		
		//Add User Feedback Comments to the tasks.
		$mform->addElement('header', 'feedback', get_string('feedback', 'mod_project'));
		$comments = getUsersComments($task->id);
		foreach($comments as $comment) {
			$name = getStudentName($comment->student_id);
			$mform->addElement('html', userdate($comment->time, get_string('strftimedatetimeshort', 'langconfig'))." - <b>".$name[0]->username."</b>: ".$comment->comment."<br />");
		}
			
		$mform->addElement('textarea', 'comments', get_string("comments", "mod_project"), 'wrap="virtual" rows="5" cols="50"');
		$mform->setType('comments', PARAM_RAW);
			
		if(count($comments)>0)
			$mform->setExpanded('feedback');
		else
			$mform->setExpanded('feedback', false);
		
		$mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);

        $mform->addElement('hidden', 'project_id', $project->id);
        $mform->setType('project_id', PARAM_INT);
		
        $mform->addElement('hidden', 'group_id', $project->currentgroup);
        $mform->setType('group_id', PARAM_INT);		

        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);
		
		$this->add_action_buttons(true);

        // set the defaults
        $this->set_data($task);
    }

    function definition_after_data(){
        $mform = $this->_form;
    }
	
	function validation($data, $files) {

	}
	
}


class history_import_form extends moodleform {

    function definition() {
        global $CFG;
		
		$history = $this->_customdata['history'];
		$project = $this->_customdata['project'];

        $mform = $this->_form;
		
        $mform->addElement('header', 'general', 'Group Conversation');
		$mform->addElement('textarea', 'history', get_string('history', 'mod_project'), 'wrap="virtual" rows="15" cols="100" maxlength="10000"');
        $mform->setType('history', PARAM_RAW);
		
		$mform->addElement('select', 'method', get_string('method', 'mod_project'), array('Skype', 'Email'));
		
		$mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
	
        $mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);
		
		$this->add_action_buttons(true);

        // set the defaults
        $this->set_data($history);
    }

    function definition_after_data(){
        $mform = $this->_form;
    }
	
}

class history_map_users extends moodleform {
	function definition() {
		global $CFG, $DB;
		
		$history = $this->_customdata['history'];
		$convo_members = $this->_customdata['convo_members'];
		$group_members = $this->_customdata['group_members'];
		
		$member_count = count($convo_members)-1;
        $mform = $this->_form;

		//Get users from mapped table based on group id
		$users_map = $DB->get_records('project_user_mapping', array('group_id'=>$history->groupid));

		$mform->addElement('header', 'general', 'Conversation Mapping');

		//Return number of Users found
		$count_convo_members = count($convo_members)-1; //less one for the blank option
		$mform->addElement('html', 'Found '.$count_convo_members.' conversation members. Please match members of this group to their respected conversation username. <br /><br />');
		
		$mform->addElement('static', 'LabelConvo', 'Group Members', 'Conversation Members');
		foreach($users_map as $key=>$user){
		//$mform->addElement('select', 'member_map--'.$user->user_id, studentidToLMS_Name($user->user_id), $convo_members);
		//Check if Name is already saved in the database for mapping
		if(empty($user->skype)){
			//$mform->setDefault('member_map--'.$user->user_id, $user->skype);
		//}//end if
		//else if not saved, take a guess
		//else{
		$mform->addElement('select', 'member_map--'.$user->user_id, studentidToLMS_Name($user->user_id), $convo_members);
			$user_sort = array();
			foreach($convo_members as $key=>$convo_member){
				$user_sort[$key] = similar_text(studentidToLMS_Name($user->user_id), $convo_member, $similar);
				if($similar >= 60) //If the probability of the chat user is >= 60% similar to a student name, set it
					$mform->setDefault('member_map--'.$user->user_id, $key);
			}//end foreach
		}//end else
		}//end foreach
		
		$mform->addElement('hidden', 'cmid');
        $mform->setType('cmid', PARAM_INT);
		
		$mform->addElement('hidden', 'map_users');
		$mform->setType('map_users', PARAM_INT);
		$mform->setDefault('map_users', true);
		
		$mform->addElement('hidden', 'method');
		$mform->setType('method', PARAM_RAW);
		
		$mform->addElement('hidden', 'groupid');
        $mform->setType('groupid', PARAM_RAW);
	
	    $this->set_data($history);
		
		$this->add_action_buttons(true);
	}
}
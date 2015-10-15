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
 * project configuration form
 *
 * @package    mod
 * @subpackage project
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

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

/// Check to see if groups are being used here
$groupmode = groups_get_activity_groupmode($cm);
$currentgroup = groups_get_activity_group($cm, true);


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
$html = "<a href='../chat/index.php?id=3'>Open Chat</a><br />";
$html .= "<table border=1><tr><td style='vertical-align:top;'><u>List of Tasks</u><br /><br />+ <a href='task_create.php'>NEW</a><br /><br />";
	foreach($tasks as $task){
		$name = getStudentName($task->members);
		$html .= "&nbsp;&nbsp;- ".$task->name ." For: ";
			$name_size = count($name);
			$name_count = 0;
			foreach($name as $assigned_name){
				$html .= "<b>".$assigned_name->username."</b>";
				if(($name_size-1) != $name_count)
					$html .= ", ";
				$name_count++;
			}
		$html .= " Due: ".userdate($task->end_date);
		$html .= "<br />";
	}
$html .= "</td><td style='vertical-align:top;'><td><td><u>Group Members</u><br /><br />"; 
	for($i = 0; $i< count($members); $i++){
		if((time() - $members[$i][2]) <= 100){
			$html .= $members[$i][1]." <font style='color:green;font-weight:bold;'>Online Now</font><br />";
		}
		else {
			$html .= $members[$i][1]." Last Online: ".userdate($members[$i][2])." <br />";
		}
	}
$html .="<br/><br /><u>Chat History</u><br/></td></tr></table>";


$content = $html;
//$content = format_text($content, $project->contentformat, $formatoptions);
echo $OUTPUT->box($content, "generalbox center clearfix");

//$strlastmodified = get_string("lastmodified");
//echo "<div class=\"modified\">$strlastmodified: ".userdate($project->timemodified)."</div>";

echo $OUTPUT->footer();
/*
require_once($CFG->dirroot.'/course/moodleform_mod.php');
require_once($CFG->dirroot.'/mod/project/locallib.php');
require_once($CFG->libdir.'/filelib.php');

class mod_project_task_mod_form extends moodleform_mod {
    function definition() {
        global $CFG, $DB;

        $mform = $this->_form;

        $config = get_config('project');

        //-------------------------------------------------------
        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'), array('size'=>'48'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $this->add_intro_editor($config->requiremodintro);


        //-------------------------------------------------------
        $mform->addElement('header', 'appearancehdr', get_string('appearance'));

        if ($this->current->instance) {
            $options = resourcelib_get_displayoptions(explode(',', $config->displayoptions), $this->current->display);
        } else {
            $options = resourcelib_get_displayoptions(explode(',', $config->displayoptions));
        }
        if (count($options) == 1) {
            $mform->addElement('hidden', 'display');
            $mform->setType('display', PARAM_INT);
            reset($options);
            $mform->setDefault('display', key($options));
        } else {
            $mform->addElement('select', 'display', get_string('displayselect', 'project'), $options);
            $mform->setDefault('display', $config->display);
        }

        if (array_key_exists(RESOURCELIB_DISPLAY_POPUP, $options)) {
            $mform->addElement('text', 'popupwidth', get_string('popupwidth', 'project'), array('size'=>3));
            if (count($options) > 1) {
                $mform->disabledIf('popupwidth', 'display', 'noteq', RESOURCELIB_DISPLAY_POPUP);
            }
            $mform->setType('popupwidth', PARAM_INT);
            $mform->setDefault('popupwidth', $config->popupwidth);

            $mform->addElement('text', 'popupheight', get_string('popupheight', 'project'), array('size'=>3));
            if (count($options) > 1) {
                $mform->disabledIf('popupheight', 'display', 'noteq', RESOURCELIB_DISPLAY_POPUP);
            }
            $mform->setType('popupheight', PARAM_INT);
            $mform->setDefault('popupheight', $config->popupheight);
        }

        $mform->addElement('advcheckbox', 'printintro', get_string('printintro', 'project'));
        $mform->setDefault('printintro', $config->printintro);

        // add legacy files flag only if used
        if (isset($this->current->legacyfiles) and $this->current->legacyfiles != RESOURCELIB_LEGACYFILES_NO) {
            $options = array(RESOURCELIB_LEGACYFILES_DONE   => get_string('legacyfilesdone', 'project'),
                             RESOURCELIB_LEGACYFILES_ACTIVE => get_string('legacyfilesactive', 'project'));
            $mform->addElement('select', 'legacyfiles', get_string('legacyfiles', 'project'), $options);
            $mform->setAdvanced('legacyfiles', 1);
        }

        //-------------------------------------------------------
        $this->standard_coursemodule_elements();

        //-------------------------------------------------------
        $this->add_action_buttons();

        //-------------------------------------------------------
        $mform->addElement('hidden', 'revision');
        $mform->setType('revision', PARAM_INT);
        $mform->setDefault('revision', 1);
    }

    function data_preprocessing(&$default_values) {
        if ($this->current->instance) {
            $draftitemid = file_get_submitted_draft_itemid('project');
            $default_values['project']['format'] = $default_values['contentformat'];
            $default_values['project']['text']   = file_prepare_draft_area($draftitemid, $this->context->id, 'mod_project', 'content', 0, project_get_editor_options($this->context), $default_values['content']);
            $default_values['project']['itemid'] = $draftitemid;
        }
        if (!empty($default_values['displayoptions'])) {
            $displayoptions = unserialize($default_values['displayoptions']);
            if (isset($displayoptions['printintro'])) {
                $default_values['printintro'] = $displayoptions['printintro'];
            }
            if (!empty($displayoptions['popupwidth'])) {
                $default_values['popupwidth'] = $displayoptions['popupwidth'];
            }
            if (!empty($displayoptions['popupheight'])) {
                $default_values['popupheight'] = $displayoptions['popupheight'];
            }
        }
    }
}

*/
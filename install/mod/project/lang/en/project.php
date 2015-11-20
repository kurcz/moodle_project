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
 * Strings for component 'page', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package   page
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['modulename'] = 'Project';
$string['description'] = 'Description';
$string['task_start_date'] = 'Task Start Date';
$string['task_end_date'] = 'Task End Date';
$string['progress'] = 'Progress %';
$string['hours'] = 'Estimated Hours:';
$string['contentheader'] = 'Content';
$string['tasks'] = 'Tasks';
$string['tasksname'] = 'Task Name';
$string['files'] = 'Files';
$string['task_status'] = 'Task Status';
$string['feedback'] = 'Feedback';
$string['comments'] = 'Comments';
$string['history'] = 'Paste History';
$string['method'] = 'Method';
$string['predefined'] = 'Predefined Task';


$string['modulename_help'] = 'The page module enables a teacher to create a web page resource using the text editor. A page can display text, images, sound, video, web links and embedded code, such as Google maps.

Advantages of using the page module rather than the file module include the resource being more accessible (for example to users of mobile devices) and easier to update.

For large amounts of content, it\'s recommended that a book is used rather than a page.

A page may be used

* To present the terms and conditions of a course or a summary of the course syllabus
* To embed several videos or sound files together with some explanatory text';
$string['modulename_link'] = 'mod/project/view';
$string['modulenameplural'] = 'Projects';
$string['neverseen'] = 'Never seen';
$string['optionsheader'] = 'Display options';
$string['page-mod-page-x'] = 'Any page module page';
$string['page:addinstance'] = 'Add a new page resource';
$string['page:view'] = 'View page content';
$string['pluginadministration'] = 'Page module administration';
$string['pluginname'] = 'Project';
$string['popupheight'] = 'Pop-up height (in pixels)';
$string['popupheightexplain'] = 'Specifies default height of popup windows.';
$string['popupwidth'] = 'Pop-up width (in pixels)';
$string['popupwidthexplain'] = 'Specifies default width of popup windows.';
$string['printintro'] = 'Display Project description';
$string['printintroexplain'] = 'Display page description above content?';
$string['configdisplayoptions'] = 'Select all options that should be available, existing settings are not modified. Hold CTRL key to select multiple fields.';

//Admin Config Settings
$string['prevcohortalertsfreq'] = 'Previous Cohort Alert Frequency';
$string['prevcohortalertsfreqexplain'] = 'Specifies the duration between group alert popups for at-risk progress. (in minutes)';

//$string['alertsfreq'] = 'Group Alert Frequency';
//$string['alertsfreqexplain'] = 'Specifies the duration between group alert popups. (in minutes)';
$string['lowchatalertsfreq'] = 'Low Chat Alert Frequency';
$string['lowchatalertsfreqexplain'] = 'Specifies the message duration between low participation alerts. (in minutes)';
$string['highchatalertsfreq'] = 'High Chat Alert Frequency';
$string['highchatalertsfreqexplain'] = 'Specifies the message duration between high participation alerts. (in minutes)';
$string['lowforumalertsfreq'] = 'Low Forum Alert Frequency';
$string['lowforumalertsfreqexplain'] = 'Specifies the low participation pop-up alert duration for forum participation. (in days)';
$string['highforumalertsfreq'] = 'High Forum Alert Frequency';
$string['highforumalertsfreqexplain'] = 'Specifies the high participation pop-up alert duration for forum participation. (in days)';
$string['lowimportalertsfreq'] = 'Low Imported Skype Alert Frequency';
$string['lowimportalertsfreqexplain'] = 'Specifies the low participation pop-up alert duration for Imported Skype participation. (in days)';
$string['highimportalertsfreq'] = 'High Imported Skype Alert Frequency';
$string['highimportalertsfreqexplain'] = 'Specifies the high participation popup alert duration for Imported Skype participation. (in days)';

$string['cronrunfreq'] = 'Cron job run Frequency.';
$string['cronrunfreqexplain'] = 'Specifies the Frequency to determine finished courses. (in days)';


$string['smallmsg'] = 'Small Message Threshold';
$string['smallmsgexplain'] = 'The percentage that determines when messages are of small size (Not included in participation analysis). ';
$string['largemsg'] = 'Large Message Threshold';
$string['largemsgexplain'] = 'The percentage that determines the when messages are of large size';
$string['lowthreshold'] = 'Low Participation Threshold';
$string['lowthresholdexplain'] = 'The percentage that determines when a user falls below recommended participation levels.';
$string['highthreshold'] = 'High Participation Threshold';
$string['highthresholdexplain'] = 'The percentage that determines when a user exceeds above recommended participation levels.';



//Error Strings
$string['starttime_error']= 'Start date must be greater than today\'s date.';
$string['endlessthanstart_error'] = 'End date must be greater than start date.';
$string['maxprogress_error'] = 'Progress cannot be greater than 100%.';
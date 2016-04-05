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
 * Page module admin settings and defaults
 *
 * @package    mod
 * @subpackage page
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    require_once("$CFG->libdir/resourcelib.php");

    $displayoptions = resourcelib_get_displayoptions(array(RESOURCELIB_DISPLAY_OPEN, RESOURCELIB_DISPLAY_POPUP));
    $defaultdisplayoptions = array(RESOURCELIB_DISPLAY_OPEN);

    //--- Alerts settings -----------------------------------------------------------------------------------
	
	$settings->add(new admin_setting_heading('alerts', 'Alert Settings', 'Set the time for adaptive recommendation alerts'));
	//$settings->add(new admin_setting_configtext('project/alertsfreq', get_string('alertsfreq', 'project'), get_string('alertsfreqexplain', 'project'), 60, PARAM_INT, 3));
	$settings->add(new admin_setting_configtext('project/prevcohortalertsfreq', get_string('prevcohortalertsfreq', 'project'), get_string('prevcohortalertsfreqexplain', 'project'), 60, PARAM_INT, 3));
	$settings->add(new admin_setting_configtext('project/lowchatalertsfreq', get_string('lowchatalertsfreq', 'project'), get_string('lowchatalertsfreqexplain', 'project'), 10, PARAM_INT, 3));	
	$settings->add(new admin_setting_configtext('project/highchatalertsfreq', get_string('highchatalertsfreq', 'project'), get_string('highchatalertsfreqexplain', 'project'), 20, PARAM_INT, 3));	
	$settings->add(new admin_setting_configtext('project/lowforumalertsfreq', get_string('lowforumalertsfreq', 'project'), get_string('lowforumalertsfreqexplain', 'project'), 1, PARAM_INT, 3));
	$settings->add(new admin_setting_configtext('project/highforumalertsfreq', get_string('highforumalertsfreq', 'project'), get_string('highforumalertsfreqexplain', 'project'), 3, PARAM_INT, 3));
	$settings->add(new admin_setting_configtext('project/lowimportalertsfreq', get_string('lowimportalertsfreq', 'project'), get_string('lowimportalertsfreqexplain', 'project'), 1, PARAM_INT, 3));
	$settings->add(new admin_setting_configtext('project/highimportalertsfreq', get_string('highimportalertsfreq', 'project'), get_string('highimportalertsfreqexplain', 'project'), 7, PARAM_INT, 3));
	
	$settings->add(new admin_setting_configtext('project/cronrunfreq', get_string('cronrunfreq', 'project'), get_string('cronrunfreqexplain', 'project'), 7, PARAM_INT, 3));		
	
	$settings->add(new admin_setting_heading('thresholds', 'Thresholds', 'Set the thresholds that will activate the adaptive recommendations'));
	$settings->add(new admin_setting_configtext('project/smallmsg', get_string('smallmsg', 'project'), get_string('smallmsgexplain', 'project'), 50, PARAM_RAW, 3));		
	$settings->add(new admin_setting_configtext('project/largemsg', get_string('largemsg', 'project'), get_string('largemsgexplain', 'project'), 150, PARAM_RAW, 3));		
	$settings->add(new admin_setting_configtext('project/lowthreshold', get_string('lowthreshold', 'project'), get_string('lowthresholdexplain', 'project'), 75, PARAM_RAW, 3));		
	$settings->add(new admin_setting_configtext('project/highthreshold', get_string('highthreshold', 'project'), get_string('highthresholdexplain', 'project'), 125, PARAM_RAW, 3));	

}

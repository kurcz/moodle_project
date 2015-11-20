<?php
/*
Moodle Project Uninstall Script
Users will place the moodle_project folder in the core main directory, DROP from there the install script will copy
and replace existing core files to allow the use of the moodle project plug-in. 
*/
global $CFG;
require_once ('../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/filelib.php');

ob_implicit_flush(true);
echo 'Moodle Project Uninstall Script<br /><br />';

$ils_dir = getcwd();
chdir('..');
$moodle_dir = getcwd();
//echo 'moodle folder '.$moodle_dir.' <br>';
chdir($auth_dir);

//Verify Backup folder exists

if(!is_dir('backup')){
	die('ERROR: Backup folder does not exist...<br>');
}

echo 'Replacing existing core files...<br><br>';
$path = realpath('backup');
$objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($ils_dir."/backup"), RecursiveIteratorIterator::SELF_FIRST);
foreach($objects as $name => $object){
	if(file_exists($moodle_dir . DIRECTORY_SEPARATOR . $objects->getSubPathName())){
    		unlink($moodle_dir . DIRECTORY_SEPARATOR . $objects->getSubPathName());
    		echo $moodle_dir . DIRECTORY_SEPARATOR . $objects->getSubPathName()."<br>";
    		}
  if($object->isDir()){
  	mkdir($moodle_dir . DIRECTORY_SEPARATOR . $objects->getSubPathName());
  } else {
  	if(copy($ils_dir."/backup" . DIRECTORY_SEPARATOR . $objects->getSubPathName(), $moodle_dir . DIRECTORY_SEPARATOR . $objects->getSubPathName()))
  		echo "<b>".$ils_dir."/backup" . DIRECTORY_SEPARATOR . $objects->getSubPathName() ."</b><br>";
  }
}

ob_flush();
sleep(1); 
echo '<br>Done.<br>';


redirect($CFG->wwwroot.'/admin/plugins.php?sesskey='.sesskey().'&uninstall=mod_project&confirm=0&return=overview');

?>
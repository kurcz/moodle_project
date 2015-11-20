<?php
/*
Moodle Project Install Script
Users will place the moodle_project folder in the core main directory, from there the install script will copy
and replace existing core files to allow the use of the Plug-in. 
*/

global $CFG;
require_once ('../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/filelib.php');

ob_implicit_flush(true);
echo 'Moodle Project Plug-in Installation Script<br /><br />';

$ils_dir = getcwd();
chdir('..');
$moodle_dir = getcwd();
//echo 'moodle folder '.$moodle_dir.' <br>';
chdir($ils_dir);


//Create a backup folder to allow uninstall

if(!is_dir('backup')){
echo 'Creating Backup Folder...<br>';
$structure = "backup";
chmod($moodle_dir, 0777);
if (!mkdir($structure, 0777)) {
	if(!chdir('backup')) {
	    echo 'ERROR: Backup folder could not be created...<br>';
		}
	}
}
chdir('backup');
$backup_dir = getcwd();
//echo $backup_dir;
chdir('..');
echo 'Backing up existing core files...<br><br>';
$path = realpath('install');
$objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($ils_dir."/install"), RecursiveIteratorIterator::SELF_FIRST);
foreach($objects as $name => $object){
    if($object->isDir()){
    	mkdir($backup_dir . DIRECTORY_SEPARATOR . $objects->getSubPathName());
    } else {
    	copy($moodle_dir . DIRECTORY_SEPARATOR . $objects->getSubPathName(), $backup_dir . DIRECTORY_SEPARATOR . $objects->getSubPathName());
    }
}

ob_flush();
sleep(2);
echo 'Installing files...<br><br>';
//Copy Files from install folder and replace existing files
$path = realpath('install');
$objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($ils_dir."/install"), RecursiveIteratorIterator::SELF_FIRST);
foreach($objects as $name => $object){
	if(file_exists($moodle_dir . DIRECTORY_SEPARATOR . $objects->getSubPathName())){
    		//unlink($moodle_dir . DIRECTORY_SEPARATOR . $objects->getSubPathName());
    		echo $moodle_dir . DIRECTORY_SEPARATOR . $objects->getSubPathName() . "<br>";
    }
    if($object->isDir()){
    	mkdir($moodle_dir . DIRECTORY_SEPARATOR . $objects->getSubPathName());
    	chmod($moodle_dir . DIRECTORY_SEPARATOR . $objects->getSubPathName(), 0755);
    } else {
    	copy($ils_dir."/install" . DIRECTORY_SEPARATOR . $objects->getSubPathName(), $moodle_dir . DIRECTORY_SEPARATOR . $objects->getSubPathName());
    		echo "<b>".$moodle_dir . DIRECTORY_SEPARATOR . $objects->getSubPathName() . "</b><br>";
    }
}

ob_flush();
sleep(1); 
echo '<br>Done.<br>';
redirect($CFG->wwwroot);

?>
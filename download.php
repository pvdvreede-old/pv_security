<?php
/**
 * This file is used to shield files from unauthorised users
 */

if (!isset ($_GET['filename']))
    die ('Must supply a filename.');

$filename = $_GET['filename'];

require_once(dirname(__FILE__) . '/../../../wp-load.php');

if (is_user_logged_in()) {
    
    // If the user is logged in they can download any files, so dont check anymore.
    
    // get the uploads path to attach to the filename
    $middle_path = get_option('upload_path');
    
    $full_filename = ABSPATH . $middle_path . $filename;
    
    header("Pragma: public"); // required 
    header("Expires: 0"); 
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0"); 
    header("Cache-Control: private",false); // required for certain browsers 
    header("Content-Type: application/force-download"); 
    header("Content-Disposition: attachment; filename=\"".basename($filename)."\";" ); 
    header("Content-Transfer-Encoding: binary"); 
    header("Content-Length: ".filesize($full_filename)); 
    readfile("$full_filename");    
} else {
    header('Location: '. get_bloginfo('url') . '/wp-login.php');
}



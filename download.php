<?php

/**
 * This file is used to shield files from unauthorised users
 */
if (!isset($_GET['filename']))
    die('Must supply a filename.');

$filename = $_GET['filename'];

require_once(dirname(__FILE__) . '/../../../wp-load.php');

if (is_user_logged_in() || (!is_user_logged_in() && is_file_secure($filename))) {

    // If the user is logged in they can download any files, so dont check anymore.
    // get the uploads path to attach to the filename
    $middle_path = get_option('upload_path');

    $full_filename = ABSPATH . $middle_path . $filename;

    // if its an image file then we want to display it, not download it, so change the header
    $extension = end(explode(".", basename($filename)));

    $image_extensions = array(
        'png',
        'jpg',
        'gif'
    );
     
    header("Pragma: public"); // required 
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: private", false); // required for certain browsers 

    if (in_array($extension, $image_extensions)) {
        header("Content-Type: image/". $extension);
    } else {
        header("Content-Type: application/force-download");
        header("Content-Disposition: attachment; filename=\"" . basename($filename) . "\";");
        header("Content-Transfer-Encoding: binary");
    }
    header("Content-Length: " . filesize($full_filename));

    readfile("$full_filename");
} else {
    // If the user isnt logged in, then redirect the site to the login page.
    header('Location: ' . get_bloginfo('url') . '/wp-login.php');
}

function is_file_secure($filename) {
    global $wpdb;
    
    $sql = $wpdb->prepare("select COUNT(*) as count
                            from wp_posts p
                            inner join wp_posts a on p.ID = a.post_parent
                            inner join wp_postmeta pm on a.ID = pm.post_id
                                    and pm.meta_key = '_wp_attached_file'
                            left join wp_pvs_user_item pvs on p.ID = pvs.object_id
                            where 1=1
                            and pvs.object_id is null
                            and pm.meta_value = '$filename';");
    
    $count = $wpdb->get_var($sql);
    
    return $count > 0;
    
}



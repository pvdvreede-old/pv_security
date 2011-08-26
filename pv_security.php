<?php

/*
Plugin Name: pv_Security
Plugin URI: http://www.vdvreede.net
Description: Adds user role security to posts and categories.
Version: 0.5
Author: Paul Van de Vreede
Author URI: http://www.vdvreede.net
License: GPL2
*/

register_activation_hook(__FILE__, 'pvs_install');
register_deactivation_hook(__FILE__, 'pvs_uninstall');
 
add_action('add_meta_boxes', 'pvs_add_post_meta_box');
 
function pvs_install () {
    global $wpdb;
    
    $table_name = $wpdb->prefix . "pvs_user_item"; 

    $sql = "CREATE TABLE " . $table_name . " (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      user_id mediumint(9) NOT NULL,
      item_id mediumint(9) NOT NULL,
      item_type varchar(25) NOT NULL
      created_date datetime NOT NULL,
      modified_date datetime NULL,
	  PRIMARY KEY  id (id)
	);";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function pvs_uninstall() {   
    global $wpdb;
    
    $table_name = $wpdb->prefix . "pvs_user_item";
    
    $sql = "DROP TABLE " . $table_name . ";"  
    
}

function pvs_add_post_meta_box() {
 
    add_meta_box('pv_document_items', 'Post Security', 'pvs_render_post_security_meta_box');
    
}

function pvs_render_post_security_meta_box($post) {
    global $wp_roles;
    
    // Use nonce for verification
    wp_nonce_field(plugin_basename(__FILE__), 'pv_security_noncename');
    
    foreach ($wp_roles->get_names() as $role) {
    
        $output = '<p>{$role}<input type="checkbox" name="pv_security_role[]" /></p>';
    
    }
    
    echo $output;
    
}
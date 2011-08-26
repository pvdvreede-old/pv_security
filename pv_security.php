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

DEFINE( 'PV_SECURITY_TABLENAME', $wpdb->prefix . 'pvs_user_item' );

register_activation_hook(__FILE__, 'pvs_install');
register_deactivation_hook(__FILE__, 'pvs_uninstall');
 
add_action('add_meta_boxes', 'pvs_add_post_meta_box');
add_action('save_post', 'pvs_save_post_security_data');

add_filter( 'posts_join', 'pvs_join_security' );
add_filter( 'posts_where', 'pvs_where_security' );
 
function pvs_install () {
    global $wpdb;
 

    $sql = "CREATE TABLE " . PV_SECURITY_TABLENAME . " (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      role mediumint(9) NOT NULL,
      object_id mediumint(9) NOT NULL,
      object_type varchar(25) NOT NULL
      created_date datetime NOT NULL,
      modified_date datetime NULL,
	  PRIMARY KEY  id (id)
	);";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function pvs_uninstall() {   
    global $wpdb;
    
    $sql = $wpdb->prepare( "DROP TABLE " . PV_SECURITY_TABLENAME . ";" ); 
    
    $wpdb->query($sql);
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

function pvs_save_post_security_data($post_id) {
    
    
    
}

function pvs_save_post_security($object_id, $role, $object_type) {
    global $wpdb;
    
    $wpdb->insert( PV_SECURITY_TABLENAME, array(
            'object_id' => $object_id,
            'role' => $role,
            'object_type' => $object_type
        ));
                           
}
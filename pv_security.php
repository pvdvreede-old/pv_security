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

register_activation_hook(__FILE__, 'pvs_install' );
register_deactivation_hook(__FILE__, 'pvs_uninstall' );
 
add_action( 'add_meta_boxes', 'pvs_add_post_meta_box' );
add_action( 'save_post', 'pvs_save_post_security_data' );

add_action( 'deleted_post', 'pvs_delete_post_security_data' );

add_filter( 'posts_join', 'pvs_join_security' );
 
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

function pvs_render_post_security_meta_box( $post ) {
    global $wp_roles;
    
    // Use nonce for verification
    wp_nonce_field(plugin_basename(__FILE__), 'pv_security_noncename');
    
    foreach ($wp_roles->get_names() as $role) {
    
        $output = '<p>{$role}<input type="checkbox" name="pv_security_role[] value="{$role}" /></p>';
    
    }
    
    echo $output;   
}

function pvs_save_post_security_data( $post_id ) {
    
    // verify if this is an auto save routine. 
    // If it is our form has not been submitted, so we dont want to do anything
    if (defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE)
        return;

    if (!wp_verify_nonce( $_POST['pv_security_noncename'], plugin_basename( __FILE__ ) ) )
        return;
    
    foreach ( $_POST['pv_security_role'] as $role ) {
    
        pvs_save_post_security( $post_id, $role, 'post' );
    
    }
      
}

function pvs_delete_post_security_data( $post_id ) {
    global $wpdb;
    
    $sql = "DELETE FROM " . PV_SECURITY_TABLENAME . " WHERE " . PV_SECURITY_TABLENAME . ".object_id = " . $post_id ." ";
    $sql .= "AND " . PV_SECURITY_TABLENAME . ".object_type = 'post' ";
    
    $sql = $wpdb->prepare( $sql );
    
    $wpdb->query( $sql );
}

function pvs_save_post_security( $object_id, $role, $object_type ) {
    global $wpdb;
    
    $wpdb->insert( PV_SECURITY_TABLENAME, array(
            'object_id' => $object_id,
            'role' => $role,
            'object_type' => $object_type,
            'created_date' => date( 'Y-m-d H:m:s' )
        ));
                           
}

function pvs_join_security( $join ) {
    global $wpdb;
    
    if ( is_user_logged_in() ) {
    
        $current_user = wp_get_current_user();
        $role = $current_user->roles[0];
    
    } else  {
        
        $role = 'anonymous';
        
    }
    
    $join .= " RIGHT JOIN " . PV_SECURITY_TABLENAME . " ON " . $wpdb->posts . ".ID = " . PV_SECURITY_TABLENAME .".object_id ";
    $join .= "AND " . PV_SECURITY_TABLENAME . ".object_type = 'post' ";
    $join .= "AND " . PV_SECURITY_TABLENAME . ".role = '" . $role . "' ";
   
    return $join;
}


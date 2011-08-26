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
global $wpdb;

DEFINE('PV_SECURITY_TABLENAME', $wpdb->prefix . 'pvs_user_item');

register_activation_hook(__FILE__, 'pvs_install');

add_action('add_meta_boxes', 'pvs_add_post_meta_box');
add_action('save_post', 'pvs_save_post_security_data');

add_action('deleted_post', 'pvs_delete_post_security_data');

add_filter('posts_join', 'pvs_join_security');

function pvs_install() {
    global $wpdb;

    $sql = "CREATE TABLE " . PV_SECURITY_TABLENAME . " (
      ID mediumint(9) NOT NULL AUTO_INCREMENT,
      role varchar(25) NOT NULL,
      object_id mediumint(9) NOT NULL,
      object_type varchar(25) NOT NULL,
      created_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
      modified_date datetime NULL,
	  PRIMARY KEY  id (ID)
	);";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

function pvs_uninstall() {
    global $wpdb;

    $sql = $wpdb->prepare("DROP TABLE " . PV_SECURITY_TABLENAME . ";");

    $wpdb->query($sql);
}

function pvs_add_post_meta_box() {

    $types = array(
        'post',
        'page',
        'pv_document'
    );

    foreach ($types as $type) {

        add_meta_box('pv_security_roles', 'Post Security', 'pvs_render_post_security_meta_box', $type, 'side', 'high');
    }
}

function pvs_render_post_security_meta_box($post) {

    // Use nonce for verification
    wp_nonce_field(plugin_basename(__FILE__), 'pv_security_noncename');

    $output = '<p>Select which user roles can see this post. Not selecting any means the whole world can see it.</p>';

    $roles = array(
        'public',
        'members'
    );

    foreach ($roles as $role) {
        $output .= '<p><input type="radio" name="pv_security_role" value="' . $role . '" />  ' . ucfirst($role) . '</p>';
    }

    echo $output;
}

function pvs_save_post_security_data($post_id) {

    // verify if this is an auto save routine. 
    // If it is our form has not been submitted, so we dont want to do anything
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
        return;

    if (!wp_verify_nonce($_POST['pv_security_noncename'], plugin_basename(__FILE__)))
        return;

    if ($_POST['pv_security_role'] == 'members')
        pvs_save_post_security($post_id, 'members', 'post');
}

function pvs_delete_post_security_data($post_id) {
    global $wpdb;

    $sql = "DELETE FROM " . PV_SECURITY_TABLENAME . " as pvs WHERE pvs.object_id = " . $post_id . " ";
    $sql .= "AND pvs.object_type = 'post' ";

    $sql = $wpdb->prepare($sql);

    $wpdb->query($sql);
}

function pvs_save_post_security($object_id, $role, $object_type) {
    global $wpdb;

    $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . PV_SECURITY_TABLENAME . " as pvs WHERE pvs.object_id = " . $object_id . " AND pvs.object_type = 'post'"));

    if ($count == 0) {
        $wpdb->insert(PV_SECURITY_TABLENAME, array(
            'object_id' => $object_id,
            'role' => $role,
            'object_type' => $object_type,
            'created_date' => date('Y-m-d H:m:s')
        ));
    }
}

function pvs_join_security($join) {
    global $wpdb;

    if (!is_user_logged_in()) {
        $join .= " RIGHT JOIN " . PV_SECURITY_TABLENAME . " pvs ON " . $wpdb->posts . ".ID = pvs.object_id ";
        $join .= "AND pvs.object_type = 'post' ";
    }

    return $join;
}


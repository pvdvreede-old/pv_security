<?php

/*
  Plugin Name: pv_Security
  Plugin URI: http://www.vdvreede.net
  Description: A brief description of the Plugin.
  Version: 0.5
  Author: Paul Van de Vreede
  Author URI: http://www.vdvreede.net
  License: A "Slug" license name e.g. GPL2
 */
 
 
 
 
 
 
function pvs_install () {
    global $wpdb;
    
    $table_name = $wpdb->prefix . "pvs_user_post"; 

    $sql = "CREATE TABLE " . $table_name . " (
      id mediumint(9) NOT NULL AUTO_INCREMENT,
      user_id mediumint(9) NOT NULL,
      item_id mediumint(9) NOT NULL,
      item_type varchar(25) NOT NULL
      created_date datetime NOT NULL,
      modified_date datetime NULL,
	  UNIQUE KEY id (id)
	);";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
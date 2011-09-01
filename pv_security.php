<?php
/*
  Plugin Name: Security
  Plugin URI: http://www.vdvreede.net
  Description: Adds user role security to posts and categories.
  Version: 0.5
  Author: Paul Van de Vreede
  Author URI: http://www.vdvreede.net
 */
global $wpdb;

DEFINE('PV_SECURITY_TABLENAME', $wpdb->prefix . 'pvs_user_item');

register_activation_hook(__FILE__, 'pvs_install');
register_uninstall_hook(__FILE__, 'pvs_uninstall');

add_action('add_meta_boxes', 'pvs_add_post_meta_box');
add_action('save_post', 'pvs_save_post_security_data');

add_action('deleted_post', 'pvs_delete_post_security_data');

add_action('admin_menu', 'pvs_add_settings_page');
add_action('admin_init', 'pvs_init_settings');

add_filter('get_terms', 'pvs_filter_categories');
add_filter('posts_join', 'pvs_join_security');
add_filter('posts_where', 'pvs_where_security');
add_filter('list_cats_exlusions', 'pvs_exclude_categories');

function pvs_install() {
    global $wpdb;

    $sql = "CREATE TABLE " . PV_SECURITY_TABLENAME . " (
      ID mediumint(9) NOT NULL AUTO_INCREMENT,
      role varchar(25) NOT NULL,
      object_id mediumint(9) NOT NULL,
      object_type varchar(25) NOT NULL,
      created_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
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

function pvs_add_settings_page() {
    add_options_page('Security', 'Security', 'administrator', __FILE__, 'pvs_options_page');
}

function pvs_init_settings() {
    register_setting('pv_security_options', 'pv_security_options');
    add_settings_section('main_section', 'Main Settings', 'pvs_section_text', __FILE__);
    add_settings_field('pvs_post_types', 'Post types', 'pvs_post_type_setting', __FILE__, 'main_section');
}

function pvs_section_text() {
  
}

function pvs_post_type_setting() {
    $options = get_option('pv_security_options');

    $types = get_post_types(array('_builtin' => false));
    $types[] = 'post';
    $types[] = 'page';

    foreach ($types as $type) {

        $checked = '';

        if (in_array($type, $options))
            $checked = 'checked';

        echo "<p><input type='checkbox' name='pv_security_options[]' value='{$type}' {$checked} />  " . ucwords($type) . "</p>";
    }
}

function pvs_options_page() {
    ?>
    <div class="wrap">
        <div class="icon32" id="icon-options-general"><br></div>
        <h2>Security Settings</h2>
        Settings for security around post and attachment visibility and access.
        <form action="options.php" method="post">
            <?php settings_fields('pv_security_options'); ?>
            <?php do_settings_sections(__FILE__); ?>
            <p class="submit">
                <input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes'); ?>" />
            </p>
        </form>
    </div>
    <?php
}

function pvs_add_post_meta_box() {

    $types = get_option('pv_security_options');

    foreach ($types as $type) {
        add_meta_box('pv_security_roles', 'Post Security', 'pvs_render_post_security_meta_box', $type, 'side', 'high');
    }
}

function pvs_render_post_security_meta_box($post) {

    // Use nonce for verification
    wp_nonce_field(plugin_basename(__FILE__), 'pv_security_noncename');

    $output = '<p>Select whether this post is public (the whole world can see it) or for members only (only valid users logged in can see it).</p>';

    $membership = pvs_in_database($post->ID, 'post');

    $roles = array(
        'public' => !$membership,
        'members' => $membership
    );

    foreach ($roles as $role => $member) {
        if ($member)
            $checked = 'checked';
        else
            $checked = '';

        $output .= '<p><input type="radio" name="pv_security_role" value="' . $role . '" ' . $checked . ' />  ' . ucfirst($role) . '</p>';
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
    elseif ($_POST['pv_security_role'] == 'public') 
        pvs_delete_post_security_data($post_id, 'post');
    
        
}

function pvs_delete_post_security_data($post_id, $type) {
    global $wpdb;

    $sql = $wpdb->prepare("DELETE FROM " . PV_SECURITY_TABLENAME . " 
                           WHERE object_id = $post_id 
                           AND object_type = '$type' ");

    $wpdb->query($sql);
}

function pvs_save_post_security($object_id, $role, $object_type) {
    global $wpdb;

    if (!pvs_in_database($object_id, $object_type)) {
        $wpdb->insert(PV_SECURITY_TABLENAME, array(
            'object_id' => $object_id,
            'role' => $role,
            'object_type' => $object_type,
            'created_date' => date('Y-m-d H:m:s')
        ));
    }
}

function pvs_in_database($object_id, $object_type) {
    global $wpdb;

    $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM " . PV_SECURITY_TABLENAME . " as pvs 
                                            WHERE pvs.object_id = $object_id 
                                            AND pvs.object_type = '$object_type'"));

    return ($count > 0);
}

function pvs_join_security($join) {
    global $wpdb;

    if (!is_user_logged_in()) {
        $join .= " LEFT JOIN " . PV_SECURITY_TABLENAME . " pvs ON " . $wpdb->posts . ".ID = pvs.object_id ";
        $join .= " AND pvs.object_type = 'post' ";
    }

    return $join;
}

function pvs_where_security($where) {
    global $wpdb;

    if (!is_user_logged_in()) {
        $where .= " AND pvs.object_id IS NULL ";
    }

    return $where;
}

function pvs_filter_categories($categories) {
    global $wpdb;
    global $pvs_cat_results;
    
    if (is_user_logged_in())
        return $categories;

    $post_types = get_option('pv_security_options');

    $last_type = array_pop($post_types);

    $in_string = '';

    foreach ($post_types as $type) {
        $in_string .= " '$type', ";
    }
    
    $in_string .= "'$last_type'";

    $sql = $wpdb->prepare("SELECT t.name as cat_name, t.term_id as cat_id, COUNT(*) as count
                           FROM $wpdb->posts p
                           LEFT JOIN " . PV_SECURITY_TABLENAME . " pvs on p.ID = pvs.object_id
                                    AND pvs.object_type = 'post'
                           LEFT JOIN $wpdb->term_relationships tr ON p.ID = tr.object_id
                           LEFT JOIN $wpdb->term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                           LEFT JOIN $wpdb->terms t on tt.term_id = t.term_id
                           WHERE tt.taxonomy = 'category'
                            AND p.post_type IN ($in_string)
                            AND p.post_status = 'publish'
                            AND pvs.object_id is null
                            GROUP BY t.term_id;");    
    
    $pvs_cat_results = $wpdb->get_results($sql);
    
    $filtered_categories = array_filter($categories, 'pvs_cat_filter');
    
    unset($pvs_cat_results);
    
    return $filtered_categories;
}

function pvs_cat_filter($cat_obj) {
    global $pvs_cat_results;
    
    foreach ($pvs_cat_results as $result) {
        
        if ($cat_obj->term_id == $result->cat_id)
            return true;
        
    }
    
    return false;
}

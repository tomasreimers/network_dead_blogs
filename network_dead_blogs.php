<?php 
/*
Plugin Name: Network Dead Blogs
Plugin URI: https://github.com/berkmancenter/network-dead-blogs
Author: Tomas Reimers
Author URI: http://tomasreimers.com
Description: Expose a list of dead blogs for review and possibly deletion by the network administrator.
Version: 0.1
*/

require_once(ABSPATH . 'wp-includes/pluggable.php');

class Network_dead_blogs {

    public function render_csv(){
        global $wpdb;
        // validate page
        if ($_GET['page'] === "network-dead-blogs-csv" && strstr($_SERVER['REQUEST_URI'], "settings.php") !== FALSE){
            // validate permissions
            if (current_user_can('manage_sites')){
                // open stream
                $output = fopen("php://output", "w");
                // send headers
                header("Content-type: application/csv");
                header("Content-Disposition: attachment; filename=deadblogs.csv");
                header("Pragma: no-cache");
                header("Expires: 0");
                // get blogs
                $blogs = $wpdb->get_results(
                    $wpdb->prepare("SELECT blog_id, spam, archived, deleted, (TIMESTAMPDIFF(DAY, registered, NOW()) > 365 AND last_updated = 0) as not_updated FROM " . $wpdb->base_prefix . "blogs WHERE spam = 1 OR deleted = 1 OR archived = '1' OR (TIMESTAMPDIFF(DAY, registered, NOW()) > 365 AND last_updated = 0)", array()),
                    ARRAY_A
                );
                // render header
                $header_array = array("BLOG ID", "BLOG NAME", "BLOG DESCRIPTION", "BLOG URL", "OWNER EMAIL", "IS SPAM", "IS DEACTIVATED", "IS ARCHIVED", "IS NOT UPDATED");
                fputcsv($output, $header_array);
                // render main body
                foreach ($blogs as $blog){
                    set_time_limit(30);

                    $name = get_blog_option($blog["blog_id"], "blogname");
                    $description = get_blog_option($blog["blog_id"], "blogdescription");
                    $url = get_blog_option($blog["blog_id"], "siteurl");
                    $email = get_blog_option($blog["blog_id"], "admin_email");

                    $row = array($blog["blog_id"], $name, $description, $url, $email, $blog["spam"], $blog["deleted"], $blog["archived"], $blog["not_updated"]);

                    fputcsv($output, $row);
                }
                // close stream
                fclose($output);
                // prevent further output
                exit();
            }
        }
    }
    public function render_page(){
        // START HTML DOCUMENT
        ?>

        <h2><?php _e('Dead Blogs'); ?></h2>

        <a href="settings.php?page=network-dead-blogs-csv" class="button-primary"><?php _e('Export'); ?></a>

        <?php
        // END HTML DOCUMENT
    }

    public function hook_in(){
        add_submenu_page(
            'settings.php',
            __('Dead Blogs'),
            __('Dead Blogs'), 
            'manage_network', 
            'network-dead-blogs', 
            array($this, 'render_page')
        );
    }

}

$network_dead_blogs = new Network_dead_blogs();

// hook into menu - admin page
add_action('network_admin_menu', array($network_dead_blogs, 'hook_in'));

// create new page
add_action('init', array($network_dead_blogs, 'render_csv'));

?>
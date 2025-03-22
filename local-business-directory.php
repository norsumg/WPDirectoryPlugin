<?php
/**
 * Plugin Name: Local Business Directory
 * Description: A directory plugin for local businesses with categories, search, and premium listings.
 * Version: 1.0
 * Author: Your Name
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Safely include files
function lbd_include_file($file) {
    $path = plugin_dir_path( __FILE__ ) . $file;
    if (file_exists($path)) {
        require_once $path;
        return true;
    }
    return false;
}

// Include necessary files
lbd_include_file('includes/post-type.php');
lbd_include_file('includes/metaboxes.php');
lbd_include_file('includes/shortcodes.php');
lbd_include_file('includes/templates.php');
lbd_include_file('includes/admin.php');
lbd_include_file('includes/activation.php');

// Enqueue basic styles
function lbd_enqueue_styles() {
    wp_enqueue_style( 'lbd-styles', plugin_dir_url( __FILE__ ) . 'assets/css/directory.css' );
}
add_action( 'wp_enqueue_scripts', 'lbd_enqueue_styles' );

// Detect permalink structure changes and flush rules
function lbd_detect_permalink_changes() {
    if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] == true ) {
        if ( isset( $_GET['page'] ) && ($_GET['page'] == 'permalink' || $_GET['page'] == 'permalinks') ) {
            flush_rewrite_rules();
        }
    }
}
add_action( 'admin_init', 'lbd_detect_permalink_changes' );

// Activation function
function lbd_activate() {
    // Create custom post type
    lbd_register_post_type();
    lbd_register_taxonomy();
    
    // Flush rewrite rules
    flush_rewrite_rules();
    
    // Trigger our activation hook for any other functions
    do_action('lbd_activation');
}
register_activation_hook(__FILE__, 'lbd_activate');

// Flush rewrite rules on deactivation
function lbd_deactivation() {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'lbd_deactivation' );

// Force rebuild rewrite rules
function lbd_force_rebuild_rules() {
    global $wp_rewrite;
    
    // Remove all rules first
    $wp_rewrite->rules = array();
    
    // Recreate post type and taxonomies
    lbd_register_post_type();
    lbd_register_taxonomy();
    
    // Flush rules - hard flush
    $wp_rewrite->flush_rules(true);
    
    // Update success message
    add_action('admin_notices', function() {
        echo '<div class="notice notice-success is-dismissible"><p>Local Business Directory: Permalink rules have been rebuilt. All pages should now be accessible.</p></div>';
    });
}

// Rewrite flush on plugin update
function lbd_plugin_loaded() {
    // Check if we need to flush rules (on version change or first install)
    $current_version = '1.2'; // Increment version
    $saved_version = get_option('lbd_plugin_version');
    
    if ($saved_version !== $current_version) {
        // Force rebuilding rules on next admin page load
        if (is_admin()) {
            add_action('admin_init', function() use ($current_version) {
                lbd_force_rebuild_rules();
                update_option('lbd_plugin_version', $current_version);
            });
        }
    }
}
add_action('plugins_loaded', 'lbd_plugin_loaded'); 
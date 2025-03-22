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
    
    // Don't add custom directory rules if disabled
    if (!get_option('lbd_disable_custom_rules')) {
        // Let WordPress know we'll add custom rules
        add_action('init', 'lbd_add_rewrite_rules', 999);
    } else {
        // Show a message that custom rules are disabled
        add_action('admin_notices', function() {
            echo '<div class="notice notice-warning is-dismissible"><p>Local Business Directory: Custom rewrite rules are currently <strong>disabled</strong>. Regular pages will work, but some directory functionality might be limited. To re-enable, visit <a href="' . admin_url('tools.php?page=lbd-fix-permalinks') . '">Fix Directory Permalinks</a>.</p></div>';
        });
    }
    
    // Flush rules - hard flush
    $wp_rewrite->flush_rules(true);
    
    // Update success message
    add_action('admin_notices', function() {
        echo '<div class="notice notice-success is-dismissible"><p>Local Business Directory: Permalink rules have been rebuilt. All pages should now be accessible.</p></div>';
    });
}

// Add an emergency permalink fixer admin page
function lbd_add_emergency_admin_page() {
    add_management_page(
        'Fix Directory Permalinks',
        'Fix Directory Permalinks',
        'manage_options',
        'lbd-fix-permalinks',
        'lbd_fix_permalinks_page'
    );
}
add_action('admin_menu', 'lbd_add_emergency_admin_page');

// Emergency permalink fixer page content
function lbd_fix_permalinks_page() {
    // Handle form submissions
    if (isset($_POST['lbd_action']) && current_user_can('manage_options')) {
        $action = sanitize_text_field($_POST['lbd_action']);
        
        if ($action === 'disable_rules' && wp_verify_nonce($_POST['_wpnonce'], 'lbd_disable_rules')) {
            update_option('lbd_disable_custom_rules', 1);
            flush_rewrite_rules(true);
            echo '<div class="notice notice-success"><p>Custom directory rewrite rules have been <strong>disabled</strong>. Your regular pages should now work.</p></div>';
        }
        else if ($action === 'enable_rules' && wp_verify_nonce($_POST['_wpnonce'], 'lbd_enable_rules')) {
            delete_option('lbd_disable_custom_rules');
            flush_rewrite_rules(true);
            echo '<div class="notice notice-info"><p>Custom directory rewrite rules have been <strong>enabled</strong>. Check your site navigation to verify everything works.</p></div>';
        }
        else if ($action === 'reset_permalinks' && wp_verify_nonce($_POST['_wpnonce'], 'lbd_reset_permalinks')) {
            flush_rewrite_rules(true);
            echo '<div class="notice notice-info"><p>WordPress permalinks have been reset. Please check your site navigation.</p></div>';
        }
    }
    
    $rules_disabled = get_option('lbd_disable_custom_rules');
    
    ?>
    <div class="wrap">
        <h1>Fix Directory Permalinks</h1>
        
        <div class="card">
            <h2>Current Status</h2>
            <p>Custom directory rewrite rules are currently: <strong><?php echo $rules_disabled ? 'DISABLED' : 'ENABLED'; ?></strong></p>
            <p>If your regular WordPress pages show "Not Found" errors, use the options below to fix the issue.</p>
        </div>
        
        <div class="card">
            <h2>Option 1: Disable Custom Directory Rules</h2>
            <p>This will disable the custom rewrite rules for business areas and categories. Your regular WordPress pages will work again, but some directory functionality might be limited.</p>
            
            <form method="post">
                <?php wp_nonce_field('lbd_disable_rules'); ?>
                <input type="hidden" name="lbd_action" value="disable_rules">
                <p><button type="submit" class="button button-primary">Disable Custom Rules</button></p>
            </form>
        </div>
        
        <div class="card">
            <h2>Option 2: Enable Custom Directory Rules</h2>
            <p>This will re-enable the custom rewrite rules for business areas and categories.</p>
            
            <form method="post">
                <?php wp_nonce_field('lbd_enable_rules'); ?>
                <input type="hidden" name="lbd_action" value="enable_rules">
                <p><button type="submit" class="button">Enable Custom Rules</button></p>
            </form>
        </div>
        
        <div class="card">
            <h2>Option 3: Reset WordPress Permalinks</h2>
            <p>This will force WordPress to regenerate all permalink rules.</p>
            
            <form method="post">
                <?php wp_nonce_field('lbd_reset_permalinks'); ?>
                <input type="hidden" name="lbd_action" value="reset_permalinks">
                <p><button type="submit" class="button">Reset Permalinks</button></p>
            </form>
        </div>
    </div>
    <?php
}

// Rewrite flush on plugin update
function lbd_plugin_loaded() {
    // Check if we need to flush rules (on version change or first install)
    $current_version = '1.3'; // Increment version
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
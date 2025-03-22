<?php
/**
 * Plugin Name: Temporary Permalinks Flusher
 * Description: Temporarily flush permalinks to fix 404 issues
 * Version: 1.0
 * Author: Claude
 */

// Don't access this file directly
if (!defined('ABSPATH')) {
    exit;
}

function lbd_force_flush_permalinks() {
    // Force recreate the .htaccess file
    flush_rewrite_rules(true);
    
    // Add admin notice
    add_action('admin_notices', function() {
        echo '<div class="notice notice-success is-dismissible"><p>Permalinks have been flushed successfully. You can now deactivate and delete this plugin.</p></div>';
    });
}

// Run on plugin activation
register_activation_hook(__FILE__, 'lbd_force_flush_permalinks');
?> 
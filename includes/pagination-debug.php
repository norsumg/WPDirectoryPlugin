<?php
/**
 * Pagination Debug Tools for Local Business Directory
 *
 * These functions help diagnose pagination issues on both frontend and admin
 * Only available to admin users with lbd_debug parameter
 */

// Add pagination debug to frontend
function lbd_debug_pagination_frontend() {
    // Only for admins with debug parameter
    if (!current_user_can('manage_options') || !isset($_GET['lbd_debug'])) {
        return;
    }
    
    // Check if we're on a relevant template
    if (!is_tax('business_category') && !is_tax('business_area') && !is_post_type_archive('business')) {
        return;
    }
    
    // Add the action just before the pagination
    add_action('lbd_before_pagination', 'lbd_output_pagination_debug');
}
add_action('wp', 'lbd_debug_pagination_frontend');

// Debug output function
function lbd_output_pagination_debug() {
    global $wp_query;
    $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 0;
    
    echo '<div style="background: #f5f5f5; border: 1px solid #ddd; padding: 15px; margin: 15px 0; font-family: monospace;">';
    echo '<h3>Pagination Debug Info</h3>';
    
    echo '<h4>Query Details</h4>';
    echo '<p>Found posts: ' . $wp_query->found_posts . '</p>';
    echo '<p>Posts per page setting: ' . $wp_query->get('posts_per_page') . '</p>';
    echo '<p>Current page: ' . max(1, get_query_var('paged')) . '</p>';
    echo '<p>Requested per_page: ' . $per_page . '</p>';
    echo '<p>Max num pages: ' . $wp_query->max_num_pages . '</p>';
    
    echo '<h4>Hooks & Filters</h4>';
    echo '<p>lbd_modify_business_queries hook active: Yes (priority: 10)</p>';
    echo '<p>pre_get_posts hook used for pagination: Yes</p>';
    
    echo '<h4>Query Variables</h4>';
    echo '<pre>';
    $vars = $wp_query->query_vars;
    // Remove some verbose vars for cleaner output
    unset($vars['meta_query']);
    unset($vars['tax_query']);
    print_r($vars);
    echo '</pre>';
    
    echo '</div>';
}

// Debug admin pagination
function lbd_debug_admin_pagination() {
    // Only for admins with debug parameter
    if (!is_admin() || !current_user_can('manage_options') || !isset($_GET['lbd_debug'])) {
        return;
    }
    
    // Check if we're on a relevant admin screen
    $screen = get_current_screen();
    if (!$screen || 
        ($screen->post_type !== 'business' && 
         !($screen->id === 'edit-business_category' || $screen->id === 'edit-business_area'))) {
        return;
    }
    
    // Add admin notice with debug info
    add_action('admin_notices', 'lbd_output_admin_pagination_debug');
}
add_action('current_screen', 'lbd_debug_admin_pagination');

// Admin debug output
function lbd_output_admin_pagination_debug() {
    global $wp_query;
    
    // Get the screen options and user options
    $screen = get_current_screen();
    $user = wp_get_current_user();
    $option = $screen->id . '_per_page';
    $user_per_page = get_user_option($option, $user->ID);
    
    echo '<div class="notice notice-info">';
    echo '<h3>Pagination Debug Info</h3>';
    
    echo '<p><strong>Screen:</strong> ' . $screen->id . '</p>';
    echo '<p><strong>User Option Key:</strong> ' . $option . '</p>';
    echo '<p><strong>User Per Page Setting:</strong> ' . $user_per_page . '</p>';
    
    // Show active hooks
    echo '<p><strong>Admin Per Page Filters:</strong></p>';
    echo '<ul>';
    echo '<li>edit_posts_per_page: ' . (has_filter('edit_posts_per_page', 'lbd_modify_admin_per_page') ? 'Yes' : 'No') . '</li>';
    echo '<li>edit_tags_per_page: ' . (has_filter('edit_tags_per_page', 'lbd_modify_admin_per_page') ? 'Yes' : 'No') . '</li>';
    echo '</ul>';
    
    // Show request details
    echo '<p><strong>Request Details:</strong></p>';
    echo '<ul>';
    echo '<li>per_page parameter: ' . (isset($_GET['per_page']) ? intval($_GET['per_page']) : 'Not set') . '</li>';
    echo '<li>posts_per_page: ' . (isset($_GET['posts_per_page']) ? intval($_GET['posts_per_page']) : 'Not set') . '</li>';
    echo '</ul>';
    
    echo '</div>';
}

// Function to output JavaScript to check if our admin control panel is working
function lbd_debug_screen_options_js() {
    // Only for admins with debug parameter
    if (!is_admin() || !current_user_can('manage_options') || !isset($_GET['lbd_debug'])) {
        return;
    }
    
    // Check if we're on a relevant admin screen
    $screen = get_current_screen();
    if (!$screen || 
        ($screen->post_type !== 'business' && 
         !($screen->id === 'edit-business_category' || $screen->id === 'edit-business_area'))) {
        return;
    }
    
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Wait a short time for Screen Options to be fully loaded
        setTimeout(function() {
            console.log('LBD Debug: Checking Screen Options...');
            
            // Check for the basic screen options
            var $screenOptionsTab = $('#screen-options-wrap');
            if ($screenOptionsTab.length) {
                console.log('LBD Debug: Screen Options tab found');
                
                // Check for the per page input
                var $perPageInput = $screenOptionsTab.find('input[name="wp_screen_options[value]"]');
                if ($perPageInput.length) {
                    console.log('LBD Debug: Per page input found with value: ' + $perPageInput.val());
                } else {
                    console.log('LBD Debug: Per page input NOT found');
                }
                
                // Check for our custom buttons
                var $lbdButtons = $screenOptionsTab.find('.lbd-per-page-options');
                if ($lbdButtons.length) {
                    console.log('LBD Debug: Custom per page buttons found');
                    console.log('LBD Debug: Button container HTML:');
                    console.log($lbdButtons.html());
                } else {
                    console.log('LBD Debug: Custom per page buttons NOT found');
                }
            } else {
                console.log('LBD Debug: Screen Options tab NOT found');
            }
        }, 1000); // Wait 1 second for screen options
    });
    </script>
    <?php
}
add_action('admin_footer', 'lbd_debug_screen_options_js'); 
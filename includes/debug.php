<?php
/**
 * Debug tools for the Local Business Directory
 * 
 * These functions help diagnose search and other issues
 * Only available to admin users
 */

// Add debug info to search results
function lbd_debug_search_query($query) {
    // Only run for admins with the debug parameter
    if (!current_user_can('manage_options') || !isset($_GET['lbd_debug'])) {
        return $query;
    }
    
    // Only modify search queries
    if (!$query->is_search() || !$query->is_main_query()) {
        return $query;
    }
    
    // Store original query for debugging
    update_option('lbd_last_search_query', array(
        'search_term' => $query->get('s'),
        'post_type' => $query->get('post_type'),
        'meta_query' => $query->get('meta_query'),
        'tax_query' => $query->get('tax_query'),
        'time' => current_time('mysql')
    ));
    
    return $query;
}
add_action('pre_get_posts', 'lbd_debug_search_query', 999); // Run last

// Display debug info at the top of search results
function lbd_show_search_debug() {
    // Only show for admins with the debug parameter
    if (!current_user_can('manage_options') || !isset($_GET['lbd_debug'])) {
        return;
    }
    
    // Only show on search pages
    if (!is_search()) {
        return;
    }
    
    $last_query = get_option('lbd_last_search_query', array());
    
    echo '<div style="background: #f5f5f5; border: 1px solid #ddd; padding: 15px; margin: 15px 0; font-family: monospace;">';
    echo '<h3>Search Debug Info</h3>';
    echo '<p>This information is only visible to administrators.</p>';
    
    echo '<h4>Search Query</h4>';
    echo '<pre>';
    print_r($last_query);
    echo '</pre>';
    
    echo '<h4>$_GET Parameters</h4>';
    echo '<pre>';
    print_r($_GET);
    echo '</pre>';
    
    echo '<h4>Active Search Functions</h4>';
    echo '<ul>';
    echo '<li>lbd_search_modification - Priority: 10 (handles both taxonomy filtering and meta search)</li>';
    echo '</ul>';
    
    echo '</div>';
}
add_action('wp_head', 'lbd_show_search_debug'); 
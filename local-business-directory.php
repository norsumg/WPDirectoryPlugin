<?php
/**
 * Plugin Name: Local Business Directory
 * Description: A directory of local businesses with reviews and ratings
 * Version: 0.9.7
 * Author: Norsu Media
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
lbd_include_file('includes/reviews.php');
lbd_include_file('includes/rankmath-integration.php');
lbd_include_file('includes/duplicates.php');

// Include debug tools after WordPress is fully loaded
function lbd_maybe_include_debug() {
    // Only load debug file if admin with proper permissions has requested it
    if (is_admin() && current_user_can('manage_options') && isset($_GET['lbd_debug'])) {
        lbd_include_file('includes/debug.php');
    }
}
add_action('plugins_loaded', 'lbd_maybe_include_debug', 20);

/**
 * Enqueue frontend styles and scripts
 */
function lbd_enqueue_styles() {
    // Main plugin CSS
    wp_enqueue_style('lbd-styles', plugin_dir_url(__FILE__) . 'assets/css/directory.css', array(), '0.7.0');
    
    // Single business page scripts - only load on business single
    if (is_singular('business')) {
        wp_enqueue_script('lbd-single-business', plugin_dir_url(__FILE__) . 'assets/js/single-business.js', array('jquery'), '0.7.0', true);
    }
}
add_action('wp_enqueue_scripts', 'lbd_enqueue_styles');

/**
 * Enqueue admin scripts and styles
 */
function lbd_admin_scripts($hook) {
    $screen = get_current_screen();
    
    // Admin CSS for all admin pages related to businesses
    if (isset($screen->post_type) && $screen->post_type === 'business') {
        wp_enqueue_style('lbd-admin-styles', plugin_dir_url(__FILE__) . 'assets/css/admin.css', array(), '0.7.0');
    }
    
    // Admin JS for the business edit screen and CSV import page
    if ((isset($screen->post_type) && $screen->post_type === 'business') || 
        (isset($_GET['page']) && $_GET['page'] === 'lbd-csv-import')) {
        wp_enqueue_script('lbd-admin-js', plugin_dir_url(__FILE__) . 'assets/js/admin.js', array('jquery'), '0.7.0', true);
    }
}
add_action('admin_enqueue_scripts', 'lbd_admin_scripts');

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

// Check for scheduled flush
function lbd_check_flush_rules() {
    if (get_option('lbd_flush_rewrite_rules') === 'true') {
        // Delete the option to prevent multiple flushes
        delete_option('lbd_flush_rewrite_rules');
        
        // Flush the rewrite rules
        flush_rewrite_rules();
        
        // Add admin notice that rewrite rules have been refreshed
        add_action('admin_notices', function() {
            echo '<div class="notice notice-success is-dismissible"><p>Local Business Directory: Rewrite rules have been refreshed with the new directory namespace structure.</p></div>';
        });
    }
}
add_action('admin_init', 'lbd_check_flush_rules');

// Force rebuild rewrite rules
function lbd_force_rebuild_rules() {
    global $wp_rewrite;
    
    // Clear existing rules
    $wp_rewrite->flush_rules(true);
    
    // Schedule a flush
    update_option('lbd_flush_rewrite_rules', 'true');
    
    // Add admin notice
    add_action('admin_notices', function() {
        echo '<div class="notice notice-success is-dismissible"><p>Directory URL structure has been updated to use /directory/ namespace. This prevents conflicts with regular WordPress pages.</p></div>';
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
        
        if ($action === 'reset_permalinks' && wp_verify_nonce($_POST['_wpnonce'], 'lbd_reset_permalinks')) {
            lbd_force_rebuild_rules();
            echo '<div class="notice notice-success"><p>Directory permalinks have been updated to use the new namespaced structure. Both regular pages and directory pages should now work correctly.</p></div>';
        }
    }
    
    ?>
    <div class="wrap">
        <h1>Directory Permalinks Manager</h1>
        
        <div class="card">
            <h2>New Namespaced URL Structure</h2>
            <p>The Directory Plugin now uses namespaced URLs to prevent conflicts with regular WordPress pages:</p>
            <ul>
                <li><code>/directory/london/restaurants/</code> - Shows all restaurants in London</li>
                <li><code>/directory/london/</code> - Shows all businesses in London</li>
                <li><code>/directory/categories/restaurants/</code> - Shows all restaurants across areas</li>
            </ul>
            <p>This structure allows regular WordPress pages to work alongside the directory functionality.</p>
            <p>For example, you can now have both:</p>
            <ul>
                <li>A directory area at <code>/directory/london/</code></li>
                <li>A regular WordPress page at <code>/london/</code></li>
            </ul>
            <p>Both will work correctly without conflicts.</p>
        </div>
        
        <div class="card">
            <h2>Creating a Directory Homepage</h2>
            <p>Now that directory pages use the <code>/directory/</code> prefix, we recommend creating a dedicated directory homepage:</p>
            <ol>
                <li>Create a new WordPress page titled "Directory" with the slug "directory"</li>
                <li>Add this shortcode to the page: <code>[directory_home]</code></li>
                <li>This will create a browsable directory listing of all areas and categories</li>
            </ol>
            <p>Using this approach, your visitors can navigate to <code>/directory/</code> to browse the business listings.</p>
        </div>
        
        <div class="card">
            <h2>Update Permalink Structure</h2>
            <p>If you're experiencing any issues with your permalinks, use this button to refresh the rewrite rules:</p>
            
            <form method="post">
                <?php wp_nonce_field('lbd_reset_permalinks'); ?>
                <input type="hidden" name="lbd_action" value="reset_permalinks">
                <p><button type="submit" class="button button-primary">Refresh Directory Permalinks</button></p>
            </form>
        </div>
        
        <div class="card">
            <h2>Important Note About Existing Links</h2>
            <p>If you had existing links to directory pages (such as <code>/london/</code>), these will now need to be updated to use the new format (<code>/directory/london/</code>).</p>
            <p>Consider adding redirects from your old URLs to the new namespaced versions if you have external links pointing to your directory pages.</p>
        </div>
    </div>
    <?php
}

// Rewrite flush on plugin update
function lbd_plugin_loaded() {
    $current_version = get_option('lbd_version', '0');
    
    // Current plugin version
    $plugin_version = '0.7.0'; // Update version number here
    
    // Check if version has changed
    if (version_compare($current_version, $plugin_version, '!=')) {
        // Update version in the database
        update_option('lbd_version', $plugin_version);
        
        // Schedule a flush of the rewrite rules for the next admin page load
        add_option('lbd_flush_rewrite_rules', 'true');
    }
}
add_action('plugins_loaded', 'lbd_plugin_loaded');

/**
 * Define a plugin constant to avoid errors
 */
if (!defined('LBD_PLUGIN_DIR')) {
    define('LBD_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

/**
 * Comprehensive search function for business directory
 * Handles taxonomy filtering for business searches
 */
function lbd_search_modification($query) {
    // Only modify search queries on the front end
    if (is_admin() || !$query->is_search() || !$query->is_main_query()) {
        return $query;
    }
    
    // Check if we're explicitly searching for businesses
    $post_type = isset($_GET['post_type']) ? sanitize_key($_GET['post_type']) : '';
    $search_businesses = $post_type === 'business';
    
    // If this is a business-specific search
    if ($search_businesses) {
        // Set post type to business
        $query->set('post_type', 'business');
        
        // Handle taxonomy filters (area and category)
        $tax_query = array();
        
        // Get and validate category
        $category = isset($_GET['category']) ? sanitize_key($_GET['category']) : '';
        if ($category) {
            // Verify the term exists in the taxonomy
            $term = get_term_by('slug', $category, 'business_category');
            if ($term && !is_wp_error($term)) {
                $tax_query[] = array(
                    'taxonomy' => 'business_category',
                    'field' => 'slug',
                    'terms' => $category
                );
            }
        }
        
        // Get and validate area
        $area = isset($_GET['area']) ? sanitize_key($_GET['area']) : '';
        if ($area) {
            // Verify the term exists in the taxonomy
            $term = get_term_by('slug', $area, 'business_area');
            if ($term && !is_wp_error($term)) {
                $tax_query[] = array(
                    'taxonomy' => 'business_area',
                    'field' => 'slug',
                    'terms' => $area
                );
            }
        }
        
        // Apply taxonomy query if we have filters
        if (!empty($tax_query)) {
            if (count($tax_query) > 1) {
                $tax_query['relation'] = 'AND';
            }
            $query->set('tax_query', $tax_query);
        }
        
        // NOTE: Meta query logic has been removed and replaced with direct SQL filters
    }
    
    return $query;
}

// Check if the old function exists before trying to remove it
if (function_exists('lbd_light_search_modification')) {
    remove_action('pre_get_posts', 'lbd_light_search_modification', 9);
}

// Add our search function
add_action('pre_get_posts', 'lbd_search_modification', 10);

/**
 * Modify the search SQL to include OR conditions for meta fields.
 *
 * @param string $search SQL search clause.
 * @param WP_Query $query The WP_Query object.
 * @return string Modified SQL search clause.
 */
function lbd_extend_search_sql($search, $query) {
    global $wpdb;

    // Only modify the main search query for 'business' post type if 's' is set
    if ($query->is_main_query() && $query->is_search() && $query->get('post_type') === 'business' && !empty($query->get('s'))) {
        $search_term = $query->get('s');
        $search_term_like = '%' . $wpdb->esc_like($search_term) . '%';

        // Add OR conditions for the meta keys
        $meta_search = $wpdb->prepare(
            " OR EXISTS (
                SELECT 1 FROM {$wpdb->postmeta} pm
                WHERE pm.post_id = {$wpdb->posts}.ID
                AND (
                    (pm.meta_key = 'lbd_address' AND pm.meta_value LIKE %s) OR
                    (pm.meta_key = 'lbd_street_address' AND pm.meta_value LIKE %s) OR
                    (pm.meta_key = 'lbd_city' AND pm.meta_value LIKE %s) OR
                    (pm.meta_key = 'lbd_postcode' AND pm.meta_value LIKE %s)
                )
            )",
            $search_term_like, $search_term_like, $search_term_like, $search_term_like
        );

        // Append the meta search condition with an OR to the existing search SQL
        if (!empty($search)) {
            $search = " AND ( " . substr($search, 5) . $meta_search . " ) "; // Replace initial AND, wrap existing, append OR meta
        } else {
            // If somehow 's' was set but $search is empty, just use the meta search
            $search = " AND ( 1=0 " . $meta_search . " ) "; // Fallback
        }
    }

    return $search;
}
add_filter('posts_search', 'lbd_extend_search_sql', 10, 2);

/**
 * Ensure results aren't duplicated if a post matches in multiple places
 *
 * @param string   $distinct The DISTINCT clause of the query.
 * @param WP_Query $query The WP_Query instance.
 * @return string Modified DISTINCT clause.
 */
function lbd_extend_search_distinct($distinct, $query) {
    // Only modify the main search query for 'business' post type if 's' is set
    if ($query->is_main_query() && $query->is_search() && $query->get('post_type') === 'business' && !empty($query->get('s'))) {
        return "DISTINCT";
    }
    
    return $distinct;
}
add_filter('posts_distinct', 'lbd_extend_search_distinct', 10, 2);

/**
 * Get cached taxonomy terms
 */
function lbd_get_cached_terms($taxonomy) {
    // Try to get cached terms
    $cache_key = 'lbd_' . $taxonomy . '_terms';
    $cached_terms = get_transient($cache_key);
    
    if (false === $cached_terms) {
        // Cache doesn't exist, get terms and cache them
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
        ));
        
        if (!is_wp_error($terms)) {
            set_transient($cache_key, $terms, HOUR_IN_SECONDS * 6); // Cache for 6 hours
            return $terms;
        }
        
        return array();
    }
    
    return $cached_terms;
}

/**
 * Add minimal CSS to hide author information in search results
 * Now handled via the main CSS file
 */
function lbd_hide_author_in_search() {
    // Function is now empty but kept for backwards compatibility
    // Styles have been moved to assets/css/directory.css
}
add_action('wp_head', 'lbd_hide_author_in_search', 999);

/**
 * Add review ratings to business content in search results
 * Direct approach using the_content filter
 * 
 * Currently disabled to prevent duplicate ratings in search results
 */
function lbd_add_ratings_to_search_content($content) {
    // We're not modifying content anymore since the excerpt filter works well
    // This prevents duplicate ratings from appearing
    return $content;
}

// Add our content filter
add_filter('the_content', 'lbd_add_ratings_to_search_content', 5);

/**
 * Add the review section to excerpts as well - many themes use excerpts in search results
 */
function lbd_add_ratings_to_search_excerpt($excerpt) {
    // Only modify search results for business post type
    $post_type = isset($_GET['post_type']) ? sanitize_key($_GET['post_type']) : '';
    if (!is_search() || $post_type !== 'business') {
        return $excerpt;
    }
    
    // Get current post ID
    $post_id = get_the_ID();
    
    // First check for native review data
    $review_average = get_post_meta($post_id, 'lbd_review_average', true);
    $review_count = get_post_meta($post_id, 'lbd_review_count', true);
    
    // If no native reviews, check for Google reviews as fallback
    $review_source = 'Native';
    if (empty($review_average)) {
        // Look for various possible Google review field names
        $google_rating = get_post_meta($post_id, 'google_rating', true);
        if (empty($google_rating)) {
            $google_rating = get_post_meta($post_id, 'lbd_google_rating', true);
        }
        
        $google_review_count = get_post_meta($post_id, 'google_review_count', true);
        if (empty($google_review_count)) {
            $google_review_count = get_post_meta($post_id, 'lbd_google_review_count', true);
        }
        
        // If we found Google reviews, use them
        if (!empty($google_rating)) {
            $review_average = $google_rating;
            $review_count = $google_review_count;
            $review_source = 'Google';
        }
    }
    
    // Debug mode - strictly limited to admin users with manage_options capability
    if (isset($_GET['debug']) && current_user_can('manage_options')) {
        $meta_data = get_post_meta($post_id);
        $debug_html = '<div style="background:#f5f5f5; border:1px solid #ddd; padding:10px; margin:10px 0; font-family:monospace;">';
        $debug_html .= '<strong>DEBUG INFO:</strong><br>';
        $debug_html .= 'Post ID: ' . $post_id . '<br>';
        $debug_html .= 'Native Review Average: ' . (get_post_meta($post_id, 'lbd_review_average', true) ? get_post_meta($post_id, 'lbd_review_average', true) : 'Not set') . '<br>';
        $debug_html .= 'Native Review Count: ' . (get_post_meta($post_id, 'lbd_review_count', true) ? get_post_meta($post_id, 'lbd_review_count', true) : 'Not set') . '<br>';
        $debug_html .= 'Google Rating: ' . (get_post_meta($post_id, 'google_rating', true) ? get_post_meta($post_id, 'google_rating', true) : 'Not set') . '<br>';
        $debug_html .= 'Google Review Count: ' . (get_post_meta($post_id, 'google_review_count', true) ? get_post_meta($post_id, 'google_review_count', true) : 'Not set') . '<br>';
        $debug_html .= 'Using Review Source: ' . $review_source . '<br>';
        $debug_html .= '<br><strong>All Meta:</strong><br>';
        
        foreach ($meta_data as $key => $values) {
            if (strpos($key, 'lbd_') === 0 || strpos($key, 'google_') === 0) { // Show plugin and Google meta
                $debug_html .= $key . ': ' . print_r($values[0], true) . '<br>';
            }
        }
        
        $debug_html .= '</div>';
        $excerpt = $debug_html . $excerpt;
    }
    
    // If no review data at all, just return the excerpt
    if (empty($review_average)) {
        return $excerpt;
    }
    
    // Use our consolidated star rating function if available
    if (function_exists('lbd_get_star_rating_html')) {
        $stars_html = lbd_get_star_rating_html($review_average, $review_count, $review_source);
        return $stars_html . $excerpt;
    }
    
    // Fallback to old method if function not available
    $stars_html = '<div class="business-rating" style="display:block; margin:10px 0; color:#f7d032; font-size:1.2em;">';
    if ($review_source === 'Google') {
        $stars_html .= '<strong>Google Rating: </strong>';
    } else {
        $stars_html .= '<strong>Rating: </strong>';
    }
    
    // Add star icons
    $full_stars = floor($review_average);
    $half_star = ($review_average - $full_stars) >= 0.5;
    
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $full_stars) {
            $stars_html .= '★'; // Full star
        } elseif ($i == $full_stars + 1 && $half_star) {
            $stars_html .= '&#189;'; // Half star
        } else {
            $stars_html .= '☆'; // Empty star
        }
    }
    
    // Add review count
    if (!empty($review_count) && $review_count > 0) {
        $stars_html .= ' <span style="color:#666; font-size:0.9em;">(' . intval($review_count) . ' reviews)</span>';
    }
    
    $stars_html .= '</div>';
    
    // Prepend rating to excerpt
    return $stars_html . $excerpt;
}
add_filter('the_excerpt', 'lbd_add_ratings_to_search_excerpt', 5); 
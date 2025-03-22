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
    $plugin_version = '1.5'; // Increment to force rebuild of rewrite rules
    
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
 * Lightweight search modification that uses WordPress's native functionality
 */
function lbd_light_search_modification($query) {
    // Only modify search queries on the front end
    if (is_admin() || !$query->is_search() || !$query->is_main_query()) {
        return $query;
    }
    
    // Check if we're explicitly searching for businesses
    $search_businesses = isset($_GET['post_type']) && $_GET['post_type'] === 'business';
    
    // If this is a business-specific search
    if ($search_businesses) {
        // Only search business post type
        $query->set('post_type', 'business');
        $query->set('posts_per_page', 20); // Limit to prevent memory issues
        
        // Set up tax query if needed
        $tax_query = array();
        
        if (isset($_GET['category']) && !empty($_GET['category'])) {
            $tax_query[] = array(
                'taxonomy' => 'business_category',
                'field' => 'slug',
                'terms' => sanitize_text_field($_GET['category'])
            );
        }
        
        if (isset($_GET['area']) && !empty($_GET['area'])) {
            $tax_query[] = array(
                'taxonomy' => 'business_area',
                'field' => 'slug',
                'terms' => sanitize_text_field($_GET['area'])
            );
        }
        
        if (!empty($tax_query)) {
            if (count($tax_query) > 1) {
                $tax_query['relation'] = 'AND';
            }
            $query->set('tax_query', $tax_query);
        }
    }
    
    return $query;
}
add_action('pre_get_posts', 'lbd_light_search_modification');

/**
 * Simple function to customize search results - uses minimal processing
 */
function lbd_light_customize_results($content) {
    // Only modify business search results
    if (!is_search() || !is_main_query() || !in_the_loop() || get_post_type() !== 'business') {
        return $content;
    }
    
    // Get post ID once to minimize function calls
    $post_id = get_the_ID();
    
    // Build a simple output - no complex data processing
    $output = '<div class="business-search-result">';
    
    // Basic premium badge
    if (get_post_meta($post_id, 'lbd_premium', true)) {
        $output .= '<span class="premium-badge">Premium</span>';
    }
    
    // Add title with permalink
    $output .= '<h2><a href="' . get_permalink() . '">' . get_the_title() . '</a></h2>';
    
    // Get the first term of each taxonomy - no complex processing
    $areas = get_the_terms($post_id, 'business_area');
    $categories = get_the_terms($post_id, 'business_category');
    
    $output .= '<div class="business-meta">';
    
    // Display category and area if they exist
    if (!empty($categories) && !is_wp_error($categories)) {
        $category = reset($categories);
        $output .= '<span class="business-category">' . esc_html($category->name) . '</span>';
    }
    
    if (!empty($areas) && !is_wp_error($areas)) {
        $area = reset($areas);
        $output .= ' in <span class="business-area">' . esc_html($area->name) . '</span>';
    }
    
    $output .= '</div>';
    
    // Get description with fallback to excerpt
    $description = get_post_meta($post_id, 'lbd_description', true);
    if (empty($description)) {
        $description = get_the_excerpt();
    }
    
    // Display description
    $output .= '<div class="business-description">' . wpautop($description) . '</div>';
    
    // Add view button
    $output .= '<div class="search-view-business">';
    $output .= '<a href="' . get_permalink() . '" class="business-view-link">View Business</a>';
    $output .= '</div>';
    
    $output .= '</div>';
    
    return $output;
}
add_filter('the_content', 'lbd_light_customize_results');

/**
 * Add styles for business search results
 */
function lbd_add_search_results_styles() {
    if (!is_search()) {
        return;
    }
    
    ?>
    <style>
    /* Business search result styling */
    .business-search-result {
        border: 1px solid #eee;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 25px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        transition: all 0.2s ease;
        position: relative;
        overflow: hidden;
    }
    
    .business-search-result:hover {
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        transform: translateY(-3px);
    }
    
    /* Hide post author in search results */
    .business-search-result .author,
    .business-search-result .entry-meta .author,
    .business-search-result .post-author,
    .search article.business .entry-meta .author,
    .search article.business .byline {
        display: none !important;
    }
    
    .premium-badge {
        position: absolute;
        top: 0;
        right: 0;
        background: #FFD700;
        color: #333;
        font-size: 0.8em;
        padding: 5px 10px;
        font-weight: bold;
    }
    
    .business-meta {
        font-size: 0.9em;
        color: #666;
        margin-bottom: 15px;
        border-bottom: 1px solid #f0f0f0;
        padding-bottom: 10px;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 15px;
    }
    
    .business-category-area,
    .business-category,
    .business-area {
        font-weight: 500;
    }
    
    .business-category-area a,
    .business-category a,
    .business-area a {
        color: #0073aa;
        text-decoration: none;
    }
    
    .business-category-area a:hover,
    .business-category a:hover,
    .business-area a:hover {
        text-decoration: underline;
    }
    
    /* Star rating styling */
    .business-rating {
        display: inline-flex;
        align-items: center;
    }
    
    .star-rating {
        color: #FFD700;
        position: relative;
        display: inline-block;
        letter-spacing: 2px;
    }
    
    .star-rating .star {
        display: inline-block;
    }
    
    .star-rating .half-star {
        position: relative;
        opacity: 0.5;
    }
    
    .star-rating .empty-star {
        color: #ccc;
    }
    
    .review-count {
        margin-left: 5px;
        color: #666;
        font-size: 0.85em;
    }
    
    /* Description */
    .business-description {
        margin-bottom: 15px;
        color: #333;
        line-height: 1.6;
    }
    
    /* Hide default read more link */
    .business-search-result .more-link {
        display: none !important;
    }
    
    .search-view-business {
        margin-top: 15px;
    }
    
    .business-view-link {
        display: inline-block;
        background: #0073aa;
        color: white !important;
        padding: 8px 16px;
        text-decoration: none !important;
        border-radius: 4px;
        font-size: 0.9em;
        transition: background 0.2s ease;
    }
    
    .business-view-link:hover {
        background: #005177;
        color: white !important;
        text-decoration: none !important;
    }
    
    /* Handle mobile responsiveness */
    @media (max-width: 768px) {
        .business-meta {
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }
    }
    </style>
    <?php
}
add_action('wp_head', 'lbd_add_search_results_styles');

/**
 * Modify search result title to be more specific for business searches
 */
function lbd_modify_search_title($title) {
    // Temporarily return the original title to troubleshoot 500 error
    return $title;
    
    /* Original code - commented out temporarily
    // Only change on search pages
    if (!is_search()) {
        return $title;
    }
    
    // Check if we're specifically searching for businesses
    if (isset($_GET['post_type']) && $_GET['post_type'] === 'business') {
        $search_term = get_search_query();
        
        // Build a more descriptive title
        $title_parts = array();
        $title_parts[] = 'Business Directory';
        
        if (!empty($search_term)) {
            $title_parts[] = 'Results for "' . esc_html($search_term) . '"';
        }
        
        // Add area if specified
        if (isset($_GET['area']) && !empty($_GET['area'])) {
            $area = get_term_by('slug', sanitize_text_field($_GET['area']), 'business_area');
            if ($area) {
                $title_parts[] = 'in ' . esc_html($area->name);
            }
        }
        
        // Add category if specified
        if (isset($_GET['category']) && !empty($_GET['category'])) {
            $category = get_term_by('slug', sanitize_text_field($_GET['category']), 'business_category');
            if ($category) {
                $title_parts[] = 'in category ' . esc_html($category->name);
            }
        }
        
        // Create the new title
        $new_title = implode(' - ', $title_parts);
        
        // Replace the title parts
        $title = str_replace('Search Results for', $new_title, $title);
    }
    
    return $title;
    */
}
// Temporarily uncomment this hook to disable title modifications
// add_filter('pre_get_document_title', 'lbd_modify_search_title', 15);

/**
 * Add the search form to the top of search results pages for business searches
 */
function lbd_add_search_form_to_search_page($content) {
    // Temporarily return content unmodified to troubleshoot 500 error
    return $content;
    
    /* Original code - commented out temporarily
    // Only add to search pages and only if it's a business search
    if (!is_search() || !isset($_GET['post_type']) || $_GET['post_type'] !== 'business') {
        return $content;
    }
    
    // Only add to the first post and only if in the main loop
    static $search_form_added = false;
    if ($search_form_added || !in_the_loop() || !is_main_query()) {
        return $content;
    }
    
    // Mark as added to avoid duplicate forms
    $search_form_added = true;
    
    // Get current search parameters
    $search_term = get_search_query();
    $area = isset($_GET['area']) ? sanitize_text_field($_GET['area']) : '';
    $category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
    
    // Get the search form using basic HTML instead of shortcode for better performance
    $form_html = '<div class="business-search-header">
        <h2>Business Directory Search</h2>
        <p>Refine your search or browse businesses by area and category.</p>
        <div class="business-search-form horizontal">
            <form role="search" method="get" action="' . esc_url(home_url('/')) . '" class="search-form">
                <input type="hidden" name="post_type" value="business" />
                
                <div class="search-inputs">
                    <div class="input-container search-field">
                        <input type="text" name="s" placeholder="Search businesses..." value="' . esc_attr($search_term) . '" />
                    </div>';
    
    // Add area dropdown with cached terms
    $form_html .= '<div class="input-container area-field">
                        <select name="area">
                            <option value="">All Areas</option>';
    
    // Get cached areas
    $areas = lbd_get_cached_terms('business_area');
    
    if (!empty($areas) && !is_wp_error($areas)) {
        foreach ($areas as $term) {
            $selected = $area === $term->slug ? ' selected="selected"' : '';
            $form_html .= '<option value="' . esc_attr($term->slug) . '"' . $selected . '>' . esc_html($term->name) . '</option>';
        }
    }
    
    $form_html .= '</select>
                    </div>';
    
    // Add category dropdown with cached terms
    $form_html .= '<div class="input-container category-field">
                        <select name="category">
                            <option value="">All Categories</option>';
    
    // Get cached categories
    $categories = lbd_get_cached_terms('business_category');
    
    if (!empty($categories) && !is_wp_error($categories)) {
        foreach ($categories as $term) {
            $selected = $category === $term->slug ? ' selected="selected"' : '';
            $form_html .= '<option value="' . esc_attr($term->slug) . '"' . $selected . '>' . esc_html($term->name) . '</option>';
        }
    }
    
    $form_html .= '</select>
                    </div>
                    
                    <button type="submit" class="search-button pill-button">Search</button>
                </div>
            </form>
        </div>
    </div>';
    
    return $form_html . $content;
    */
}
// Temporarily uncomment this hook to disable the search form at the top of search results
// add_filter('the_content', 'lbd_add_search_form_to_search_page', 5);

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
 * Preload data for search results to improve performance
 */
function lbd_preload_search_results_data() {
    // Temporarily disabled to troubleshoot performance issues
    return;
    
    /* Rest of the function is kept for future reference but won't execute */
    
    // Only on search pages for business post type
    if (!is_search() || !isset($_GET['post_type']) || $_GET['post_type'] !== 'business') {
        return;
    }
    
    // Set a reasonable time limit for this function
    $time_start = microtime(true);
    $time_limit = 2; // seconds
    
    global $wp_query;
    
    // If no posts found, don't need to preload
    if (!$wp_query->have_posts() || empty($wp_query->posts)) {
        return;
    }
    
    // Get all post IDs from search results
    $post_ids = wp_list_pluck($wp_query->posts, 'ID');
    
    // Skip if no post IDs (shouldn't happen but just in case)
    if (empty($post_ids)) {
        return;
    }
    
    // Safety check - limit to max 50 posts to prevent overload
    if (count($post_ids) > 50) {
        $post_ids = array_slice($post_ids, 0, 50);
    }
    
    try {
        // 1. Preload all post meta for business posts
        $meta_keys = array(
            'lbd_premium',
            'lbd_description',
            'lbd_review_count',
            'lbd_review_average'
        );
        
        // Store in global variable for retrieval in business_search_results
        global $lbd_preloaded_meta;
        $lbd_preloaded_meta = lbd_preload_post_meta($post_ids, $meta_keys);
        
        // Check time to avoid timeout
        if ((microtime(true) - $time_start) > $time_limit) {
            // Too slow, skip remaining preloads
            return;
        }
        
        // 2. Preload all terms for areas and categories
        global $lbd_preloaded_terms;
        $lbd_preloaded_terms = lbd_preload_post_terms($post_ids, array('business_area', 'business_category'));
    } catch (Exception $e) {
        // If anything goes wrong, just continue without preloaded data
        // This will fall back to standard WordPress functions
        error_log('Business Directory preload error: ' . $e->getMessage());
    }
}
add_action('wp', 'lbd_preload_search_results_data');

/**
 * Preload post meta for a set of post IDs
 */
function lbd_preload_post_meta($post_ids, $meta_keys = array()) {
    if (empty($post_ids)) {
        return array();
    }
    
    // Safety check - make sure post_ids is an array
    if (!is_array($post_ids)) {
        return array();
    }
    
    global $wpdb;
    
    try {
        $post_ids_string = implode(',', array_map('intval', $post_ids));
        
        // If specific meta keys are provided, only get those
        $meta_keys_condition = '';
        if (!empty($meta_keys)) {
            $meta_keys_placeholders = implode("','", array_map('esc_sql', $meta_keys));
            $meta_keys_condition = "AND meta_key IN ('$meta_keys_placeholders')";
        }
        
        // Get all post meta in a single query
        $query = "
            SELECT post_id, meta_key, meta_value 
            FROM $wpdb->postmeta 
            WHERE post_id IN ($post_ids_string) 
            $meta_keys_condition
        ";
        
        $results = $wpdb->get_results($query);
        
        // Check for SQL errors
        if ($wpdb->last_error) {
            error_log('SQL Error in lbd_preload_post_meta: ' . $wpdb->last_error);
            return array();
        }
        
        // Organize by post ID
        $meta_by_post = array();
        foreach ($results as $row) {
            if (!isset($meta_by_post[$row->post_id])) {
                $meta_by_post[$row->post_id] = array();
            }
            $meta_by_post[$row->post_id][$row->meta_key] = $row->meta_value;
        }
        
        return $meta_by_post;
    } catch (Exception $e) {
        error_log('Error in lbd_preload_post_meta: ' . $e->getMessage());
        return array();
    }
}

/**
 * Preload terms for multiple posts
 */
function lbd_preload_post_terms($post_ids, $taxonomies) {
    if (empty($post_ids) || empty($taxonomies)) {
        return array();
    }
    
    // Safety check - make sure inputs are arrays
    if (!is_array($post_ids) || !is_array($taxonomies)) {
        return array();
    }
    
    try {
        // Get terms for all posts at once
        $terms_objects = wp_get_object_terms($post_ids, $taxonomies, array('fields' => 'all_with_object_id'));
        
        if (is_wp_error($terms_objects)) {
            error_log('WordPress Error in lbd_preload_post_terms: ' . $terms_objects->get_error_message());
            return array();
        }
        
        // Organize by post ID and taxonomy
        $terms_by_post = array();
        foreach ($terms_objects as $term) {
            $post_id = $term->object_id;
            $taxonomy = $term->taxonomy;
            
            if (!isset($terms_by_post[$post_id])) {
                $terms_by_post[$post_id] = array();
            }
            
            if (!isset($terms_by_post[$post_id][$taxonomy])) {
                $terms_by_post[$post_id][$taxonomy] = array();
            }
            
            $terms_by_post[$post_id][$taxonomy][] = $term;
        }
        
        return $terms_by_post;
    } catch (Exception $e) {
        error_log('Error in lbd_preload_post_terms: ' . $e->getMessage());
        return array();
    }
}

/**
 * Get preloaded post meta if available, otherwise fall back to get_post_meta
 */
function lbd_get_preloaded_meta($post_id, $meta_key, $single = true) {
    // Temporarily disable preloading and always use regular get_post_meta
    return get_post_meta($post_id, $meta_key, $single);
    
    /* Original code - commented out temporarily
    global $lbd_preloaded_meta;
    
    if (isset($lbd_preloaded_meta[$post_id]) && isset($lbd_preloaded_meta[$post_id][$meta_key])) {
        $value = $lbd_preloaded_meta[$post_id][$meta_key];
        return $value;
    }
    
    // Fall back to regular get_post_meta
    return get_post_meta($post_id, $meta_key, $single);
    */
}

/**
 * Get preloaded terms if available, otherwise get_the_terms
 */
function lbd_get_preloaded_terms($post_id, $taxonomy) {
    // Temporarily disable preloading and always use get_the_terms
    return get_the_terms($post_id, $taxonomy);
    
    /* Original code - commented out temporarily
    global $lbd_preloaded_terms;
    
    if (isset($lbd_preloaded_terms[$post_id]) && isset($lbd_preloaded_terms[$post_id][$taxonomy])) {
        return $lbd_preloaded_terms[$post_id][$taxonomy];
    }
    
    // Fall back to regular get_the_terms
    return get_the_terms($post_id, $taxonomy);
    */
}

/**
 * Create a simple search form shortcode with minimal database queries
 */
function lbd_simple_search_form_shortcode($atts) {
    $atts = shortcode_atts(array(
        'layout' => 'horizontal',
        'placeholder' => 'Search for businesses...',
        'show_filters' => 'yes'
    ), $atts);
    
    // Prepare CSS classes
    $form_classes = 'business-search-form';
    if ($atts['layout'] === 'horizontal') {
        $form_classes .= ' horizontal';
    }
    
    // Build the form directly without complex logic
    ob_start();
    ?>
    <div class="<?php echo esc_attr($form_classes); ?>">
        <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>" class="search-form">
            <input type="hidden" name="post_type" value="business" />
            
            <div class="search-inputs">
                <div class="input-container search-field">
                    <input type="text" name="s" placeholder="<?php echo esc_attr($atts['placeholder']); ?>" value="<?php echo esc_attr(get_search_query()); ?>" />
                </div>
                
                <?php if ($atts['show_filters'] !== 'no'): ?>
                    <div class="input-container area-field">
                        <select name="area">
                            <option value="">All Areas</option>
                            <?php
                            // Get areas using get_terms - more efficient than a full query
                            $areas = get_terms(array(
                                'taxonomy' => 'business_area',
                                'hide_empty' => false,
                                'orderby' => 'name',
                                'order' => 'ASC',
                                'number' => 50, // Limit to prevent memory issues
                            ));
                            
                            if (!empty($areas) && !is_wp_error($areas)) {
                                foreach ($areas as $term) {
                                    $selected = (isset($_GET['area']) && $_GET['area'] === $term->slug) ? ' selected="selected"' : '';
                                    echo '<option value="' . esc_attr($term->slug) . '"' . $selected . '>' . esc_html($term->name) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="input-container category-field">
                        <select name="category">
                            <option value="">All Categories</option>
                            <?php
                            // Get categories using get_terms - more efficient than a full query
                            $categories = get_terms(array(
                                'taxonomy' => 'business_category',
                                'hide_empty' => false,
                                'orderby' => 'name',
                                'order' => 'ASC',
                                'number' => 50, // Limit to prevent memory issues
                            ));
                            
                            if (!empty($categories) && !is_wp_error($categories)) {
                                foreach ($categories as $term) {
                                    $selected = (isset($_GET['category']) && $_GET['category'] === $term->slug) ? ' selected="selected"' : '';
                                    echo '<option value="' . esc_attr($term->slug) . '"' . $selected . '>' . esc_html($term->name) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                <?php endif; ?>
                
                <button type="submit" class="search-button pill-button">Search</button>
            </div>
        </form>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('business_search', 'lbd_simple_search_form_shortcode'); 
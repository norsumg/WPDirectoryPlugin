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
 * Modify WordPress search to include businesses properly
 */
function lbd_modify_search_query($query) {
    // Only modify search queries on the front end
    if (!is_admin() && $query->is_search() && $query->is_main_query()) {
        // Check if we're explicitly searching for businesses
        $search_businesses = isset($_GET['post_type']) && $_GET['post_type'] === 'business';
        
        // If this is a business-specific search
        if ($search_businesses) {
            // Only search business post type
            $query->set('post_type', 'business');
            
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
    }
    
    return $query;
}
add_action('pre_get_posts', 'lbd_modify_search_query');

/**
 * Customize how businesses appear in search results
 */
function lbd_customize_business_search_results($content) {
    // Only modify on the main search page
    if (!is_search() || !in_the_loop() || !is_main_query()) {
        return $content;
    }
    
    // Only modify business post types
    if (get_post_type() !== 'business') {
        return $content;
    }
    
    // Get business details
    $business_id = get_the_ID();
    $area_terms = get_the_terms($business_id, 'business_area');
    $category_terms = get_the_terms($business_id, 'business_category');
    $area_name = $area_terms && !is_wp_error($area_terms) ? $area_terms[0]->name : '';
    $is_premium = get_post_meta($business_id, 'lbd_premium', true);
    
    // Start building the enhanced content
    $output = '<div class="business-search-result">';
    
    // Add a premium badge if applicable
    if ($is_premium) {
        $output .= '<span class="premium-badge">Premium</span>';
    }
    
    // Add area and category info
    $output .= '<div class="business-meta">';
    if ($area_name) {
        $output .= '<span class="business-area">Location: ' . esc_html($area_name) . '</span>';
    }
    
    if ($category_terms && !is_wp_error($category_terms)) {
        $output .= ' <span class="business-categories">Categories: ';
        $cats = array();
        foreach ($category_terms as $term) {
            $cats[] = '<a href="' . get_term_link($term) . '">' . esc_html($term->name) . '</a>';
        }
        $output .= implode(', ', $cats);
        $output .= '</span>';
    }
    $output .= '</div>';
    
    // Add the content
    $output .= $content;
    
    // Add a view business link
    $output .= '<div class="search-view-business">';
    $output .= '<a href="' . get_permalink() . '" class="business-view-link">View Business Details</a>';
    $output .= '</div>';
    
    $output .= '</div>';
    
    return $output;
}
add_filter('the_content', 'lbd_customize_business_search_results');

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
    }
    
    .business-area {
        margin-right: 15px;
    }
    
    .business-categories a {
        color: #0073aa;
        text-decoration: none;
    }
    
    .business-categories a:hover {
        text-decoration: underline;
    }
    
    .search-view-business {
        margin-top: 15px;
    }
    
    .business-view-link {
        display: inline-block;
        background: #0073aa;
        color: white;
        padding: 8px 16px;
        text-decoration: none;
        border-radius: 4px;
        font-size: 0.9em;
        transition: background 0.2s ease;
    }
    
    .business-view-link:hover {
        background: #005177;
        text-decoration: none;
    }
    
    /* Style search results with business post type */
    .search-results article.business {
        position: relative;
    }
    
    /* Make the search template notice the specific post type */
    .search-results .business-type-indicator {
        font-size: 0.8em;
        background: #f0f0f0;
        color: #333;
        padding: 2px 8px;
        border-radius: 3px;
        display: inline-block;
        margin-bottom: 10px;
    }
    </style>
    <?php
}
add_action('wp_head', 'lbd_add_search_results_styles');

/**
 * Modify search result title to be more specific for business searches
 */
function lbd_modify_search_title($title) {
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
}
add_filter('pre_get_document_title', 'lbd_modify_search_title', 15);

/**
 * Add the search form to the top of search results pages for business searches
 */
function lbd_add_search_form_to_search_page($content) {
    // Only add to search pages
    if (!is_search() || !in_the_loop() || !is_main_query()) {
        return $content;
    }
    
    // Only for business searches
    if (!isset($_GET['post_type']) || $_GET['post_type'] !== 'business') {
        return $content;
    }
    
    // Only add to the first post in search results
    static $search_form_added = false;
    if ($search_form_added) {
        return $content;
    }
    
    // Get the search form
    ob_start();
    ?>
    <div class="business-search-header">
        <h2>Business Directory Search</h2>
        <p>Refine your search or browse businesses by area and category.</p>
        <?php echo do_shortcode('[business_search_form layout="horizontal" button_style="pill" show_filters="yes"]'); ?>
    </div>
    <?php
    $search_form = ob_get_clean();
    
    // Mark as added
    $search_form_added = true;
    
    return $search_form . $content;
}
add_filter('the_content', 'lbd_add_search_form_to_search_page', 5); 
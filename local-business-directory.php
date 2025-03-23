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
 * Basic search filter - only keeps the post type filter
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
 * Create a simple search form shortcode
 */
function lbd_simple_search_form_shortcode($atts) {
    $atts = shortcode_atts(array(
        'layout' => 'horizontal',
        'placeholder' => 'Search for businesses...',
        'show_filters' => 'yes'
    ), $atts);
    
    // Build form HTML
    ob_start();
    ?>
    <div class="business-search-form <?php echo esc_attr($atts['layout']); ?>">
        <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>">
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
                            $areas = get_terms(array(
                                'taxonomy' => 'business_area',
                                'hide_empty' => false,
                                'number' => 50, 
                            ));
                            
                            if (!empty($areas) && !is_wp_error($areas)) {
                                foreach ($areas as $term) {
                                    echo '<option value="' . esc_attr($term->slug) . '">' . esc_html($term->name) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="input-container category-field">
                        <select name="category">
                            <option value="">All Categories</option>
                            <?php
                            $categories = get_terms(array(
                                'taxonomy' => 'business_category',
                                'hide_empty' => false,
                                'number' => 50,
                            ));
                            
                            if (!empty($categories) && !is_wp_error($categories)) {
                                foreach ($categories as $term) {
                                    echo '<option value="' . esc_attr($term->slug) . '">' . esc_html($term->name) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                <?php endif; ?>
                
                <button type="submit" class="search-button">Search</button>
            </div>
        </form>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('business_search', 'lbd_simple_search_form_shortcode');

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
 */
function lbd_hide_author_in_search() {
    if (!is_search()) {
        return;
    }
    
    // Only add CSS if we're searching for businesses
    if (!isset($_GET['post_type']) || $_GET['post_type'] !== 'business') {
        return;
    }
    
    ?>
    <style>
    /* Hide author information in business search results */
    body.search .post-type-business .entry-meta,
    body.search .post-type-business .byline,
    body.search .post-type-business .author,
    body.search .post-type-business .posted-by,
    body.search article.business .entry-meta,
    body.search article.business .byline,
    body.search article.business .author,
    body.search article.business .posted-by,
    body.search .business .author-info,
    body.search .business .author-avatar,
    body.search .business .author-bio,
    body.search .business .entry-meta a[rel="author"],
    /* Additional common author classes */
    body.search .business .entry-meta .author,
    body.search .business .post-author,
    body.search .business .meta-author,
    body.search .business .post-by,
    body.search .business .author-name,
    body.search .business .author-link {
        display: none !important;
    }
    
    /* Style for review ratings */
    .business-rating {
        display: inline-block;
        margin-left: 10px;
        font-size: 0.9em;
        color: #f7d032;
        vertical-align: middle;
    }
    
    .business-rating .rating-count {
        color: #666;
        font-size: 0.85em;
        margin-left: 5px;
    }
    
    .business-rating .stars-container {
        display: inline-block;
    }
    </style>
    <?php
}
add_action('wp_head', 'lbd_hide_author_in_search', 999);

/**
 * Add review ratings to business titles in search results
 */
function lbd_add_ratings_to_search_titles($title, $post_id = null) {
    // Only modify business search results
    if (!is_search() || !isset($_GET['post_type']) || $_GET['post_type'] !== 'business') {
        return $title;
    }
    
    // Make sure we have a post ID
    if (!$post_id) {
        global $post;
        if (isset($post->ID)) {
            $post_id = $post->ID;
        } else {
            return $title;
        }
    }
    
    // Check if this is a business post type
    if (get_post_type($post_id) !== 'business') {
        return $title;
    }
    
    // Get review data
    $review_average = get_post_meta($post_id, 'lbd_review_average', true);
    $review_count = get_post_meta($post_id, 'lbd_review_count', true);
    
    // If no reviews, just return the title
    if (empty($review_average)) {
        return $title;
    }
    
    // Format the rating
    $rating_html = '<span class="business-rating">';
    $rating_html .= '<span class="stars-container">';
    
    // Add star icons based on rating
    $full_stars = floor($review_average);
    $half_star = ($review_average - $full_stars) >= 0.5;
    
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $full_stars) {
            $rating_html .= '★'; // Full star
        } elseif ($i == $full_stars + 1 && $half_star) {
            $rating_html .= '½'; // Half star
        } else {
            $rating_html .= '☆'; // Empty star
        }
    }
    
    $rating_html .= '</span>';
    
    // Add review count if available
    if (!empty($review_count) && $review_count > 0) {
        $rating_html .= '<span class="rating-count">(' . intval($review_count) . ')</span>';
    }
    
    $rating_html .= '</span>';
    
    // Add the rating after the title
    return $title . $rating_html;
}
add_filter('the_title', 'lbd_add_ratings_to_search_titles', 10, 2); 
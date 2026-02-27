<?php
/**
 * Activation and database functions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create the reviews table on plugin activation
 */
function lbd_create_reviews_table() {
    global $wpdb;
    
    // Table name with prefix
    $table_name = $wpdb->prefix . 'lbd_reviews';
    
    // Check if table exists
    $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name;
    
    // Only create table if it doesn't exist
    if (!$table_exists) {
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            business_id bigint(20) NOT NULL,
            reviewer_name varchar(100) NOT NULL,
            reviewer_email varchar(255) DEFAULT '',
            review_text text NOT NULL,
            rating tinyint(1) NOT NULL,
            review_date datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            source varchar(50) DEFAULT 'google' NOT NULL,
            source_id varchar(100) DEFAULT '',
            approved tinyint(1) DEFAULT 0 NOT NULL,
            PRIMARY KEY  (id),
            KEY business_id (business_id)
        ) $charset_collate;";
        
        // Include WordPress database upgrade functions
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Create the table
        dbDelta($sql);
    }
}
add_action('lbd_activation', 'lbd_create_reviews_table');

/**
 * Update existing reviews table with new columns
 * This is run when the plugin version is updated
 */
function lbd_update_reviews_table_structure() {
    global $wpdb;
    
    // Table name with prefix
    $table_name = $wpdb->prefix . 'lbd_reviews';
    
    // Check if table exists
    $table_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table_name)) === $table_name;
    
    if ($table_exists) {
        // Check if reviewer_email column exists
        $column_exists = $wpdb->get_var("SHOW COLUMNS FROM {$table_name} LIKE 'reviewer_email'");
        
        // If the column doesn't exist, add it
        if (!$column_exists) {
            $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN reviewer_email varchar(255) DEFAULT '' AFTER reviewer_name");
        }
    }
}
add_action('plugins_loaded', 'lbd_update_reviews_table_structure');

/**
 * Add a review to the database
 * 
 * @param int $business_id The business post ID
 * @param string $reviewer_name Name of the reviewer
 * @param string $review_text Review content
 * @param int $rating Rating value 1-5
 * @param string $source Source of the review (e.g., 'google', 'manual')
 * @param string $source_id ID from the source system if applicable
 * @param bool $approved Whether the review is approved for display
 * @param string $reviewer_email Email of the reviewer
 * @return int|false The review ID or false on failure
 */
function lbd_add_review($business_id, $reviewer_name, $review_text, $rating, $source = 'google', $source_id = '', $approved = false, $reviewer_email = '') {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'lbd_reviews';
    
    $result = $wpdb->insert(
        $table_name,
        array(
            'business_id' => $business_id,
            'reviewer_name' => $reviewer_name,
            'reviewer_email' => $reviewer_email,
            'review_text' => $review_text,
            'rating' => $rating,
            'source' => $source,
            'source_id' => $source_id,
            'approved' => $approved ? 1 : 0
        ),
        array('%d', '%s', '%s', '%s', '%d', '%s', '%s', '%d')
    );
    
    if ($result) {
        return $wpdb->insert_id;
    }
    
    return false;
}

/**
 * Get reviews for a business
 * 
 * @param int $business_id The business post ID
 * @param bool $only_approved Whether to return only approved reviews
 * @return array Array of review objects
 */
function lbd_get_business_reviews($business_id, $only_approved = true) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'lbd_reviews';
    
    $where = "WHERE business_id = %d";
    $params = array($business_id);
    
    if ($only_approved) {
        $where .= " AND approved = 1";
    }
    
    $query = $wpdb->prepare(
        "SELECT * FROM {$table_name} {$where} ORDER BY review_date DESC",
        $params
    );
    
    return $wpdb->get_results($query);
}

/**
 * Calculate average rating for a business
 * 
 * @param int $business_id The business post ID
 * @return float|false Average rating or false if no reviews
 */
function lbd_get_business_average_rating($business_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'lbd_reviews';
    
    $query = $wpdb->prepare(
        "SELECT AVG(rating) FROM {$table_name} WHERE business_id = %d AND approved = 1",
        $business_id
    );
    
    $avg = $wpdb->get_var($query);
    
    if ($avg === null) {
        return false;
    }
    
    return round($avg, 1);
}

/**
 * Get total review count for a business
 * 
 * @param int $business_id The business post ID
 * @return int Total number of approved reviews
 */
function lbd_get_business_review_count($business_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'lbd_reviews';
    
    $query = $wpdb->prepare(
        "SELECT COUNT(*) FROM {$table_name} WHERE business_id = %d AND approved = 1",
        $business_id
    );
    
    return (int) $wpdb->get_var($query);
}

/**
 * Register the business_owner role with minimal capabilities.
 */
function lbd_register_business_owner_role() {
    if (!get_role('business_owner')) {
        add_role('business_owner', 'Business Owner', array('read' => true));
    }
}
add_action('lbd_activation', 'lbd_register_business_owner_role');
add_action('init', 'lbd_register_business_owner_role', 5);

/**
 * Create the "Claim Your Business" page if it doesn't already exist.
 * Stores the page ID in option lbd_claim_page_id.
 */
function lbd_create_claim_page() {
    $existing_page_id = get_option('lbd_claim_page_id');

    if ($existing_page_id && get_post_status($existing_page_id) === 'publish') {
        return;
    }

    $page_id = wp_insert_post(array(
        'post_title'   => 'Claim Your Business',
        'post_content' => '[claim_business_form]',
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_name'    => 'claim-your-business',
    ));

    if ($page_id && !is_wp_error($page_id)) {
        update_option('lbd_claim_page_id', $page_id);
    }
}
add_action('lbd_activation', 'lbd_create_claim_page');

/**
 * Create the "My Business" dashboard page if it doesn't already exist.
 */
function lbd_create_dashboard_page() {
    $existing = get_option('lbd_dashboard_page_id');
    if ($existing && get_post_status($existing) === 'publish') {
        return;
    }
    $page_id = wp_insert_post(array(
        'post_title'   => 'My Business',
        'post_content' => '[lbd_owner_dashboard]',
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_name'    => 'my-business',
    ));
    if ($page_id && !is_wp_error($page_id)) {
        update_option('lbd_dashboard_page_id', $page_id);
    }
}
add_action('lbd_activation', 'lbd_create_dashboard_page');

/**
 * Create the "Edit My Business" page if it doesn't already exist.
 */
function lbd_create_edit_business_page() {
    $existing = get_option('lbd_edit_business_page_id');
    if ($existing && get_post_status($existing) === 'publish') {
        return;
    }
    $page_id = wp_insert_post(array(
        'post_title'   => 'Edit My Business',
        'post_content' => '[lbd_owner_edit_business]',
        'post_status'  => 'publish',
        'post_type'    => 'page',
        'post_name'    => 'edit-my-business',
    ));
    if ($page_id && !is_wp_error($page_id)) {
        update_option('lbd_edit_business_page_id', $page_id);
    }
}
add_action('lbd_activation', 'lbd_create_edit_business_page');

/**
 * Ensure plugin pages exist for sites that already have the plugin installed.
 * Hooked to 'init' (not 'plugins_loaded') because wp_insert_post requires
 * the permalink / rewrite system to be initialised.
 */
function lbd_maybe_create_plugin_pages() {
    $pages = array(
        'lbd_claim_page_id'         => 'lbd_create_claim_page',
        'lbd_dashboard_page_id'     => 'lbd_create_dashboard_page',
        'lbd_edit_business_page_id' => 'lbd_create_edit_business_page',
    );
    foreach ($pages as $option_key => $creator_fn) {
        $page_id = get_option($option_key);
        if (!$page_id || get_post_status($page_id) !== 'publish') {
            call_user_func($creator_fn);
        }
    }
}
add_action('init', 'lbd_maybe_create_plugin_pages', 99);

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
            approved tinyint(1) DEFAULT 1 NOT NULL,
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
function lbd_add_review($business_id, $reviewer_name, $review_text, $rating, $source = 'google', $source_id = '', $approved = true, $reviewer_email = '') {
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

// Removed custom rewrite rules and template redirect functions
// These were causing errors in the plugin
// Instead, we'll use a WordPress page with the shortcode

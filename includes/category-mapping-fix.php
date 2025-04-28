<?php
/**
 * Fix for category mapping persistence
 * 
 * This file adds hooks to ensure category mappings are properly used during CSV import.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Hook into CSV import to apply our persistent category mappings
 * This function is called before WordPress processes taxonomies during import
 *
 * @param int $post_id The post ID being imported
 * @param array $data The CSV data for this post
 * @param array $options Import options
 */
function lbd_apply_persistent_category_mappings($post_id, $data, $options) {
    // Only process if we have category data and our mapping function exists
    if (!isset($data['business_category']) || empty($data['business_category']) || !function_exists('lbd_get_category_mapping_for_import')) {
        return;
    }
    
    $csv_category_name = trim($data['business_category']);
    
    // Try to get the mapping for this category
    $mapped_term_id = lbd_get_category_mapping_for_import($csv_category_name);
    
    // If we found a mapping, apply it directly
    if ($mapped_term_id > 0) {
        $term = get_term($mapped_term_id, 'business_category');
        if ($term && !is_wp_error($term)) {
            // Set the category directly
            wp_set_object_terms($post_id, $mapped_term_id, 'business_category');
            error_log("Pre-applied category mapping hook: Set '{$csv_category_name}' to term ID {$mapped_term_id} ({$term->name})");
            
            // This prevents the normal category processing in lbd_create_business_from_csv
            // by setting a flag on the options array
            add_filter('lbd_csv_import_options', function($import_options) use ($csv_category_name, $mapped_term_id) {
                $import_options['category_already_set'] = true;
                $import_options['mapped_category'] = $csv_category_name;
                $import_options['mapped_term_id'] = $mapped_term_id;
                return $import_options;
            });
        }
    }
}
add_action('lbd_before_csv_import_row', 'lbd_apply_persistent_category_mappings', 10, 3);

/**
 * Debug function to log all category mappings on plugin init
 */
function lbd_log_all_mappings_on_init() {
    // Only run in admin
    if (!is_admin()) {
        return;
    }
    
    // Check for our combined mappings option
    $all_mappings = get_option('lbd_all_category_mappings');
    if (is_array($all_mappings) && !empty($all_mappings)) {
        error_log('Found ' . count($all_mappings) . ' persistent category mappings:');
        foreach ($all_mappings as $key => $value) {
            error_log("Mapping: {$key} -> {$value}");
        }
    } else {
        error_log('No persistent category mappings found in lbd_all_category_mappings option.');
    }
}
add_action('admin_init', 'lbd_log_all_mappings_on_init'); 
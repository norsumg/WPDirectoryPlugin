<?php
/**
 * Category Mapping functionality for CSV imports
 * 
 * Provides intelligent category mapping with fuzzy matching and saved mappings
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Extract unique categories from a CSV file
 * 
 * @param string $file_path Path to the uploaded CSV file
 * @return array List of unique category names from the CSV
 */
function lbd_extract_categories_from_csv($file_path) {
    $unique_categories = array();
    
    // Validate file exists
    if (!file_exists($file_path)) {
        error_log("CSV file not found: " . $file_path);
        return array();
    }
    
    // Open the CSV file
    $handle = fopen($file_path, 'r');
    if (!$handle) {
        error_log("Failed to open CSV file: " . $file_path);
        return array();
    }
    
    // Get headers
    $headers = fgetcsv($handle);
    if (!$headers) {
        fclose($handle);
        error_log("Failed to read CSV headers or file is empty");
        return array();
    }
    
    // Only use parent_category_name for mapping
    $parent_idx = array_search('parent_category_name', $headers);
    if ($parent_idx === false) {
        fclose($handle);
        error_log("No parent_category_name column found in CSV");
        return array();
    }
    
    // Process each row
    $row_counter = 0;
    try {
        while (($data = fgetcsv($handle)) !== false) {
            $row_counter++;
            // Skip empty rows
            if (empty($data) || count($data) < $parent_idx + 1) {
                continue;
            }
            // Get parent category name
            if (isset($data[$parent_idx]) && !empty($data[$parent_idx])) {
                $parent_name = trim($data[$parent_idx]);
                if (!in_array($parent_name, $unique_categories)) {
                    $unique_categories[] = $parent_name;
                }
            }
            // Safety check - don't process too many rows
            if ($row_counter > 10000) {
                error_log("CSV processing stopped after 10,000 rows for safety");
                break;
            }
        }
    } catch (Exception $e) {
        error_log("Error processing CSV: " . $e->getMessage());
    }
    fclose($handle);
    return $unique_categories;
}

/**
 * Find the closest matching category using fuzzy matching
 * 
 * @param string $category_name The category name to match
 * @return array|null Matching data or null if no good match found
 */
function lbd_find_closest_category($category_name) {
    $categories = get_terms(array(
        'taxonomy' => 'business_category',
        'hide_empty' => false
    ));
    
    if (empty($categories) || is_wp_error($categories)) {
        return null;
    }
    
    $best_match = null;
    $highest_similarity = 0;
    
    foreach ($categories as $category) {
        // Calculate similarity percentage
        similar_text(
            strtolower($category_name),
            strtolower($category->name),
            $percent
        );
        
        // Track the best match
        if ($percent > $highest_similarity) {
            $highest_similarity = $percent;
            $best_match = array(
                'term' => $category,
                'similarity' => $percent
            );
        }
    }
    
    // Only return matches above 70% similarity
    return ($highest_similarity >= 70) ? $best_match : null;
}

/**
 * Save a category mapping for future use
 * 
 * @param string $csv_category Category name from CSV
 * @param int $wp_category_id WordPress category term_id
 * @return bool Success status
 */
function lbd_save_category_mapping($csv_category, $wp_category_id) {
    global $wpdb;
    $mapping_key = 'lbd_category_mapping_' . sanitize_title($csv_category);
    
    // Debug log before saving
    error_log("Attempting to save mapping for: " . $csv_category);
    error_log("Mapping key: " . $mapping_key);
    error_log("WordPress Category ID: " . $wp_category_id);
    
    // First, try to get all mappings from a single option
    $all_mappings = get_option('lbd_all_category_mappings', array());
    if (!is_array($all_mappings)) {
        $all_mappings = array();
    }
    
    // Add or update this mapping
    $all_mappings[sanitize_title($csv_category)] = intval($wp_category_id);
    
    // Save all mappings as a single option - FORCE autoload to YES and try direct database update first
    $serialized_mappings = maybe_serialize($all_mappings);
    $result = false;
    
    // First try direct database query for reliability
    $db_result = $wpdb->query($wpdb->prepare(
        "REPLACE INTO {$wpdb->options} (option_name, option_value, autoload) VALUES (%s, %s, %s)",
        'lbd_all_category_mappings',
        $serialized_mappings,
        'yes'
    ));
    
    if ($db_result !== false) {
        error_log("Saved mappings directly to database");
        $result = true;
        
        // Also ensure WordPress cache is updated
        wp_cache_set('lbd_all_category_mappings', $all_mappings, 'options');
    } else {
        // Fallback to WordPress API
        $result = update_option('lbd_all_category_mappings', $all_mappings, true); // true = autoload
    }
    
    // Also try to save as individual option for maximum compatibility
    update_option($mapping_key, intval($wp_category_id), true); // true = autoload
    
    // Debug log after saving
    error_log("Save result: " . ($result ? "Success" : "Failed"));
    
    // Double-check that our save worked
    $verify = get_option('lbd_all_category_mappings');
    if ($verify !== false && is_array($verify)) {
        error_log("Verified mappings saved successfully - found " . count($verify) . " mappings");
    } else {
        error_log("WARNING: Verification failed - could not retrieve saved mappings");
    }
    
    return $result;
}

/**
 * Get the suggested mapping for a category
 * 
 * @param string $csv_category Category name from CSV
 * @return array Mapping suggestion data
 */
function lbd_get_category_mapping_suggestion($category_name) {
    // Check if we have a saved mapping
    $mapping_key = 'lbd_category_mapping_' . sanitize_title($category_name);
    
    // First try the combined mappings approach
    $all_mappings = get_option('lbd_all_category_mappings', array());
    $saved_mapping = null;
    
    if (is_array($all_mappings) && isset($all_mappings[sanitize_title($category_name)])) {
        $saved_mapping = $all_mappings[sanitize_title($category_name)];
        error_log("Found mapping in combined option: " . $saved_mapping);
    } else {
        // Fall back to individual option
        $saved_mapping = get_option($mapping_key);
        error_log("Looking up individual option: " . $mapping_key);
    }
    
    // Debug log for retrieval attempt
    error_log("Looking up mapping for: " . $category_name);
    error_log("Mapping key: " . $mapping_key);
    error_log("Saved mapping found: " . ($saved_mapping ? "Yes (ID: $saved_mapping)" : "No"));
    
    if ($saved_mapping) {
        $term = get_term($saved_mapping, 'business_category');
        if ($term && !is_wp_error($term)) {
            error_log("Retrieved term name: " . $term->name);
            
            return array(
                'suggested_id' => $saved_mapping,
                'suggested_name' => $term->name,
                'similarity' => 100,
                'is_saved' => true
            );
        } else {
            error_log("Failed to get term with ID: " . $saved_mapping);
            if (is_wp_error($term)) {
                error_log("Term error: " . $term->get_error_message());
            }
        }
    }
    
    // No saved mapping, try fuzzy matching
    $closest = lbd_find_closest_category($category_name);
    
    if ($closest) {
        return array(
            'suggested_id' => $closest['term']->term_id,
            'suggested_name' => $closest['term']->name,
            'similarity' => $closest['similarity'],
            'is_saved' => false
        );
    }
    
    // No match found
    return array(
        'suggested_id' => 0,
        'suggested_name' => '',
        'similarity' => 0,
        'is_saved' => false
    );
}

/**
 * Create form for mapping categories
 * 
 * @param array $categories List of category names from CSV
 * @return string HTML form for category mapping
 */
function lbd_category_mapping_form($categories) {
    $html = '<form method="post" id="category-mapping-form">';
    $nonce = wp_nonce_field('lbd_category_mapping_action', 'lbd_category_mapping_nonce', true, false);
    $html .= $nonce;
    $html .= '<input type="hidden" name="lbd_action" value="save_category_mappings">';
    
    $html .= '<table class="wp-list-table widefat fixed striped">';
    $html .= '<thead><tr>';
    $html .= '<th>CSV Category</th>';
    $html .= '<th>Hierarchy</th>';
    $html .= '<th>WordPress Category</th>';
    $html .= '<th>Match Quality</th>';
    $html .= '<th>Status</th>';
    $html .= '</tr></thead>';
    $html .= '<tbody>';
    
    foreach ($categories as $category) {
        // Check if this is a child category with metadata
        $is_child = false;
        $parent_name = '';
        $display_name = $category;
        $actual_name = $category;
        
        if (preg_match('/^(.*?)\s*\[child of (.*?)\]$/', $category, $matches)) {
            $is_child = true;
            $actual_name = $matches[1];
            $parent_name = $matches[2];
            $display_name = $actual_name;
        }
        
        $suggestion = lbd_get_category_mapping_suggestion($actual_name);
        
        $html .= '<tr' . ($is_child ? ' class="is-child-category"' : '') . '>';
        $html .= '<td>' . esc_html($display_name) . '</td>';
        $html .= '<td>';
        if ($is_child) {
            $html .= '<span class="parent-indicator">Child of: <strong>' . esc_html($parent_name) . '</strong></span>';
        } else {
            $html .= '<span class="parent-indicator">Top level</span>';
        }
        $html .= '</td>';
        $html .= '<td>';
        
        // Store the actual name without the metadata
        $html .= '<input type="hidden" name="original_category[' . esc_attr($category) . ']" value="' . esc_attr($actual_name) . '">';
        
        // For child categories, store their parent
        if ($is_child) {
            $html .= '<input type="hidden" name="parent_category[' . esc_attr($category) . ']" value="' . esc_attr($parent_name) . '">';
        }
        
        // Dropdown with all available categories
        $html .= '<select name="category_mapping[' . esc_attr($category) . ']" class="category-mapping-select">';
        $html .= '<option value="0">-- Select Category --</option>';
        
        // Get all categories for dropdown
        $all_categories = get_terms(array(
            'taxonomy' => 'business_category',
            'hide_empty' => false
        ));
        
        if (!empty($all_categories) && !is_wp_error($all_categories)) {
            // Group categories by parentage
            $top_level_categories = array();
            $child_categories = array();
            
            foreach ($all_categories as $term) {
                if ($term->parent == 0) {
                    $top_level_categories[] = $term;
                } else {
                    if (!isset($child_categories[$term->parent])) {
                        $child_categories[$term->parent] = array();
                    }
                    $child_categories[$term->parent][] = $term;
                }
            }
            
            // Display categories in optgroups
            foreach ($top_level_categories as $parent_term) {
                // Option for the parent itself
                $selected = ($suggestion['suggested_id'] == $parent_term->term_id) ? 'selected' : '';
                $html .= '<option value="' . esc_attr($parent_term->term_id) . '" ' . $selected . '>';
                $html .= esc_html($parent_term->name);
                $html .= '</option>';
                
                // Child categories as indented options
                if (isset($child_categories[$parent_term->term_id])) {
                    foreach ($child_categories[$parent_term->term_id] as $child_term) {
                        $selected = ($suggestion['suggested_id'] == $child_term->term_id) ? 'selected' : '';
                        $html .= '<option value="' . esc_attr($child_term->term_id) . '" ' . $selected . '>';
                        $html .= '— ' . esc_html($child_term->name);
                        $html .= '</option>';
                    }
                }
            }
        }
        
        $html .= '</select>';
        $html .= '</td>';
        
        // Match quality indicator
        $quality_class = 'poor';
        if ($suggestion['similarity'] >= 90) {
            $quality_class = 'excellent';
        } elseif ($suggestion['similarity'] >= 80) {
            $quality_class = 'good';
        } elseif ($suggestion['similarity'] >= 70) {
            $quality_class = 'fair';
        }
        
        $html .= '<td><span class="match-quality ' . $quality_class . '">';
        if ($suggestion['similarity'] > 0) {
            $html .= round($suggestion['similarity']) . '%';
        } else {
            $html .= 'No match';
        }
        $html .= '</span></td>';
        
        // Status indicator
        $html .= '<td>';
        if ($suggestion['is_saved']) {
            $html .= '<span class="status-saved">Saved Mapping</span>';
        } elseif ($suggestion['suggested_id'] != 0) {
            $html .= '<span class="status-suggested">Suggested Match</span>';
        } else {
            $html .= '<span class="status-new">New / Unmatched</span>';
        }
        $html .= '</td>';
        
        $html .= '</tr>';
    }
    
    $html .= '</tbody></table>';
    
    // Save button
    $html .= '<p class="submit">';
    $html .= '<input type="submit" name="save_mappings" class="button button-primary" value="Save Mappings">';
    $html .= '</p>';
    
    $html .= '</form>';
    
    // Add some CSS
    $html .= '<style>
        .match-quality {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-weight: bold;
        }
        .excellent {
            background-color: #d4edda;
            color: #155724;
        }
        .good {
            background-color: #d1ecf1;
            color: #0c5460;
        }
        .fair {
            background-color: #fff3cd;
            color: #856404;
        }
        .poor {
            background-color: #f8d7da;
            color: #721c24;
        }
        .status-saved {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            background-color: #d4edda;
            color: #155724;
            font-weight: bold;
        }
        .status-suggested {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            background-color: #cce5ff;
            color: #004085;
            font-weight: bold;
        }
        .status-new {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            background-color: #f8d7da;
            color: #721c24;
            font-weight: bold;
        }
        select.category-mapping-select {
            width: 100%;
            max-width: 350px;
        }
        .parent-indicator {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            background-color: #f0f0f1;
            color: #50575e;
        }
        tr.is-child-category {
            background-color: #f9f9f9;
        }
    </style>';
    
    return $html;
}

/**
 * Process category mapping form submission
 */
function lbd_process_category_mapping() {
    // Check if form was submitted
    if (!isset($_POST['lbd_action']) || $_POST['lbd_action'] !== 'save_category_mappings') {
        error_log("Category mapping process: No form submission detected");
        return;
    }
    
    error_log("Category mapping form submitted - processing started");
    
    // Verify nonce
    if (!isset($_POST['lbd_category_mapping_nonce']) || 
        !wp_verify_nonce($_POST['lbd_category_mapping_nonce'], 'lbd_category_mapping_action')) {
        error_log("Category mapping process: Nonce verification failed");
        wp_die('Security check failed. Please try again.');
    }
    
    // Check for mapping data
    if (!isset($_POST['category_mapping']) || !is_array($_POST['category_mapping'])) {
        error_log("Category mapping process: No mapping data found in submission");
        return;
    }
    
    error_log("Category mapping data found: " . count($_POST['category_mapping']) . " mappings");
    error_log("POST data: " . print_r($_POST, true));
    
    $saved_count = 0;
    
    // Process each mapping
    foreach ($_POST['category_mapping'] as $csv_category => $wp_category_id) {
        $wp_category_id = intval($wp_category_id);
        
        // Skip empty selections
        if ($wp_category_id <= 0) {
            error_log("Skipping empty mapping for: " . $csv_category);
            continue;
        }
        
        // Get original category name without metadata
        $original_category = isset($_POST['original_category'][$csv_category]) 
            ? sanitize_text_field($_POST['original_category'][$csv_category]) 
            : $csv_category;
        
        error_log("Processing mapping: CSV=" . $original_category . ", WP ID=" . $wp_category_id);
        
        // Save the mapping
        if (lbd_save_category_mapping($original_category, $wp_category_id)) {
            $saved_count++;
            error_log("Mapping saved successfully for: " . $original_category);
        } else {
            error_log("Failed to save mapping for: " . $original_category);
        }
    }
    
    error_log("Category mapping complete: " . $saved_count . " mappings saved");
    
    // Set admin notice for next page load
    if ($saved_count > 0) {
        add_action('admin_notices', function() use ($saved_count) {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>' . sprintf(_n(
                '%d category mapping saved successfully.',
                '%d category mappings saved successfully.',
                $saved_count,
                'local-business-directory'
            ), $saved_count) . '</p>';
            echo '</div>';
        });
    }
}
add_action('admin_init', 'lbd_process_category_mapping');

/**
 * Display diagnostic information about saved category mappings
 * Helps debug why mappings aren't being remembered
 */
function lbd_display_mapping_diagnostics() {
    global $wpdb;
    
    // Only run this on the category mapping page
    if (!isset($_GET['page']) || $_GET['page'] !== 'lbd-import-businesses') {
        return;
    }
    
    echo '<div class="notice notice-info">';
    echo '<h3>Category Mapping Diagnostics</h3>';
    
    // Show sanitization examples
    echo '<h4>Sanitization Examples</h4>';
    echo '<p>This shows how category names are transformed into option keys:</p>';
    echo '<table class="widefat striped" style="width: auto; margin-bottom: 20px;">';
    echo '<thead><tr><th>Original Category Name</th><th>Sanitized Key</th></tr></thead>';
    echo '<tbody>';
    
    $example_categories = array(
        'Restaurants',
        'Health & Beauty',
        'Professional Services',
        'Home & Garden',
        'Restaurants [child of Food & Drink]',
        'IT & Computing'
    );
    
    foreach ($example_categories as $category) {
        // Extract actual name if it has metadata
        $actual_name = $category;
        if (preg_match('/^(.*?)\s*\[child of (.*?)\]$/', $category, $matches)) {
            $actual_name = $matches[1];
        }
        
        $sanitized = 'lbd_category_mapping_' . sanitize_title($actual_name);
        
        echo '<tr>';
        echo '<td>' . esc_html($actual_name) . '</td>';
        echo '<td><code>' . esc_html($sanitized) . '</code></td>';
        echo '</tr>';
    }
    
    echo '</tbody></table>';
    
    // Direct database check for mappings
    echo '<h4>Raw Database Records</h4>';
    echo '<p>Directly querying the database for our options:</p>';
    
    $raw_option = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->options} WHERE option_name = %s",
        'lbd_all_category_mappings'
    ));
    
    if ($raw_option) {
        echo '<div style="background: #f0f0f1; padding: 10px; margin-bottom: 15px; border-radius: 4px;">';
        echo '<p><strong>Combined Mappings in Database:</strong></p>';
        echo '<ul>';
        echo '<li><strong>Option ID:</strong> ' . esc_html($raw_option->option_id) . '</li>';
        echo '<li><strong>Option Name:</strong> ' . esc_html($raw_option->option_name) . '</li>';
        echo '<li><strong>Autoload:</strong> ' . esc_html($raw_option->autoload) . '</li>';
        echo '<li><strong>Raw Value Length:</strong> ' . strlen($raw_option->option_value) . ' bytes</li>';
        echo '</ul>';
        
        // Try to unserialize for display
        $unserialized = maybe_unserialize($raw_option->option_value);
        if (is_array($unserialized)) {
            echo '<p>Successfully unserialized into array with ' . count($unserialized) . ' items</p>';
        } else {
            echo '<p>Warning: Could not unserialize into array</p>';
        }
        echo '</div>';
    } else {
        echo '<p><strong>Error:</strong> No direct record found in options table for combined mappings.</p>';
    }
    
    // Check for combined mappings
    $all_mappings = get_option('lbd_all_category_mappings', array());
    
    if (!empty($all_mappings) && is_array($all_mappings)) {
        echo '<h4>Combined Mappings</h4>';
        echo '<p>Found ' . count($all_mappings) . ' mappings in the combined option:</p>';
        echo '<table class="widefat striped" style="width: auto; margin-bottom: 20px;">';
        echo '<thead><tr><th>CSV Category (sanitized)</th><th>WP Category ID</th><th>WP Category Name</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($all_mappings as $sanitized_cat => $wp_cat_id) {
            // Get the WordPress category name
            $term = get_term($wp_cat_id, 'business_category');
            $wp_cat_name = is_wp_error($term) ? 'Error: ' . $term->get_error_message() : ($term ? $term->name : 'Category not found');
            
            echo '<tr>';
            echo '<td><code>' . esc_html($sanitized_cat) . '</code></td>';
            echo '<td>' . esc_html($wp_cat_id) . '</td>';
            echo '<td>' . esc_html($wp_cat_name) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    } else {
        echo '<p><strong>No combined mappings found.</strong> The combined option is not set or not an array.</p>';
    }
    
    // Query the options table for individual mappings
    $mapping_options = $wpdb->get_results(
        "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'lbd_category_mapping_%'"
    );
    
    if (empty($mapping_options)) {
        echo '<p><strong>No individual category mappings found</strong> in the database.</p>';
    } else {
        echo '<h4>Individual Mappings</h4>';
        echo '<p>Found ' . count($mapping_options) . ' saved individual category mappings:</p>';
        echo '<table class="widefat striped" style="width: auto;">';
        echo '<thead><tr><th>CSV Category</th><th>WP Category ID</th><th>WP Category Name</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($mapping_options as $option) {
            $option_key = $option->option_name;
            $wp_cat_id = intval($option->option_value);
            
            // Extract the original category name from the option name
            $csv_category = str_replace('lbd_category_mapping_', '', $option_key);
            
            // Try to unsanitize the title (approximate)
            $csv_category = ucwords(str_replace('-', ' ', $csv_category));
            
            // Get the WordPress category name
            $term = get_term($wp_cat_id, 'business_category');
            $wp_cat_name = is_wp_error($term) ? 'Error: ' . $term->get_error_message() : ($term ? $term->name : 'Category not found');
            
            echo '<tr>';
            echo '<td>' . esc_html($csv_category) . '</td>';
            echo '<td>' . esc_html($wp_cat_id) . '</td>';
            echo '<td>' . esc_html($wp_cat_name) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }
    
    // Add a reset button
    echo '<form method="post">';
    echo wp_nonce_field('lbd_reset_mappings', 'lbd_reset_mappings_nonce', true, false);
    echo '<input type="hidden" name="lbd_action" value="reset_mappings">';
    echo '<p><button type="submit" class="button button-secondary">Reset All Category Mappings</button></p>';
    echo '</form>';
    
    echo '<p><strong>Debug Tips:</strong></p>';
    echo '<ol>';
    echo '<li>Check the WordPress error log for detailed debugging information.</li>';
    echo '<li>The plugin is using two approaches to save mappings - a combined option and individual options.</li>';
    echo '<li>Make sure your WordPress installation allows option saving (check permissions, database integrity).</li>';
    echo '</ol>';
    
    echo '</div>';
}

/**
 * Reset all category mappings if requested
 */
function lbd_reset_category_mappings() {
    if (!isset($_POST['lbd_action']) || $_POST['lbd_action'] !== 'reset_mappings') {
        return;
    }
    
    if (!isset($_POST['lbd_reset_mappings_nonce']) || 
        !wp_verify_nonce($_POST['lbd_reset_mappings_nonce'], 'lbd_reset_mappings')) {
        wp_die('Security check failed');
    }
    
    global $wpdb;
    
    // Delete combined mappings
    delete_option('lbd_all_category_mappings');
    
    // Delete all individual mappings
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'lbd_category_mapping_%'");
    
    // Clear potential caches
    wp_cache_delete('lbd_all_category_mappings', 'options');
    
    // Add admin notice
    add_action('admin_notices', function() {
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p>All category mappings have been reset.</p>';
        echo '</div>';
    });
}
add_action('admin_init', 'lbd_reset_category_mappings');
add_action('admin_notices', 'lbd_display_mapping_diagnostics');

/**
 * Get category mapping for import - optimized version for import process
 * This function is more efficient as it loads all mappings at once
 * 
 * @param string $category_name Category name from CSV
 * @return int|null WordPress category ID or null if not found
 */
function lbd_get_category_mapping_for_import($category_name) {
    static $all_mappings = null;
    static $cache = array();
    
    // Check in local memory cache first (fastest)
    if (isset($cache[$category_name])) {
        return $cache[$category_name];
    }
    
    // Load all mappings once (for performance)
    if ($all_mappings === null) {
        $all_mappings = get_option('lbd_all_category_mappings', array());
        error_log("Loaded " . count($all_mappings) . " mappings from combined option");
    }
    
    $sanitized_name = sanitize_title($category_name);
    
    // Check in combined mappings
    if (is_array($all_mappings) && isset($all_mappings[$sanitized_name])) {
        $wp_category_id = $all_mappings[$sanitized_name];
        $cache[$category_name] = $wp_category_id; // Cache for future use
        error_log("Used mapping for '$category_name' -> term_id: $wp_category_id");
        return $wp_category_id;
    }
    
    // Try individual option as fallback
    $mapping_key = 'lbd_category_mapping_' . $sanitized_name;
    $wp_category_id = get_option($mapping_key);
    
    if ($wp_category_id) {
        $cache[$category_name] = $wp_category_id; // Cache for future use
        error_log("Used individual mapping for '$category_name' -> term_id: $wp_category_id");
        return $wp_category_id;
    }
    
    // Special case - log when no mapping is found
    error_log("No mapping found for category: '$category_name'");
    $cache[$category_name] = null; // Cache the miss too
    return null;
}

/**
 * Check saved mappings on page load
 * This will run on every admin page to help debug persistence issues
 */
function lbd_check_saved_mappings() {
    // Only run this on our plugin's admin pages
    if (!isset($_GET['page']) || strpos($_GET['page'], 'lbd-') !== 0) {
        return;
    }
    
    error_log('----------- CHECKING SAVED MAPPINGS ON PAGE LOAD -----------');
    
    // Check combined mappings
    $all_mappings = get_option('lbd_all_category_mappings');
    error_log('Combined mappings option exists: ' . ($all_mappings !== false ? 'YES' : 'NO'));
    
    if ($all_mappings !== false) {
        if (is_array($all_mappings)) {
            error_log('Combined mappings is an array with ' . count($all_mappings) . ' items');
            error_log('Combined mappings contents: ' . print_r($all_mappings, true));
        } else {
            error_log('Combined mappings is NOT an array but: ' . gettype($all_mappings));
            error_log('Value: ' . print_r($all_mappings, true));
        }
    }
    
    // Check for individual mappings
    global $wpdb;
    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE 'lbd_category_mapping_%'");
    error_log('Individual mapping count in database: ' . $count);
    
    // Check autoload status for our options
    $autoload_status = $wpdb->get_var("SELECT autoload FROM {$wpdb->options} WHERE option_name = 'lbd_all_category_mappings'");
    error_log('Autoload status for combined mappings: ' . ($autoload_status ?: 'option not found'));
    
    error_log('----------- END CHECKING SAVED MAPPINGS -----------');
}
add_action('admin_init', 'lbd_check_saved_mappings', 5); // Run early 
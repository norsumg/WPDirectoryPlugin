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
    $hierarchical_categories = array();
    
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
    
    // Find the category column index
    $category_idx = array_search('business_category', $headers);
    if ($category_idx === false) {
        // Also check for parent_category_name column
        $parent_idx = array_search('parent_category_name', $headers);
        if ($parent_idx !== false) {
            // Find column for child category
            $possible_category_columns = array('category_name', 'subcategory', 'child_category');
            foreach ($possible_category_columns as $column) {
                $idx = array_search($column, $headers);
                if ($idx !== false) {
                    $category_idx = $idx;
                    break;
                }
            }
        }
        
        if ($category_idx === false) {
            fclose($handle);
            error_log("No business_category column found in CSV");
            return array();
        }
    }
    
    // Also check for parent_category_name column
    $parent_idx = array_search('parent_category_name', $headers);
    
    // Process each row
    $row_counter = 0;
    try {
        while (($data = fgetcsv($handle)) !== false) {
            $row_counter++;
            
            // Skip empty rows
            if (empty($data) || count($data) < $category_idx + 1) {
                continue;
            }
            
            // Get category name
            if (isset($data[$category_idx]) && !empty($data[$category_idx])) {
                $category_name = trim($data[$category_idx]);
                
                // Handle hierarchical categories (Parent > Child)
                if (strpos($category_name, '>') !== false) {
                    $category_parts = array_map('trim', explode('>', $category_name));
                    $parent_name = $category_parts[0];
                    $child_name = $category_parts[1];
                    
                    // Store hierarchical relationship for later processing
                    if (!isset($hierarchical_categories[$parent_name])) {
                        $hierarchical_categories[$parent_name] = array();
                    }
                    if (!in_array($child_name, $hierarchical_categories[$parent_name])) {
                        $hierarchical_categories[$parent_name][] = $child_name;
                    }
                    
                    // Add both parent and child to unique categories
                    if (!in_array($parent_name, $unique_categories)) {
                        $unique_categories[] = $parent_name;
                    }
                    if (!in_array($child_name, $unique_categories)) {
                        $unique_categories[] = $child_name;
                    }
                } else {
                    // Check if there's a separate parent category column
                    if ($parent_idx !== false && isset($data[$parent_idx]) && !empty($data[$parent_idx])) {
                        $parent_name = trim($data[$parent_idx]);
                        $child_name = $category_name;
                        
                        // Store hierarchical relationship
                        if (!isset($hierarchical_categories[$parent_name])) {
                            $hierarchical_categories[$parent_name] = array();
                        }
                        if (!in_array($child_name, $hierarchical_categories[$parent_name])) {
                            $hierarchical_categories[$parent_name][] = $child_name;
                        }
                        
                        // Add both parent and child to unique categories
                        if (!in_array($parent_name, $unique_categories)) {
                            $unique_categories[] = $parent_name;
                        }
                        if (!in_array($child_name, $unique_categories)) {
                            $unique_categories[] = $child_name;
                        }
                    } else {
                        // Simple category
                        if (!in_array($category_name, $unique_categories)) {
                            $unique_categories[] = $category_name;
                        }
                    }
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
    
    // Add special metadata to help with mapping child categories
    foreach ($hierarchical_categories as $parent => $children) {
        foreach ($children as $child) {
            // Find the child's index in the unique_categories array
            $index = array_search($child, $unique_categories);
            if ($index !== false) {
                // Add a hint to show this is a child category
                $unique_categories[$index] = $child . ' [child of ' . $parent . ']';
            }
        }
    }
    
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
 * Get the suggested mapping for a category
 * 
 * @param string $category_name Category name from CSV
 * @return array Mapping suggestion data
 */
function lbd_get_category_mapping_suggestion($category_name) {
    // Check if we have a saved mapping
    $mapping_key = 'lbd_category_mapping_' . sanitize_title($category_name);
    $saved_mapping = get_option($mapping_key);
    
    if ($saved_mapping) {
        $term = get_term($saved_mapping, 'business_category');
        if ($term && !is_wp_error($term)) {
            return array(
                'suggested_id' => $saved_mapping,
                'suggested_name' => $term->name,
                'similarity' => 100,
                'is_saved' => true
            );
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
 * Save a category mapping for future use
 * 
 * @param string $csv_category Category name from CSV
 * @param int $wp_category_id WordPress category term_id
 * @return bool Success status
 */
function lbd_save_category_mapping($csv_category, $wp_category_id) {
    $mapping_key = 'lbd_category_mapping_' . sanitize_title($csv_category);
    return update_option($mapping_key, intval($wp_category_id), false);
}

/**
 * Create form for mapping categories
 * 
 * @param array $categories List of category names from CSV
 * @return string HTML form for category mapping
 */
function lbd_category_mapping_form($categories) {
    $html = '<form method="post" id="category-mapping-form">';
    $html .= wp_nonce_field('lbd_category_mapping_action', 'lbd_category_mapping_nonce', true, false);
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
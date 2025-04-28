<?php
/**
 * Category Debug Script
 * 
 * This script inspects the current category mappings and
 * directly updates the category for A S Gane.
 */

// Load WordPress - adjust path as needed
define('WP_DEBUG', true);
require_once('../../../wp-load.php');

// Only allow admins to run this script
if (!current_user_can('manage_options')) {
    die('You do not have permission to run this script.');
}

echo "<h1>Category Debug Information</h1>";

// 1. Get the saved category mappings
echo "<h2>Saved Category Mappings</h2>";
$category_mappings = get_option('lbd_category_mappings', array());

if (empty($category_mappings)) {
    echo "No saved category mappings found.<br>";
} else {
    echo "<pre>";
    foreach ($category_mappings as $key => $term_id) {
        echo "CSV Key: '$key' → Term ID: $term_id<br>";
        $term = get_term($term_id, 'business_category');
        if ($term && !is_wp_error($term)) {
            echo "  → Term Name: '{$term->name}'<br>";
        } else {
            echo "  → Term not found!<br>";
        }
    }
    echo "</pre>";
}

// 2. Find the Bathroom Fitter category
echo "<h2>Bathroom Fitter Category</h2>";
$bathroom_fitter = get_term_by('name', 'Bathroom Fitter', 'business_category');
if ($bathroom_fitter) {
    echo "Found Bathroom Fitter category (ID: {$bathroom_fitter->term_id})";
    $bathroom_fitter_id = $bathroom_fitter->term_id;
} else {
    $bathroom_fitter = get_term_by('name', 'Bathroom Fitters', 'business_category');
    if ($bathroom_fitter) {
        echo "Found Bathroom Fitters category (ID: {$bathroom_fitter->term_id})";
        $bathroom_fitter_id = $bathroom_fitter->term_id;
    } else {
        echo "Could not find Bathroom Fitter or Bathroom Fitters category";
        
        // Search for similar categories
        echo "<h3>Similar Categories:</h3>";
        $all_terms = get_terms([
            'taxonomy' => 'business_category',
            'hide_empty' => false,
            'search' => 'bathroom',
        ]);
        
        if (!is_wp_error($all_terms) && !empty($all_terms)) {
            echo "<ul>";
            foreach ($all_terms as $term) {
                echo "<li>{$term->name} (ID: {$term->term_id})</li>";
            }
            echo "</ul>";
        } else {
            echo "No bathroom-related categories found.";
        }
        
        $bathroom_fitter_id = null;
    }
}

// 3. Find A S Gane business
echo "<h2>A S Gane Business</h2>";
$business = get_page_by_title('A S Gane', OBJECT, 'business');
if (!$business) {
    echo "Could not find A S Gane business post!";
} else {
    echo "Found A S Gane business (ID: {$business->ID})<br>";
    
    // Get current terms
    $current_terms = wp_get_object_terms($business->ID, 'business_category');
    if (!is_wp_error($current_terms)) {
        echo "Current categories:<br><ul>";
        foreach ($current_terms as $term) {
            echo "<li>{$term->name} (ID: {$term->term_id})</li>";
        }
        echo "</ul>";
    } else {
        echo "Error getting current categories.";
    }
    
    // Update the category if Bathroom Fitter was found
    if ($bathroom_fitter_id) {
        echo "<h3>Updating category to Bathroom Fitter</h3>";
        $result = wp_set_object_terms($business->ID, [$bathroom_fitter_id], 'business_category');
        if (!is_wp_error($result)) {
            echo "Successfully updated category for A S Gane to Bathroom Fitter.";
        } else {
            echo "Error updating category: " . $result->get_error_message();
        }
    }
}

// 4. Get any CSV mappings related to Bathroom remodeler
echo "<h2>Mappings for 'Bathroom remodeler'</h2>";
foreach ($category_mappings as $key => $term_id) {
    if (strpos($key, 'Bathroom remodeler') !== false) {
        echo "Found mapping: '$key' → Term ID: $term_id<br>";
        $term = get_term($term_id, 'business_category');
        if ($term && !is_wp_error($term)) {
            echo "  → Term Name: '{$term->name}'<br>";
        } else {
            echo "  → Term not found!<br>";
        }
    }
}

// 5. Add a direct mapping if needed
echo "<h2>Adding Direct Mapping</h2>";
if ($bathroom_fitter_id) {
    // Add mappings for various formats that might be in the CSV
    $mappings_to_add = [
        'Bathroom remodeler' => $bathroom_fitter_id,
        'Bathroom Remodeler' => $bathroom_fitter_id,
        'bathroom remodeler' => $bathroom_fitter_id,
        'Bathroom remodeller' => $bathroom_fitter_id,
        'Bathroom Remodeller' => $bathroom_fitter_id,
        'Bathroom Renovator' => $bathroom_fitter_id,
        'bathroom renovator' => $bathroom_fitter_id,
    ];
    
    foreach ($mappings_to_add as $key => $id) {
        $category_mappings[$key] = $id;
        echo "Added mapping: '$key' → Bathroom Fitter (ID: $id)<br>";
    }
    
    update_option('lbd_category_mappings', $category_mappings);
    echo "Updated category mappings in database.<br>";
}

echo "<h2>Done!</h2>";
echo "<p>Please try your CSV import again with these updated mappings.</p>";
?> 
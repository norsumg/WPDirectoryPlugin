<?php
/**
 * Category Importer for Local Business Directory
 * 
 * Allows admin to import business categories from CSV file
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add the Category Importer submenu page
 */
function lbd_add_category_importer_page() {
    add_submenu_page(
        'edit.php?post_type=business',
        'Category Importer',
        'Category Importer',
        'manage_options',
        'lbd-category-importer',
        'lbd_category_importer_page'
    );
}
add_action('admin_menu', 'lbd_add_category_importer_page');

/**
 * Render the Category Importer admin page
 */
function lbd_category_importer_page() {
    // Security check
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    
    // Process import if submitted
    $import_results = array();
    if (isset($_POST['action']) && $_POST['action'] === 'import_categories') {
        // Verify nonce
        check_admin_referer('lbd_import_categories_nonce');
        
        // Handle file upload
        if (!empty($_FILES['category_csv']['tmp_name'])) {
            $import_results = lbd_process_category_import($_FILES['category_csv']['tmp_name']);
        } else {
            $import_results['error'] = 'No file was uploaded.';
        }
    }
    
    ?>
    <div class="wrap">
        <h1>Business Directory: Category Importer</h1>
        
        <div class="card">
            <h2>Import Categories from CSV</h2>
            <p>Use this tool to import business categories and subcategories from a CSV file.</p>
            
            <h3>CSV Format</h3>
            <p>Your CSV file should have the following columns:</p>
            <ul>
                <li><strong>category_name</strong> (required) - The name of the category</li>
                <li><strong>parent_category_name</strong> (optional) - The name of the parent category (leave blank for top-level categories)</li>
                <li><strong>description</strong> (optional) - Category description</li>
                <li><strong>slug</strong> (optional) - Custom slug (will be auto-generated if empty)</li>
            </ul>
            
            <p>Example:</p>
            <pre>category_name,parent_category_name,description,slug
Restaurants,,All food establishments,restaurants
Italian,Restaurants,Italian cuisine restaurants,italian-restaurants
Pizza,Italian,Pizza restaurants,pizza
Chinese,Restaurants,Chinese cuisine restaurants,chinese-restaurants</pre>
            
            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('lbd_import_categories_nonce'); ?>
                <input type="hidden" name="action" value="import_categories">
                
                <p>
                    <label for="category_csv"><strong>Upload CSV File:</strong></label><br />
                    <input type="file" name="category_csv" id="category_csv" accept=".csv" required />
                </p>
                
                <p>
                    <label>
                        <input type="checkbox" name="update_existing" value="1" checked />
                        Update existing categories if they already exist
                    </label>
                </p>
                
                <p>
                    <button type="submit" class="button button-primary">Import Categories</button>
                </p>
            </form>
        </div>
        
        <?php if (!empty($import_results)): ?>
            <div class="card import-results">
                <h2>Import Results</h2>
                
                <?php if (isset($import_results['error'])): ?>
                    <div class="notice notice-error">
                        <p><?php echo esc_html($import_results['error']); ?></p>
                    </div>
                <?php else: ?>
                    <div class="notice notice-success">
                        <p>Import completed with the following results:</p>
                    </div>
                    
                    <ul>
                        <li><strong>Categories created:</strong> <?php echo intval($import_results['created']); ?></li>
                        <li><strong>Categories updated:</strong> <?php echo intval($import_results['updated']); ?></li>
                        <li><strong>Categories skipped:</strong> <?php echo intval($import_results['skipped']); ?></li>
                        <li><strong>Errors:</strong> <?php echo intval($import_results['errors']); ?></li>
                    </ul>
                    
                    <?php if (!empty($import_results['error_details'])): ?>
                        <h3>Error Details</h3>
                        <ul class="error-list">
                            <?php foreach ($import_results['error_details'] as $error): ?>
                                <li><?php echo esc_html($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <style>
        .error-list {
            background: #f8f8f8;
            padding: 10px 20px;
            border-left: 4px solid #dc3232;
        }
    </style>
    <?php
}

/**
 * Process the category import from CSV
 *
 * @param string $file_path Path to the uploaded CSV file
 * @return array Import results
 */
function lbd_process_category_import($file_path) {
    // Initialize results
    $results = array(
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => 0,
        'error_details' => array()
    );
    
    // Open the CSV file
    $handle = fopen($file_path, 'r');
    if (!$handle) {
        return array('error' => 'Could not open the CSV file.');
    }
    
    // Read the header row to get column names
    $header = fgetcsv($handle);
    if (!$header) {
        fclose($handle);
        return array('error' => 'Could not read CSV header row.');
    }
    
    // Normalize header names
    $header = array_map('trim', array_map('strtolower', $header));
    
    // Check for required columns
    if (!in_array('category_name', $header)) {
        fclose($handle);
        return array('error' => 'CSV file must have a "category_name" column.');
    }
    
    // Get column indexes
    $category_name_idx = array_search('category_name', $header);
    $parent_category_name_idx = array_search('parent_category_name', $header);
    $description_idx = array_search('description', $header);
    $slug_idx = array_search('slug', $header);
    
    // Whether to update existing categories
    $update_existing = isset($_POST['update_existing']) && $_POST['update_existing'] == '1';
    
    // Process each row
    $row_number = 1; // Start at 1 because we already read the header row
    $category_cache = array(); // Cache for looking up categories
    
    while (($row = fgetcsv($handle)) !== false) {
        $row_number++;
        
        // Skip empty rows
        if (empty($row[$category_name_idx])) {
            $results['skipped']++;
            continue;
        }
        
        // Get values from the row
        $category_name = trim($row[$category_name_idx]);
        $parent_category_name = ($parent_category_name_idx !== false && isset($row[$parent_category_name_idx])) ? trim($row[$parent_category_name_idx]) : '';
        $description = ($description_idx !== false && isset($row[$description_idx])) ? trim($row[$description_idx]) : '';
        $slug = ($slug_idx !== false && isset($row[$slug_idx])) ? trim($row[$slug_idx]) : '';
        
        // Find parent category ID if provided
        $parent_term_id = 0;
        if (!empty($parent_category_name)) {
            // Check if we've already processed this parent
            if (isset($category_cache[$parent_category_name])) {
                $parent_term_id = $category_cache[$parent_category_name];
            } else {
                // Look up parent by name
                $parent_term = term_exists($parent_category_name, 'business_category');
                
                if (!$parent_term) {
                    // Parent doesn't exist, create an error
                    $results['errors']++;
                    $results['error_details'][] = "Row {$row_number}: Parent category '{$parent_category_name}' does not exist for '{$category_name}'. Parents must be imported before their children.";
                    continue;
                }
                
                $parent_term_id = $parent_term['term_id'];
                $category_cache[$parent_category_name] = $parent_term_id;
            }
        }
        
        // Check if category exists
        $term_args = array(
            'parent' => $parent_term_id,
            'description' => $description
        );
        
        if (!empty($slug)) {
            $term_args['slug'] = $slug;
        }
        
        $existing_term = term_exists($category_name, 'business_category', $parent_term_id);
        
        if ($existing_term) {
            // Category already exists
            if ($update_existing) {
                // Update the existing category
                $result = wp_update_term($existing_term['term_id'], 'business_category', $term_args);
                
                if (is_wp_error($result)) {
                    $results['errors']++;
                    $results['error_details'][] = "Row {$row_number}: Failed to update '{$category_name}': " . $result->get_error_message();
                } else {
                    $results['updated']++;
                    $category_cache[$category_name] = $result['term_id'];
                }
            } else {
                // Skip this category
                $results['skipped']++;
                $category_cache[$category_name] = $existing_term['term_id'];
            }
        } else {
            // Create new category
            $result = wp_insert_term($category_name, 'business_category', $term_args);
            
            if (is_wp_error($result)) {
                $results['errors']++;
                $results['error_details'][] = "Row {$row_number}: Failed to create '{$category_name}': " . $result->get_error_message();
            } else {
                $results['created']++;
                $category_cache[$category_name] = $result['term_id'];
            }
        }
    }
    
    // Close the file
    fclose($handle);
    
    return $results;
}

/**
 * Add parent category dropdown to the Quick Edit interface
 */
function lbd_add_parent_category_dropdown_to_quick_edit() {
    $screen = get_current_screen();
    
    // Only add to the business_category taxonomy screen
    if (!$screen || $screen->id !== 'edit-business_category') {
        return;
    }
    
    // Add our custom JavaScript to enhance the quick edit form
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Make the parent dropdown more visible and prominent in WordPress quick edit
        $('.inline-edit-row fieldset .inline-edit-col').css('width', '100%');
        
        // Enhance the existing parent dropdown UI
        $(document).on('click', '.editinline', function() {
            // Add a delay to ensure the quick edit form is loaded
            setTimeout(function() {
                var parentDropdown = $('.inline-edit-row select[name="parent"]');
                
                // Add a more visible label
                if (parentDropdown.length > 0) {
                    // Remove existing label if any
                    parentDropdown.prev('label').remove();
                    
                    // Move parent dropdown to the top for better visibility
                    var parentField = parentDropdown.parent();
                    parentField.prepend('<label style="display:block; margin-bottom:5px; font-weight:bold;">Parent Category:</label>');
                    parentField.prepend(parentDropdown);
                    
                    // Style the parent dropdown
                    parentDropdown.css({
                        'width': '100%',
                        'max-width': '400px',
                        'padding': '8px',
                        'margin-bottom': '15px',
                        'border': '2px solid #2271b1',
                        'border-radius': '4px'
                    });
                }
            }, 100);
        });
    });
    </script>
    <?php
}
add_action('admin_footer-edit-tags.php', 'lbd_add_parent_category_dropdown_to_quick_edit');

/**
 * AJAX handler to get business categories - no longer needed with native dropdown enhancement
 */ 
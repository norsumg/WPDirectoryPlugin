<?php
/**
 * Admin functionality for Local Business Directory
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add admin menu pages
 */
function lbd_add_admin_menu() {
    add_menu_page(
        'Local Business Directory',
        'Business Directory',
        'manage_options',
        'local-business-directory',
        'lbd_admin_main_page',
        'dashicons-store',
        30
    );
    
    add_submenu_page(
        'local-business-directory',
        'CSV Import',
        'CSV Import',
        'manage_options',
        'lbd-csv-import',
        'lbd_csv_import_page'
    );

    add_submenu_page(
        'local-business-directory',
        'Reviews Manager',
        'Reviews',
        'manage_options',
        'lbd-reviews',
        'lbd_reviews_page'
    );
    
    add_submenu_page(
        'local-business-directory',
        'Link Categories',
        'Link Categories',
        'manage_options',
        'lbd-link-categories',
        'lbd_link_categories_page'
    );
}
add_action('admin_menu', 'lbd_add_admin_menu');

/**
 * AJAX handler to toggle schema debug mode
 */
function lbd_ajax_toggle_schema_debug() {
    check_ajax_referer('lbd_debug_actions', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
    }
    
    $enable = isset($_POST['enable']) && $_POST['enable'] === '1';
    update_option('lbd_debug_schema', $enable);
    
    wp_send_json_success(['enabled' => $enable]);
}
add_action('wp_ajax_lbd_toggle_schema_debug', 'lbd_ajax_toggle_schema_debug');

/**
 * AJAX handler to delete schema debug log
 */
function lbd_ajax_delete_schema_log() {
    check_ajax_referer('lbd_debug_actions', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permission denied');
    }
    
    $log_file = WP_CONTENT_DIR . '/lbd-schema-debug.log';
    $log_file_old = $log_file . '.old';
    
    $deleted = false;
    $errors = [];
    
    if (file_exists($log_file)) {
        if (@unlink($log_file)) {
            $deleted = true;
        } else {
            $errors[] = 'Could not delete main log file';
        }
    }
    
    // Also delete the rotated log file if it exists
    if (file_exists($log_file_old)) {
        if (@unlink($log_file_old)) {
            $deleted = true;
        } else {
            $errors[] = 'Could not delete rotated log file';
        }
    }
    
    if (!empty($errors)) {
        wp_send_json_error(implode(', ', $errors));
    }
    
    wp_send_json_success(['deleted' => $deleted]);
}
add_action('wp_ajax_lbd_delete_schema_log', 'lbd_ajax_delete_schema_log');

/**
 * Add Export button to businesses list
 */
function lbd_add_export_button() {
    global $post_type;
    
    if ($post_type == 'business') {
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Businesses</h1>
            <a href="<?php echo admin_url('admin.php?page=lbd-csv-import'); ?>" class="page-title-action">Import</a>
            <a href="<?php echo admin_url('admin.php?page=local-business-directory&action=export_csv'); ?>" class="page-title-action">Export All</a>
        </div>
        <?php
    }
}
add_action('all_admin_notices', 'lbd_add_export_button');

/**
 * Handle export request
 */
function lbd_handle_export_request() {
    if (!isset($_GET['page']) || $_GET['page'] != 'local-business-directory') {
        return;
    }
    
    if (!isset($_GET['action']) || $_GET['action'] != 'export_csv') {
        return;
    }
    
    // Check capabilities
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }
    
    // Export the businesses
    lbd_export_businesses_to_csv();
}
add_action('admin_init', 'lbd_handle_export_request');

/**
 * Export businesses to CSV
 */
function lbd_export_businesses_to_csv() {
    // Set up headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=businesses-export-' . date('Y-m-d') . '.csv');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add CSV headers
    $headers = array(
        'business_name',
        'business_description',
        'business_excerpt',
        'business_area',
        'business_category',
        'business_phone',
        'business_address',
        'business_street_address',
        'business_city',
        'business_postcode',
        'business_latitude',
        'business_longitude',
        'business_website',
        'business_email',
        'business_facebook',
        'business_instagram',
        'business_hours_24',
        'business_hours_monday',
        'business_hours_tuesday',
        'business_hours_wednesday',
        'business_hours_thursday',
        'business_hours_friday',
        'business_hours_saturday',
        'business_hours_sunday',
        'business_payments',
        'business_parking',
        'business_amenities',
        'business_accessibility',
        'business_premium',
        'business_logo_url',
        'business_image_url',
        'business_extra_categories',
        'business_service_options',
        'business_black_owned',
        'business_women_owned',
        'business_lgbtq_friendly',
        'business_google_rating',
        'business_google_review_count',
        'business_google_reviews_url',
        'business_photos',
        'business_accreditations'
    );
    fputcsv($output, $headers);
    
    // Query all businesses
    $businesses = get_posts(array(
        'post_type' => 'business',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    ));
    
    foreach ($businesses as $business) {
        // Get business data
        $areas = get_the_terms($business->ID, 'business_area');
        $area_name = $areas && !is_wp_error($areas) ? $areas[0]->name : '';
        
        $categories = get_the_terms($business->ID, 'business_category');
        $category_name = $categories && !is_wp_error($categories) ? $categories[0]->name : '';
        
        $phone = get_post_meta($business->ID, 'lbd_phone', true);
        $address = get_post_meta($business->ID, 'lbd_address', true);
        $street_address = get_post_meta($business->ID, 'lbd_street_address', true);
        $city = get_post_meta($business->ID, 'lbd_city', true);
        $postcode = get_post_meta($business->ID, 'lbd_postcode', true);
        $latitude = get_post_meta($business->ID, 'lbd_latitude', true);
        $longitude = get_post_meta($business->ID, 'lbd_longitude', true);
        $website = get_post_meta($business->ID, 'lbd_website', true);
        $premium = get_post_meta($business->ID, 'lbd_premium', true) ? 'yes' : 'no';
        
        // Get logo URL
        $logo_url = get_post_meta($business->ID, 'lbd_logo', true);
        
        // Get featured image URL
        $image_url = '';
        if (has_post_thumbnail($business->ID)) {
            $image_id = get_post_thumbnail_id($business->ID);
            $image_url = wp_get_attachment_url($image_id);
        }
        
        // Get extra categories and service options
        $extra_categories = get_post_meta($business->ID, 'lbd_extra_categories', true);
        $service_options = get_post_meta($business->ID, 'lbd_service_options', true);
        
        // Get photos as comma-separated list of URLs
        $photos = get_post_meta($business->ID, 'lbd_business_photos', true);
        $photo_urls = '';
        if (!empty($photos) && is_array($photos)) {
            $photo_url_array = array();
            foreach ($photos as $photo_id => $photo_url) {
                $photo_url_array[] = $photo_url;
            }
            $photo_urls = implode('|', $photo_url_array);
        }
        
        // Get accreditations as JSON
        $accreditations = get_post_meta($business->ID, 'lbd_accreditations', true);
        $accreditations_json = '';
        if (!empty($accreditations) && is_array($accreditations)) {
            $accreditations_json = json_encode($accreditations);
        }
        
        // Add row to CSV
        fputcsv($output, array(
            $business->post_title,
            $business->post_content,
            $business->post_excerpt,
            $area_name,
            $category_name,
            $phone,
            $address,
            $street_address,
            $city,
            $postcode,
            $latitude,
            $longitude,
            $website,
            get_post_meta($business->ID, 'lbd_email', true),
            get_post_meta($business->ID, 'lbd_facebook', true),
            get_post_meta($business->ID, 'lbd_instagram', true),
            get_post_meta($business->ID, 'lbd_hours_24', true) ? 'yes' : 'no',
            lbd_format_hours_for_export(lbd_get_business_hours($business->ID, 'monday')),
            lbd_format_hours_for_export(lbd_get_business_hours($business->ID, 'tuesday')),
            lbd_format_hours_for_export(lbd_get_business_hours($business->ID, 'wednesday')),
            lbd_format_hours_for_export(lbd_get_business_hours($business->ID, 'thursday')),
            lbd_format_hours_for_export(lbd_get_business_hours($business->ID, 'friday')),
            lbd_format_hours_for_export(lbd_get_business_hours($business->ID, 'saturday')),
            lbd_format_hours_for_export(lbd_get_business_hours($business->ID, 'sunday')),
            get_post_meta($business->ID, 'lbd_payments', true),
            get_post_meta($business->ID, 'lbd_parking', true),
            get_post_meta($business->ID, 'lbd_amenities', true),
            get_post_meta($business->ID, 'lbd_accessibility', true),
            $premium,
            $logo_url,
            $image_url,
            $extra_categories,
            $service_options,
            get_post_meta($business->ID, 'lbd_black_owned', true),
            get_post_meta($business->ID, 'lbd_women_owned', true),
            get_post_meta($business->ID, 'lbd_lgbtq_friendly', true),
            get_post_meta($business->ID, 'lbd_google_rating', true),
            get_post_meta($business->ID, 'lbd_google_review_count', true),
            get_post_meta($business->ID, 'lbd_google_reviews_url', true),
            $photo_urls,
            $accreditations_json
        ));
    }
    
    fclose($output);
    exit;
}

/**
 * Main admin page callback
 */
function lbd_admin_main_page() {
    // Get schema debug log info
    $log_file = WP_CONTENT_DIR . '/lbd-schema-debug.log';
    $log_exists = file_exists($log_file);
    $log_size = $log_exists ? filesize($log_file) : 0;
    $debug_enabled = get_option('lbd_debug_schema', false);
    
    // Format file size
    $log_size_formatted = $log_size < 1024 ? $log_size . ' B' : 
        ($log_size < 1048576 ? round($log_size / 1024, 1) . ' KB' : 
        round($log_size / 1048576, 2) . ' MB');
    ?>
    <div class="wrap">
        <h1>Local Business Directory</h1>
        <div class="card">
            <h2>Welcome to Local Business Directory</h2>
            <p>Manage your local business listings using this plugin.</p>
            <p><a href="<?php echo admin_url('edit.php?post_type=business'); ?>" class="button button-primary">Manage Businesses</a></p>
        </div>
        
        <div class="card">
            <h2>Import Businesses</h2>
            <p>Import businesses in bulk using a CSV file.</p>
            <p><a href="<?php echo admin_url('admin.php?page=lbd-csv-import'); ?>" class="button button-primary">CSV Import</a></p>
        </div>
        
        <div class="card">
            <h2>Debug Tools</h2>
            <p>Schema debug logging for troubleshooting Rank Math integration.</p>
            
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row">Debug Mode</th>
                    <td>
                        <span id="lbd-debug-status" class="<?php echo $debug_enabled ? 'lbd-status-on' : 'lbd-status-off'; ?>">
                            <?php echo $debug_enabled ? '● Enabled' : '○ Disabled'; ?>
                        </span>
                        <button type="button" id="lbd-toggle-debug" class="button" data-enabled="<?php echo $debug_enabled ? '1' : '0'; ?>">
                            <?php echo $debug_enabled ? 'Disable Debug' : 'Enable Debug'; ?>
                        </button>
                        <p class="description">When enabled, schema data is logged to help troubleshoot issues.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Log File</th>
                    <td>
                        <?php if ($log_exists): ?>
                            <span id="lbd-log-size"><strong><?php echo esc_html($log_size_formatted); ?></strong></span>
                            <button type="button" id="lbd-delete-log" class="button button-secondary" style="color: #a00;">Delete Log</button>
                            <p class="description">Location: <code><?php echo esc_html($log_file); ?></code></p>
                        <?php else: ?>
                            <span class="lbd-status-off">No log file exists</span>
                            <p class="description">A log file will be created when debug mode is enabled and a business page is viewed.</p>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
            
            <style>
                .lbd-status-on { color: #00a32a; font-weight: bold; margin-right: 10px; }
                .lbd-status-off { color: #666; margin-right: 10px; }
            </style>
            
            <script>
            jQuery(document).ready(function($) {
                $('#lbd-toggle-debug').on('click', function() {
                    var $btn = $(this);
                    var enabled = $btn.data('enabled') == '1';
                    $btn.prop('disabled', true).text('Saving...');
                    
                    $.post(ajaxurl, {
                        action: 'lbd_toggle_schema_debug',
                        enable: enabled ? '0' : '1',
                        nonce: '<?php echo wp_create_nonce('lbd_debug_actions'); ?>'
                    }, function(response) {
                        if (response.success) {
                            var newEnabled = response.data.enabled;
                            $btn.data('enabled', newEnabled ? '1' : '0');
                            $btn.text(newEnabled ? 'Disable Debug' : 'Enable Debug');
                            $('#lbd-debug-status')
                                .removeClass('lbd-status-on lbd-status-off')
                                .addClass(newEnabled ? 'lbd-status-on' : 'lbd-status-off')
                                .text(newEnabled ? '● Enabled' : '○ Disabled');
                        } else {
                            alert('Error: ' + response.data);
                        }
                        $btn.prop('disabled', false);
                    });
                });
                
                $('#lbd-delete-log').on('click', function() {
                    if (!confirm('Are you sure you want to delete the schema debug log?')) {
                        return;
                    }
                    
                    var $btn = $(this);
                    $btn.prop('disabled', true).text('Deleting...');
                    
                    $.post(ajaxurl, {
                        action: 'lbd_delete_schema_log',
                        nonce: '<?php echo wp_create_nonce('lbd_debug_actions'); ?>'
                    }, function(response) {
                        if (response.success) {
                            $btn.after('<span class="lbd-status-off" style="margin-left: 10px;">Log deleted!</span>');
                            $btn.remove();
                            $('#lbd-log-size').text('0 B');
                        } else {
                            alert('Error: ' + response.data);
                            $btn.prop('disabled', false).text('Delete Log');
                        }
                    });
                });
            });
            </script>
        </div>
    </div>
    <?php
}

/**
 * CSV Import admin page callback
 */
function lbd_csv_import_page() {
    ?>
    <div class="wrap">
        <h1>Import Businesses from CSV</h1>
        
        <?php
        // Enable error reporting for debugging
        ini_set('display_errors', 1);
        error_reporting(E_ALL);
        
        try {
            // Check if this is the mapping step
            if (isset($_POST['analyze_csv']) && isset($_FILES['csv_file'])) {
                lbd_handle_csv_mapping_step_simple();
            }
            // Save mappings and continue to import
            else if (isset($_POST['lbd_action']) && $_POST['lbd_action'] === 'save_category_mappings') {
                // First verify the category mapping nonce
                if (!isset($_POST['lbd_category_mapping_nonce']) || !wp_verify_nonce($_POST['lbd_category_mapping_nonce'], 'lbd_category_mapping_action')) {
                    echo '<div class="notice notice-error"><p>Security check failed. Please try again.</p></div>';
                } else {
                    // Make sure there's a valid file path
                    if (!isset($_POST['csv_file_path']) || empty($_POST['csv_file_path']) || !file_exists($_POST['csv_file_path'])) {
                        echo '<div class="notice notice-error"><p>CSV file not found at: ' . (isset($_POST['csv_file_path']) ? esc_html($_POST['csv_file_path']) : 'undefined path') . '. Please upload the file again.</p></div>';
                    } else {
                        $csv_file_path = $_POST['csv_file_path'];
                        
                        // Debug info
                        echo '<!-- Using file: ' . esc_html($csv_file_path) . ' -->';
                        
                        // Check if the file is readable
                        if (!is_readable($csv_file_path)) {
                            echo '<div class="notice notice-error"><p>The CSV file exists but is not readable. Please check file permissions.</p></div>';
                            // Try to fix permissions
                            @chmod($csv_file_path, 0644);
                        }
                        
                        // Create a temporary file that mimics an uploaded file
                        $_FILES['lbd_csv_file'] = array(
                            'tmp_name' => $csv_file_path,
                            'name' => basename($csv_file_path),
                            'type' => 'text/csv',
                            'error' => UPLOAD_ERR_OK,
                            'size' => filesize($csv_file_path)
                        );
                        
                        // Save the category mappings
                        $category_mappings = isset($_POST['category_mapping']) ? $_POST['category_mapping'] : array();
                        if (!empty($category_mappings)) {
                            update_option('lbd_category_mappings', $category_mappings);
                            // --- Ensure mappings persist for future imports ---
                            // Save to lbd_all_category_mappings (combined)
                            $all_mappings = get_option('lbd_all_category_mappings', array());
                            if (!is_array($all_mappings)) $all_mappings = array();
                            foreach ($category_mappings as $csv_cat => $wp_cat_id) {
                                $sanitized = sanitize_title($csv_cat);
                                $all_mappings[$sanitized] = intval($wp_cat_id);
                                // Also save as individual option for max compatibility
                                update_option('lbd_category_mapping_' . $sanitized, intval($wp_cat_id), true);
                            }
                            update_option('lbd_all_category_mappings', $all_mappings, true);
                        }
                        
                        // Set direct import flag
                        $_POST['lbd_direct_import'] = '1';
                        
                        // Create a nonce for the CSV import
                        $_POST['lbd_csv_import_nonce'] = wp_create_nonce('lbd_csv_import');
                        
                        // Now process the import
            lbd_handle_csv_import();
        }
                }
            }
            // Normal CSV upload and processing
            else if (isset($_POST['submit']) && isset($_FILES['csv_file'])) {
                lbd_handle_csv_import();
            }
            else {
                // Display the initial upload form
                ?>
        <div class="card">
            <h2>CSV File Upload</h2>
            <p>Upload a CSV file containing business data to import.</p>
            
            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('lbd_csv_import_action', 'lbd_csv_import_nonce'); ?>
                        
                        <p>
                            <label for="csv_file">CSV File:</label>
                            <input type="file" name="csv_file" id="csv_file" accept=".csv" required>
                        </p>
                        
                        <h3>Import Options</h3>
                        <p>
                            <label>
                                <input type="radio" name="category_mode" value="analyze" checked> 
                                Analyze Categories First
                            </label>
                            <br>
                            <small>Choose this to map CSV categories to existing WordPress categories.</small>
                        </p>
                        
                        <p>
                            <label>
                                <input type="radio" name="category_mode" value="direct"> 
                                Direct Import
                            </label>
                            <br>
                            <small>Choose this to import directly without category mapping.</small>
                        </p>
                
                <h3>CSV Format</h3>
                <p>Your CSV file should have the following columns:</p>
                        <ul>
                            <li><strong>business_name</strong> - Business name (required)</li>
                            <li><strong>business_area</strong> - Geographic area (required)</li>
                            <li><strong>business_description</strong> - Full description</li>
                            <li><strong>business_excerpt</strong> - Short description</li>
                    <li><strong>parent_category_name</strong> - Parent category for mapping (required for mapping)</li>
                    <li><strong>business_category</strong> - Extra services/categories (stored as meta only, not used for mapping)</li>
                    <li><strong>business_phone</strong> - Phone number</li>
                    <li><strong>business_address</strong> - Physical address</li>
                    <li><strong>business_website</strong> - Website URL</li>
                    <li><strong>business_email</strong> - Email address</li>
                    <li><strong>business_facebook</strong> - Facebook page URL</li>
                    <li><strong>business_instagram</strong> - Instagram username (without @)</li>
                    <li><strong>business_hours_24</strong> - Set to "yes" if business is open 24 hours</li>
                    <li><strong>business_hours_monday</strong> - Monday opening hours (e.g., "9:00 AM - 5:00 PM" or "Closed")</li>
                    <li><strong>business_hours_tuesday</strong> - Tuesday opening hours</li>
                    <li><strong>business_hours_wednesday</strong> - Wednesday opening hours</li>
                    <li><strong>business_hours_thursday</strong> - Thursday opening hours</li>
                    <li><strong>business_hours_friday</strong> - Friday opening hours</li>
                    <li><strong>business_hours_saturday</strong> - Saturday opening hours</li>
                    <li><strong>business_hours_sunday</strong> - Sunday opening hours</li>
                    <li><strong>business_payments</strong> - Accepted payment methods</li>
                    <li><strong>business_parking</strong> - Parking information</li>
                    <li><strong>business_amenities</strong> - Available amenities</li>
                    <li><strong>business_accessibility</strong> - Accessibility features</li>
                    <li><strong>business_premium</strong> - Set to "yes" for premium listings</li>
                    <li><strong>business_image_url</strong> - URL to a featured image</li>
                    <li><strong>business_black_owned</strong> - Set to "yes" if business is Black owned</li>
                    <li><strong>business_women_owned</strong> - Set to "yes" if business is Women owned</li>
                    <li><strong>business_lgbtq_friendly</strong> - Set to "yes" if business is LGBTQ+ friendly</li>
                    <li><strong>business_google_rating</strong> - Average rating from Google (e.g. "4.5")</li>
                    <li><strong>business_google_review_count</strong> - Number of reviews on Google</li>
                    <li><strong>business_google_reviews_url</strong> - Link to Google reviews</li>
                </ul>
                
                <p class="submit">
                            <input type="submit" name="analyze_csv" class="button button-primary" value="Analyze CSV Categories" id="analyze-button">
                            <input type="submit" name="submit" class="button" value="Skip Analysis & Import Directly" id="direct-button" style="display:none;">
                </p>
            </form>

                    <script>
                        jQuery(document).ready(function($) {
                            // Handle radio button changes
                            $('input[name="category_mode"]').change(function() {
                                var mode = $(this).val();
                                if (mode === 'analyze') {
                                    $('#analyze-button').show().addClass('button-primary');
                                    $('#direct-button').hide().removeClass('button-primary');
                                } else {
                                    $('#analyze-button').hide().removeClass('button-primary');
                                    $('#direct-button').show().addClass('button-primary');
                                }
                            });
                        });
                    </script>
        </div>
        
        <div class="card">
            <h2>Sample CSV</h2>
            <p>Download a sample CSV file to see the expected format.</p>
            <p><a href="#" class="button" id="lbd-sample-csv">Download Sample</a></p>
        </div>
                <?php
            }
        } catch (Exception $e) {
            echo '<div class="error"><p><strong>Error:</strong> ' . esc_html($e->getMessage()) . '</p>';
            echo '<p><strong>File:</strong> ' . esc_html($e->getFile()) . ' on line ' . esc_html($e->getLine()) . '</p></div>';
        }
        ?>
    </div>
    <?php
}

/**
 * Simplified CSV mapping step handler
 */
function lbd_handle_csv_mapping_step_simple() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }
    
    // Enable full error reporting
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
    
    try {
        // Verify nonce
        check_admin_referer('lbd_csv_import_action', 'lbd_csv_import_nonce');
        
        // Get uploaded file
        $file = $_FILES['csv_file'];
        
        // Basic validation
        if ($file['error'] !== UPLOAD_ERR_OK) {
            echo '<div class="error"><p>Error uploading file. Please try again.</p></div>';
            return;
        }
        
        if ($file['type'] !== 'text/csv' && !in_array(pathinfo($file['name'], PATHINFO_EXTENSION), array('csv', 'txt'))) {
            echo '<div class="error"><p>Please upload a CSV file.</p></div>';
            return;
        }
        
        // Store the uploaded file temporarily
        $upload_dir = wp_upload_dir();
        $csv_dir = $upload_dir['basedir'] . '/lbd-csv-imports';
        
        // Create directory if it doesn't exist
        if (!file_exists($csv_dir)) {
            wp_mkdir_p($csv_dir);
        }
        
        // Ensure the directory is writable
        if (!is_writable($csv_dir)) {
            @chmod($csv_dir, 0755);
            if (!is_writable($csv_dir)) {
                echo '<div class="error"><p>Upload directory is not writable. Please check permissions on ' . esc_html($csv_dir) . '</p></div>';
                return;
            }
        }
        
        // Create a unique filename
        $timestamp = time();
        $csv_file = $csv_dir . '/import-' . $timestamp . '.csv';
        
        // Move the uploaded file
        if (!move_uploaded_file($file['tmp_name'], $csv_file)) {
            echo '<div class="error"><p>Failed to move uploaded file. Check directory permissions.</p></div>';
            return;
        }
        
        // Set appropriate permissions
        @chmod($csv_file, 0644);
        
        // Basic CSV parsing to extract categories
        $handle = fopen($csv_file, 'r');
        if (!$handle) {
            echo '<div class="error"><p>Error opening file. Please try again.</p></div>';
            return;
        }
        
        // Read headers
        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            echo '<div class="error"><p>Error reading CSV headers.</p></div>';
            return;
        }
        
        // Find parent_category_name column index
        $parent_idx = array_search('parent_category_name', $headers);
        if ($parent_idx === false) {
            fclose($handle);
            echo '<div class="error"><p>No parent_category_name column found in CSV. This column is required for category mapping.</p></div>';
            return;
        }
        
        // Extract unique parent categories
        $parent_categories = array();
        while (($data = fgetcsv($handle)) !== false) {
            if (isset($data[$parent_idx]) && !empty($data[$parent_idx])) {
                $parent = trim($data[$parent_idx]);
                if (!in_array($parent, $parent_categories)) {
                    $parent_categories[] = $parent;
                }
            }
        }
        fclose($handle);
        
        if (empty($parent_categories)) {
            echo '<div class="error"><p>No parent categories found in the CSV file.</p></div>';
            return;
        }
        
        // Use only parent categories for mapping
        $all_categories = $parent_categories;
        
        // Load saved mappings for pre-selection
        $saved_mappings = get_option('lbd_all_category_mappings', array());
        if (!is_array($saved_mappings)) $saved_mappings = array();
        
        // Display the mapping form
        ?>
        <div class="card">
            <h2>Map Categories</h2>
            <p>We found <?php echo count($all_categories); ?> unique categories in your CSV file. Please map them to existing WordPress categories:</p>
            <p class="notice notice-info" style="padding: 5px 10px;">
                <strong>Note:</strong> Only the <code>parent_category_name</code> column is used for category mapping. The <code>business_category</code> column will be stored as extra services only.
            </p>
            <p class="notice notice-info" style="padding: 5px 10px;">
                <strong>Note:</strong> Categories left as "-- Select Category --" will be assigned to the "Unassigned" category during import.
            </p>
            
            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('lbd_category_mapping_action', 'lbd_category_mapping_nonce'); ?>
                <input type="hidden" name="csv_file_path" value="<?php echo esc_attr($csv_file); ?>">
                <input type="hidden" name="lbd_action" value="save_category_mappings">
                
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Parent Category (from CSV)</th>
                            <th>WordPress Category</th>
                            <th style="width: 120px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_categories as $category): ?>
                            <tr>
                                <td><?php echo esc_html($category); ?></td>
                                <td>
                                    <select name="category_mapping[<?php echo esc_attr($category); ?>]" style="width: 100%;">
                                        <option value="0">-- Select Category --</option>
                                        <?php 
                                        $wp_categories = get_terms(array(
                                            'taxonomy' => 'business_category',
                                            'hide_empty' => false
                                        ));
                                        $selected = lbd_get_saved_mapping($category);
                                        if (!empty($wp_categories) && !is_wp_error($wp_categories)) {
                                            foreach ($wp_categories as $term) {
                                                $indent = '';
                                                $parent_name = '';
                                                if ($term->parent > 0) {
                                                    $parent = get_term($term->parent, 'business_category');
                                                    if ($parent && !is_wp_error($parent)) {
                                                        $indent = '— ';
                                                        $parent_name = $parent->name . ' > ';
                                                    }
                                                }
                                                $is_selected = ($selected && $selected == $term->term_id) ? 'selected' : '';
                                                echo '<option value="' . esc_attr($term->term_id) . '" ' . $is_selected . '>' . 
                                                     esc_html($indent . $term->name) . 
                                                     '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </td>
                                <td>
                                    <button type="button" class="button skip-category" data-category="<?php echo esc_attr($category); ?>">Skip</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <script>
                jQuery(document).ready(function($) {
                    $('.skip-category').on('click', function() {
                        var category = $(this).data('category');
                        $('select[name="category_mapping[' + category + ']"]').val('0');
                        $(this).closest('tr').css('opacity', '0.5');
                    });
                });
                </script>
                
                <p style="margin-top: 18px;">
                    <label>
                        <input type="checkbox" name="save_mappings" value="1" checked>
                        Save these mappings for future imports
                    </label>
                </p>
                <p class="submit">
                    <input type="submit" class="button button-primary" value="Save Mappings & Import">
                </p>
            </form>
        </div>
        <?php
        
    } catch (Exception $e) {
        echo '<div class="error"><p><strong>Error:</strong> ' . esc_html($e->getMessage()) . '</p>';
        echo '<p><strong>File:</strong> ' . esc_html($e->getFile()) . ' on line ' . esc_html($e->getLine()) . '</p>';
        echo '<p><strong>Stack Trace:</strong></p>';
        echo '<pre>' . esc_html($e->getTraceAsString()) . '</pre></div>';
    }
}

/**
 * Creates or updates a business from CSV data row.
 * Processes a single row from the CSV file.
 *
 * @param array $data Associative array of business data from CSV row (trimmed).
 * @param array $options Associative array of import options ('direct_import', 'category_mappings').
 * @return array|WP_Error Result array with 'post_id' and 'status' ('created' or 'updated'), or WP_Error on failure.
 */
function lbd_create_business_from_csv($data, $options = array()) {
    global $wpdb;

    // Ensure helpers are loaded/available if they aren't global includes
    if (!function_exists('lbd_sanitize_yes_no')) { /* Add definition or include file */ return new WP_Error('helper_missing', 'lbd_sanitize_yes_no missing'); }
    if (!function_exists('lbd_sanitize_google_rating')) { /* Add definition or include file */ return new WP_Error('helper_missing', 'lbd_sanitize_google_rating missing'); }
    if (!function_exists('lbd_parse_hours_from_text')) { /* Add definition or include file */ return new WP_Error('helper_missing', 'lbd_parse_hours_from_text missing'); }
    if (!function_exists('lbd_get_or_create_business_category')) { /* Add definition or include file */ return new WP_Error('helper_missing', 'lbd_get_or_create_business_category missing'); }
    if (!function_exists('lbd_import_image')) { /* Add definition or include file */ return new WP_Error('helper_missing', 'lbd_import_image missing'); }


    $options = wp_parse_args($options, array(
        'direct_import' => false,
        'category_mappings' => array()
    ));

    // --- Validation ---
    if (empty($data['business_name'])) return new WP_Error('missing_name', 'Business name is required.');
    $business_name = sanitize_text_field($data['business_name']);
    $postcode = isset($data['business_postcode']) ? sanitize_text_field($data['business_postcode']) : '';
    if (empty($postcode)) return new WP_Error('missing_postcode', 'Business postcode is required.');

    // --- Find Existing Post ---
    $existing_id = $wpdb->get_var($wpdb->prepare(
        "SELECT p.ID FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id WHERE p.post_type = 'business' AND p.post_status != 'trash' AND p.post_title = %s AND pm.meta_key = 'lbd_postcode' AND pm.meta_value = %s LIMIT 1",
        $business_name, $postcode
    ));

    // --- Prepare Post Data ---
    $post_data = array(
        'post_title'   => $business_name,
        'post_content' => isset($data['business_description']) ? wp_kses_post($data['business_description']) : '',
        'post_excerpt' => isset($data['business_excerpt']) ? sanitize_text_field($data['business_excerpt']) : '',
        'post_status'  => 'publish',
        'post_type'    => 'business',
    );

    // --- Create or Update Post ---
    $status = 'unknown';
    $updated_fields = array(); // Add array to track updated fields
    
    if ($existing_id) {
        $post_data['ID'] = $existing_id;
        $post_id = wp_update_post($post_data, true);
        if (!is_wp_error($post_id)) {
            $status = 'updated';
            // Track basic post fields that were updated
            if (isset($data['business_description'])) $updated_fields[] = 'description';
            if (isset($data['business_excerpt'])) $updated_fields[] = 'excerpt';
        }
    } else {
        $post_id = wp_insert_post($post_data, true);
        if (!is_wp_error($post_id)) $status = 'created';
    }
    if (is_wp_error($post_id) || $post_id === 0) { return new WP_Error('post_failed', is_wp_error($post_id)?$post_id->get_error_message():'Post creation/update returned 0.'); }


    // --- Process Taxonomies ---
    // Areas
    $area_term_ids = array();
    if (isset($data['business_area']) && !empty($data['business_area'])) {
        $areas = array_map('trim', explode(',', $data['business_area']));
        foreach ($areas as $area_name) {
            if (empty($area_name)) continue;
            $term = term_exists($area_name, 'business_area');
            if (!$term && $options['direct_import']) { $term = wp_insert_term($area_name, 'business_area'); if (is_wp_error($term)) continue; }
            if (is_array($term) && isset($term['term_id'])) $area_term_ids[] = (int)$term['term_id'];
        }
    }
    if (!empty($area_term_ids)) { wp_set_object_terms($post_id, array_unique($area_term_ids), 'business_area'); }
    else { error_log("LBD Import Warning: No valid area for Post ID {$post_id} ('{$business_name}')."); }

    // Categories - Only use parent_category_name for taxonomy assignment
    $category_term_ids = array();
    $assign_unassigned = false;
    $category_processed_via_mapping = false;
    $parent_category_name = isset($data['parent_category_name']) ? trim($data['parent_category_name']) : '';

    if ($parent_category_name && function_exists('lbd_get_category_mapping_for_import')) {
        $mapped_term_id = lbd_get_category_mapping_for_import($parent_category_name);
        if ($mapped_term_id) {
            $category_term_ids[] = $mapped_term_id;
            $category_processed_via_mapping = true;
            error_log("Used mapping for parent category '{$parent_category_name}' -> term_id: {$mapped_term_id}");
        }
    }

    if (!$category_processed_via_mapping) {
        $assign_unassigned = true;
        error_log("No mapping found for parent category: '{$parent_category_name}'");
    }

    // Set terms or assign to Unassigned
    if (!empty($category_term_ids)) {
        wp_set_object_terms($post_id, array_unique($category_term_ids), 'business_category');
    } else {
        // Create or get Unassigned category
        $unassigned_term = term_exists('Unassigned', 'business_category');
        if (!$unassigned_term) {
            $unassigned_term = wp_insert_term('Unassigned', 'business_category', array('slug' => 'unassigned'));
        }
        if (is_array($unassigned_term) && isset($unassigned_term['term_id'])) {
            wp_set_object_terms($post_id, (int)$unassigned_term['term_id'], 'business_category');
            error_log("Assigned business ID {$post_id} to Unassigned category");
        }
    }

    // Store business_category as extra services meta
    if (isset($data['business_category']) && $data['business_category']) {
        update_post_meta($post_id, 'lbd_extra_categories', $data['business_category']);
    }
    if (isset($data['business_extra_categories']) && $data['business_extra_categories']) {
        update_post_meta($post_id, 'lbd_extra_categories', $data['business_extra_categories']);
    }
    
    // --- Process Meta Fields ---
    $field_map = array(
        'business_phone' => array('meta_key' => 'lbd_phone', 'sanitize' => 'sanitize_text_field'), 'business_email' => array('meta_key' => 'lbd_email', 'sanitize' => 'sanitize_email'), 'business_website' => array('meta_key' => 'lbd_website', 'sanitize' => 'esc_url_raw'),
        'business_address' => array('meta_key' => 'lbd_address', 'sanitize' => 'sanitize_text_field'), 'business_street_address' => array('meta_key' => 'lbd_street_address', 'sanitize' => 'sanitize_text_field'), 'business_city' => array('meta_key' => 'lbd_city', 'sanitize' => 'sanitize_text_field'), 'business_postcode' => array('meta_key' => 'lbd_postcode', 'sanitize' => 'sanitize_text_field'),
        'business_latitude' => array('meta_key' => 'lbd_latitude', 'sanitize' => 'sanitize_text_field'), 'business_longitude' => array('meta_key' => 'lbd_longitude', 'sanitize' => 'sanitize_text_field'), 'business_facebook' => array('meta_key' => 'lbd_facebook', 'sanitize' => 'esc_url_raw'), 'business_instagram' => array('meta_key' => 'lbd_instagram', 'sanitize' => 'sanitize_text_field'),
        'business_extra_categories' => array('meta_key' => 'lbd_extra_categories', 'sanitize' => 'sanitize_text_field'), 'business_service_options' => array('meta_key' => 'lbd_service_options', 'sanitize' => 'sanitize_text_field'), 'business_payments' => array('meta_key' => 'lbd_payments', 'sanitize' => 'sanitize_text_field'), 'business_parking' => array('meta_key' => 'lbd_parking', 'sanitize' => 'sanitize_text_field'), 'business_amenities' => array('meta_key' => 'lbd_amenities', 'sanitize' => 'sanitize_textarea_field'), 'business_accessibility' => array('meta_key' => 'lbd_accessibility', 'sanitize' => 'sanitize_textarea_field'),
        'business_premium' => array('meta_key' => 'lbd_premium', 'sanitize' => 'lbd_sanitize_yes_no'), 'business_black_owned' => array('meta_key' => 'lbd_black_owned', 'sanitize' => 'lbd_sanitize_yes_no'), 'business_women_owned' => array('meta_key' => 'lbd_women_owned', 'sanitize' => 'lbd_sanitize_yes_no'), 'business_lgbtq_friendly' => array('meta_key' => 'lbd_lgbtq_friendly', 'sanitize' => 'lbd_sanitize_yes_no'),
        'business_google_rating' => array('meta_key' => 'lbd_google_rating', 'sanitize' => 'lbd_sanitize_google_rating'), 'business_google_review_count' => array('meta_key' => 'lbd_google_review_count', 'sanitize' => 'absint'), 'business_google_reviews_url' => array('meta_key' => 'lbd_google_reviews_url', 'sanitize' => 'esc_url_raw'), 'business_hours_24' => array('meta_key' => 'lbd_hours_24', 'sanitize' => 'lbd_sanitize_yes_no'),
    );
    foreach ($field_map as $csv_key => $meta_info) { 
        if (isset($data[$csv_key]) && $data[$csv_key] !== '') { 
            $raw_value = $data[$csv_key]; 
            $sanitize_callback = $meta_info['sanitize']; 
            $wp_meta_key = $meta_info['meta_key']; 
            $sanitized_value = is_callable($sanitize_callback) ? call_user_func($sanitize_callback, $raw_value) : sanitize_text_field($raw_value); 
            
            // For existing businesses, check if the value is actually changing
            if ($existing_id) {
                $current_value = get_post_meta($post_id, $wp_meta_key, true);
                if ($current_value != $sanitized_value) {
                    $updated_fields[] = str_replace('lbd_', '', $wp_meta_key); // Add to updated fields list
                }
            }
            
            update_post_meta($post_id, $wp_meta_key, $sanitized_value); 
        } else if (isset($data[$csv_key]) && $data[$csv_key] === '' && $existing_id) { 
            // Track field deletions as updates too
            $current_value = get_post_meta($post_id, $meta_info['meta_key'], true);
            if (!empty($current_value)) {
                $updated_fields[] = str_replace('lbd_', '', $meta_info['meta_key']);
            }
            delete_post_meta($post_id, $meta_info['meta_key']); 
        } 
    }

    // --- Process Hours ---
    $days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
    foreach ($days as $day) {
        $hours_key = "business_hours_{$day}";
        $meta_key = "lbd_hours_{$day}_group";
        if (isset($data[$hours_key])) { // Process even if empty to potentially clear old data
             $hours_text = $data[$hours_key];
             if(function_exists('lbd_parse_hours_from_text')) {
                 $parsed_hours = lbd_parse_hours_from_text($hours_text);
                 // Only save if parsing was successful OR it was explicitly empty/closed
                 if (!empty($parsed_hours['open']) || !empty($parsed_hours['close']) || !empty($parsed_hours['closed'])) {
                     
                     // Check if hours are actually changing (for existing businesses)
                     if ($existing_id) {
                         $existing_hours = get_post_meta($post_id, $meta_key, true);
                         // If existing hours are different or don't exist
                         if (empty($existing_hours) || 
                             !isset($existing_hours[0]['open']) || 
                             !isset($existing_hours[0]['close']) || 
                             !isset($existing_hours[0]['closed']) ||
                             $existing_hours[0]['open'] != $parsed_hours['open'] ||
                             $existing_hours[0]['close'] != $parsed_hours['close'] ||
                             $existing_hours[0]['closed'] != $parsed_hours['closed']) {
                             $updated_fields[] = 'hours_' . $day;
                         }
                     }
                     
                     update_post_meta($post_id, $meta_key, array($parsed_hours));
                 } else {
                      // If parsing failed for a non-empty string, or CSV was empty, delete existing meta
                      $existing_hours = get_post_meta($post_id, $meta_key, true);
                      if (!empty($existing_hours) && $existing_id) {
                          $updated_fields[] = 'hours_' . $day;
                      }
                      delete_post_meta($post_id, $meta_key);
                 }
             }
        }
    }

     // --- Process Images ---
     $business_name_for_alt = $business_name;
     if (isset($data['business_logo_url']) && !empty($data['business_logo_url'])) { 
         $logo_url = esc_url_raw(trim($data['business_logo_url'])); 
         
         // Check if logo is changing
         if ($existing_id) {
             $current_logo = get_post_meta($post_id, 'lbd_logo', true);
             if ($current_logo != $logo_url) {
                 $updated_fields[] = 'logo';
             }
         }
         
         if (filter_var($logo_url, FILTER_VALIDATE_URL) && function_exists('lbd_import_image')) { 
             $logo_id = lbd_import_image($logo_url, $post_id, $business_name_for_alt . ' Logo', false); 
             if ($logo_id && !is_wp_error($logo_id)) { 
                 update_post_meta($post_id, 'lbd_logo', wp_get_attachment_url($logo_id)); 
             } else { 
                 update_post_meta($post_id, 'lbd_logo', $logo_url); 
             } 
         } else { 
             update_post_meta($post_id, 'lbd_logo', $logo_url); 
         } 
     } else if (isset($data['business_logo_url']) && $data['business_logo_url'] === '' && $existing_id) { 
         // Check if we're deleting an existing logo
         $current_logo = get_post_meta($post_id, 'lbd_logo', true);
         if (!empty($current_logo)) {
             $updated_fields[] = 'logo';
         }
         delete_post_meta($post_id, 'lbd_logo');
     }
     
     if (isset($data['business_image_url']) && !empty($data['business_image_url'])) { 
         $image_url = esc_url_raw(trim($data['business_image_url'])); 
         
         // Check if featured image is changing
         if ($existing_id) {
             $current_thumbnail_id = get_post_thumbnail_id($post_id);
             if (!$current_thumbnail_id || $image_url != wp_get_attachment_url($current_thumbnail_id)) {
                 $updated_fields[] = 'featured_image';
             }
         }
         
         if (filter_var($image_url, FILTER_VALIDATE_URL) && function_exists('lbd_import_image')) { 
             lbd_import_image($image_url, $post_id, $business_name_for_alt, true); 
         } 
     } else if (isset($data['business_image_url']) && $data['business_image_url'] === '' && $existing_id) { 
         // Check if we're removing a featured image
         if (has_post_thumbnail($post_id)) {
             $updated_fields[] = 'featured_image';
         }
         delete_post_thumbnail($post_id); 
     }

     // --- Process Photos Gallery ---
     if (isset($data['business_photos'])) { // Process even if empty to clear gallery
        $photo_urls = !empty($data['business_photos']) ? explode('|', $data['business_photos']) : array();
        
        // Check if gallery is changing
        if ($existing_id) {
            $current_gallery = get_post_meta($post_id, 'lbd_business_photos', true);
            $current_urls = is_array($current_gallery) ? array_values($current_gallery) : array();
            $new_photo_count = count($photo_urls);
            $current_photo_count = count($current_urls);
            
            // If count differs or URLs don't match, mark as updated
            if ($new_photo_count != $current_photo_count || 
                (!empty($photo_urls) && !empty($current_urls) && 
                 array_diff($photo_urls, $current_urls))) {
                $updated_fields[] = 'photo_gallery';
            }
        }
        
        $photo_meta_array = array();
        foreach ($photo_urls as $url) { 
            $url = trim($url); 
            if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) continue; 
            if (function_exists('lbd_import_image')) { 
                $photo_id = lbd_import_image($url, $post_id, $business_name_for_alt . ' Photo', false); 
                if ($photo_id && !is_wp_error($photo_id)) { 
                    $photo_meta_array[$photo_id] = wp_get_attachment_url($photo_id); 
                } 
            } 
        }
        update_post_meta($post_id, 'lbd_business_photos', $photo_meta_array); // Update with new array (or empty if no valid URLs)
     }

     // --- Process Accreditations JSON ---
     if (isset($data['business_accreditations'])) { // Process even if empty
         $json = trim($data['business_accreditations']);
         
         // Check if accreditations are changing
         if ($existing_id) {
             $current_accreditations = get_post_meta($post_id, 'lbd_accreditations', true);
             $current_json = !empty($current_accreditations) ? json_encode($current_accreditations) : '';
             
             // Compare JSON representations
             if ($current_json != $json && (!empty($current_json) || !empty($json))) {
                 $updated_fields[] = 'accreditations';
             }
         }
         
         if (!empty($json) && is_string($json) && ($json[0] === '[' || $json[0] === '{')) {
             $arr = json_decode($json, true);
             if (json_last_error() === JSON_ERROR_NONE && is_array($arr)) {
                 update_post_meta($post_id, 'lbd_accreditations', $arr);
             } else {
                 delete_post_meta($post_id, 'lbd_accreditations'); // Delete if invalid JSON
                 error_log("LBD Import Error: Invalid JSON in business_accreditations for Post ID {$post_id}.");
             }
         } else {
              delete_post_meta($post_id, 'lbd_accreditations'); // Delete if empty or not JSON
         }
     }

    do_action('lbd_after_csv_import_row', $post_id, $data, $options);
    
    // Return results with updated fields info
    return array(
        'post_id' => $post_id, 
        'status' => $status, 
        'updated_fields' => array_unique($updated_fields)
    );
}

/**
 * Handle CSV import form submission
 * Processes the uploaded CSV file and creates/updates businesses
 */
function lbd_handle_csv_import() {
    // Start output buffering to capture any errors
    ob_start();
    
    // Enable error reporting for debugging
    $original_error_reporting = error_reporting();
    $original_display_errors = ini_get('display_errors');
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    // Set a longer timeout for large imports
    $original_max_execution_time = ini_get('max_execution_time');
    set_time_limit(600); // 10 minutes
    
    // Increase memory limit if possible
    $original_memory_limit = ini_get('memory_limit');
    if (function_exists('wp_raise_memory_limit')) {
        wp_raise_memory_limit('admin');
    }
    
    // Initialize variables
    $file_path = '';
    $is_direct_upload = false;
    $direct_import = false;
    $category_mappings = array();
    
    try {
        // Check user permissions
        if (!current_user_can('manage_options')) {
            throw new Exception(__('You do not have permission to import businesses.', 'local-business-directory'));
        }
        
        // --- Determine import type and get file path ---
        if (isset($_POST['lbd_action']) && $_POST['lbd_action'] === 'save_category_mappings') {
            // Scenario: Post-Mapping Import
            
            // Check nonce
            if (!isset($_POST['lbd_category_mapping_nonce']) || !wp_verify_nonce($_POST['lbd_category_mapping_nonce'], 'lbd_category_mapping_action')) {
                throw new Exception(__('Security check failed for category mapping step.', 'local-business-directory'));
            }
            
            // Make sure there's a valid file path
            if (!isset($_POST['csv_file_path']) || empty($_POST['csv_file_path']) || !file_exists($_POST['csv_file_path'])) {
                throw new Exception(__('CSV file not found. Please upload the file again.', 'local-business-directory'));
            }
            
            $file_path = sanitize_text_field($_POST['csv_file_path']);
            $is_direct_upload = false;
            $direct_import = true; // Coming from mapping means we can use term mappings
            
            // Get category mappings
            if (isset($_POST['category_mapping']) && is_array($_POST['category_mapping'])) {
                $category_mappings = array_map('intval', $_POST['category_mapping']);
                
                // Save for future imports if we're using persistent mappings
                if (isset($_POST['save_mappings']) && $_POST['save_mappings'] === '1') {
                    update_option('lbd_category_mappings', $category_mappings);
                    // --- Ensure mappings persist for future imports ---
                    $all_mappings = get_option('lbd_all_category_mappings', array());
                    if (!is_array($all_mappings)) $all_mappings = array();
                    foreach ($category_mappings as $csv_cat => $wp_cat_id) {
                        $sanitized = sanitize_title($csv_cat);
                        $all_mappings[$sanitized] = intval($wp_cat_id);
                        update_option('lbd_category_mapping_' . $sanitized, intval($wp_cat_id), true);
                    }
                    update_option('lbd_all_category_mappings', $all_mappings, true);
                }
            }
            
        } elseif (isset($_POST['lbd_csv_import_nonce']) && wp_verify_nonce($_POST['lbd_csv_import_nonce'], 'lbd_csv_import')) {
            // Scenario: Direct Upload Import
            $direct_import = isset($_POST['lbd_direct_import']) && $_POST['lbd_direct_import'] === '1';
            
            // Check if a file was uploaded
            if (!isset($_FILES['lbd_csv_file']) || !is_uploaded_file($_FILES['lbd_csv_file']['tmp_name'])) {
                throw new Exception(__('No file was uploaded or the upload failed.', 'local-business-directory'));
            }
            
            // Check for upload errors
            if ($_FILES['lbd_csv_file']['error'] !== UPLOAD_ERR_OK) {
                $upload_error_messages = array(
                    UPLOAD_ERR_INI_SIZE => __('The uploaded file exceeds the upload_max_filesize directive in php.ini.', 'local-business-directory'),
                    UPLOAD_ERR_FORM_SIZE => __('The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form.', 'local-business-directory'),
                    UPLOAD_ERR_PARTIAL => __('The uploaded file was only partially uploaded.', 'local-business-directory'),
                    UPLOAD_ERR_NO_FILE => __('No file was uploaded.', 'local-business-directory'),
                    UPLOAD_ERR_NO_TMP_DIR => __('Missing a temporary folder.', 'local-business-directory'),
                    UPLOAD_ERR_CANT_WRITE => __('Failed to write file to disk.', 'local-business-directory'),
                    UPLOAD_ERR_EXTENSION => __('A PHP extension stopped the file upload.', 'local-business-directory'),
                );
                
                $error_message = isset($upload_error_messages[$_FILES['lbd_csv_file']['error']]) 
                    ? $upload_error_messages[$_FILES['lbd_csv_file']['error']] 
                    : __('Unknown upload error.', 'local-business-directory');
                
                throw new Exception($error_message);
            }
            
            // Check file type
            $file_info = wp_check_filetype_and_ext(
                $_FILES['lbd_csv_file']['tmp_name'],
                $_FILES['lbd_csv_file']['name']
            );
            
            if (empty($file_info['ext']) || !in_array(strtolower($file_info['ext']), array('csv', 'txt'))) {
                throw new Exception(__('Please upload a valid CSV file.', 'local-business-directory'));
            }
            
            $file_path = $_FILES['lbd_csv_file']['tmp_name'];
            $is_direct_upload = true;
            
            // Try to load saved category mappings if we're not doing direct import
            if (!$direct_import) {
                $saved_mappings = get_option('lbd_category_mappings', array());
                if (!empty($saved_mappings)) {
                    $category_mappings = $saved_mappings;
                }
            }
            
        } else {
            throw new Exception(__('Invalid import request. Security check failed.', 'local-business-directory'));
        }
        
        // --- Proceed with Import ---
        
        // Store a copy for reference
        if ($is_direct_upload) {
            $upload_dir = wp_upload_dir();
            $csv_dir = $upload_dir['basedir'] . '/lbd-csv-imports';
            
            // Create directory if it doesn't exist
            if (!file_exists($csv_dir)) {
                wp_mkdir_p($csv_dir);
            }
            
            // Create a unique filename for the copy
            $timestamp = time();
            $csv_stored_file = $csv_dir . '/import-' . $timestamp . '.csv';
            
            // Copy the uploaded file
            if (!copy($file_path, $csv_stored_file)) {
                error_log("Failed to save a copy of the CSV file: " . $csv_stored_file);
            }
        }
        
        // Open the file
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            throw new Exception(__('Could not open the CSV file: '. $file_path, 'local-business-directory'));
        }
        
        // Check for BOM and skip if needed
        $first_bytes = fread($handle, 3);
        if ($first_bytes !== "\xEF\xBB\xBF") {
            // It's not a BOM, so we need to go back to the start of the file
            rewind($handle);
        }
        
        // Read the header row
        $header = fgetcsv($handle);
        if (!$header) {
            fclose($handle);
            throw new Exception(__('Could not read the CSV header.', 'local-business-directory'));
        }
        
        // Normalize header row - convert to lowercase and trim
        $header = array_map('trim', array_map('strtolower', $header));
        
        // Validate required columns
        $required_columns = array('business_name', 'business_postcode');
        $missing_columns = array();
        
        foreach ($required_columns as $required) {
            if (!in_array(strtolower($required), $header)) {
                $missing_columns[] = $required;
            }
        }
        
        if (!empty($missing_columns)) {
            fclose($handle);
            throw new Exception(sprintf(
                __('Missing required columns: %s', 'local-business-directory'),
                implode(', ', $missing_columns)
            ));
        }
        
        // Check for category column 
        $has_category_column = in_array('business_category', $header);
        if ($has_category_column && !$direct_import) {
            $existing_terms = get_terms(array(
                'taxonomy' => 'business_category',
                'hide_empty' => false,
                'fields' => 'all'
            ));
        }
        
        // Get total rows for progress
        $total_rows = 0;
        $temp_handle = fopen($file_path, 'r');
        if ($temp_handle) {
            // Skip BOM if present
            $first_bytes_temp = fread($temp_handle, 3);
            if ($first_bytes_temp !== "\xEF\xBB\xBF") { 
                rewind($temp_handle); 
            }
            fgetcsv($temp_handle); // Skip header
            while (fgetcsv($temp_handle) !== false) { 
            $total_rows++;
        }
            fclose($temp_handle);
        } else {
            $total_rows = 'Unknown'; // Cannot count
        }
        
        // Reset file pointer to after header
        rewind($handle);
        fgetcsv($handle); // Skip header row again
        
        // Initialize counters
        $row_num = 1; // Start after header
        $created_count = 0;
        $updated_count = 0;
        $skipped_count = 0;
        $error_count = 0;
        $row_errors = array();
        
        // Create a progress bar
        echo '<div class="lbd-import-progress-container">';
        echo '<div class="lbd-import-progress" style="width: 0;"></div>';
        echo '</div>';
        echo '<div class="lbd-import-status">Processing row 1 of ' . $total_rows . '</div>';
        echo '<div class="lbd-import-log"></div>';
        
        // Set up debug log
        echo '<div class="lbd-debug-info" style="display:none;"><h3>Debug Info</h3><pre>';
        echo 'File path: ' . esc_html($file_path) . "\n";
        echo 'Direct import: ' . ($direct_import ? 'Yes' : 'No') . "\n";
        echo 'Is direct upload: ' . ($is_direct_upload ? 'Yes' : 'No') . "\n";
        echo 'Total rows: ' . $total_rows . "\n";
        echo 'Category mappings: ' . count($category_mappings) . "\n";
        echo '</pre></div>';
        
        // Batch size - process this many rows before flushing output
        $batch_size = 5;
        $current_batch = 0;
        
        // Prepare import options
        $import_options = array(
            'direct_import' => $direct_import,
            'category_mappings' => $category_mappings
        );
        
        // Process each row
        while (($row = fgetcsv($handle)) !== false) {
            // Update progress every few rows
            $current_batch++;
            if ($current_batch >= $batch_size) {
                $progress_percent = $total_rows !== 'Unknown' ? round(($row_num / $total_rows) * 100) : 0;
                echo '<script>
                    document.querySelector(".lbd-import-progress").style.width = "' . $progress_percent . '%";
                    document.querySelector(".lbd-import-status").textContent = "Processing row ' . $row_num . ' of ' . $total_rows . '";
                </script>';
                ob_flush();
                flush();
                $current_batch = 0;
            }
            
            // Skip empty rows
            if (count($row) === 0 || (count($row) === 1 && empty($row[0]))) {
                $skipped_count++;
                $row_num++;
                continue;
            }
            
            // Convert row to associative array using headers
            $data = array();
            foreach ($header as $index => $key) {
                $data[$key] = isset($row[$index]) ? $row[$index] : '';
            }
            
            // Try to import the business
            try {
                $result = lbd_create_business_from_csv($data, $import_options);
            
                if (is_wp_error($result)) {
                    $error_count++;
                    $row_errors[$row_num] = $result->get_error_message();
                    echo '<div class="lbd-import-error">Error on row ' . $row_num . ': ' . 
                         esc_html($result->get_error_message()) . '</div>';
                } else {
                    if ($result['status'] === 'created') {
                        $created_count++;
                        echo '<div class="lbd-import-success">Created business: ' . esc_html($data['business_name']) . ' (ID: ' . $result['post_id'] . ')</div>';
                    } else {
                        $updated_count++;
                        // Display what fields were updated
                        $updated_fields_text = !empty($result['updated_fields']) ? 
                            ' Updated fields: ' . esc_html(implode(', ', array_unique($result['updated_fields']))) : '';
                        echo '<div class="lbd-import-success">Updated business: ' . esc_html($data['business_name']) . 
                             ' (ID: ' . $result['post_id'] . ')' . $updated_fields_text . '</div>';
                    }
                }
            } catch (Exception $e) {
                $error_count++;
                $row_errors[$row_num] = $e->getMessage();
                echo '<div class="lbd-import-error">Exception on row ' . $row_num . ': ' . 
                     esc_html($e->getMessage()) . '</div>';
            }
            
            $row_num++;
        }
        
        // Close the file
        fclose($handle);
        
        // Delete the temporary file if it wasn't a direct upload and we had a successful import
        if (!$is_direct_upload && file_exists($file_path) && !empty($created_count)) {
            @unlink($file_path);
        }
        
        // Close debug info
        echo '</pre></div>';
        
        // Display final results
        echo '<div class="lbd-import-results">';
        echo '<h3>' . __('Import Completed', 'local-business-directory') . '</h3>';
        echo '<p>' . __('Businesses Created:', 'local-business-directory') . ' <strong>' . $created_count . '</strong></p>';
        echo '<p>' . __('Businesses Updated:', 'local-business-directory') . ' <strong>' . $updated_count . '</strong></p>';
        echo '<p>' . __('Rows Skipped:', 'local-business-directory') . ' <strong>' . $skipped_count . '</strong></p>';
        echo '<p>' . __('Errors:', 'local-business-directory') . ' <strong>' . $error_count . '</strong></p>';
        
        // Display errors summary if there were any
        if ($error_count > 0) {
            echo '<div class="lbd-import-errors-summary">';
            echo '<h4>' . __('Error Summary', 'local-business-directory') . '</h4>';
            echo '<ul>';
            foreach ($row_errors as $row => $error) {
                echo '<li>Row ' . $row . ': ' . esc_html($error) . '</li>';
            }
            echo '</ul>';
            echo '</div>';
        }
        
        echo '<p><a href="' . admin_url('admin.php?page=lbd-csv-import') . '" class="button button-primary">' . 
             __('Import Another File', 'local-business-directory') . '</a></p>';
        echo '</div>';
        
    } catch (Exception $e) {
        // Handle overall exception
        echo '<div class="notice notice-error"><p>' . esc_html($e->getMessage()) . '</p></div>';
        echo '<p><a href="' . admin_url('admin.php?page=lbd-csv-import') . '" class="button button-primary">' . 
             __('Try Again', 'local-business-directory') . '</a></p>';
    }
    
    // Restore original PHP settings
    error_reporting($original_error_reporting);
    ini_set('display_errors', $original_display_errors);
    set_time_limit($original_max_execution_time);
    @ini_set('memory_limit', $original_memory_limit);
    
    // Get buffered output
    $output = ob_get_clean();
    
    // Echo the output
    echo $output;
}

/**
 * Parse text-based opening hours to structured array format
 * 
 * @param string $hours_text Text representation of opening hours (e.g., "9 am–5 pm" or "Closed")
 * @return array Structured array with open, close, and closed status
 */
function lbd_parse_hours_from_text($hours_text) {
    $hours_text = trim($hours_text);
    $result = array(
        'open' => '',
        'close' => '',
        'closed' => ''
    );
    
    // Debug output
    error_log("Parsing hours: '{$hours_text}'");
    
    // Check if it's closed
    if (empty($hours_text) || strtolower($hours_text) === 'closed') {
        $result['closed'] = 'on';
        error_log("Hours marked as closed");
        return $result;
    }
    
    // Normalize different dash types to standard hyphen for consistency
    // This handles en dash (–), em dash (—), and other variants
    $normalized_text = preg_replace('/[–—−]/u', '-', $hours_text);
    if ($normalized_text !== $hours_text) {
        error_log("Normalized hours text from '{$hours_text}' to '{$normalized_text}'");
        $hours_text = $normalized_text;
    }
    
    // Try to parse the hours
    // Common formats: "9 am-5 pm", "9:00 AM - 5:00 PM", "9-5", etc.
    $patterns = array(
        // Basic pattern with possible space around dash: 9am-5pm, 9 am - 5 pm
        '/([0-9]{1,2})(?::([0-9]{2}))?\s*([aApP][mM])?\s*-\s*([0-9]{1,2})(?::([0-9]{2}))?\s*([aApP][mM])?/',
        
        // Alternative format with "to": 9am to 5pm
        '/([0-9]{1,2})(?::([0-9]{2}))?\s*([aApP][mM])?\s*to\s*([0-9]{1,2})(?::([0-9]{2}))?\s*([aApP][mM])?/',
        
        // Last resort - any digits followed by dash followed by digits
        '/([0-9]{1,2})(?::([0-9]{2}))?\s*([aApP][mM])?.+?([0-9]{1,2})(?::([0-9]{2}))?\s*([aApP][mM])?/'
    );
    
    $matched = false;
    foreach ($patterns as $index => $pattern) {
        if (preg_match($pattern, $hours_text, $matches)) {
            $matched = true;
            error_log("Matched pattern #" . ($index + 1) . " for hours: " . $hours_text);
            error_log("Matches: " . print_r($matches, true));
            
            // Parse opening hours
            $open_hour = isset($matches[1]) ? $matches[1] : '';
            $open_minutes = isset($matches[2]) ? $matches[2] : '00';
            $open_ampm = isset($matches[3]) ? strtoupper($matches[3]) : '';
            
            // Parse closing hours
            $close_hour = isset($matches[4]) ? $matches[4] : '';
            $close_minutes = isset($matches[5]) ? $matches[5] : '00';
            $close_ampm = isset($matches[6]) ? strtoupper($matches[6]) : '';
            
            // If AM/PM not specified, infer based on hours
            if (empty($open_ampm) && empty($close_ampm)) {
                // If open hour is 12 or less, assume AM
                $open_ampm = ($open_hour <= 12) ? 'AM' : 'PM';
                // If close hour is 12 or less and less than open hour, assume PM
                $close_ampm = ($close_hour <= 12 && $close_hour < $open_hour) ? 'PM' : $open_ampm;
            } else if (empty($open_ampm)) {
                $open_ampm = $close_ampm;
            } else if (empty($close_ampm)) {
                $close_ampm = $open_ampm;
            }
            
            // Format the time in the expected CMB2 format (g:i A)
            $result['open'] = sprintf('%d:%02d %s', $open_hour, (int)$open_minutes, $open_ampm);
            $result['close'] = sprintf('%d:%02d %s', $close_hour, (int)$close_minutes, $close_ampm);
            error_log("Parsed hours: Open={$result['open']}, Close={$result['close']}");
            break;
        }
    }
    
    // If no pattern matched, try to split on any character between digits
    if (!$matched) {
        error_log("No pattern matched for hours: {$hours_text}, trying manual split");
        
        // Look for time components: digits followed by optional am/pm
        if (preg_match_all('/([0-9]{1,2})(?::([0-9]{2}))?\s*([aApP][mM])?/', $hours_text, $time_components)) {
            if (count($time_components[0]) >= 2) {
                // We found at least 2 time components, use first and last
                $open_hour = $time_components[1][0];
                $open_minutes = !empty($time_components[2][0]) ? $time_components[2][0] : '00';
                $open_ampm = !empty($time_components[3][0]) ? strtoupper($time_components[3][0]) : 'AM';
                
                $last_idx = count($time_components[0]) - 1;
                $close_hour = $time_components[1][$last_idx];
                $close_minutes = !empty($time_components[2][$last_idx]) ? $time_components[2][$last_idx] : '00';
                $close_ampm = !empty($time_components[3][$last_idx]) ? strtoupper($time_components[3][$last_idx]) : 'PM';
                
                // Format the time
                $result['open'] = sprintf('%d:%02d %s', $open_hour, (int)$open_minutes, $open_ampm);
                $result['close'] = sprintf('%d:%02d %s', $close_hour, (int)$close_minutes, $close_ampm);
                $matched = true;
                error_log("Manual split successful: Open={$result['open']}, Close={$result['close']}");
            }
        }
    }
    
    // If still no match, store the raw text in the open field
    if (!$matched) {
        error_log("Failed to parse hours: {$hours_text}, storing as raw text");
        $result['open'] = $hours_text;
    }
    
    return $result;
}

/**
 * Convert structured hours array to text format for CSV export
 * 
 * @param array $hours_data Structured array with open, close, and closed status
 * @return string Text representation of opening hours
 */
function lbd_format_hours_for_export($hours_data) {
    // If no data, return empty
    if (empty($hours_data) || !is_array($hours_data)) {
        return '';
    }
    
    // Check if it's the first item in the group
    $hours_item = $hours_data[0];
    
    // If marked as closed, return "Closed"
    if (isset($hours_item['closed']) && $hours_item['closed']) {
        return 'Closed';
    }
    
    // If we have both opening and closing times
    if (!empty($hours_item['open']) && !empty($hours_item['close'])) {
        return $hours_item['open'] . ' - ' . $hours_item['close'];
    }
    
    // If we only have opening time
    if (!empty($hours_item['open'])) {
        return $hours_item['open'];
    }
    
    // Default return empty
    return '';
}

/**
 * Get business hours for a specific day.
 * 
 * @param int $post_id Business post ID
 * @param string $day Day of the week (monday, tuesday, etc.)
 * @return array|null Hours data array (e.g., [['open'=>'9:00 AM', 'close'=>'5:00 PM', 'closed'=>'']]) or null if not found
 */
function lbd_get_business_hours($post_id, $day) {
    // ONLY try to get the new structured format.
    $hours_group = get_post_meta($post_id, 'lbd_hours_' . $day . '_group', true);
    
    // Ensure it's an array and potentially contains the expected structure
    if (!empty($hours_group) && is_array($hours_group)) {
        // Ensure it's wrapped in an outer array like CMB2 non-repeatable groups are
        if (isset($hours_group['open']) || isset($hours_group['close']) || isset($hours_group['closed'])) {
             // If the data is directly the fields, wrap it
             return array($hours_group);
        }
        // Check if the first element is an array (correct structure)
        if (isset($hours_group[0]) && is_array($hours_group[0])) {
        return $hours_group;
    }
    }
    
    // If we didn't find valid data in the expected format, return null.
    return null;
}

// Make the function available outside of admin
add_action('init', function() {
    if (!function_exists('lbd_get_business_hours')) {
        function lbd_get_business_hours($post_id, $day) {
            // Call the admin function
            if (function_exists('\lbd_get_business_hours')) {
            return \lbd_get_business_hours($post_id, $day);
            }
            // Fallback if the admin function isn't loaded for some reason
            return get_post_meta($post_id, 'lbd_hours_' . $day . '_group', true);
        }
    }
});

/**
 * Creates or updates a business from CSV data
 *
 * @param array $data Associative array of business data from CSV
 * @param array|bool $options Import options array or boolean for backward compatibility
 * @return array|WP_Error Result of the operation with status and post ID
 */

/**
 * Get attachment ID by its source URL metadata
 * 
 * @param string $source_url The source URL to check
 * @return int|false Attachment ID or false if not found
 */
function lbd_get_attachment_id_by_source_url($source_url) {
    global $wpdb;
    
    // Query meta directly for better performance
    $attachment_id = $wpdb->get_var($wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_lbd_source_url' AND meta_value = %s LIMIT 1",
        $source_url
    ));
    
    return $attachment_id ? (int) $attachment_id : false;
}

/**
 * Import an image from a URL and optionally set as featured image
 * 
 * @param string $image_url The URL of the image to import
 * @param int $post_id The post ID to attach the image to
 * @param string $title The title to use for the attachment
 * @param bool $set_featured Whether to set the image as the featured image
 * @return int|WP_Error The attachment ID or WP_Error
 */
function lbd_import_image($image_url, $post_id, $title = '', $set_featured = false) {
    // Check if this image has already been uploaded
    $existing_attachment = get_posts(array(
        'post_type' => 'attachment',
        'meta_key' => '_lbd_source_url',
        'meta_value' => $image_url,
        'posts_per_page' => 1,
    ));
    
    if (!empty($existing_attachment)) {
        $attach_id = $existing_attachment[0]->ID;
        
        // Set as featured image if requested
        if ($set_featured) {
            set_post_thumbnail($post_id, $attach_id);
        }
        
        return $attach_id;
    }
    
    // Include necessary files for media handling
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    
    // Set a longer timeout for image downloads
    $timeout = apply_filters('lbd_image_download_timeout', 30);
    
    // Download the image with extended timeout
    $tmp = download_url($image_url, $timeout);
    
    if (is_wp_error($tmp)) {
        error_log('LBD Image Download Error: ' . $tmp->get_error_message() . ' for URL: ' . $image_url);
        return $tmp;
    }
    
    // Prepare file parameters
    $file_array = array(
        'name' => basename($image_url),
        'tmp_name' => $tmp,
    );
    
    // If the URL has no extension, add one
    if (!preg_match('/\.(jpg|jpeg|png|gif)$/i', $file_array['name'])) {
        $file_array['name'] .= '.jpg';
    }
    
    // Add a timestamp to the file name to avoid duplicates
    $file_array['name'] = time() . '-' . $file_array['name'];
    
    // Upload the image and attach it to the post
    $attach_id = media_handle_sideload($file_array, $post_id, $title);
    
    // Remove the temporary file
    @unlink($tmp);
    
    if (is_wp_error($attach_id)) {
        error_log('LBD Image Sideload Error: ' . $attach_id->get_error_message() . ' for URL: ' . $image_url);
        return $attach_id;
    }
    
    // Save the source URL as post meta for future reference
    update_post_meta($attach_id, '_lbd_source_url', $image_url);
    
    // Set as featured image if requested
    if ($set_featured) {
        set_post_thumbnail($post_id, $attach_id);
    }
    
    return $attach_id;
}

/**
 * Set featured image from URL (wrapper for backward compatibility)
 * 
 * @param string $image_url The URL of the image to import
 * @param int $post_id The post ID to attach the image to
 * @param string $title The title to use for the attachment
 * @return int|WP_Error The attachment ID or WP_Error
 */
function lbd_set_featured_image_from_url($image_url, $post_id, $title = '') {
    return lbd_import_image($image_url, $post_id, $title, true);
}

/**
 * Import image from URL (wrapper for backward compatibility)
 * 
 * @param string $image_url The URL of the image to import
 * @param int $post_id The post ID to attach the image to
 * @param string $title The title to use for the attachment
 * @return int|WP_Error The attachment ID or WP_Error
 */
function lbd_import_image_from_url($image_url, $post_id, $title = '') {
    return lbd_import_image($image_url, $post_id, $title, false);
}

/**
 * Reviews Manager admin page callback
 */
function lbd_reviews_page() {
    global $wpdb;
    
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    // Handle review actions (approval, deletion, etc.)
    if ($action === 'approve' && isset($_GET['review_id']) && current_user_can('manage_options')) {
        $review_id = intval($_GET['review_id']);
        $table_name = $wpdb->prefix . 'lbd_reviews';
        
        $wpdb->update(
            $table_name,
            array('approved' => 1),
            array('id' => $review_id),
            array('%d'),
            array('%d')
        );
        
        // Redirect to remove action from URL
        wp_redirect(admin_url('admin.php?page=lbd-reviews&approved=1'));
        exit;
    }
    
    if ($action === 'unapprove' && isset($_GET['review_id']) && current_user_can('manage_options')) {
        $review_id = intval($_GET['review_id']);
        $table_name = $wpdb->prefix . 'lbd_reviews';
        
        $wpdb->update(
            $table_name,
            array('approved' => 0),
            array('id' => $review_id),
            array('%d'),
            array('%d')
        );
        
        // Redirect to remove action from URL
        wp_redirect(admin_url('admin.php?page=lbd-reviews&unapproved=1'));
        exit;
    }
    
    if ($action === 'delete' && isset($_GET['review_id']) && current_user_can('manage_options')) {
        $review_id = intval($_GET['review_id']);
        $table_name = $wpdb->prefix . 'lbd_reviews';
        
        $wpdb->delete(
            $table_name,
            array('id' => $review_id),
            array('%d')
        );
        
        // Redirect to remove action from URL
        wp_redirect(admin_url('admin.php?page=lbd-reviews&deleted=1'));
        exit;
    }
    
    // Handle CSV import of reviews
    if (isset($_POST['lbd_import_reviews_submit']) && isset($_FILES['lbd_reviews_file'])) {
        lbd_handle_reviews_import();
    }
    
    // Handle manual review addition
    if (isset($_POST['lbd_add_review_submit'])) {
        lbd_handle_add_review();
    }
    
    // Show success messages
    if (isset($_GET['approved'])) {
        echo '<div class="notice notice-success"><p>Review approved successfully.</p></div>';
    }
    
    if (isset($_GET['unapproved'])) {
        echo '<div class="notice notice-success"><p>Review unapproved successfully.</p></div>';
    }
    
    if (isset($_GET['deleted'])) {
        echo '<div class="notice notice-success"><p>Review deleted successfully.</p></div>';
    }
    
    if (isset($_GET['added'])) {
        echo '<div class="notice notice-success"><p>Review added successfully.</p></div>';
    }
    
    if (isset($_GET['imported'])) {
        echo '<div class="notice notice-success"><p>' . intval($_GET['imported']) . ' reviews imported successfully.</p></div>';
    }
    
    ?>
    <div class="wrap">
        <h1>Reviews Manager</h1>
        
        <div class="card">
            <h2>Import Reviews from CSV</h2>
            <p>Upload a CSV file containing Google reviews to import.</p>
            
            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('lbd_import_reviews_action', 'lbd_import_reviews_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="lbd_reviews_file">CSV File</label></th>
                        <td>
                            <input type="file" name="lbd_reviews_file" id="lbd_reviews_file" accept=".csv" required>
                            <p class="description">File must be a CSV with headers: business_id, reviewer_name, reviewer_email, review_text, rating, review_date, source_id</p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="lbd_import_reviews_submit" class="button button-primary" value="Import Reviews">
                </p>
            </form>
        </div>
        
        <div class="card">
            <h2>Add Review Manually</h2>
            
            <form method="post">
                <?php wp_nonce_field('lbd_add_review_action', 'lbd_add_review_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="lbd_business_id">Business</label></th>
                        <td>
                            <select name="lbd_business_id" id="lbd_business_id" required>
                                <option value="">Select a Business</option>
                                <?php
                                $businesses = get_posts(array(
                                    'post_type' => 'business',
                                    'posts_per_page' => -1,
                                    'orderby' => 'title',
                                    'order' => 'ASC',
                                ));
                                
                                foreach ($businesses as $business) {
                                    echo '<option value="' . esc_attr($business->ID) . '">' . esc_html($business->post_title) . '</option>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="lbd_reviewer_name">Reviewer Name</label></th>
                        <td>
                            <input type="text" name="lbd_reviewer_name" id="lbd_reviewer_name" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="lbd_reviewer_email">Reviewer Email</label></th>
                        <td>
                            <input type="email" name="lbd_reviewer_email" id="lbd_reviewer_email" class="regular-text">
                            <p class="description">Optional. Email will not be displayed publicly.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="lbd_review_text">Review Text</label></th>
                        <td>
                            <textarea name="lbd_review_text" id="lbd_review_text" rows="5" class="large-text" required></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="lbd_rating">Rating (1-5)</label></th>
                        <td>
                            <select name="lbd_rating" id="lbd_rating" required>
                                <option value="5">5 Stars</option>
                                <option value="4">4 Stars</option>
                                <option value="3">3 Stars</option>
                                <option value="2">2 Stars</option>
                                <option value="1">1 Star</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="lbd_source">Source</label></th>
                        <td>
                            <select name="lbd_source" id="lbd_source">
                                <option value="google">Google</option>
                                <option value="manual">Manual</option>
                                <option value="imported">Imported</option>
                            </select>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="lbd_add_review_submit" class="button button-primary" value="Add Review">
                </p>
            </form>
        </div>
        
        <div class="card">
            <h2>Manage Reviews</h2>
            <?php
            $table_name = $wpdb->prefix . 'lbd_reviews';
            $reviews = $wpdb->get_results($wpdb->prepare(
                "SELECT r.*, p.post_title AS business_name 
                FROM {$table_name} r 
                JOIN {$wpdb->posts} p ON r.business_id = p.ID 
                ORDER BY r.review_date DESC"
            ));
            
            if ($reviews) {
                echo '<table class="wp-list-table widefat fixed striped">';
                echo '<thead>';
                echo '<tr>';
                echo '<th>Business</th>';
                echo '<th>Reviewer</th>';
                echo '<th>Email</th>';
                echo '<th>Review</th>';
                echo '<th>Rating</th>';
                echo '<th>Date</th>';
                echo '<th>Source</th>';
                echo '<th>Status</th>';
                echo '<th>Actions</th>';
                echo '</tr>';
                echo '</thead>';
                echo '<tbody>';
                
                foreach ($reviews as $review) {
                    echo '<tr>';
                    echo '<td>' . esc_html($review->business_name) . '</td>';
                    echo '<td>' . esc_html($review->reviewer_name) . '</td>';
                    echo '<td>' . esc_html($review->reviewer_email) . '</td>';
                    echo '<td>' . esc_html(wp_trim_words($review->review_text, 15)) . '</td>';
                    echo '<td>' . esc_html($review->rating) . ' / 5</td>';
                    echo '<td>' . esc_html(date('M j, Y', strtotime($review->review_date))) . '</td>';
                    echo '<td>' . esc_html(ucfirst($review->source)) . '</td>';
                    echo '<td>' . ($review->approved ? '<span style="color:green">Approved</span>' : '<span style="color:red">Pending</span>') . '</td>';
                    echo '<td>';
                    
                    // Approval/unapproval link
                    if ($review->approved) {
                        echo '<a href="' . admin_url('admin.php?page=lbd-reviews&action=unapprove&review_id=' . $review->id) . '" class="button button-small">Unapprove</a> ';
                    } else {
                        echo '<a href="' . admin_url('admin.php?page=lbd-reviews&action=approve&review_id=' . $review->id) . '" class="button button-small button-primary">Approve</a> ';
                    }
                    
                    // Delete link
                    echo '<a href="' . admin_url('admin.php?page=lbd-reviews&action=delete&review_id=' . $review->id) . '" class="button button-small" onclick="return confirm(\'Are you sure you want to delete this review?\')">Delete</a>';
                    
                    echo '</td>';
                    echo '</tr>';
                }
                
                echo '</tbody>';
                echo '</table>';
            } else {
                echo '<p>No reviews found. Add reviews manually or import from CSV.</p>';
            }
            ?>
        </div>
    </div>
    <?php
}

/**
 * Handle manual review addition
 */
function lbd_handle_add_review() {
    // Check nonce for security
    if (!isset($_POST['lbd_add_review_nonce']) || !wp_verify_nonce($_POST['lbd_add_review_nonce'], 'lbd_add_review_action')) {
        echo '<div class="notice notice-error"><p>Security check failed. Please try again.</p></div>';
        return;
    }
    
    // Get form data
    $business_id = isset($_POST['lbd_business_id']) ? intval($_POST['lbd_business_id']) : 0;
    $reviewer_name = isset($_POST['lbd_reviewer_name']) ? sanitize_text_field($_POST['lbd_reviewer_name']) : '';
    $reviewer_email = isset($_POST['lbd_reviewer_email']) ? sanitize_email($_POST['lbd_reviewer_email']) : '';
    $review_text = isset($_POST['lbd_review_text']) ? sanitize_textarea_field($_POST['lbd_review_text']) : '';
    $rating = isset($_POST['lbd_rating']) ? intval($_POST['lbd_rating']) : 5;
    $source = isset($_POST['lbd_source']) ? sanitize_text_field($_POST['lbd_source']) : 'manual';
    
    // Validate
    if (!$business_id || !$reviewer_name || !$review_text || $rating < 1 || $rating > 5) {
        echo '<div class="notice notice-error"><p>Please fill in all required fields correctly.</p></div>';
        return;
    }
    
    // Add the review
    $result = lbd_add_review($business_id, $reviewer_name, $review_text, $rating, $source, '', true, $reviewer_email);
    
    if ($result) {
        // Redirect to avoid form resubmission
        wp_redirect(admin_url('admin.php?page=lbd-reviews&added=1'));
        exit;
    } else {
        echo '<div class="notice notice-error"><p>Error adding review. Please try again.</p></div>';
    }
}

/**
 * Handle reviews CSV import
 */
function lbd_handle_reviews_import() {
    // Check nonce for security
    if (!isset($_POST['lbd_import_reviews_nonce']) || !wp_verify_nonce($_POST['lbd_import_reviews_nonce'], 'lbd_import_reviews_action')) {
        echo '<div class="notice notice-error"><p>Security check failed. Please try again.</p></div>';
        return;
    }
    
    // Get the uploaded file
    $file = $_FILES['lbd_reviews_file'];
    
    // Check for errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo '<div class="notice notice-error"><p>Error uploading file. Please try again.</p></div>';
        return;
    }
    
    // Check file type
    $file_type = wp_check_filetype(basename($file['name']));
    if ($file_type['ext'] !== 'csv') {
        echo '<div class="notice notice-error"><p>Please upload a valid CSV file.</p></div>';
        return;
    }
    
    // Open the file
    $handle = fopen($file['tmp_name'], 'r');
    if ($handle === false) {
        echo '<div class="notice notice-error"><p>Error opening file. Please try again.</p></div>';
        return;
    }
    
    // Get headers
    $headers = fgetcsv($handle);
    if ($headers === false) {
        fclose($handle);
        echo '<div class="notice notice-error"><p>Error reading CSV headers.</p></div>';
        return;
    }
    
    // Required fields
    $required_fields = array('business_id', 'reviewer_name', 'review_text', 'rating');
    $missing_fields = array();
    
    // Check for required fields
    foreach ($required_fields as $field) {
        if (!in_array($field, $headers)) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        fclose($handle);
        echo '<div class="notice notice-error"><p>Missing required fields: ' . implode(', ', $missing_fields) . '</p></div>';
        return;
    }
    
    // Start import
    $imported = 0;
    $skipped = 0;
    
    // Process rows
    while (($row = fgetcsv($handle)) !== false) {
        // Skip empty rows
        if (count($row) <= 1 && empty($row[0])) {
            continue;
        }
        
        // Create associative array from row
        $data = array();
        foreach ($headers as $index => $header) {
            $data[$header] = isset($row[$index]) ? trim($row[$index]) : '';
        }
        
        // Skip if missing required fields
        if (empty($data['business_id']) || empty($data['reviewer_name']) || 
            empty($data['review_text']) || empty($data['rating'])) {
            $skipped++;
            continue;
        }
        
        // Add the review
        $business_id = intval($data['business_id']);
        $reviewer_name = sanitize_text_field($data['reviewer_name']);
        $reviewer_email = !empty($data['reviewer_email']) ? sanitize_email($data['reviewer_email']) : '';
        $review_text = sanitize_textarea_field($data['review_text']);
        $rating = intval($data['rating']);
        $source = !empty($data['source']) ? sanitize_text_field($data['source']) : 'google';
        $source_id = !empty($data['source_id']) ? sanitize_text_field($data['source_id']) : '';
        
        // If review_date is provided, use it
        $review_date = !empty($data['review_date']) ? $data['review_date'] : null;
        
        // Validate the business exists
        $business = get_post($business_id);
        if (!$business || $business->post_type !== 'business') {
            $skipped++;
            continue;
        }
        
        // Add the review using our function from activation.php
        $result = lbd_add_review($business_id, $reviewer_name, $review_text, $rating, $source, $source_id, true, $reviewer_email);
        
        if ($result) {
            // If review date was provided, update it directly in the database
            if ($review_date) {
                global $wpdb;
                $table_name = $wpdb->prefix . 'lbd_reviews';
                $wpdb->update(
                    $table_name,
                    array('review_date' => $review_date),
                    array('id' => $result),
                    array('%s'),
                    array('%d')
                );
            }
            
            $imported++;
        } else {
            $skipped++;
        }
    }
    
    fclose($handle);
    
    // Redirect with results
    wp_redirect(admin_url('admin.php?page=lbd-reviews&imported=' . $imported . ($skipped > 0 ? '&skipped=' . $skipped : '')));
    exit;
}

// Search function has been moved to local-business-directory.php 

/**
 * Link Categories admin page callback
 */
function lbd_link_categories_page() {
    // Check user capabilities
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }
    
    // Process the form submission if there is one
    $results = array();
    $errors = array();
    $processed = 0;
    $updated = 0;
    $skipped = 0;
    $mapping_method = '';
    
    if (isset($_POST['lbd_link_categories_submit']) && isset($_POST['lbd_link_categories_nonce'])) {
        // Verify nonce
        if (!wp_verify_nonce($_POST['lbd_link_categories_nonce'], 'lbd_link_categories_action')) {
            $errors[] = 'Security check failed. Please try again.';
        } else {
            // Get selected mapping method
            $mapping_method = isset($_POST['mapping_method']) ? sanitize_text_field($_POST['mapping_method']) : '';
            
            // Start batch processing
            set_time_limit(300); // Give ourselves 5 minutes to run
            
            // Process businesses based on mapping method
            if ($mapping_method === 'name_match') {
                // Method 1: Match by existing term names
                $results = lbd_link_categories_by_name_match();
                $processed = $results['processed'];
                $updated = $results['updated'];
                $skipped = $results['skipped'];
                $errors = $results['errors'];
            } elseif ($mapping_method === 'csv_upload' && isset($_FILES['mapping_csv'])) {
                // Method 2: Use CSV mapping file
                $results = lbd_link_categories_by_csv($_FILES['mapping_csv']);
                $processed = $results['processed'];
                $updated = $results['updated'];
                $skipped = $results['skipped'];
                $errors = $results['errors'];
            } elseif ($mapping_method === 'content_analysis') {
                // Method 3: Analyze business content
                $results = lbd_link_categories_by_content_analysis();
                $processed = $results['processed'];
                $updated = $results['updated'];
                $skipped = $results['skipped'];
                $errors = $results['errors'];
            } else {
                $errors[] = 'Invalid mapping method selected.';
            }
        }
    }
    
    // Display the admin page
    ?>
    <div class="wrap">
        <h1>Link Businesses to Categories</h1>
        
        <div class="card">
            <h2>Important: Backup Your Database First</h2>
            <p>This tool will modify category relationships for existing businesses. Please backup your database before proceeding.</p>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="notice notice-error">
                <h3>Errors Occurred:</h3>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo esc_html($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if ($processed > 0): ?>
            <div class="notice notice-success">
                <h3>Processing Complete!</h3>
                <p>Processed <?php echo intval($processed); ?> businesses:</p>
                <ul>
                    <li><strong>Updated:</strong> <?php echo intval($updated); ?> businesses</li>
                    <li><strong>Skipped:</strong> <?php echo intval($skipped); ?> businesses</li>
                </ul>
                <?php if (!empty($results['log']) && is_array($results['log'])): ?>
                    <h4>Processing Log:</h4>
                    <div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #f9f9f9;">
                        <ul>
                            <?php foreach ($results['log'] as $log): ?>
                                <li><?php echo esc_html($log); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Choose Linking Method</h2>
            <p>This tool will help you link existing businesses to the correct categories in the hierarchy.</p>
            
            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('lbd_link_categories_action', 'lbd_link_categories_nonce'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">Mapping Method</th>
                        <td>
                            <label>
                                <input type="radio" name="mapping_method" value="name_match" <?php checked($mapping_method, 'name_match'); ?>>
                                Match by existing term names
                                <p class="description">Uses the existing category name to find the matching category in the hierarchy.</p>
                            </label>
                            <br><br>
                            
                            <label>
                                <input type="radio" name="mapping_method" value="csv_upload" <?php checked($mapping_method, 'csv_upload'); ?>>
                                Use CSV mapping file
                                <p class="description">Upload a CSV file with columns: business_id, category_name, parent_category_name (optional)</p>
                                <input type="file" name="mapping_csv" accept=".csv" style="margin-top: 5px;">
                            </label>
                            <br><br>
                            
                            <label>
                                <input type="radio" name="mapping_method" value="content_analysis" <?php checked($mapping_method, 'content_analysis'); ?>>
                                Analyze business content (less reliable)
                                <p class="description">Attempts to determine categories based on business name and content.</p>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="lbd_link_categories_submit" class="button button-primary" value="Link Categories Now">
                </p>
            </form>
        </div>
        
        <div class="card">
            <h2>Available Categories</h2>
            <p>Review the current category hierarchy to understand available options:</p>
            
            <?php
            // Display the current category hierarchy
            $categories = get_terms(array(
                'taxonomy' => 'business_category',
                'hide_empty' => false,
                'parent' => 0,
            ));
            
            if (!empty($categories)) {
                echo '<ul class="category-hierarchy">';
                foreach ($categories as $category) {
                    echo '<li><strong>' . esc_html($category->name) . '</strong> (ID: ' . $category->term_id . ')';
                    
                    // Get children
                    $children = get_terms(array(
                        'taxonomy' => 'business_category',
                        'hide_empty' => false,
                        'parent' => $category->term_id,
                    ));
                    
                    if (!empty($children)) {
                        echo '<ul>';
                        foreach ($children as $child) {
                            echo '<li>' . esc_html($child->name) . ' (ID: ' . $child->term_id . ')</li>';
                        }
                        echo '</ul>';
                    }
                    
                    echo '</li>';
                }
                echo '</ul>';
            } else {
                echo '<p>No categories found.</p>';
            }
            ?>
        </div>
    </div>
    
    <style>
        .category-hierarchy {
            margin-left: 20px;
        }
        .category-hierarchy ul {
            margin-left: 30px;
        }
    </style>
    <?php
}

/**
 * Link categories using name matching
 * 
 * @return array Processing results
 */
function lbd_link_categories_by_name_match() {
    $results = array(
        'processed' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => array(),
        'log' => array()
    );
    
    // Get all businesses
    $businesses = get_posts(array(
        'post_type' => 'business',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    ));
    
    if (empty($businesses)) {
        $results['errors'][] = 'No published businesses found.';
        return $results;
    }
    
    $results['processed'] = count($businesses);
    
    // Get all categories with parent information
    $all_categories = get_terms(array(
        'taxonomy' => 'business_category',
        'hide_empty' => false,
    ));
    
    $category_map = array();
    $child_categories = array();
    
    foreach ($all_categories as $category) {
        $category_map[$category->term_id] = $category;
        
        // Group children by parent
        if ($category->parent > 0) {
            if (!isset($child_categories[$category->parent])) {
                $child_categories[$category->parent] = array();
            }
            $child_categories[$category->parent][] = $category;
        }
    }
    
    // Process each business
    foreach ($businesses as $business) {
        // Get current categories for the business
        $current_terms = wp_get_object_terms($business->ID, 'business_category');
        
        if (empty($current_terms)) {
            $results['log'][] = "Business #{$business->ID} ({$business->post_title}): No existing categories found.";
            $results['skipped']++;
            continue;
        }
        
        // Get the first term (primary category)
        $current_term = $current_terms[0];
        
        // Check if this term is already a child category (has a parent)
        if ($current_term->parent > 0) {
            $results['log'][] = "Business #{$business->ID} ({$business->post_title}): Already has a parent category. Term: {$current_term->name} (ID: {$current_term->term_id})";
            $results['skipped']++;
            continue;
        }
        
        // This is a top-level category. Check if there's a child with the same name
        if (!isset($child_categories[$current_term->term_id]) || empty($child_categories[$current_term->term_id])) {
            // No children for this category
            $results['log'][] = "Business #{$business->ID} ({$business->post_title}): Top-level category with no children. Term: {$current_term->name} (ID: {$current_term->term_id})";
            $results['skipped']++;
            continue;
        }
        
        // Find a child with the same name
        $found_child = null;
        foreach ($child_categories[$current_term->term_id] as $child) {
            if (strtolower($child->name) === strtolower($current_term->name)) {
                $found_child = $child;
                break;
            }
        }
        
        if ($found_child) {
            // Found a child with the same name, update the business
            wp_set_object_terms($business->ID, $found_child->term_id, 'business_category', false);
            $results['log'][] = "Business #{$business->ID} ({$business->post_title}): Updated from {$current_term->name} (ID: {$current_term->term_id}) to child category {$found_child->name} (ID: {$found_child->term_id})";
            $results['updated']++;
        } else {
            // No matching child found
            $results['log'][] = "Business #{$business->ID} ({$business->post_title}): No matching child category found for {$current_term->name} (ID: {$current_term->term_id})";
            $results['skipped']++;
        }
    }
    
    return $results;
}

/**
 * Link categories using a CSV mapping file
 * 
 * @param array $file Uploaded file data
 * @return array Processing results
 */
function lbd_link_categories_by_csv($file) {
    $results = array(
        'processed' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => array(),
        'log' => array()
    );
    
    // Check for errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $results['errors'][] = 'Error uploading file. Please try again.';
        return $results;
    }
    
    // Check file type
    $file_type = wp_check_filetype(basename($file['name']));
    if ($file_type['ext'] !== 'csv') {
        $results['errors'][] = 'Please upload a valid CSV file.';
        return $results;
    }
    
    // Open the file
    $handle = fopen($file['tmp_name'], 'r');
    if (!$handle) {
        $results['errors'][] = 'Error opening file. Please try again.';
        return $results;
    }
    
    // Get headers
    $headers = fgetcsv($handle);
    if (!$headers) {
        fclose($handle);
        $results['errors'][] = 'Error reading CSV headers.';
        return $results;
    }
    
    // Required fields
    $required_fields = array('business_id', 'category_name');
    $missing_fields = array();
    
    // Check for required fields
    foreach ($required_fields as $field) {
        if (!in_array($field, $headers)) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        fclose($handle);
        $results['errors'][] = 'Missing required fields: ' . implode(', ', $missing_fields);
        return $results;
    }
    
    $parent_category_idx = array_search('parent_category_name', $headers);
    
    // Count rows for processed total
    $row_count = 0;
    while (fgetcsv($handle) !== false) {
        $row_count++;
    }
    rewind($handle);
    fgetcsv($handle); // Skip headers
    
    $results['processed'] = $row_count;
    
    // Process each row
    $row_number = 1;
    while (($row = fgetcsv($handle)) !== false) {
        $row_number++;
        
        // Create associative array
        $data = array();
        foreach ($headers as $index => $header) {
            $data[$header] = isset($row[$index]) ? trim($row[$index]) : '';
        }
        
        // Skip if missing required fields
        if (empty($data['business_id']) || empty($data['category_name'])) {
            $results['log'][] = "Row {$row_number}: Missing required fields. Skipping.";
            $results['skipped']++;
            continue;
        }
        
        $business_id = intval($data['business_id']);
        $category_name = sanitize_text_field($data['category_name']);
        $parent_name = ($parent_category_idx !== false && isset($data['parent_category_name'])) ? 
                      sanitize_text_field($data['parent_category_name']) : '';
        
        // Verify business exists
        $business = get_post($business_id);
        if (!$business || $business->post_type !== 'business') {
            $results['log'][] = "Row {$row_number}: Business #{$business_id} not found or not a business post. Skipping.";
            $results['skipped']++;
            continue;
        }
        
        // Find the category term
        $target_term_id = null;
        
        if (!empty($parent_name)) {
            // First, find the parent
            $parent_term = term_exists($parent_name, 'business_category');
            
            if (!$parent_term) {
                $results['log'][] = "Row {$row_number}: Parent category '{$parent_name}' not found for business #{$business_id}. Skipping.";
                $results['skipped']++;
                continue;
            }
            
            // Then find the child category under this parent
            $child_term = term_exists($category_name, 'business_category', $parent_term['term_id']);
            
            if (!$child_term) {
                $results['log'][] = "Row {$row_number}: Category '{$category_name}' not found under parent '{$parent_name}' for business #{$business_id}. Skipping.";
                $results['skipped']++;
                continue;
            }
            
            $target_term_id = $child_term['term_id'];
        } else {
            // Look for a top-level category
            $term = term_exists($category_name, 'business_category');
            
            if (!$term) {
                $results['log'][] = "Row {$row_number}: Category '{$category_name}' not found for business #{$business_id}. Skipping.";
                $results['skipped']++;
                continue;
            }
            
            $target_term_id = $term['term_id'];
        }
        
        // Update the business
        if ($target_term_id) {
            wp_set_object_terms($business_id, $target_term_id, 'business_category', false);
            $results['log'][] = "Row {$row_number}: Business #{$business_id} ({$business->post_title}) updated with category '{$category_name}'" . 
                               (!empty($parent_name) ? " under parent '{$parent_name}'" : '') . ".";
            $results['updated']++;
        }
    }
    
    fclose($handle);
    return $results;
}

/**
 * Link categories using content analysis
 * 
 * @return array Processing results
 */
function lbd_link_categories_by_content_analysis() {
    $results = array(
        'processed' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => array(),
        'log' => array()
    );
    
    // Get all businesses
    $businesses = get_posts(array(
        'post_type' => 'business',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    ));
    
    if (empty($businesses)) {
        $results['errors'][] = 'No published businesses found.';
        return $results;
    }
    
    $results['processed'] = count($businesses);
    
    // Get all categories
    $all_categories = get_terms(array(
        'taxonomy' => 'business_category',
        'hide_empty' => false,
    ));
    
    // Group by parent and create keyword maps
    $parent_categories = array();
    $child_categories = array();
    $category_keywords = array();
    
    foreach ($all_categories as $category) {
        // Prepare keywords from category name
        $keywords = explode(' ', strtolower($category->name));
        $keywords = array_filter($keywords, function($word) {
            return strlen($word) > 3; // Only use words with more than 3 characters
        });
        
        // Store in our maps
        $category_keywords[$category->term_id] = $keywords;
        
        if ($category->parent === 0) {
            $parent_categories[$category->term_id] = $category;
        } else {
            if (!isset($child_categories[$category->parent])) {
                $child_categories[$category->parent] = array();
            }
            $child_categories[$category->parent][] = $category;
        }
    }
    
    // Process each business
    foreach ($businesses as $business) {
        // Get content for analysis
        $content = $business->post_title . ' ' . $business->post_content . ' ' . $business->post_excerpt;
        $content = strtolower($content);
        
        // Check if already has categories
        $current_terms = wp_get_object_terms($business->ID, 'business_category');
        
        // Start with best match as null
        $best_parent_match = null;
        $best_parent_score = 0;
        $best_child_match = null;
        $best_child_score = 0;
        
        // First, find the best parent match
        foreach ($parent_categories as $parent_id => $parent) {
            $score = 0;
            
            // Check for exact name match (highest priority)
            if (stripos($content, $parent->name) !== false) {
                $score += 10;
            }
            
            // Check for keyword matches
            foreach ($category_keywords[$parent_id] as $keyword) {
                if (stripos($content, $keyword) !== false) {
                    $score += 2;
                }
            }
            
            // Update best match if score is higher
            if ($score > $best_parent_score) {
                $best_parent_score = $score;
                $best_parent_match = $parent;
            }
        }
        
        // If we found a parent match, look for the best child
        if ($best_parent_match && isset($child_categories[$best_parent_match->term_id])) {
            foreach ($child_categories[$best_parent_match->term_id] as $child) {
                $score = 0;
                
                // Check for exact name match (highest priority)
                if (stripos($content, $child->name) !== false) {
                    $score += 10;
                }
                
                // Check for keyword matches
                foreach ($category_keywords[$child->term_id] as $keyword) {
                    if (stripos($content, $keyword) !== false) {
                        $score += 2;
                    }
                }
                
                // Update best match if score is higher
                if ($score > $best_child_score) {
                    $best_child_score = $score;
                    $best_child_match = $child;
                }
            }
        }
        
        // Determine which category to use
        $target_term = null;
        
        if ($best_child_match && $best_child_score >= 5) {
            // Use child if good match found
            $target_term = $best_child_match;
            $match_type = "child category {$best_child_match->name} (score: {$best_child_score})";
        } elseif ($best_parent_match && $best_parent_score >= 5) {
            // Fallback to parent if good match but no good child match
            $target_term = $best_parent_match;
            $match_type = "parent category {$best_parent_match->name} (score: {$best_parent_score})";
        }
        
        if ($target_term) {
            // Update the business
            wp_set_object_terms($business->ID, $target_term->term_id, 'business_category', false);
            $results['log'][] = "Business #{$business->ID} ({$business->post_title}): Updated to {$match_type}";
            $results['updated']++;
        } else {
            $results['log'][] = "Business #{$business->ID} ({$business->post_title}): No good category match found";
            $results['skipped']++;
        }
    }
    
    return $results;
}

/**
 * Get the whitelist of allowed top-level business categories
 * These are the ONLY categories that can exist at the top level
 * All other categories MUST be children of these categories
 * 
 * @return array Array of category names that are allowed at the top level
 */
function lbd_get_allowed_top_level_categories() {
    // You can customize this list as needed
    return array(
        'Restaurants',
        'Retail',
        'Services',
        'Health & Beauty',
        'Entertainment',
        'Accommodation',
        'Education',
        'Professional Services',
        'Bars & Pubs',
        'Cafes',
        'Shopping',
        'Transport',
        'Sports & Recreation',
        'Arts & Culture',
        'Home & Garden',
        'Automotive',
        'Travel',
        'Financial Services',
        'Nightlife',
        'Community',
        'Real Estate',
        'Technology',
        'Legal Services',
        'Animals & Pets',
        'Religious Organizations',
        'Manufacturing',
        'Construction',
        'Government',
        'Utilities',
        'Media'
    );
}

/**
 * Get or create a business category with strict parent-child hierarchy
 * 
 * @param string $category_name The name of the category to get or create
 * @param string $parent_name Optional parent category name
 * @param bool $allow_create Whether to create categories that don't exist
 * @return array|null Term array or null if not found/created
 */
function lbd_get_or_create_business_category($category_name, $parent_name = '', $allow_create = false) {
    // Clean the inputs
    $category_name = trim($category_name);
    $parent_name = trim($parent_name);
    
    if (empty($category_name)) {
        return null;
    }
    
    // Normalize the category names
    $normalized_category = strtolower($category_name);
    $normalized_parent = strtolower($parent_name);
    
    error_log("get_or_create_category: Category='{$category_name}', Parent='{$parent_name}', Allow Create={$allow_create}");
    
    // Check if this is a top-level category
    $is_top_level = empty($parent_name);
    
    // If this is a top-level category, ensure it's in the whitelist
    if ($is_top_level) {
        $allowed_categories = lbd_get_allowed_top_level_categories();
        $allowed_top_level = false;
        
        // Case-insensitive search through allowed categories
        foreach ($allowed_categories as $allowed) {
            if (strtolower($allowed) === $normalized_category) {
                $allowed_top_level = true;
                $category_name = $allowed; // Use the proper casing from the whitelist
                break;
            }
        }
        
        // If not in whitelist, try to find a suitable parent
        if (!$allowed_top_level) {
            // Get existing top-level categories to find the best match
            $existing_parents = get_terms([
                'taxonomy' => 'business_category',
                'hide_empty' => false,
                'parent' => 0
            ]);
            
            if (!is_wp_error($existing_parents) && !empty($existing_parents)) {
                // Try to assign to the "Services" category by default if it exists
                foreach ($existing_parents as $term) {
                    if (strtolower($term->name) === 'services') {
                        $parent_name = $term->name;
                        $is_top_level = false;
                        break;
                    }
                }
                
                // If "Services" doesn't exist, just use the first available parent
                if ($is_top_level) {
                    $parent_name = $existing_parents[0]->name;
                    $is_top_level = false;
                }
            } else {
                // If no parents exist yet, create "Services" as the default parent
                if ($allow_create) {
                    $result = wp_insert_term('Services', 'business_category');
                    if (!is_wp_error($result)) {
                        $parent_name = 'Services';
                        $is_top_level = false;
                    }
                }
            }
            
            // Log this forced parent assignment for transparency
            error_log("Forced category '{$category_name}' under parent '{$parent_name}' because it's not in the allowed top-level whitelist");
        }
    }
    
    // Process top-level category
    if ($is_top_level) {
        // Check if it exists
        $term = term_exists($category_name, 'business_category');
        
        // Create only if allowed and not exists
        if (!$term && $allow_create) {
            $term = wp_insert_term($category_name, 'business_category');
            error_log("Created top-level category: '{$category_name}'");
        }
        
        return is_wp_error($term) ? null : $term;
    }
    
    // Process child category (with parent)
    
    // First, get or create the parent
    $parent_term = lbd_get_or_create_business_category($parent_name, '', $allow_create);
    
    // If parent doesn't exist and we can't create it, fail
    if (!$parent_term) {
        error_log("Could not get or create parent category '{$parent_name}' for '{$category_name}'");
        return null;
    }
    
    // First, try to find an exact match with the correct parent
    $term = term_exists($category_name, 'business_category', $parent_term['term_id']);
    if ($term) {
        error_log("Found exact match for '{$category_name}' with parent ID {$parent_term['term_id']}");
        return $term;
    }
    
    // If no exact match, try a more flexible approach to find similar categories
    $all_terms = get_terms([
        'taxonomy' => 'business_category',
        'hide_empty' => false,
        'search' => $category_name,
    ]);
    
    if (!is_wp_error($all_terms)) {
        // First, look for exact matches with any parent
        foreach ($all_terms as $existing_term) {
            if (strtolower($existing_term->name) === $normalized_category) {
                // We found an exact match by name, but maybe with a different parent
                // Update the parent if allowed
                if ($allow_create) {
                    $result = wp_update_term($existing_term->term_id, 'business_category', [
                        'parent' => $parent_term['term_id']
                    ]);
                    
                    if (!is_wp_error($result)) {
                        error_log("Updated category '{$category_name}' to have parent ID {$parent_term['term_id']}");
                        return $result;
                    }
                }
                
                // Even if we couldn't update the parent, return the term we found
                error_log("Found category '{$category_name}' with different parent, returning as-is");
                return array(
                    'term_id' => $existing_term->term_id,
                    'term_taxonomy_id' => $existing_term->term_taxonomy_id
                );
            }
        }
        
        // Next, look for similar matches in the same parent
        foreach ($all_terms as $existing_term) {
            if ($existing_term->parent == $parent_term['term_id'] && 
                (stripos($existing_term->name, $category_name) !== false || 
                 stripos($category_name, $existing_term->name) !== false)) {
                
                error_log("Found similar category '{$existing_term->name}' (ID: {$existing_term->term_id}) with matching parent");
                return array(
                    'term_id' => $existing_term->term_id,
                    'term_taxonomy_id' => $existing_term->term_taxonomy_id
                );
            }
        }
        
        // Finally, look for similar matches with any parent
        foreach ($all_terms as $existing_term) {
            if (stripos($existing_term->name, $category_name) !== false || 
                stripos($category_name, $existing_term->name) !== false) {
                
                // Found a similar term, update parent if allowed
                if ($allow_create) {
                    $result = wp_update_term($existing_term->term_id, 'business_category', [
                        'parent' => $parent_term['term_id']
                    ]);
                    
                    if (!is_wp_error($result)) {
                        error_log("Updated similar category '{$existing_term->name}' to have parent ID {$parent_term['term_id']}");
                        return $result;
                    }
                }
                
                error_log("Found similar category '{$existing_term->name}' with different parent, returning as-is");
                return array(
                    'term_id' => $existing_term->term_id,
                    'term_taxonomy_id' => $existing_term->term_taxonomy_id
                );
            }
        }
    }
    
    // If no suitable term found and creation is allowed, create it
    if ($allow_create) {
        $term = wp_insert_term($category_name, 'business_category', [
            'parent' => $parent_term['term_id']
        ]);
        
        if (!is_wp_error($term)) {
            error_log("Created new category '{$category_name}' with parent ID {$parent_term['term_id']}");
            return $term;
        }
    }
    
    error_log("Could not find or create category '{$category_name}' with parent '{$parent_name}'");
    return null;
}

/**
 * Custom sanitizer for Google rating to handle potential non-numeric characters
 */
function lbd_sanitize_google_rating($value) {
    // Remove any non-numeric characters (except for decimal point)
    $value = preg_replace('/[^0-9.]/', '', $value);
    return floatval($value);
}

/**
 * Sanitize yes/no values to boolean or CMB2 compatible format
 * 
 * @param string $value The value to sanitize
 * @return string|boolean 'on' for yes values when using with CMB2, boolean true/false for general use
 */
if (!function_exists('lbd_sanitize_yes_no')) {
    function lbd_sanitize_yes_no($value) {
        // Handle boolean values
        if (is_bool($value)) {
            return $value;
        }
        
        // Handle numeric values
        if (is_numeric($value)) {
            return (int)$value === 1 ? 'on' : '';
        }
        
        // Convert string values
        $value = strtolower(trim($value));
        if (in_array($value, array('yes', 'true', 'y', '1', 'on'))) {
            return 'on'; // Use 'on' for CMB2 checkbox checked state
        }
        
        // Return empty string for 'no' or anything else, CMB2 treats empty as unchecked
        return ''; 
    }
}

// 1. Add new admin menu item
add_action('admin_menu', function() {
    add_submenu_page(
        'local-business-directory',
        'Update Category Mappings',
        'Update Category Mappings',
        'manage_options',
        'lbd-update-category-mappings',
        'lbd_update_category_mappings_page'
    );
});

// 2. Admin page callback
function lbd_update_category_mappings_page() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }
    $updated = 0;
    $skipped = 0;
    $errors = 0;
    if (isset($_POST['lbd_update_categories'])) {
        $unassigned_term = get_term_by('slug', 'unassigned', 'business_category');
        if ($unassigned_term) {
            $args = array(
                'post_type' => 'business',
                'posts_per_page' => -1,
                'tax_query' => array([
                    'taxonomy' => 'business_category',
                    'field' => 'term_id',
                    'terms' => $unassigned_term->term_id,
                ]),
                'post_status' => 'publish',
            );
            $businesses = get_posts($args);
            foreach ($businesses as $business) {
                $parent_cat = get_post_meta($business->ID, 'parent_category_name', true);
                if ($parent_cat) {
                    if (function_exists('lbd_get_category_mapping_for_import')) {
                        $mapped_term_id = lbd_get_category_mapping_for_import($parent_cat);
                        if ($mapped_term_id) {
                            wp_set_object_terms($business->ID, $mapped_term_id, 'business_category', false);
                            $updated++;
                            continue;
                        }
                    }
                }
                $skipped++;
            }
        } else {
            $errors++;
        }
    }
    echo '<div class="wrap"><h1>Update Category Mappings</h1>';
    echo '<form method="post"><p>This tool will re-assign all businesses in the Unassigned category to their mapped parent category if available.</p>';
    echo '<input type="submit" name="lbd_update_categories" class="button button-primary" value="Update Categories">';
    echo '</form>';
    if (isset($_POST['lbd_update_categories'])) {
        echo '<div class="notice notice-success"><p>Updated: ' . intval($updated) . ' | Skipped: ' . intval($skipped) . ' | Errors: ' . intval($errors) . '</p></div>';
    }
    echo '</div>';
}

// Add back the helper function
function lbd_get_saved_mapping($cat) {
    $sanitized = sanitize_title($cat);
    $all = get_option('lbd_all_category_mappings', array());
    if (is_array($all) && isset($all[$sanitized])) return intval($all[$sanitized]);
    $ind = get_option('lbd_category_mapping_' . $sanitized);
    if ($ind) return intval($ind);
    return 0;
}
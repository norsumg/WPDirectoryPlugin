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
}
add_action('admin_menu', 'lbd_add_admin_menu');

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
    fputcsv($output, array(
        'business_name',
        'business_description',
        'business_excerpt',
        'business_area',
        'business_category',
        'business_phone',
        'business_address',
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
        'business_image_url',
        'business_black_owned',
        'business_women_owned',
        'business_lgbtq_friendly',
        'business_google_rating',
        'business_google_review_count',
        'business_google_reviews_url',
        'business_photos',
        'business_accreditations'
    ));
    
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
        $website = get_post_meta($business->ID, 'lbd_website', true);
        $premium = get_post_meta($business->ID, 'lbd_premium', true) ? 'yes' : 'no';
        
        // Get featured image URL
        $image_url = '';
        if (has_post_thumbnail($business->ID)) {
            $image_id = get_post_thumbnail_id($business->ID);
            $image_url = wp_get_attachment_url($image_id);
        }
        
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
            $image_url,
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
 * Enqueue admin styles
 */
function lbd_admin_styles() {
    wp_enqueue_style('lbd-admin-styles', plugin_dir_url(dirname(__FILE__)) . 'assets/css/admin.css');
}
add_action('admin_enqueue_scripts', 'lbd_admin_styles');

/**
 * Main admin page callback
 */
function lbd_admin_main_page() {
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
        // Handle CSV upload and processing
        if (isset($_POST['lbd_csv_import_submit']) && isset($_FILES['lbd_csv_file'])) {
            lbd_handle_csv_import();
        }
        ?>
        
        <div class="card">
            <h2>CSV File Upload</h2>
            <p>Upload a CSV file containing business data to import.</p>
            
            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('lbd_csv_import_action', 'lbd_csv_import_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="lbd_csv_file">CSV File</label></th>
                        <td>
                            <input type="file" name="lbd_csv_file" id="lbd_csv_file" accept=".csv" required>
                            <p class="description">File must be a valid CSV with headers.</p>
                        </td>
                    </tr>
                </table>
                
                <h3>CSV Format</h3>
                <p>Your CSV file should have the following columns:</p>
                <ul class="csv-instructions">
                    <li><strong>business_name</strong> - The name of the business (required)</li>
                    <li><strong>business_description</strong> - The full description</li>
                    <li><strong>business_excerpt</strong> - A short description</li>
                    <li><strong>business_area</strong> - The area name (required)</li>
                    <li><strong>business_category</strong> - The category name (required)</li>
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
                    <input type="submit" name="lbd_csv_import_submit" class="button button-primary" value="Import Businesses">
                </p>
            </form>
        </div>
        
        <div class="card">
            <h2>Sample CSV</h2>
            <p>Download a sample CSV file to see the expected format.</p>
            <p><a href="#" class="button" id="lbd-sample-csv">Download Sample</a></p>
        </div>
    </div>
    
    <script>
    // Generate and download a sample CSV file
    document.getElementById('lbd-sample-csv').addEventListener('click', function(e) {
        e.preventDefault();
        
        const headers = 'business_name,business_description,business_excerpt,business_area,business_category,business_phone,business_address,business_website,business_email,business_facebook,business_instagram,business_hours_24,business_hours_monday,business_hours_tuesday,business_hours_wednesday,business_hours_thursday,business_hours_friday,business_hours_saturday,business_hours_sunday,business_payments,business_parking,business_amenities,business_accessibility,business_premium,business_image_url,business_black_owned,business_women_owned,business_lgbtq_friendly,business_google_rating,business_google_review_count,business_google_reviews_url\n';
        const sampleRow1 = 'ACME Web Design,"We create beautiful websites for small businesses. Our team has over 10 years of experience designing responsive websites that convert visitors into customers.",Web design experts in Ashford area,Ashford,Web Design,01234 567890,"123 Main St, Ashford",https://example.com,info@example.com,https://facebook.com/acmewebdesign,acmewebdesign,no,"9:00 AM - 5:00 PM","9:00 AM - 5:00 PM","9:00 AM - 5:00 PM","9:00 AM - 5:00 PM","9:00 AM - 5:00 PM","10:00 AM - 2:00 PM",Closed,"Cash, Credit Cards, PayPal","Free parking available","Free WiFi, Coffee, Meeting room","Wheelchair accessible entrance, Elevator",yes,https://example.com/sample-image1.jpg,yes,no,yes,4.7,23,https://g.page/acme-web-design\n';
        const sampleRow2 = 'Smith & Co Accountants,"Professional accounting services for small businesses and individuals. We provide tax preparation, bookkeeping, and financial planning.",Trusted local accountants serving Canterbury since 2005,Canterbury,Accountants,01234 123456,"45 High Street, Canterbury",https://example-accountants.com,contact@example-accountants.com,https://facebook.com/smithcoaccountants,smithcoaccountants,yes,"24 Hours","24 Hours","24 Hours","24 Hours","24 Hours","24 Hours","24 Hours","All major credit cards","Street parking","Private consultation rooms, Tea and coffee","Wheelchair accessible",no,https://example.com/sample-image2.jpg,no,yes,no,4.2,17,https://g.page/smith-co-accountants\n';
        
        const csvContent = headers + sampleRow1 + sampleRow2;
        const blob = new Blob([csvContent], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        
        const a = document.createElement('a');
        a.setAttribute('hidden', '');
        a.setAttribute('href', url);
        a.setAttribute('download', 'sample_businesses.csv');
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    });
    </script>
    <?php
}

/**
 * Handle CSV import process
 */
function lbd_handle_csv_import() {
    // Check nonce for security
    if (!isset($_POST['lbd_csv_import_nonce']) || !wp_verify_nonce($_POST['lbd_csv_import_nonce'], 'lbd_csv_import_action')) {
        echo '<div class="notice notice-error"><p>Security check failed. Please try again.</p></div>';
        return;
    }
    
    // Get the uploaded file
    $file = $_FILES['lbd_csv_file'];
    
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
    if (!$handle) {
        echo '<div class="notice notice-error"><p>Error opening file. Please try again.</p></div>';
        return;
    }
    
    // Get headers
    $headers = fgetcsv($handle);
    if (!$headers) {
        fclose($handle);
        echo '<div class="notice notice-error"><p>Error reading CSV headers.</p></div>';
        return;
    }
    
    // Required fields
    $required_fields = array('business_name', 'business_area', 'business_category');
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
    $errors = array();
    
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
        if (empty($data['business_name']) || empty($data['business_area']) || empty($data['business_category'])) {
            $skipped++;
            continue;
        }
        
        // Create the business
        $result = lbd_create_business_from_csv($data);
        
        if (is_wp_error($result)) {
            $errors[] = $data['business_name'] . ': ' . $result->get_error_message();
        } else {
            $imported++;
        }
    }
    
    fclose($handle);
    
    // Display results
    echo '<div class="notice notice-success"><p>Import complete. ' . $imported . ' businesses imported successfully.</p></div>';
    
    if ($skipped > 0) {
        echo '<div class="notice notice-warning"><p>' . $skipped . ' businesses were skipped due to missing required fields.</p></div>';
    }
    
    if (!empty($errors)) {
        echo '<div class="notice notice-error"><p>Errors:</p><ul>';
        foreach ($errors as $error) {
            echo '<li>' . esc_html($error) . '</li>';
        }
        echo '</ul></div>';
    }
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
    
    // Check if it's closed
    if (empty($hours_text) || strtolower($hours_text) === 'closed') {
        $result['closed'] = 'on';
        return $result;
    }
    
    // Try to parse the hours
    // Common formats: "9 am–5 pm", "9:00 AM - 5:00 PM", "9-5", etc.
    $patterns = array(
        // 9 am–5 pm (en dash)
        '/([0-9]{1,2})(?::([0-9]{2}))?\s*([aApP][mM])?(?:–|-)([0-9]{1,2})(?::([0-9]{2}))?\s*([aApP][mM])?/',
        // 9:00 AM - 5:00 PM (hyphen)
        '/([0-9]{1,2})(?::([0-9]{2}))?\s*([aApP][mM])?\s*(?:-|to)\s*([0-9]{1,2})(?::([0-9]{2}))?\s*([aApP][mM])?/',
    );
    
    $matched = false;
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $hours_text, $matches)) {
            $matched = true;
            
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
            break;
        }
    }
    
    // If no pattern matched, store the raw text in the open field
    if (!$matched) {
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
 * Get business hours for a specific day, with backward compatibility
 * 
 * @param int $post_id Business post ID
 * @param string $day Day of the week (monday, tuesday, etc.)
 * @return array|null Hours data array or null if not found
 */
function lbd_get_business_hours($post_id, $day) {
    // Try to get the new structured format first
    $hours_group = get_post_meta($post_id, 'lbd_hours_' . $day . '_group', true);
    
    // If we have data in the new format, return it
    if (!empty($hours_group) && is_array($hours_group)) {
        return $hours_group;
    }
    
    // Try the old format
    $old_hours = get_post_meta($post_id, 'lbd_hours_' . $day, true);
    
    // If we have old format data, convert it
    if (!empty($old_hours)) {
        // Parse the old format
        $hours_data = lbd_parse_hours_from_text($old_hours);
        
        // Store it in the new format
        $group_data = array($hours_data);
        update_post_meta($post_id, 'lbd_hours_' . $day . '_group', $group_data);
        
        // Return the converted data
        return $group_data;
    }
    
    return null;
}

// Make the function available outside of admin
add_action('init', function() {
    if (!function_exists('lbd_get_business_hours')) {
        function lbd_get_business_hours($post_id, $day) {
            // Call the admin function
            return \lbd_get_business_hours($post_id, $day);
        }
    }
});

/**
 * Create a business post from CSV data
 *
 * @param array $data CSV row data
 * @return int|WP_Error Post ID on success, WP_Error on failure
 */
function lbd_create_business_from_csv($data) {
    // Prepare post data
    $post_data = array(
        'post_title'    => sanitize_text_field($data['business_name']),
        'post_content'  => isset($data['business_description']) ? wp_kses_post($data['business_description']) : '',
        'post_excerpt'  => isset($data['business_excerpt']) ? sanitize_text_field($data['business_excerpt']) : '',
        'post_status'   => 'publish',
        'post_type'     => 'business',
    );
    
    // Insert post
    $post_id = wp_insert_post($post_data, true);
    
    if (is_wp_error($post_id)) {
        return $post_id;
    }
    
    // Add business area
    $area_name = sanitize_text_field($data['business_area']);
    $area = term_exists($area_name, 'business_area');
    
    if (!$area) {
        // Create the area if it doesn't exist
        $area = wp_insert_term($area_name, 'business_area');
    }
    
    if (!is_wp_error($area)) {
        wp_set_object_terms($post_id, intval($area['term_id']), 'business_area');
    }
    
    // Add business category
    $category_name = sanitize_text_field($data['business_category']);
    $category = term_exists($category_name, 'business_category');
    
    if (!$category) {
        // Create the category if it doesn't exist
        $category = wp_insert_term($category_name, 'business_category');
    }
    
    if (!is_wp_error($category)) {
        wp_set_object_terms($post_id, intval($category['term_id']), 'business_category');
    }
    
    // Store business meta data
    update_post_meta($post_id, 'lbd_phone', sanitize_text_field($data['business_phone'] ?? ''));
    update_post_meta($post_id, 'lbd_address', sanitize_text_field($data['business_address'] ?? ''));
    update_post_meta($post_id, 'lbd_website', esc_url_raw($data['business_website'] ?? ''));

    // Store new fields
    update_post_meta($post_id, 'lbd_email', sanitize_email($data['business_email'] ?? ''));
    update_post_meta($post_id, 'lbd_facebook', esc_url_raw($data['business_facebook'] ?? ''));
    update_post_meta($post_id, 'lbd_instagram', sanitize_text_field($data['business_instagram'] ?? ''));

    // Set 24 hours flag if provided
    if (isset($data['business_hours_24']) && strtolower($data['business_hours_24']) === 'yes') {
        update_post_meta($post_id, 'lbd_hours_24', '1');
    }

    // Store opening hours
    $days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
    foreach ($days as $day) {
        if (isset($data['business_hours_' . $day])) {
            $hours_text = sanitize_text_field($data['business_hours_' . $day]);
            
            // Parse hours text into structured format
            $hours_data = lbd_parse_hours_from_text($hours_text);
            
            // Store in the CMB2 group format
            $group_data = array($hours_data); // Just one entry for now
            update_post_meta($post_id, 'lbd_hours_' . $day . '_group', $group_data);
            
            // Remove old format data if it exists
            delete_post_meta($post_id, 'lbd_hours_' . $day);
        }
    }

    // Store additional information
    update_post_meta($post_id, 'lbd_payments', sanitize_text_field($data['business_payments'] ?? ''));
    update_post_meta($post_id, 'lbd_parking', sanitize_text_field($data['business_parking'] ?? ''));
    update_post_meta($post_id, 'lbd_amenities', sanitize_textarea_field($data['business_amenities'] ?? ''));
    update_post_meta($post_id, 'lbd_accessibility', sanitize_textarea_field($data['business_accessibility'] ?? ''));

    // Set premium status
    if (isset($data['business_premium']) && strtolower($data['business_premium']) === 'yes') {
        update_post_meta($post_id, 'lbd_premium', '1');
    }

    // Set business attributes
    if (isset($data['business_black_owned']) && strtolower($data['business_black_owned']) === 'yes') {
        update_post_meta($post_id, 'lbd_black_owned', '1');
    }

    if (isset($data['business_women_owned']) && strtolower($data['business_women_owned']) === 'yes') {
        update_post_meta($post_id, 'lbd_women_owned', '1');
    }

    if (isset($data['business_lgbtq_friendly']) && strtolower($data['business_lgbtq_friendly']) === 'yes') {
        update_post_meta($post_id, 'lbd_lgbtq_friendly', '1');
    }
    
    // Store Google Reviews data
    if (!empty($data['business_google_rating'])) {
        update_post_meta($post_id, 'lbd_google_rating', sanitize_text_field($data['business_google_rating']));
    }
    
    if (!empty($data['business_google_review_count'])) {
        update_post_meta($post_id, 'lbd_google_review_count', sanitize_text_field($data['business_google_review_count']));
    }
    
    if (!empty($data['business_google_reviews_url'])) {
        update_post_meta($post_id, 'lbd_google_reviews_url', esc_url_raw($data['business_google_reviews_url']));
    }
    
    // Import photos if provided
    if (!empty($data['business_photos'])) {
        $photo_urls = explode('|', $data['business_photos']);
        $photos = array();
        
        foreach ($photo_urls as $photo_url) {
            if (empty($photo_url)) continue;
            
            $photo_url = trim($photo_url);
            $attachment_id = lbd_import_image_from_url($photo_url, $post_id, $data['business_name'] . ' - Photo');
            
            if (!is_wp_error($attachment_id)) {
                $photos[$attachment_id] = wp_get_attachment_url($attachment_id);
            }
        }
        
        if (!empty($photos)) {
            update_post_meta($post_id, 'lbd_business_photos', $photos);
        }
    }
    
    // Import accreditations if provided
    if (!empty($data['business_accreditations'])) {
        $accreditations = json_decode(stripslashes($data['business_accreditations']), true);
        
        if (is_array($accreditations)) {
            // Import logos for accreditations
            foreach ($accreditations as $key => $accreditation) {
                if (!empty($accreditation['logo_url'])) {
                    $logo_url = $accreditation['logo_url'];
                    $attachment_id = lbd_import_image_from_url($logo_url, $post_id, $accreditation['name'] . ' - Logo');
                    
                    if (!is_wp_error($attachment_id)) {
                        $accreditations[$key]['logo'] = $attachment_id;
                    }
                    
                    // Remove the logo_url as it's not part of our schema
                    unset($accreditations[$key]['logo_url']);
                }
            }
            
            update_post_meta($post_id, 'lbd_accreditations', $accreditations);
        }
    }
    
    // Set featured image if URL is provided
    if (!empty($data['business_image_url'])) {
        $image_url = esc_url_raw($data['business_image_url']);
        $image_id = lbd_set_featured_image_from_url($image_url, $post_id, $data['business_name']);
        
        if (is_wp_error($image_id)) {
            // Log the error but continue with the import
            error_log('Error importing image for ' . $data['business_name'] . ': ' . $image_id->get_error_message());
        }
    }
    
    return $post_id;
}

/**
 * Set a featured image from a URL
 *
 * @param string $image_url The URL of the image
 * @param int $post_id The post ID to attach the image to
 * @param string $title The title for the image
 * @return int|WP_Error The attachment ID or WP_Error
 */
function lbd_set_featured_image_from_url($image_url, $post_id, $title = '') {
    // Check if this image has already been uploaded
    $existing_attachment = get_posts(array(
        'post_type' => 'attachment',
        'meta_key' => '_lbd_source_url',
        'meta_value' => $image_url,
        'posts_per_page' => 1,
    ));
    
    if (!empty($existing_attachment)) {
        $attach_id = $existing_attachment[0]->ID;
        set_post_thumbnail($post_id, $attach_id);
        return $attach_id;
    }
    
    // Include necessary files for media handling
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    
    // Download the image
    $tmp = download_url($image_url);
    
    if (is_wp_error($tmp)) {
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
        return $attach_id;
    }
    
    // Save the source URL as post meta for future reference
    update_post_meta($attach_id, '_lbd_source_url', $image_url);
    
    // Set as featured image
    set_post_thumbnail($post_id, $attach_id);
    
    return $attach_id;
}

/**
 * Import an image from a URL and attach it to a post
 * Similar to lbd_set_featured_image_from_url but doesn't set as featured image
 */
function lbd_import_image_from_url($image_url, $post_id, $title = '') {
    // Check if this image has already been uploaded
    $existing_attachment = get_posts(array(
        'post_type' => 'attachment',
        'meta_key' => '_lbd_source_url',
        'meta_value' => $image_url,
        'posts_per_page' => 1,
    ));
    
    if (!empty($existing_attachment)) {
        return $existing_attachment[0]->ID;
    }
    
    // Include necessary files for media handling
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    
    // Download the image
    $tmp = download_url($image_url);
    
    if (is_wp_error($tmp)) {
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
        return $attach_id;
    }
    
    // Save the source URL as post meta for future reference
    update_post_meta($attach_id, '_lbd_source_url', $image_url);
    
    return $attach_id;
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
                            <p class="description">File must be a CSV with headers: business_id, reviewer_name, review_text, rating, review_date, source_id</p>
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
    $review_text = isset($_POST['lbd_review_text']) ? sanitize_textarea_field($_POST['lbd_review_text']) : '';
    $rating = isset($_POST['lbd_rating']) ? intval($_POST['lbd_rating']) : 5;
    $source = isset($_POST['lbd_source']) ? sanitize_text_field($_POST['lbd_source']) : 'manual';
    
    // Validate
    if (!$business_id || !$reviewer_name || !$review_text || $rating < 1 || $rating > 5) {
        echo '<div class="notice notice-error"><p>Please fill in all required fields correctly.</p></div>';
        return;
    }
    
    // Add the review
    $result = lbd_add_review($business_id, $reviewer_name, $review_text, $rating, $source);
    
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
    if (!$handle) {
        echo '<div class="notice notice-error"><p>Error opening file. Please try again.</p></div>';
        return;
    }
    
    // Get headers
    $headers = fgetcsv($handle);
    if (!$headers) {
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
        $result = lbd_add_review($business_id, $reviewer_name, $review_text, $rating, $source, $source_id);
        
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
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
        'business_premium',
        'business_image_url'
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
            $premium,
            $image_url
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
                <ul style="list-style-type: disc; margin-left: 2em;">
                    <li><strong>business_name</strong> - The name of the business (required)</li>
                    <li><strong>business_description</strong> - The full description</li>
                    <li><strong>business_excerpt</strong> - A short description</li>
                    <li><strong>business_area</strong> - The area name (required)</li>
                    <li><strong>business_category</strong> - The category name (required)</li>
                    <li><strong>business_phone</strong> - Phone number</li>
                    <li><strong>business_address</strong> - Physical address</li>
                    <li><strong>business_website</strong> - Website URL</li>
                    <li><strong>business_premium</strong> - Set to "yes" for premium listings</li>
                    <li><strong>business_image_url</strong> - URL to a featured image</li>
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
        
        const headers = 'business_name,business_description,business_excerpt,business_area,business_category,business_phone,business_address,business_website,business_premium,business_image_url\n';
        const sampleRow1 = 'ACME Web Design,"We create beautiful websites for small businesses. Our team has over 10 years of experience designing responsive websites that convert visitors into customers.",Web design experts in Ashford area,Ashford,Web Design,01234 567890,"123 Main St, Ashford",https://example.com,yes,https://example.com/sample-image1.jpg\n';
        const sampleRow2 = 'Smith & Co Accountants,"Professional accounting services for small businesses and individuals. We provide tax preparation, bookkeeping, and financial planning.",Trusted local accountants serving Canterbury since 2005,Canterbury,Accountants,01234 123456,"45 High Street, Canterbury",https://example-accountants.com,no,https://example.com/sample-image2.jpg\n';
        
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
    
    // Add meta data
    if (isset($data['business_phone'])) {
        update_post_meta($post_id, 'lbd_phone', sanitize_text_field($data['business_phone']));
    }
    
    if (isset($data['business_address'])) {
        update_post_meta($post_id, 'lbd_address', sanitize_text_field($data['business_address']));
    }
    
    if (isset($data['business_website'])) {
        update_post_meta($post_id, 'lbd_website', esc_url_raw($data['business_website']));
    }
    
    // Set premium status
    if (isset($data['business_premium']) && strtolower($data['business_premium']) === 'yes') {
        update_post_meta($post_id, 'lbd_premium', '1');
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
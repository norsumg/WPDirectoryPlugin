<?php
// Check if CMB2 exists before including it
$cmb2_file = plugin_dir_path( __FILE__ ) . '../lib/CMB2/init.php';
if (file_exists($cmb2_file)) {
    require_once $cmb2_file;
}

function lbd_metaboxes() {
    // Only proceed if CMB2 is available
    if (!function_exists('new_cmb2_box')) {
        return;
    }

    $cmb = new_cmb2_box( array(
        'id'            => 'lbd_metabox',
        'title'         => __( 'Business Details', 'lbd' ),
        'object_types'  => array( 'business', ), // Post type
        'context'       => 'normal',
        'priority'      => 'high',
        'show_names'    => true, // Show field names on the left
    ) );

    // Cover Photo
    $cmb->add_field( array(
        'name' => 'Cover Photo',
        'desc' => 'Upload a landscape image (recommended size: 1024x280px) to be displayed at the top of your business profile',
        'id'   => 'lbd_cover_photo',
        'type' => 'file',
        'options' => array(
            'url' => true, // Allow URL storage for more reliable retrieval
        ),
        'preview_size' => 'medium',
        'text' => array(
            'add_upload_file_text' => 'Add Cover Photo'
        ),
    ) );

    $cmb->add_field( array(
        'name' => 'Phone',
        'id' => 'lbd_phone',
        'type' => 'text',
    ) );

    $cmb->add_field( array(
        'name' => 'Address',
        'id' => 'lbd_address',
        'type' => 'text',
    ) );

    // Add new address fields
    $cmb->add_field( array(
        'name' => 'Street Address',
        'id' => 'lbd_street_address',
        'type' => 'text',
        'desc' => 'The street address of the business',
    ) );

    $cmb->add_field( array(
        'name' => 'City',
        'id' => 'lbd_city',
        'type' => 'text',
        'desc' => 'The city where the business is located',
    ) );

    $cmb->add_field( array(
        'name' => 'Postcode',
        'id' => 'lbd_postcode',
        'type' => 'text',
        'desc' => 'The postal code of the business',
    ) );

    // Add location coordinates
    $cmb->add_field( array(
        'name' => 'Latitude',
        'id' => 'lbd_latitude',
        'type' => 'text',
        'desc' => 'The latitude coordinate of the business location',
    ) );

    $cmb->add_field( array(
        'name' => 'Longitude',
        'id' => 'lbd_longitude',
        'type' => 'text',
        'desc' => 'The longitude coordinate of the business location',
    ) );

    // Add logo field
    $cmb->add_field( array(
        'name' => 'Business Logo',
        'desc' => 'Upload a square logo image for your business',
        'id'   => 'lbd_logo',
        'type' => 'file',
        'options' => array(
            'url' => true,
        ),
        'preview_size' => 'medium',
        'text' => array(
            'add_upload_file_text' => 'Add Logo'
        ),
    ) );

    // Add extra categories field
    $cmb->add_field( array(
        'name' => 'Extra Service Categories',
        'id' => 'lbd_extra_categories',
        'type' => 'text',
        'desc' => 'Additional service categories, comma separated (e.g. "Electrical installation service, Electrician, Service establishment")',
    ) );

    // Add service options field
    $cmb->add_field( array(
        'name' => 'Service Options',
        'id' => 'lbd_service_options',
        'type' => 'text',
        'desc' => 'Available service options, comma separated (e.g. "On-site services, Online estimates")',
    ) );

    $cmb->add_field( array(
        'name' => 'Website',
        'id' => 'lbd_website',
        'type' => 'text_url',
    ) );

    $cmb->add_field( array(
        'name' => 'Email',
        'id' => 'lbd_email',
        'type' => 'text_email',
    ) );

    $cmb->add_field( array(
        'name' => 'Facebook',
        'id' => 'lbd_facebook',
        'type' => 'text_url',
        'desc' => 'Full URL to Facebook page',
    ) );

    $cmb->add_field( array(
        'name' => 'Instagram',
        'id' => 'lbd_instagram',
        'type' => 'text',
        'desc' => 'Instagram username without the @ symbol',
    ) );

    $cmb->add_field( array(
        'name' => 'Premium',
        'id' => 'lbd_premium',
        'type' => 'checkbox',
        'desc' => 'Check to mark this business as premium (appears first in search results)',
    ) );

    // Opening Hours Section
    $cmb->add_field( array(
        'name' => 'Opening Hours',
        'desc' => 'Set business hours for each day of the week',
        'id'   => 'lbd_hours_title',
        'type' => 'title',
    ) );

    $cmb->add_field( array(
        'name' => 'Open 24 Hours',
        'desc' => 'Check if this business is open 24 hours, 7 days a week',
        'id'   => 'lbd_hours_24',
        'type' => 'checkbox',
    ) );

    $days = array(
        'monday' => 'Monday',
        'tuesday' => 'Tuesday',
        'wednesday' => 'Wednesday',
        'thursday' => 'Thursday',
        'friday' => 'Friday',
        'saturday' => 'Saturday',
        'sunday' => 'Sunday'
    );

    foreach ($days as $day_id => $day_name) {
        // Create a group field for each day
        $day_group = $cmb->add_field( array(
            'name' => $day_name,
            'id' => 'lbd_hours_' . $day_id . '_group',
            'type' => 'group',
            'repeatable' => false,
            'options' => array(
                'group_title' => $day_name,
                'sortable' => false,
                'closed' => false,
            ),
        ) );

        // Open Time
        $cmb->add_group_field( $day_group, array(
            'name' => 'Opens',
            'id' => 'open',
            'type' => 'text_time',
            'time_format' => 'g:i A',
            'attributes' => array(
                'data-timepicker' => json_encode(array(
                    'stepMinute' => 5,
                )),
            ),
        ) );
        
        // Close Time
        $cmb->add_group_field( $day_group, array(
            'name' => 'Closes',
            'id' => 'close',
            'type' => 'text_time',
            'time_format' => 'g:i A',
            'attributes' => array(
                'data-timepicker' => json_encode(array(
                    'stepMinute' => 5,
                )),
            ),
        ) );
        
        // Closed Checkbox
        $cmb->add_group_field( $day_group, array(
            'name' => 'Closed',
            'id' => 'closed',
            'type' => 'checkbox',
        ) );
    }

    // Additional Information Section
    $cmb->add_field( array(
        'name' => 'Additional Information',
        'desc' => 'More details about your business',
        'id'   => 'lbd_additional_info_title',
        'type' => 'title',
    ) );

    $cmb->add_field( array(
        'name' => 'Payments Accepted',
        'id' => 'lbd_payments',
        'type' => 'text',
        'desc' => 'e.g. "Cash, Credit Card, PayPal"',
    ) );

    $cmb->add_field( array(
        'name' => 'Parking',
        'id' => 'lbd_parking',
        'type' => 'text',
        'desc' => 'e.g. "Free parking available", "Street parking only"',
    ) );

    $cmb->add_field( array(
        'name' => 'Amenities',
        'id' => 'lbd_amenities',
        'type' => 'textarea_small',
        'desc' => 'List special amenities offered by your business',
    ) );

    $cmb->add_field( array(
        'name' => 'Accessibility',
        'id' => 'lbd_accessibility',
        'type' => 'textarea_small',
        'desc' => 'Describe accessibility features',
    ) );

    // Business Attributes Section
    $cmb->add_field( array(
        'name' => 'Business Attributes',
        'desc' => 'Special characteristics of this business',
        'id'   => 'lbd_attributes_title',
        'type' => 'title',
    ) );

    $cmb->add_field( array(
        'name' => 'Black Owned',
        'id' => 'lbd_black_owned',
        'type' => 'checkbox',
    ) );

    $cmb->add_field( array(
        'name' => 'Women Owned',
        'id' => 'lbd_women_owned',
        'type' => 'checkbox',
    ) );

    $cmb->add_field( array(
        'name' => 'LGBTQ+ Friendly',
        'id' => 'lbd_lgbtq_friendly',
        'type' => 'checkbox',
    ) );

    // Google Reviews Section
    $cmb->add_field( array(
        'name' => 'Google Reviews',
        'desc' => 'Reviews data imported from Google',
        'id'   => 'lbd_google_reviews_title',
        'type' => 'title',
    ) );

    $cmb->add_field( array(
        'name' => 'Google Rating',
        'id' => 'lbd_google_rating',
        'type' => 'text',
        'desc' => 'Average rating from Google (e.g. "4.5")',
    ) );

    $cmb->add_field( array(
        'name' => 'Google Review Count',
        'id' => 'lbd_google_review_count',
        'type' => 'text',
        'desc' => 'Number of reviews on Google',
    ) );

    $cmb->add_field( array(
        'name' => 'Google Reviews URL',
        'id' => 'lbd_google_reviews_url',
        'type' => 'text_url',
        'desc' => 'Link to Google reviews (will be displayed to users)',
    ) );
    
    // Business Photos Gallery Section
    $cmb->add_field( array(
        'name' => 'Business Photos',
        'desc' => 'Upload photos to showcase your business',
        'id'   => 'lbd_photos_title',
        'type' => 'title',
    ) );
    
    $cmb->add_field( array(
        'name' => 'Photo Gallery',
        'id' => 'lbd_business_photos',
        'type' => 'file_list',
        'preview_size' => 'medium',
        'query_args' => array(
            'type' => 'image',
        ),
        'options' => array(
            'url' => true, // Store URLs for more reliable retrieval
        ),
        'desc' => 'Upload or select images. These will appear in the photos tab on your business page.',
    ) );
    
    // Accreditations Section
    $cmb->add_field( array(
        'name' => 'Accreditations',
        'desc' => 'Add certifications, memberships, and other professional accreditations',
        'id'   => 'lbd_accreditations_title',
        'type' => 'title',
    ) );
    
    $accreditation_group = $cmb->add_field( array(
        'id'          => 'lbd_accreditations',
        'type'        => 'group',
        'description' => 'Add each accreditation with a name, link, and logo',
        'options'     => array(
            'group_title'   => 'Accreditation {#}',
            'add_button'    => 'Add Another Accreditation',
            'remove_button' => 'Remove Accreditation',
            'sortable'      => true,
        ),
    ) );
    
    $cmb->add_group_field( $accreditation_group, array(
        'name' => 'Name',
        'id'   => 'name',
        'type' => 'text',
        'desc' => 'The name of the accreditation or certification',
    ) );
    
    $cmb->add_group_field( $accreditation_group, array(
        'name' => 'Link',
        'id'   => 'link',
        'type' => 'text_url',
        'desc' => 'URL to the accreditation organization (optional)',
    ) );
    
    $cmb->add_group_field( $accreditation_group, array(
        'name' => 'Logo',
        'id'   => 'logo',
        'type' => 'file',
        'preview_size' => 'medium',
        'options' => array(
            'url' => false,
        ),
        'desc' => 'Upload or select an image for the accreditation logo',
    ) );
    
    $cmb->add_group_field( $accreditation_group, array(
        'name' => 'Description',
        'id'   => 'description',
        'type' => 'textarea_small',
        'desc' => 'Brief description of the accreditation (optional)',
    ) );
}
add_action( 'cmb2_admin_init', 'lbd_metaboxes' );

// Hours admin script is now loaded via admin.js
function lbd_hours_admin_script() {
    // Function is now empty but kept for backwards compatibility
    // Script has been moved to assets/js/admin.js
}
add_action('admin_footer', 'lbd_hours_admin_script'); 
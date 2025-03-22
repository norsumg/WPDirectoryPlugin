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
        'id' => 'lbd_business_metabox',
        'title' => 'Business Details',
        'object_types' => array( 'business' ),
        'context' => 'normal',
        'priority' => 'high',
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
        $cmb->add_field( array(
            'name' => $day_name,
            'id' => 'lbd_hours_' . $day_id,
            'type' => 'text',
            'desc' => 'e.g. "9:00 AM - 5:00 PM" or "Closed"',
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

/**
 * Add JavaScript for 24-hour checkbox functionality
 */
function lbd_hours_admin_script() {
    global $post_type;
    if ($post_type !== 'business') {
        return;
    }
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        var daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        
        // Function to update hours fields based on 24-hour checkbox
        function update24HoursFields() {
            var is24Hours = $('#lbd_hours_24').prop('checked');
            
            // Loop through each day and update the field
            daysOfWeek.forEach(function(day) {
                var field = $('#lbd_hours_' + day);
                
                if (is24Hours) {
                    // Store original value as data attribute if not already stored
                    if (!field.data('original-value')) {
                        field.data('original-value', field.val());
                    }
                    field.val('24 Hours');
                    field.prop('readonly', true);
                    field.css('background-color', '#f0f0f0');
                } else {
                    // Restore original value if exists
                    if (field.data('original-value')) {
                        field.val(field.data('original-value'));
                    } else {
                        field.val('');
                    }
                    field.prop('readonly', false);
                    field.css('background-color', '');
                }
            });
        }
        
        // Set initial state
        update24HoursFields();
        
        // Handle checkbox change
        $('#lbd_hours_24').on('change', function() {
            update24HoursFields();
        });
    });
    </script>
    <?php
}
add_action('admin_footer', 'lbd_hours_admin_script'); 
<?php
/**
 * Business Submission Handler
 * 
 * Handles processing of business submission forms
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Process business submission form
 */
function lbd_process_business_submission() {
    // Check if form was submitted
    if (!isset($_POST['lbd_submit_business']) || !isset($_POST['lbd_submission_nonce'])) {
        return array(
            'success' => false,
            'message' => 'Invalid submission.'
        );
    }
    
    // Verify nonce
    if (!wp_verify_nonce($_POST['lbd_submission_nonce'], 'lbd_submit_business_action')) {
        return array(
            'success' => false,
            'message' => 'Security check failed. Please try again.'
        );
    }
    
    // Check honeypot field
    if (!empty($_POST['website'])) {
        // Silently reject spam submissions
        return array(
            'success' => true,
            'message' => 'Thank you for your submission!'
        );
    }
    
    // Rate limiting
    $user_ip = $_SERVER['REMOTE_ADDR'];
    $transient_key = 'lbd_submission_' . md5($user_ip);
    if (get_transient($transient_key)) {
        return array(
            'success' => false,
            'message' => 'Please wait a few minutes before submitting another business.'
        );
    }
    
    // Get and sanitize form data
    $submission_data = array(
        'business_name' => isset($_POST['business_name']) ? sanitize_text_field($_POST['business_name']) : '',
        'business_description' => isset($_POST['business_description']) ? wp_kses_post($_POST['business_description']) : '',
        'business_phone' => isset($_POST['business_phone']) ? sanitize_text_field($_POST['business_phone']) : '',
        'business_email' => isset($_POST['business_email']) ? sanitize_email($_POST['business_email']) : '',
        'business_website' => isset($_POST['business_website']) ? esc_url_raw($_POST['business_website']) : '',
        'business_street_address' => isset($_POST['business_street_address']) ? sanitize_text_field($_POST['business_street_address']) : '',
        'business_city' => isset($_POST['business_city']) ? sanitize_text_field($_POST['business_city']) : '',
        'business_postcode' => isset($_POST['business_postcode']) ? sanitize_text_field($_POST['business_postcode']) : '',
        'business_latitude' => isset($_POST['business_latitude']) ? sanitize_text_field($_POST['business_latitude']) : '',
        'business_longitude' => isset($_POST['business_longitude']) ? sanitize_text_field($_POST['business_longitude']) : '',
        'business_category' => isset($_POST['business_category']) ? sanitize_text_field($_POST['business_category']) : '',
        'business_area' => isset($_POST['business_area']) ? sanitize_text_field($_POST['business_area']) : '',
        'business_facebook' => isset($_POST['business_facebook']) ? esc_url_raw($_POST['business_facebook']) : '',
        'business_instagram' => isset($_POST['business_instagram']) ? sanitize_text_field($_POST['business_instagram']) : '',
        'business_black_owned' => isset($_POST['business_black_owned']) ? 'yes' : 'no',
        'business_women_owned' => isset($_POST['business_women_owned']) ? 'yes' : 'no',
        'business_lgbtq_friendly' => isset($_POST['business_lgbtq_friendly']) ? 'yes' : 'no',
        'business_hours_24' => isset($_POST['business_hours_24']) ? 'yes' : 'no',
        'business_owner_name' => isset($_POST['owner_name']) ? sanitize_text_field($_POST['owner_name']) : '',
        'business_owner_email' => isset($_POST['owner_email']) ? sanitize_email($_POST['owner_email']) : '',
        'business_owner_phone' => isset($_POST['owner_phone']) ? sanitize_text_field($_POST['owner_phone']) : '',
    );
    
    // Validate required fields
    $required_fields = array(
        'business_name' => 'Business Name',
        'business_phone' => 'Phone Number',
        'business_owner_name' => 'Your Name',
        'business_owner_email' => 'Your Email',
        'business_category' => 'Business Category',
        'business_area' => 'Business Area'
    );
    
    $errors = array();
    foreach ($required_fields as $field => $label) {
        if (empty($submission_data[$field])) {
            $errors[] = $label . ' is required.';
        }
    }
    
    // Validate email
    if (!empty($submission_data['business_owner_email']) && !is_email($submission_data['business_owner_email'])) {
        $errors[] = 'Please enter a valid email address.';
    }
    
    // Validate category and area exist
    if (!empty($submission_data['business_category'])) {
        $category_term = get_term_by('slug', $submission_data['business_category'], 'business_category');
        if (!$category_term || is_wp_error($category_term)) {
            $errors[] = 'Selected category is not valid.';
        }
    }
    
    if (!empty($submission_data['business_area'])) {
        $area_term = get_term_by('slug', $submission_data['business_area'], 'business_area');
        if (!$area_term || is_wp_error($area_term)) {
            $errors[] = 'Selected area is not valid.';
        }
    }
    
    // Check for duplicate submissions
    $existing_submissions = get_posts(array(
        'post_type' => 'business_submission',
        'post_status' => 'any',
        'meta_query' => array(
            array(
                'key' => 'business_owner_email',
                'value' => $submission_data['business_owner_email'],
                'compare' => '='
            ),
            array(
                'key' => 'business_name',
                'value' => $submission_data['business_name'],
                'compare' => '='
            )
        ),
        'posts_per_page' => 1
    ));
    
    if (!empty($existing_submissions)) {
        $errors[] = 'A submission for this business has already been made.';
    }
    
    // If there are errors, return them
    if (!empty($errors)) {
        return array(
            'success' => false,
            'message' => 'Please correct the following errors:',
            'errors' => $errors
        );
    }
    
    // Create the submission post
    $submission_id = wp_insert_post(array(
        'post_title' => $submission_data['business_name'],
        'post_content' => $submission_data['business_description'],
        'post_status' => 'publish',
        'post_type' => 'business_submission'
    ));
    
    if (is_wp_error($submission_id)) {
        return array(
            'success' => false,
            'message' => 'Error creating submission. Please try again.'
        );
    }
    
    // Save submission meta data
    update_post_meta($submission_id, 'submission_status', 'pending');
    update_post_meta($submission_id, 'submission_type', 'new_business');
    update_post_meta($submission_id, 'submission_date', current_time('mysql'));
    update_post_meta($submission_id, 'original_submission_data', json_encode($submission_data));
    
    // Save business owner info
    update_post_meta($submission_id, 'business_owner_name', $submission_data['business_owner_name']);
    update_post_meta($submission_id, 'business_owner_email', $submission_data['business_owner_email']);
    update_post_meta($submission_id, 'business_owner_phone', $submission_data['business_owner_phone']);
    
    // Save business data
    foreach ($submission_data as $key => $value) {
        if (strpos($key, 'business_') === 0 && $key !== 'business_owner_name' && $key !== 'business_owner_email' && $key !== 'business_owner_phone') {
            update_post_meta($submission_id, 'lbd_' . substr($key, 9), $value);
        }
    }
    
    // Set rate limiting
    set_transient($transient_key, true, 300); // 5 minutes
    
    // Send email notifications
    lbd_send_submission_notifications($submission_id, $submission_data);
    
    return array(
        'success' => true,
        'message' => 'Thank you for your submission! We will review it and get back to you soon.',
        'submission_id' => $submission_id
    );
}

/**
 * Process business claim form
 */
function lbd_process_business_claim() {
    // Check if form was submitted
    if (!isset($_POST['lbd_claim_business']) || !isset($_POST['lbd_claim_nonce'])) {
        return array(
            'success' => false,
            'message' => 'Invalid submission.'
        );
    }
    
    // Verify nonce
    if (!wp_verify_nonce($_POST['lbd_claim_nonce'], 'lbd_claim_business_action')) {
        return array(
            'success' => false,
            'message' => 'Security check failed. Please try again.'
        );
    }
    
    // Check honeypot field
    if (!empty($_POST['website'])) {
        return array(
            'success' => true,
            'message' => 'Thank you for your submission!'
        );
    }
    
    // Get and sanitize form data
    $claim_data = array(
        'business_id' => isset($_POST['business_id']) ? intval($_POST['business_id']) : 0,
        'owner_name' => isset($_POST['owner_name']) ? sanitize_text_field($_POST['owner_name']) : '',
        'owner_email' => isset($_POST['owner_email']) ? sanitize_email($_POST['owner_email']) : '',
        'owner_phone' => isset($_POST['owner_phone']) ? sanitize_text_field($_POST['owner_phone']) : '',
        'claim_reason' => isset($_POST['claim_reason']) ? sanitize_textarea_field($_POST['claim_reason']) : '',
        'verification_proof' => isset($_POST['verification_proof']) ? sanitize_textarea_field($_POST['verification_proof']) : ''
    );
    
    // Validate required fields
    $required_fields = array(
        'business_id' => 'Business',
        'owner_name' => 'Your Name',
        'owner_email' => 'Your Email',
        'owner_phone' => 'Your Phone',
        'claim_reason' => 'Reason for Claim',
        'verification_proof' => 'Verification Proof'
    );
    
    $errors = array();
    foreach ($required_fields as $field => $label) {
        if (empty($claim_data[$field])) {
            $errors[] = $label . ' is required.';
        }
    }
    
    // Validate business exists
    $business = get_post($claim_data['business_id']);
    if (!$business || $business->post_type !== 'business') {
        $errors[] = 'Selected business is not valid.';
    }
    
    // Check for existing claims
    $existing_claims = get_posts(array(
        'post_type' => 'business_submission',
        'post_status' => 'any',
        'meta_query' => array(
            array(
                'key' => 'claimed_business_id',
                'value' => $claim_data['business_id'],
                'compare' => '='
            ),
            array(
                'key' => 'submission_status',
                'value' => array('pending', 'approved'),
                'compare' => 'IN'
            )
        ),
        'posts_per_page' => 1
    ));
    
    if (!empty($existing_claims)) {
        $errors[] = 'This business has already been claimed or is pending claim approval.';
    }
    
    // If there are errors, return them
    if (!empty($errors)) {
        return array(
            'success' => false,
            'message' => 'Please correct the following errors:',
            'errors' => $errors
        );
    }
    
    // Create the claim submission
    $submission_id = wp_insert_post(array(
        'post_title' => 'Claim: ' . $business->post_title,
        'post_content' => $claim_data['claim_reason'] . "\n\nVerification Proof:\n" . $claim_data['verification_proof'],
        'post_status' => 'publish',
        'post_type' => 'business_submission'
    ));
    
    if (is_wp_error($submission_id)) {
        return array(
            'success' => false,
            'message' => 'Error creating claim. Please try again.'
        );
    }
    
    // Save claim meta data
    update_post_meta($submission_id, 'submission_status', 'pending');
    update_post_meta($submission_id, 'submission_type', 'claim_business');
    update_post_meta($submission_id, 'submission_date', current_time('mysql'));
    update_post_meta($submission_id, 'claimed_business_id', $claim_data['business_id']);
    update_post_meta($submission_id, 'original_submission_data', json_encode($claim_data));
    
    // Save owner info
    update_post_meta($submission_id, 'business_owner_name', $claim_data['owner_name']);
    update_post_meta($submission_id, 'business_owner_email', $claim_data['owner_email']);
    update_post_meta($submission_id, 'business_owner_phone', $claim_data['owner_phone']);
    
    // Send email notifications
    lbd_send_claim_notifications($submission_id, $claim_data, $business);
    
    return array(
        'success' => true,
        'message' => 'Thank you for your claim! We will verify your ownership and get back to you soon.',
        'submission_id' => $submission_id
    );
}

/**
 * Send submission notifications
 */
function lbd_send_submission_notifications($submission_id, $submission_data) {
    // Send email to admin
    $admin_email = get_option('admin_email');
    $subject = 'New Business Submission: ' . $submission_data['business_name'];
    
    $admin_message = "A new business has been submitted for review:\n\n";
    $admin_message .= "Business: " . $submission_data['business_name'] . "\n";
    $admin_message .= "Owner: " . $submission_data['business_owner_name'] . "\n";
    $admin_message .= "Email: " . $submission_data['business_owner_email'] . "\n";
    $admin_message .= "Phone: " . $submission_data['business_owner_phone'] . "\n";
    $admin_message .= "Category: " . $submission_data['business_category'] . "\n";
    $admin_message .= "Area: " . $submission_data['business_area'] . "\n\n";
    $admin_message .= "Review the submission: " . admin_url('post.php?post=' . $submission_id . '&action=edit');
    
    wp_mail($admin_email, $subject, $admin_message);
    
    // Send confirmation email to business owner
    $owner_subject = 'Business Submission Received: ' . $submission_data['business_name'];
    
    $owner_message = "Dear " . $submission_data['business_owner_name'] . ",\n\n";
    $owner_message .= "Thank you for submitting your business '" . $submission_data['business_name'] . "' to our directory.\n\n";
    $owner_message .= "We have received your submission and will review it shortly. You will receive an email notification once your submission has been reviewed.\n\n";
    $owner_message .= "Submission ID: " . $submission_id . "\n\n";
    $owner_message .= "Best regards,\n";
    $owner_message .= get_bloginfo('name') . " Team";
    
    wp_mail($submission_data['business_owner_email'], $owner_subject, $owner_message);
}

/**
 * Send claim notifications
 */
function lbd_send_claim_notifications($submission_id, $claim_data, $business) {
    // Send email to admin
    $admin_email = get_option('admin_email');
    $subject = 'New Business Claim: ' . $business->post_title;
    
    $admin_message = "A new business claim has been submitted:\n\n";
    $admin_message .= "Business: " . $business->post_title . "\n";
    $admin_message .= "Claimant: " . $claim_data['owner_name'] . "\n";
    $admin_message .= "Email: " . $claim_data['owner_email'] . "\n";
    $admin_message .= "Phone: " . $claim_data['owner_phone'] . "\n";
    $admin_message .= "Reason: " . $claim_data['claim_reason'] . "\n\n";
    $admin_message .= "Review the claim: " . admin_url('post.php?post=' . $submission_id . '&action=edit');
    
    wp_mail($admin_email, $subject, $admin_message);
    
    // Send confirmation email to claimant
    $owner_subject = 'Business Claim Received: ' . $business->post_title;
    
    $owner_message = "Dear " . $claim_data['owner_name'] . ",\n\n";
    $owner_message .= "Thank you for claiming ownership of '" . $business->post_title . "'.\n\n";
    $owner_message .= "We have received your claim and will verify your ownership. This typically involves contacting you via phone or email to confirm your relationship to the business.\n\n";
    $owner_message .= "Claim ID: " . $submission_id . "\n\n";
    $owner_message .= "Best regards,\n";
    $owner_message .= get_bloginfo('name') . " Team";
    
    wp_mail($claim_data['owner_email'], $owner_subject, $owner_message);
} 
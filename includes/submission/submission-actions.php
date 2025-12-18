<?php
/**
 * Business Submission Actions
 * 
 * Handles AJAX actions for approving and rejecting submissions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize AJAX handlers
 */
function lbd_init_submission_actions() {
    add_action('wp_ajax_lbd_approve_submission', 'lbd_ajax_approve_submission');
    add_action('wp_ajax_lbd_reject_submission', 'lbd_ajax_reject_submission');
    add_action('wp_ajax_lbd_claim_business', 'lbd_ajax_claim_business');
}
add_action('init', 'lbd_init_submission_actions');

/**
 * AJAX handler for approving submissions
 */
function lbd_ajax_approve_submission() {
    // Check nonce
    if (!wp_verify_nonce($_POST['nonce'], 'lbd_submission_action')) {
        wp_die('Security check failed');
    }
    
    // Check permissions
    if (!current_user_can('edit_posts')) {
        wp_die('Insufficient permissions');
    }
    
    $submission_id = intval($_POST['submission_id']);
    $submission = get_post($submission_id);
    
    if (!$submission || $submission->post_type !== 'business_submission') {
        wp_die('Invalid submission');
    }
    
    $submission_type = get_post_meta($submission_id, 'submission_type', true);
    $result = array();
    
    if ($submission_type === 'new_business') {
        $result = lbd_approve_new_business_submission($submission_id);
    } elseif ($submission_type === 'claim_business') {
        $result = lbd_approve_business_claim($submission_id);
    } else {
        $result = array(
            'success' => false,
            'message' => 'Unknown submission type'
        );
    }
    
    wp_send_json($result);
}

/**
 * AJAX handler for rejecting submissions
 */
function lbd_ajax_reject_submission() {
    // Check nonce
    if (!wp_verify_nonce($_POST['nonce'], 'lbd_submission_action')) {
        wp_die('Security check failed');
    }
    
    // Check permissions
    if (!current_user_can('edit_posts')) {
        wp_die('Insufficient permissions');
    }
    
    $submission_id = intval($_POST['submission_id']);
    $rejection_reason = sanitize_textarea_field($_POST['rejection_reason'] ?? '');
    
    $result = lbd_reject_submission($submission_id, $rejection_reason);
    wp_send_json($result);
}

/**
 * Approve a new business submission
 */
function lbd_approve_new_business_submission($submission_id) {
    $submission = get_post($submission_id);
    $original_data = json_decode(get_post_meta($submission_id, 'original_submission_data', true), true);
    
    if (!$original_data) {
        return array(
            'success' => false,
            'message' => 'Original submission data not found'
        );
    }
    
    // Create the business post
    $business_data = array(
        'post_title' => $original_data['business_name'],
        'post_content' => $original_data['business_description'],
        'post_status' => 'publish',
        'post_type' => 'business'
    );
    
    $business_id = wp_insert_post($business_data);
    
    if (is_wp_error($business_id)) {
        return array(
            'success' => false,
            'message' => 'Error creating business: ' . $business_id->get_error_message()
        );
    }
    
    // Set taxonomies
    if (!empty($original_data['business_category'])) {
        wp_set_object_terms($business_id, $original_data['business_category'], 'business_category');
    }
    
    if (!empty($original_data['business_area'])) {
        wp_set_object_terms($business_id, $original_data['business_area'], 'business_area');
    }
    
    // Save business meta fields
    $meta_fields = array(
        'lbd_phone' => $original_data['business_phone'],
        'lbd_email' => $original_data['business_email'],
        'lbd_website' => $original_data['business_website'],
        'lbd_street_address' => $original_data['business_street_address'],
        'lbd_city' => $original_data['business_city'],
        'lbd_postcode' => $original_data['business_postcode'],
        'lbd_latitude' => $original_data['business_latitude'],
        'lbd_longitude' => $original_data['business_longitude'],
        'lbd_facebook' => $original_data['business_facebook'],
        'lbd_instagram' => $original_data['business_instagram'],
        'lbd_black_owned' => $original_data['business_black_owned'],
        'lbd_women_owned' => $original_data['business_women_owned'],
        'lbd_lgbtq_friendly' => $original_data['business_lgbtq_friendly'],
        'lbd_hours_24' => $original_data['business_hours_24'],
        'lbd_owner_name' => $original_data['business_owner_name'],
        'lbd_owner_email' => $original_data['business_owner_email'],
        'lbd_owner_phone' => $original_data['business_owner_phone'],
        'lbd_verified' => 'verified',
    );
    
    foreach ($meta_fields as $key => $value) {
        if (!empty($value)) {
            update_post_meta($business_id, $key, $value);
        }
    }
    
    // Update submission status
    update_post_meta($submission_id, 'submission_status', 'approved');
    update_post_meta($submission_id, 'reviewed_date', current_time('mysql'));
    update_post_meta($submission_id, 'reviewer_id', get_current_user_id());
    update_post_meta($submission_id, 'created_business_id', $business_id);
    
    // Send approval email
    lbd_send_approval_email($submission_id, $business_id, 'new_business');
    
    return array(
        'success' => true,
        'message' => 'Business created successfully!',
        'business_id' => $business_id,
        'business_url' => get_edit_post_link($business_id)
    );
}

/**
 * Approve a business claim
 */
function lbd_approve_business_claim($submission_id) {
    $claimed_business_id = get_post_meta($submission_id, 'claimed_business_id', true);
    $original_data = json_decode(get_post_meta($submission_id, 'original_submission_data', true), true);
    
    if (!$claimed_business_id) {
        return array(
            'success' => false,
            'message' => 'Claimed business ID not found'
        );
    }
    
    $business = get_post($claimed_business_id);
    if (!$business || $business->post_type !== 'business') {
        return array(
            'success' => false,
            'message' => 'Claimed business not found'
        );
    }
    
    // Update business with owner information
    update_post_meta($claimed_business_id, 'lbd_owner_name', $original_data['owner_name']);
    update_post_meta($claimed_business_id, 'lbd_owner_email', $original_data['owner_email']);
    update_post_meta($claimed_business_id, 'lbd_owner_phone', $original_data['owner_phone']);
    update_post_meta($claimed_business_id, 'lbd_claimed', 'yes');
    update_post_meta($claimed_business_id, 'lbd_claimed_date', current_time('mysql'));
    update_post_meta($claimed_business_id, 'lbd_verified', 'verified');
    
    // Update submission status
    update_post_meta($submission_id, 'submission_status', 'approved');
    update_post_meta($submission_id, 'reviewed_date', current_time('mysql'));
    update_post_meta($submission_id, 'reviewer_id', get_current_user_id());
    
    // Send approval email
    lbd_send_approval_email($submission_id, $claimed_business_id, 'claim');
    
    return array(
        'success' => true,
        'message' => 'Business claim approved!',
        'business_id' => $claimed_business_id,
        'business_url' => get_edit_post_link($claimed_business_id)
    );
}

/**
 * Reject a submission
 */
function lbd_reject_submission($submission_id, $rejection_reason = '') {
    $submission = get_post($submission_id);
    
    if (!$submission || $submission->post_type !== 'business_submission') {
        return array(
            'success' => false,
            'message' => 'Invalid submission'
        );
    }
    
    // Update submission status
    update_post_meta($submission_id, 'submission_status', 'rejected');
    update_post_meta($submission_id, 'reviewed_date', current_time('mysql'));
    update_post_meta($submission_id, 'reviewer_id', get_current_user_id());
    
    if (!empty($rejection_reason)) {
        update_post_meta($submission_id, 'reviewer_notes', $rejection_reason);
    }
    
    // Send rejection email
    lbd_send_rejection_email($submission_id, $rejection_reason);
    
    return array(
        'success' => true,
        'message' => 'Submission rejected successfully'
    );
}

/**
 * Send approval email
 */
function lbd_send_approval_email($submission_id, $business_id, $type) {
    $owner_email = get_post_meta($submission_id, 'business_owner_email', true);
    $owner_name = get_post_meta($submission_id, 'business_owner_name', true);
    $business = get_post($business_id);
    
    if (!$owner_email || !$business) {
        return false;
    }
    
    $subject = 'Business Submission Approved: ' . $business->post_title;
    
    $message = "Dear " . $owner_name . ",\n\n";
    
    if ($type === 'new_business') {
        $message .= "Great news! Your business submission for '" . $business->post_title . "' has been approved and is now live in our directory.\n\n";
        $message .= "Your business listing: " . get_permalink($business_id) . "\n\n";
    } else {
        $message .= "Great news! Your claim for '" . $business->post_title . "' has been approved. You are now the verified owner of this business listing.\n\n";
        $message .= "Your business listing: " . get_permalink($business_id) . "\n\n";
    }
    
    $message .= "You can now manage your business information through our admin panel.\n\n";
    $message .= "Best regards,\n";
    $message .= get_bloginfo('name') . " Team";
    
    return wp_mail($owner_email, $subject, $message);
}

/**
 * Send rejection email
 */
function lbd_send_rejection_email($submission_id, $rejection_reason) {
    $owner_email = get_post_meta($submission_id, 'business_owner_email', true);
    $owner_name = get_post_meta($submission_id, 'business_owner_name', true);
    $submission = get_post($submission_id);
    
    if (!$owner_email || !$submission) {
        return false;
    }
    
    $subject = 'Business Submission Update: ' . $submission->post_title;
    
    $message = "Dear " . $owner_name . ",\n\n";
    $message .= "Thank you for your interest in our directory. Unfortunately, we are unable to approve your submission for '" . $submission->post_title . "' at this time.\n\n";
    
    if (!empty($rejection_reason)) {
        $message .= "Reason: " . $rejection_reason . "\n\n";
    }
    
    $message .= "If you have any questions or would like to submit additional information, please don't hesitate to contact us.\n\n";
    $message .= "Best regards,\n";
    $message .= get_bloginfo('name') . " Team";
    
    return wp_mail($owner_email, $subject, $message);
}

/**
 * Add JavaScript for admin actions
 */
function lbd_admin_submission_scripts() {
    global $post_type;
    
    if ($post_type === 'business_submission') {
        ?>
        <script>
        jQuery(document).ready(function($) {
            $('.approve-submission').on('click', function() {
                var submissionId = $(this).data('id');
                var button = $(this);
                
                if (confirm('Are you sure you want to approve this submission?')) {
                    button.prop('disabled', true).text('Processing...');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'lbd_approve_submission',
                            submission_id: submissionId,
                            nonce: '<?php echo wp_create_nonce('lbd_submission_action'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('Submission approved! ' + response.message);
                                if (response.business_url) {
                                    window.open(response.business_url, '_blank');
                                }
                                location.reload();
                            } else {
                                alert('Error: ' + response.message);
                                button.prop('disabled', false).text('Approve & Create Business');
                            }
                        },
                        error: function() {
                            alert('An error occurred. Please try again.');
                            button.prop('disabled', false).text('Approve & Create Business');
                        }
                    });
                }
            });
            
            $('.reject-submission').on('click', function() {
                var submissionId = $(this).data('id');
                var rejectionReason = prompt('Please provide a reason for rejection (optional):');
                
                if (rejectionReason !== null) {
                    var button = $(this);
                    button.prop('disabled', true).text('Processing...');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'lbd_reject_submission',
                            submission_id: submissionId,
                            rejection_reason: rejectionReason,
                            nonce: '<?php echo wp_create_nonce('lbd_submission_action'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('Submission rejected successfully.');
                                location.reload();
                            } else {
                                alert('Error: ' + response.message);
                                button.prop('disabled', false).text('Reject Submission');
                            }
                        },
                        error: function() {
                            alert('An error occurred. Please try again.');
                            button.prop('disabled', false).text('Reject Submission');
                        }
                    });
                }
            });
        });
        </script>
        <?php
    }
}
add_action('admin_footer', 'lbd_admin_submission_scripts'); 
<?php
/**
 * Owner Dashboard
 *
 * Frontend dashboard and edit form for verified business owners,
 * revision submission handler, and login redirect for business_owner role.
 */

if (!defined('ABSPATH')) {
    exit;
}

// ─── Login redirect: keep business owners on the frontend ───

function lbd_owner_login_redirect($redirect_to, $requested_redirect_to, $user) {
    if (!is_wp_error($user) && in_array('business_owner', (array) $user->roles, true)) {
        $dashboard_id = get_option('lbd_dashboard_page_id');
        if ($dashboard_id) {
            return get_permalink($dashboard_id);
        }
    }
    return $redirect_to;
}
add_filter('login_redirect', 'lbd_owner_login_redirect', 10, 3);

function lbd_owner_block_admin() {
    if (defined('DOING_AJAX') && DOING_AJAX) {
        return;
    }
    if (!is_user_logged_in()) {
        return;
    }
    $user = wp_get_current_user();
    if (in_array('business_owner', (array) $user->roles, true) && is_admin()) {
        $dashboard_id = get_option('lbd_dashboard_page_id');
        wp_safe_redirect($dashboard_id ? get_permalink($dashboard_id) : home_url());
        exit;
    }
}
add_action('admin_init', 'lbd_owner_block_admin');

// Hide the admin bar for business owners
function lbd_owner_hide_admin_bar($show) {
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        if (in_array('business_owner', (array) $user->roles, true)) {
            return false;
        }
    }
    return $show;
}
add_filter('show_admin_bar', 'lbd_owner_hide_admin_bar');

// ─── Helper: get the business owned by the current user ───

function lbd_get_owner_business($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    if (!$user_id) {
        return null;
    }
    $businesses = get_posts(array(
        'post_type'      => 'business',
        'posts_per_page' => 1,
        'meta_query'     => array(
            array('key' => 'lbd_owner_user_id', 'value' => $user_id, 'compare' => '='),
        ),
    ));
    return !empty($businesses) ? $businesses[0] : null;
}

// ─── [lbd_owner_dashboard] shortcode ───

function lbd_owner_dashboard_shortcode() {
    ob_start();

    if (!is_user_logged_in()) {
        $dashboard_id = get_option('lbd_dashboard_page_id');
        $redirect_url = $dashboard_id ? get_permalink($dashboard_id) : '';
        ?>
        <div class="lbd-dashboard-container">
            <h2>My Business Dashboard</h2>
            <div class="lbd-dashboard-login">
                <p>Please log in to access your business dashboard.</p>
                <?php
                wp_login_form(array(
                    'redirect' => $redirect_url,
                    'label_username' => 'Email Address',
                ));
                ?>
                <p class="lbd-dashboard-reset-link">
                    <a href="<?php echo esc_url(wp_lostpassword_url($redirect_url)); ?>">Forgot your password?</a>
                </p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    $business = lbd_get_owner_business();

    if (!$business) {
        ?>
        <div class="lbd-dashboard-container">
            <h2>My Business Dashboard</h2>
            <div class="lbd-dashboard-empty">
                <p>You haven't claimed a business yet. Find your business in our directory and click "Claim This Business" to get started.</p>
                <p><a href="<?php echo esc_url(home_url('/directory/')); ?>" class="claim-business-button">Browse Directory</a></p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    $business_id = $business->ID;
    $edit_page_id = get_option('lbd_edit_business_page_id');
    $edit_url = $edit_page_id ? add_query_arg('business_id', $business_id, get_permalink($edit_page_id)) : '';

    // Fetch key meta
    $phone   = get_post_meta($business_id, 'lbd_phone', true);
    $email   = get_post_meta($business_id, 'lbd_email', true);
    $website = get_post_meta($business_id, 'lbd_website', true);
    $address = get_post_meta($business_id, 'lbd_address', true);
    if (!$address) {
        $parts = array_filter(array(
            get_post_meta($business_id, 'lbd_street_address', true),
            get_post_meta($business_id, 'lbd_city', true),
            get_post_meta($business_id, 'lbd_postcode', true),
        ));
        $address = implode(', ', $parts);
    }

    // Pending revisions
    $pending_revisions = get_posts(array(
        'post_type'      => 'business_submission',
        'post_status'    => 'any',
        'posts_per_page' => 5,
        'meta_query'     => array(
            array('key' => 'claimed_business_id', 'value' => $business_id),
            array('key' => 'submission_type', 'value' => 'revision'),
        ),
        'orderby'        => 'date',
        'order'          => 'DESC',
    ));
    ?>
    <div class="lbd-dashboard-container">
        <h2>My Business Dashboard</h2>

        <div class="lbd-dashboard-card">
            <div class="lbd-dashboard-card-header">
                <h3><?php echo esc_html($business->post_title); ?></h3>
                <span class="lbd-badge lbd-badge-verified">Verified</span>
            </div>

            <table class="lbd-dashboard-details">
                <?php if ($address) : ?>
                <tr><th>Address</th><td><?php echo esc_html($address); ?></td></tr>
                <?php endif; ?>
                <?php if ($phone) : ?>
                <tr><th>Phone</th><td><?php echo esc_html($phone); ?></td></tr>
                <?php endif; ?>
                <?php if ($email) : ?>
                <tr><th>Email</th><td><?php echo esc_html($email); ?></td></tr>
                <?php endif; ?>
                <?php if ($website) : ?>
                <tr><th>Website</th><td><a href="<?php echo esc_url($website); ?>" target="_blank"><?php echo esc_html($website); ?></a></td></tr>
                <?php endif; ?>
            </table>

            <div class="lbd-dashboard-actions">
                <a href="<?php echo esc_url(get_permalink($business_id)); ?>" class="submit-button lbd-btn-secondary" target="_blank">View Listing</a>
                <?php if ($edit_url) : ?>
                <a href="<?php echo esc_url($edit_url); ?>" class="submit-button">Edit Business</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($pending_revisions)) : ?>
        <div class="lbd-dashboard-revisions">
            <h3>Recent Change Requests</h3>
            <table class="lbd-dashboard-details lbd-revisions-table">
                <thead>
                    <tr><th>Date</th><th>Status</th></tr>
                </thead>
                <tbody>
                <?php foreach ($pending_revisions as $rev) :
                    $rev_status = get_post_meta($rev->ID, 'submission_status', true);
                    $rev_date   = get_post_meta($rev->ID, 'submission_date', true);
                    $status_map = array(
                        'pending'  => '<span class="lbd-badge lbd-badge-pending">Pending Review</span>',
                        'approved' => '<span class="lbd-badge lbd-badge-approved">Approved</span>',
                        'rejected' => '<span class="lbd-badge lbd-badge-rejected">Rejected</span>',
                    );
                ?>
                    <tr>
                        <td><?php echo $rev_date ? esc_html(date('M j, Y', strtotime($rev_date))) : 'N/A'; ?></td>
                        <td><?php echo $status_map[$rev_status] ?? esc_html($rev_status); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <p class="lbd-dashboard-logout">
            <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>">Log out</a>
        </p>
    </div>
    <?php

    return ob_get_clean();
}
add_shortcode('lbd_owner_dashboard', 'lbd_owner_dashboard_shortcode');

// ─── [lbd_owner_edit_business] shortcode ───

function lbd_owner_edit_business_shortcode() {
    ob_start();

    if (!is_user_logged_in()) {
        $edit_page_id = get_option('lbd_edit_business_page_id');
        echo '<div class="lbd-claim-form-container"><p>Please <a href="' . esc_url(wp_login_url($edit_page_id ? get_permalink($edit_page_id) : '')) . '">log in</a> to edit your business.</p></div>';
        return ob_get_clean();
    }

    $business_id = isset($_GET['business_id']) ? intval($_GET['business_id']) : 0;
    if (!$business_id && isset($_POST['business_id'])) {
        $business_id = intval($_POST['business_id']);
    }

    $business = $business_id ? get_post($business_id) : null;
    $owner_user_id = $business ? get_post_meta($business_id, 'lbd_owner_user_id', true) : 0;

    if (!$business || $business->post_type !== 'business' || intval($owner_user_id) !== get_current_user_id()) {
        echo '<div class="lbd-claim-form-container"><div class="lbd-submission-error"><h3>Access Denied</h3><p>You do not have permission to edit this business.</p></div></div>';
        return ob_get_clean();
    }

    // Process revision submission
    $result = null;
    if (isset($_POST['lbd_submit_revision'])) {
        $result = lbd_process_business_revision($business_id);
    }

    if ($result && !empty($result['success'])) {
        $dashboard_id = get_option('lbd_dashboard_page_id');
        echo '<div class="lbd-claim-form-container">';
        echo '<div class="lbd-submission-success">';
        echo '<h3>Changes Submitted!</h3>';
        echo '<p>' . esc_html($result['message']) . '</p>';
        if ($dashboard_id) {
            echo '<p><a href="' . esc_url(get_permalink($dashboard_id)) . '" class="submit-button lbd-btn-secondary">Back to Dashboard</a></p>';
        }
        echo '</div></div>';
        return ob_get_clean();
    }

    // Load current values
    $fields = lbd_get_editable_fields();
    $current = array();
    foreach ($fields as $key => $field) {
        $current[$key] = get_post_meta($business_id, $key, true);
    }
    $current['post_content'] = $business->post_content;
    ?>
    <div class="lbd-claim-form-container">
        <h2>Edit: <?php echo esc_html($business->post_title); ?></h2>
        <p>Submit changes to your business listing. All changes will be reviewed before going live.</p>

        <?php if ($result && empty($result['success'])) : ?>
            <div class="lbd-submission-error">
                <h3>Error</h3>
                <p><?php echo esc_html($result['message']); ?></p>
                <?php if (!empty($result['errors'])) : ?>
                    <ul><?php foreach ($result['errors'] as $e) echo '<li>' . esc_html($e) . '</li>'; ?></ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="post" class="lbd-claim-form">
            <?php wp_nonce_field('lbd_edit_business_action', 'lbd_edit_nonce'); ?>
            <input type="hidden" name="business_id" value="<?php echo esc_attr($business_id); ?>">

            <div class="form-section">
                <h3>Business Description</h3>
                <div class="form-row">
                    <div class="form-field">
                        <label for="edit_description">Description</label>
                        <textarea name="business_description" id="edit_description" rows="5"><?php echo esc_textarea($current['post_content']); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Contact Details</h3>
                <div class="form-row">
                    <div class="form-field">
                        <label for="edit_phone">Phone</label>
                        <input type="tel" name="lbd_phone" id="edit_phone" value="<?php echo esc_attr($current['lbd_phone'] ?? ''); ?>">
                    </div>
                    <div class="form-field">
                        <label for="edit_email">Email</label>
                        <input type="email" name="lbd_email" id="edit_email" value="<?php echo esc_attr($current['lbd_email'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-field">
                        <label for="edit_website">Website</label>
                        <input type="url" name="lbd_website" id="edit_website" value="<?php echo esc_attr($current['lbd_website'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Address</h3>
                <div class="form-row">
                    <div class="form-field">
                        <label for="edit_street">Street Address</label>
                        <input type="text" name="lbd_street_address" id="edit_street" value="<?php echo esc_attr($current['lbd_street_address'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-field">
                        <label for="edit_city">City</label>
                        <input type="text" name="lbd_city" id="edit_city" value="<?php echo esc_attr($current['lbd_city'] ?? ''); ?>">
                    </div>
                    <div class="form-field">
                        <label for="edit_postcode">Postcode</label>
                        <input type="text" name="lbd_postcode" id="edit_postcode" value="<?php echo esc_attr($current['lbd_postcode'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Social Media</h3>
                <div class="form-row">
                    <div class="form-field">
                        <label for="edit_facebook">Facebook Page URL</label>
                        <input type="url" name="lbd_facebook" id="edit_facebook" value="<?php echo esc_attr($current['lbd_facebook'] ?? ''); ?>">
                    </div>
                    <div class="form-field">
                        <label for="edit_instagram">Instagram Handle</label>
                        <input type="text" name="lbd_instagram" id="edit_instagram" value="<?php echo esc_attr($current['lbd_instagram'] ?? ''); ?>">
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Additional Information</h3>
                <div class="form-row">
                    <div class="form-field">
                        <label for="edit_extra_categories">Extra Service Categories</label>
                        <input type="text" name="lbd_extra_categories" id="edit_extra_categories" value="<?php echo esc_attr($current['lbd_extra_categories'] ?? ''); ?>">
                        <p class="field-help">Comma separated, e.g. "Plumbing, Heating, Gas Engineer"</p>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-field">
                        <label for="edit_service_options">Service Options</label>
                        <input type="text" name="lbd_service_options" id="edit_service_options" value="<?php echo esc_attr($current['lbd_service_options'] ?? ''); ?>">
                        <p class="field-help">Comma separated, e.g. "On-site services, Online estimates"</p>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-field">
                        <label for="edit_payments">Payments Accepted</label>
                        <input type="text" name="lbd_payments" id="edit_payments" value="<?php echo esc_attr($current['lbd_payments'] ?? ''); ?>">
                    </div>
                    <div class="form-field">
                        <label for="edit_parking">Parking</label>
                        <input type="text" name="lbd_parking" id="edit_parking" value="<?php echo esc_attr($current['lbd_parking'] ?? ''); ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-field">
                        <label for="edit_amenities">Amenities</label>
                        <textarea name="lbd_amenities" id="edit_amenities" rows="3"><?php echo esc_textarea($current['lbd_amenities'] ?? ''); ?></textarea>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-field">
                        <label for="edit_accessibility">Accessibility</label>
                        <textarea name="lbd_accessibility" id="edit_accessibility" rows="3"><?php echo esc_textarea($current['lbd_accessibility'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Business Attributes</h3>
                <div class="form-row">
                    <div class="form-field checkbox-field">
                        <label><input type="checkbox" name="lbd_black_owned" value="on" <?php checked($current['lbd_black_owned'] ?? '', 'on'); ?>> Black Owned</label>
                    </div>
                    <div class="form-field checkbox-field">
                        <label><input type="checkbox" name="lbd_women_owned" value="on" <?php checked($current['lbd_women_owned'] ?? '', 'on'); ?>> Women Owned</label>
                    </div>
                    <div class="form-field checkbox-field">
                        <label><input type="checkbox" name="lbd_lgbtq_friendly" value="on" <?php checked($current['lbd_lgbtq_friendly'] ?? '', 'on'); ?>> LGBTQ+ Friendly</label>
                    </div>
                </div>
            </div>

            <div class="form-submit">
                <button type="submit" name="lbd_submit_revision" class="submit-button">Submit Changes for Review</button>
            </div>
        </form>
    </div>
    <?php

    return ob_get_clean();
}
add_shortcode('lbd_owner_edit_business', 'lbd_owner_edit_business_shortcode');

// ─── Editable fields definition ───

function lbd_get_editable_fields() {
    return array(
        'lbd_phone'            => array('label' => 'Phone', 'type' => 'text'),
        'lbd_email'            => array('label' => 'Email', 'type' => 'email'),
        'lbd_website'          => array('label' => 'Website', 'type' => 'url'),
        'lbd_street_address'   => array('label' => 'Street Address', 'type' => 'text'),
        'lbd_city'             => array('label' => 'City', 'type' => 'text'),
        'lbd_postcode'         => array('label' => 'Postcode', 'type' => 'text'),
        'lbd_facebook'         => array('label' => 'Facebook', 'type' => 'url'),
        'lbd_instagram'        => array('label' => 'Instagram', 'type' => 'text'),
        'lbd_extra_categories' => array('label' => 'Extra Service Categories', 'type' => 'text'),
        'lbd_service_options'  => array('label' => 'Service Options', 'type' => 'text'),
        'lbd_payments'         => array('label' => 'Payments Accepted', 'type' => 'text'),
        'lbd_parking'          => array('label' => 'Parking', 'type' => 'text'),
        'lbd_amenities'        => array('label' => 'Amenities', 'type' => 'textarea'),
        'lbd_accessibility'    => array('label' => 'Accessibility', 'type' => 'textarea'),
        'lbd_black_owned'      => array('label' => 'Black Owned', 'type' => 'checkbox'),
        'lbd_women_owned'      => array('label' => 'Women Owned', 'type' => 'checkbox'),
        'lbd_lgbtq_friendly'   => array('label' => 'LGBTQ+ Friendly', 'type' => 'checkbox'),
    );
}

// ─── Revision processing ───

function lbd_process_business_revision($business_id) {
    if (!isset($_POST['lbd_edit_nonce']) || !wp_verify_nonce($_POST['lbd_edit_nonce'], 'lbd_edit_business_action')) {
        return array('success' => false, 'message' => 'Security check failed. Please try again.');
    }

    $business = get_post($business_id);
    if (!$business || $business->post_type !== 'business') {
        return array('success' => false, 'message' => 'Business not found.');
    }

    $owner_user_id = get_post_meta($business_id, 'lbd_owner_user_id', true);
    if (intval($owner_user_id) !== get_current_user_id()) {
        return array('success' => false, 'message' => 'You do not own this business.');
    }

    // Collect proposed changes — only include fields that differ from current values
    $fields = lbd_get_editable_fields();
    $changes = array();

    $new_description = isset($_POST['business_description']) ? wp_kses_post($_POST['business_description']) : '';
    if ($new_description !== $business->post_content) {
        $changes['post_content'] = $new_description;
    }

    foreach ($fields as $key => $field) {
        $current = get_post_meta($business_id, $key, true);
        if ($field['type'] === 'checkbox') {
            $new_val = isset($_POST[$key]) ? 'on' : '';
        } elseif ($field['type'] === 'url') {
            $new_val = isset($_POST[$key]) ? esc_url_raw($_POST[$key]) : '';
        } elseif ($field['type'] === 'email') {
            $new_val = isset($_POST[$key]) ? sanitize_email($_POST[$key]) : '';
        } elseif ($field['type'] === 'textarea') {
            $new_val = isset($_POST[$key]) ? sanitize_textarea_field($_POST[$key]) : '';
        } else {
            $new_val = isset($_POST[$key]) ? sanitize_text_field($_POST[$key]) : '';
        }
        if ($new_val !== ($current ?: '')) {
            $changes[$key] = $new_val;
        }
    }

    if (empty($changes)) {
        return array('success' => false, 'message' => 'No changes detected. Your listing is already up to date.');
    }

    // Build a readable summary for the post content
    $summary_lines = array();
    $all_fields = array_merge(array('post_content' => array('label' => 'Description')), $fields);
    foreach ($changes as $key => $val) {
        $label = isset($all_fields[$key]) ? $all_fields[$key]['label'] : $key;
        $summary_lines[] = $label . ': ' . ($val ?: '(cleared)');
    }

    $submission_id = wp_insert_post(array(
        'post_title'   => 'Revision: ' . $business->post_title,
        'post_content' => implode("\n", $summary_lines),
        'post_status'  => 'publish',
        'post_type'    => 'business_submission',
    ));

    if (is_wp_error($submission_id)) {
        return array('success' => false, 'message' => 'Error submitting revision. Please try again.');
    }

    $user = wp_get_current_user();
    update_post_meta($submission_id, 'submission_status', 'pending');
    update_post_meta($submission_id, 'submission_type', 'revision');
    update_post_meta($submission_id, 'submission_date', current_time('mysql'));
    update_post_meta($submission_id, 'claimed_business_id', $business_id);
    update_post_meta($submission_id, 'business_owner_name', $user->display_name);
    update_post_meta($submission_id, 'business_owner_email', $user->user_email);
    update_post_meta($submission_id, 'original_submission_data', json_encode($changes));

    // Notify admin
    $admin_email = get_option('admin_email');
    $admin_msg  = "A business owner has submitted changes for review:\n\n";
    $admin_msg .= "Business: " . $business->post_title . "\n";
    $admin_msg .= "Owner: " . $user->display_name . " (" . $user->user_email . ")\n\n";
    $admin_msg .= "Proposed changes:\n" . implode("\n", $summary_lines) . "\n\n";
    $admin_msg .= "Review: " . admin_url('post.php?post=' . $submission_id . '&action=edit');
    wp_mail($admin_email, 'Business Revision Request: ' . $business->post_title, $admin_msg);

    return array(
        'success' => true,
        'message' => 'Your changes have been submitted for review. You\'ll receive an email once they\'re approved.',
    );
}

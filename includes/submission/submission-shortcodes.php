<?php
/**
 * Business Submission Shortcodes
 * 
 * Provides shortcodes for business submission and claim forms
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Business submission form shortcode
 * [submit_business_form]
 */
function lbd_submit_business_form_shortcode($atts) {
    $atts = shortcode_atts(array(
        'title' => 'Submit Your Business',
        'description' => 'Add your business to our directory by filling out the form below.',
    ), $atts);
    
    // Process form submission only if form was submitted
    $result = null;
    if (isset($_POST['lbd_submit_business'])) {
        $result = lbd_process_business_submission();
    }
    
    ob_start();
    
    // Display success/error messages
    if ($result && isset($result['success'])) {
        if ($result['success']) {
            echo '<div class="lbd-submission-success">';
            echo '<h3>Submission Successful!</h3>';
            echo '<p>' . esc_html($result['message']) . '</p>';
            echo '</div>';
        } else {
            echo '<div class="lbd-submission-error">';
            echo '<h3>Submission Error</h3>';
            echo '<p>' . esc_html($result['message']) . '</p>';
            if (isset($result['errors']) && is_array($result['errors'])) {
                echo '<ul>';
                foreach ($result['errors'] as $error) {
                    echo '<li>' . esc_html($error) . '</li>';
                }
                echo '</ul>';
            }
            echo '</div>';
        }
    }
    
    // Only show form if not successful
    if (!$result || !$result['success']) {
        ?>
        <div class="lbd-submission-form-container">
            <h2><?php echo esc_html($atts['title']); ?></h2>
            <p><?php echo esc_html($atts['description']); ?></p>
            
            <form method="post" class="lbd-submission-form" enctype="multipart/form-data">
                <?php wp_nonce_field('lbd_submit_business_action', 'lbd_submission_nonce'); ?>
                
                <!-- Honeypot field -->
                <div style="display: none;">
                    <input type="text" name="website" value="">
                </div>
                
                <div class="form-section">
                    <h3>Business Information</h3>
                    
                    <div class="form-row">
                        <div class="form-field">
                            <label for="business_name">Business Name <span class="required">*</span></label>
                            <input type="text" name="business_name" id="business_name" required 
                                   value="<?php echo isset($_POST['business_name']) ? esc_attr($_POST['business_name']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-field">
                            <label for="business_description">Business Description</label>
                            <textarea name="business_description" id="business_description" rows="4"><?php echo isset($_POST['business_description']) ? esc_textarea($_POST['business_description']) : ''; ?></textarea>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-field">
                            <label for="business_phone">Phone Number <span class="required">*</span></label>
                            <input type="tel" name="business_phone" id="business_phone" required 
                                   value="<?php echo isset($_POST['business_phone']) ? esc_attr($_POST['business_phone']) : ''; ?>">
                        </div>
                        <div class="form-field">
                            <label for="business_email">Business Email</label>
                            <input type="email" name="business_email" id="business_email" 
                                   value="<?php echo isset($_POST['business_email']) ? esc_attr($_POST['business_email']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-field">
                            <label for="business_website">Website</label>
                            <input type="url" name="business_website" id="business_website" 
                                   value="<?php echo isset($_POST['business_website']) ? esc_attr($_POST['business_website']) : ''; ?>">
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>Address Information</h3>
                    
                    <div class="form-row">
                        <div class="form-field">
                            <label for="business_street_address">Street Address</label>
                            <input type="text" name="business_street_address" id="business_street_address" 
                                   value="<?php echo isset($_POST['business_street_address']) ? esc_attr($_POST['business_street_address']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-field">
                            <label for="business_city">City</label>
                            <input type="text" name="business_city" id="business_city" 
                                   value="<?php echo isset($_POST['business_city']) ? esc_attr($_POST['business_city']) : ''; ?>">
                        </div>
                        <div class="form-field">
                            <label for="business_postcode">Postcode</label>
                            <input type="text" name="business_postcode" id="business_postcode" 
                                   value="<?php echo isset($_POST['business_postcode']) ? esc_attr($_POST['business_postcode']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-field">
                            <label for="business_latitude">Latitude</label>
                            <input type="text" name="business_latitude" id="business_latitude" 
                                   value="<?php echo isset($_POST['business_latitude']) ? esc_attr($_POST['business_latitude']) : ''; ?>">
                        </div>
                        <div class="form-field">
                            <label for="business_longitude">Longitude</label>
                            <input type="text" name="business_longitude" id="business_longitude" 
                                   value="<?php echo isset($_POST['business_longitude']) ? esc_attr($_POST['business_longitude']) : ''; ?>">
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>Category & Area</h3>
                    
                    <div class="form-row">
                        <div class="form-field">
                            <label for="business_category">Business Category <span class="required">*</span></label>
                            <select name="business_category" id="business_category" required>
                                <option value="">Select a Category</option>
                                <?php
                                $categories = get_terms(array(
                                    'taxonomy' => 'business_category',
                                    'hide_empty' => false,
                                ));
                                
                                if (!empty($categories) && !is_wp_error($categories)) {
                                    // Separate into top-level and child categories
                                    $top_level_categories = array();
                                    $child_categories = array();
                                    
                                    foreach ($categories as $category) {
                                        if ($category->parent == 0) {
                                            $top_level_categories[] = $category;
                                        } else {
                                            if (!isset($child_categories[$category->parent])) {
                                                $child_categories[$category->parent] = array();
                                            }
                                            $child_categories[$category->parent][] = $category;
                                        }
                                    }
                                    
                                    // Display categories in hierarchical format
                                    foreach ($top_level_categories as $parent) {
                                        $selected = (isset($_POST['business_category']) && $_POST['business_category'] === $parent->slug) ? 'selected="selected"' : '';
                                        echo '<option value="' . esc_attr($parent->slug) . '" ' . $selected . '>' . esc_html($parent->name) . '</option>';
                                        
                                        // Add child categories with indentation
                                        if (isset($child_categories[$parent->term_id])) {
                                            foreach ($child_categories[$parent->term_id] as $child) {
                                                $selected = (isset($_POST['business_category']) && $_POST['business_category'] === $child->slug) ? 'selected="selected"' : '';
                                                echo '<option value="' . esc_attr($child->slug) . '" ' . $selected . '>&nbsp;&nbsp;— ' . esc_html($child->name) . '</option>';
                                            }
                                        }
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-field">
                            <label for="business_area">Business Area <span class="required">*</span></label>
                            <select name="business_area" id="business_area" required>
                                <option value="">Select an Area</option>
                                <?php
                                $areas = get_terms(array(
                                    'taxonomy' => 'business_area',
                                    'hide_empty' => false,
                                ));
                                
                                if (!empty($areas) && !is_wp_error($areas)) {
                                    foreach ($areas as $area) {
                                        $selected = (isset($_POST['business_area']) && $_POST['business_area'] === $area->slug) ? 'selected="selected"' : '';
                                        echo '<option value="' . esc_attr($area->slug) . '" ' . $selected . '>' . esc_html($area->name) . '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>Social Media</h3>
                    
                    <div class="form-row">
                        <div class="form-field">
                            <label for="business_facebook">Facebook Page</label>
                            <input type="url" name="business_facebook" id="business_facebook" 
                                   value="<?php echo isset($_POST['business_facebook']) ? esc_attr($_POST['business_facebook']) : ''; ?>">
                        </div>
                        <div class="form-field">
                            <label for="business_instagram">Instagram Handle</label>
                            <input type="text" name="business_instagram" id="business_instagram" 
                                   value="<?php echo isset($_POST['business_instagram']) ? esc_attr($_POST['business_instagram']) : ''; ?>">
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>Business Attributes</h3>
                    
                    <div class="form-row">
                        <div class="form-field checkbox-field">
                            <label>
                                <input type="checkbox" name="business_black_owned" value="yes" 
                                       <?php checked(isset($_POST['business_black_owned'])); ?>>
                                Black Owned Business
                            </label>
                        </div>
                        <div class="form-field checkbox-field">
                            <label>
                                <input type="checkbox" name="business_women_owned" value="yes" 
                                       <?php checked(isset($_POST['business_women_owned'])); ?>>
                                Women Owned Business
                            </label>
                        </div>
                        <div class="form-field checkbox-field">
                            <label>
                                <input type="checkbox" name="business_lgbtq_friendly" value="yes" 
                                       <?php checked(isset($_POST['business_lgbtq_friendly'])); ?>>
                                LGBTQ+ Friendly
                            </label>
                        </div>
                        <div class="form-field checkbox-field">
                            <label>
                                <input type="checkbox" name="business_hours_24" value="yes" 
                                       <?php checked(isset($_POST['business_hours_24'])); ?>>
                                24/7 Service
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>Your Information</h3>
                    
                    <div class="form-row">
                        <div class="form-field">
                            <label for="owner_name">Your Name <span class="required">*</span></label>
                            <input type="text" name="owner_name" id="owner_name" required 
                                   value="<?php echo isset($_POST['owner_name']) ? esc_attr($_POST['owner_name']) : ''; ?>">
                        </div>
                        <div class="form-field">
                            <label for="owner_email">Your Email <span class="required">*</span></label>
                            <input type="email" name="owner_email" id="owner_email" required 
                                   value="<?php echo isset($_POST['owner_email']) ? esc_attr($_POST['owner_email']) : ''; ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-field">
                            <label for="owner_phone">Your Phone <span class="required">*</span></label>
                            <input type="tel" name="owner_phone" id="owner_phone" required 
                                   value="<?php echo isset($_POST['owner_phone']) ? esc_attr($_POST['owner_phone']) : ''; ?>">
                        </div>
                    </div>
                </div>
                
                <div class="form-submit">
                    <button type="submit" name="lbd_submit_business" class="submit-button">
                        Submit Business
                    </button>
                </div>
            </form>
        </div>
        <?php
    }
    
    return ob_get_clean();
}
add_shortcode('submit_business_form', 'lbd_submit_business_form_shortcode');

/**
 * Mask an email address for display (e.g. john@example.com -> j***@example.com)
 */
function lbd_mask_email($email) {
    $parts = explode('@', $email);
    if (count($parts) !== 2) {
        return '****@****.com';
    }
    $local = $parts[0];
    $domain = $parts[1];
    $masked_local = substr($local, 0, 1) . str_repeat('*', max(strlen($local) - 1, 3));
    return $masked_local . '@' . $domain;
}

/**
 * Business claim form shortcode — hybrid verification
 * [claim_business_form]
 *
 * Path 1 (email verification): business has a listed email — send a verification link to it.
 * Path 2 (manual review): no listed email, or user clicks "Can't access this email?".
 * Path 3 (token callback): ?verify_claim=TOKEN in the URL — validate and auto-approve.
 */
function lbd_claim_business_form_shortcode($atts) {
    $atts = shortcode_atts(array(
        'title'       => 'Claim Your Business',
        'description' => 'Verify your ownership to manage your listing.',
    ), $atts);

    ob_start();

    // --- Path 3: Token verification callback ---
    if (isset($_GET['verify_claim'])) {
        $result = lbd_verify_claim_token(sanitize_text_field($_GET['verify_claim']));
        if ($result['success']) {
            echo '<div class="lbd-claim-form-container">';
            echo '<div class="lbd-submission-success lbd-claim-verified">';
            echo '<h3>Business Verified!</h3>';
            echo '<p>' . esc_html($result['message']) . '</p>';
            if (!empty($result['business_url'])) {
                echo '<p><a href="' . esc_url($result['business_url']) . '" class="claim-business-button">View Your Business</a></p>';
            }
            echo '</div></div>';
        } else {
            echo '<div class="lbd-claim-form-container">';
            echo '<div class="lbd-submission-error">';
            echo '<h3>Verification Failed</h3>';
            echo '<p>' . esc_html($result['message']) . '</p>';
            echo '</div></div>';
        }
        return ob_get_clean();
    }

    // --- Determine business from URL ---
    $business_id = isset($_GET['business_id']) ? intval($_GET['business_id']) : 0;
    // Also accept POST value when forms are submitted
    if (!$business_id && isset($_POST['business_id'])) {
        $business_id = intval($_POST['business_id']);
    }

    $business = $business_id ? get_post($business_id) : null;

    if (!$business || $business->post_type !== 'business') {
        echo '<div class="lbd-claim-form-container">';
        echo '<div class="lbd-submission-error">';
        echo '<h3>Business Not Found</h3>';
        echo '<p>We couldn\'t find the business you\'re trying to claim. Please go back to the business listing and try again.</p>';
        echo '</div></div>';
        return ob_get_clean();
    }

    // Check if already claimed / verified / pending
    $is_verified = get_post_meta($business_id, 'lbd_verified', true);
    if ($is_verified === 'verified') {
        echo '<div class="lbd-claim-form-container">';
        echo '<div class="lbd-submission-success"><h3>Already Verified</h3><p>This business has already been verified by its owner.</p></div>';
        echo '</div>';
        return ob_get_clean();
    }
    $is_claimed = get_post_meta($business_id, 'lbd_claimed', true);
    if ($is_claimed === 'yes') {
        echo '<div class="lbd-claim-form-container">';
        echo '<div class="lbd-submission-success"><h3>Already Claimed</h3><p>This business has already been claimed.</p></div>';
        echo '</div>';
        return ob_get_clean();
    }
    $pending_claims = get_posts(array(
        'post_type'  => 'business_submission',
        'post_status'=> 'any',
        'meta_query' => array(
            array('key' => 'claimed_business_id', 'value' => $business_id),
            array('key' => 'submission_status', 'value' => array('pending', 'email_pending'), 'compare' => 'IN'),
        ),
        'posts_per_page' => 1,
    ));
    if (!empty($pending_claims)) {
        echo '<div class="lbd-claim-form-container">';
        echo '<div class="lbd-submission-error"><h3>Claim Pending</h3><p>A claim for this business is already being processed.</p></div>';
        echo '</div>';
        return ob_get_clean();
    }

    $business_email = get_post_meta($business_id, 'lbd_email', true);
    $has_email = !empty($business_email) && is_email($business_email);

    // --- Process form submissions ---
    $result = null;
    $email_result = null;

    if (isset($_POST['lbd_claim_send_verification'])) {
        $email_result = lbd_process_claim_email_verification();
    } elseif (isset($_POST['lbd_claim_business'])) {
        $result = lbd_process_business_claim();
    }

    // Show success messages and return early
    if ($email_result && !empty($email_result['success'])) {
        $masked = lbd_mask_email($business_email);
        echo '<div class="lbd-claim-form-container">';
        echo '<div class="lbd-submission-success">';
        echo '<h3>Verification Email Sent!</h3>';
        echo '<p>We\'ve sent a verification link to <strong>' . esc_html($masked) . '</strong>.</p>';
        echo '<p>Please check that inbox and click the link within 24 hours to complete your claim.</p>';
        echo '<p class="field-help">Don\'t see it? Check your spam/junk folder.</p>';
        echo '</div></div>';
        return ob_get_clean();
    }
    if ($result && !empty($result['success'])) {
        echo '<div class="lbd-claim-form-container">';
        echo '<div class="lbd-submission-success">';
        echo '<h3>Claim Submitted!</h3>';
        echo '<p>' . esc_html($result['message']) . '</p>';
        echo '</div></div>';
        return ob_get_clean();
    }

    // --- Render claim form ---
    ?>
    <div class="lbd-claim-form-container">
        <h2><?php echo esc_html($atts['title']); ?></h2>
        <p class="lbd-claim-business-name">Claiming: <strong><?php echo esc_html($business->post_title); ?></strong></p>

        <?php
        // Show errors from either form
        $active_result = $email_result ?: $result;
        if ($active_result && empty($active_result['success'])) {
            echo '<div class="lbd-submission-error">';
            echo '<h3>Error</h3>';
            echo '<p>' . esc_html($active_result['message']) . '</p>';
            if (!empty($active_result['errors']) && is_array($active_result['errors'])) {
                echo '<ul>';
                foreach ($active_result['errors'] as $error) {
                    echo '<li>' . esc_html($error) . '</li>';
                }
                echo '</ul>';
            }
            echo '</div>';
        }
        ?>

        <?php if ($has_email) : ?>
        <!-- ========== EMAIL VERIFICATION PATH ========== -->
        <div id="lbd-claim-email-path" class="lbd-claim-path">
            <div class="form-section lbd-claim-email-section">
                <h3>Verify via Email</h3>
                <p>We can verify your ownership by sending a confirmation link to the email address associated with this business.</p>
                <p class="lbd-masked-email">Verification email will be sent to: <strong><?php echo esc_html(lbd_mask_email($business_email)); ?></strong></p>

                <form method="post" class="lbd-claim-form">
                    <?php wp_nonce_field('lbd_claim_email_verify_action', 'lbd_claim_email_nonce'); ?>
                    <input type="hidden" name="business_id" value="<?php echo esc_attr($business_id); ?>">
                    <div style="display:none;"><input type="text" name="website" value=""></div>

                    <div class="form-section">
                        <h3>Your Information</h3>
                        <div class="form-row">
                            <div class="form-field">
                                <label for="verify_owner_name">Your Name <span class="required">*</span></label>
                                <input type="text" name="owner_name" id="verify_owner_name" required
                                       value="<?php echo isset($_POST['owner_name']) ? esc_attr($_POST['owner_name']) : ''; ?>">
                            </div>
                            <div class="form-field">
                                <label for="verify_owner_email">Your Email <span class="required">*</span></label>
                                <input type="email" name="owner_email" id="verify_owner_email" required
                                       value="<?php echo isset($_POST['owner_email']) ? esc_attr($_POST['owner_email']) : ''; ?>">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-field">
                                <label for="verify_owner_phone">Your Phone <span class="required">*</span></label>
                                <input type="tel" name="owner_phone" id="verify_owner_phone" required
                                       value="<?php echo isset($_POST['owner_phone']) ? esc_attr($_POST['owner_phone']) : ''; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-submit">
                        <button type="submit" name="lbd_claim_send_verification" class="submit-button">
                            Send Verification Email
                        </button>
                    </div>
                </form>
            </div>

            <p class="lbd-claim-fallback-link">
                <a href="#" id="lbd-show-manual-form">Can't access this email? Verify manually instead</a>
            </p>
        </div>
        <?php endif; ?>

        <!-- ========== MANUAL REVIEW PATH ========== -->
        <div id="lbd-claim-manual-path" class="lbd-claim-path" <?php echo $has_email ? 'style="display:none;"' : ''; ?>>
            <?php if (!$has_email) : ?>
            <div class="lbd-claim-no-email-notice">
                <p>This business doesn't have an email address on file, so we'll need to verify your ownership manually.</p>
            </div>
            <?php endif; ?>

            <form method="post" class="lbd-claim-form">
                <?php wp_nonce_field('lbd_claim_business_action', 'lbd_claim_nonce'); ?>
                <input type="hidden" name="business_id" value="<?php echo esc_attr($business_id); ?>">
                <div style="display:none;"><input type="text" name="website" value=""></div>

                <div class="form-section">
                    <h3>Your Information</h3>
                    <div class="form-row">
                        <div class="form-field">
                            <label for="manual_owner_name">Your Name <span class="required">*</span></label>
                            <input type="text" name="owner_name" id="manual_owner_name" required
                                   value="<?php echo isset($_POST['owner_name']) ? esc_attr($_POST['owner_name']) : ''; ?>">
                        </div>
                        <div class="form-field">
                            <label for="manual_owner_email">Your Email <span class="required">*</span></label>
                            <input type="email" name="owner_email" id="manual_owner_email" required
                                   value="<?php echo isset($_POST['owner_email']) ? esc_attr($_POST['owner_email']) : ''; ?>">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-field">
                            <label for="manual_owner_phone">Your Phone <span class="required">*</span></label>
                            <input type="tel" name="owner_phone" id="manual_owner_phone" required
                                   value="<?php echo isset($_POST['owner_phone']) ? esc_attr($_POST['owner_phone']) : ''; ?>">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Proof of Ownership</h3>
                    <div class="form-row">
                        <div class="form-field">
                            <label for="claim_reason">Reason for Claim <span class="required">*</span></label>
                            <textarea name="claim_reason" id="claim_reason" rows="3" required><?php echo isset($_POST['claim_reason']) ? esc_textarea($_POST['claim_reason']) : ''; ?></textarea>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-field">
                            <label for="verification_proof">Verification Proof <span class="required">*</span></label>
                            <textarea name="verification_proof" id="verification_proof" rows="4" required><?php echo isset($_POST['verification_proof']) ? esc_textarea($_POST['verification_proof']) : ''; ?></textarea>
                            <p class="field-help">Please provide proof of ownership (e.g., business registration number, domain ownership, utility bill, etc.)</p>
                        </div>
                    </div>
                </div>

                <div class="form-submit">
                    <button type="submit" name="lbd_claim_business" class="submit-button">
                        Submit Claim for Review
                    </button>
                </div>
            </form>

            <?php if ($has_email) : ?>
            <p class="lbd-claim-fallback-link">
                <a href="#" id="lbd-show-email-form">Go back to email verification</a>
            </p>
            <?php endif; ?>
        </div>
    </div>

    <script>
    (function() {
        var showManual = document.getElementById('lbd-show-manual-form');
        var showEmail  = document.getElementById('lbd-show-email-form');
        var emailPath  = document.getElementById('lbd-claim-email-path');
        var manualPath = document.getElementById('lbd-claim-manual-path');

        if (showManual) {
            showManual.addEventListener('click', function(e) {
                e.preventDefault();
                emailPath.style.display  = 'none';
                manualPath.style.display = 'block';
            });
        }
        if (showEmail) {
            showEmail.addEventListener('click', function(e) {
                e.preventDefault();
                manualPath.style.display = 'none';
                emailPath.style.display  = 'block';
            });
        }
    })();
    </script>
    <?php

    return ob_get_clean();
}
add_shortcode('claim_business_form', 'lbd_claim_business_form_shortcode');

/**
 * Claim business button shortcode
 * [claim_business_button business_id="123"]
 */
function lbd_claim_business_button_shortcode($atts) {
    $atts = shortcode_atts(array(
        'business_id' => get_the_ID(),
        'text' => 'Claim This Business',
        'class' => 'claim-business-button'
    ), $atts);
    
    $business_id = intval($atts['business_id']);
    $business = get_post($business_id);
    
    if (!$business || $business->post_type !== 'business') {
        return '';
    }
    
    // Check if business is verified (verified businesses don't show claim button)
    $is_verified = get_post_meta($business_id, 'lbd_verified', true);
    if ($is_verified === 'verified') {
        return '<p class="business-verified-notice">This business has been verified by its owner.</p>';
    }
    
    // Check if business is already claimed
    $is_claimed = get_post_meta($business_id, 'lbd_claimed', true);
    if ($is_claimed === 'yes') {
        return '<p class="business-claimed-notice">This business has been claimed by its owner.</p>';
    }
    
    // Check if there's already a pending claim
    $pending_claims = get_posts(array(
        'post_type' => 'business_submission',
        'post_status' => 'any',
        'meta_query' => array(
            array(
                'key' => 'claimed_business_id',
                'value' => $business_id,
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
    
    if (!empty($pending_claims)) {
        return '<p class="business-claim-pending">This business is pending claim verification.</p>';
    }
    
    $claim_page_id = get_option('lbd_claim_page_id');
    if (!$claim_page_id) {
        return '';
    }
    $claim_url = add_query_arg('business_id', $business_id, get_permalink($claim_page_id));

    ob_start();
    ?>
    <div class="claim-business-section">
        <h3>Own This Business?</h3>
        <p>If you own or manage this business, you can claim it to update information and manage your listing.</p>
        <a href="<?php echo esc_url($claim_url); ?>" 
           class="<?php echo esc_attr($atts['class']); ?>">
            <?php echo esc_html($atts['text']); ?>
        </a>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('claim_business_button', 'lbd_claim_business_button_shortcode'); 
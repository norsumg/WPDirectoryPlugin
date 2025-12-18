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
 * Business claim form shortcode
 * [claim_business_form]
 */
function lbd_claim_business_form_shortcode($atts) {
    $atts = shortcode_atts(array(
        'title' => 'Claim Your Business',
        'description' => 'Is your business already listed? Claim ownership to manage your listing.',
    ), $atts);
    
    // Process form submission only if form was submitted
    $result = null;
    if (isset($_POST['lbd_claim_business'])) {
        $result = lbd_process_business_claim();
    }
    
    ob_start();
    
    // Display success/error messages
    if ($result && isset($result['success'])) {
        if ($result['success']) {
            echo '<div class="lbd-submission-success">';
            echo '<h3>Claim Submitted!</h3>';
            echo '<p>' . esc_html($result['message']) . '</p>';
            echo '</div>';
        } else {
            echo '<div class="lbd-submission-error">';
            echo '<h3>Claim Error</h3>';
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
        <div class="lbd-claim-form-container">
            <h2><?php echo esc_html($atts['title']); ?></h2>
            <p><?php echo esc_html($atts['description']); ?></p>
            
            <form method="post" class="lbd-claim-form">
                <?php wp_nonce_field('lbd_claim_business_action', 'lbd_claim_nonce'); ?>
                
                <!-- Honeypot field -->
                <div style="display: none;">
                    <input type="text" name="website" value="">
                </div>
                
                <div class="form-section">
                    <h3>Select Your Business</h3>
                    
                    <div class="form-row">
                        <div class="form-field">
                            <label for="business_id">Business <span class="required">*</span></label>
                            <select name="business_id" id="business_id" required>
                                <option value="">Search and select your business</option>
                                <?php
                                $businesses = get_posts(array(
                                    'post_type' => 'business',
                                    'posts_per_page' => -1,
                                    'orderby' => 'title',
                                    'order' => 'ASC',
                                ));
                                
                                foreach ($businesses as $business) {
                                    $selected = (isset($_POST['business_id']) && $_POST['business_id'] == $business->ID) ? 'selected="selected"' : '';
                                    echo '<option value="' . esc_attr($business->ID) . '" ' . $selected . '>' . esc_html($business->post_title) . '</option>';
                                }
                                ?>
                            </select>
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
                
                <div class="form-section">
                    <h3>Claim Details</h3>
                    
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
                            <p class="field-help">Please provide proof of ownership (e.g., business registration, website ownership, phone number verification, etc.)</p>
                        </div>
                    </div>
                </div>
                
                <div class="form-submit">
                    <button type="submit" name="lbd_claim_business" class="submit-button">
                        Submit Claim
                    </button>
                </div>
            </form>
        </div>
        <?php
    }
    
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
    
    ob_start();
    ?>
    <div class="claim-business-section">
        <h3>Own This Business?</h3>
        <p>If you own or manage this business, you can claim it to update information and manage your listing.</p>
        <a href="<?php echo esc_url(add_query_arg('claim_business', $business_id, get_permalink())); ?>" 
           class="<?php echo esc_attr($atts['class']); ?>">
            <?php echo esc_html($atts['text']); ?>
        </a>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('claim_business_button', 'lbd_claim_business_button_shortcode'); 
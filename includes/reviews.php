<?php
/**
 * Review processing functions
 */

/**
 * Process a review submission
 * 
 * @param int $business_id The business ID
 * @param array $review_data Associative array of review data
 * @param bool $check_nonce Whether to check nonce or not
 * @param string $nonce_action The nonce action to verify if checking
 * @param string $nonce_field The name of the nonce field
 * @return array Result array with keys: success, errors, form_submitted
 */
function lbd_process_review_submission($business_id, $review_data = array(), $check_nonce = true, $nonce_action = 'lbd_submit_review_action', $nonce_field = 'lbd_review_nonce') {
    $result = array(
        'success' => false,
        'errors' => array(),
        'form_submitted' => false
    );
    
    // Verify we have a valid business
    $business = get_post($business_id);
    if (!$business || $business->post_type !== 'business') {
        $result['errors'][] = 'Invalid business selected.';
        return $result;
    }
    
    // Check if submitting the form
    if (!isset($_POST['lbd_submit_review'])) {
        return $result;
    }
    
    $result['form_submitted'] = true;
    
    // Verify nonce if needed
    if ($check_nonce) {
        if (!isset($_POST[$nonce_field]) || !wp_verify_nonce($_POST[$nonce_field], $nonce_action)) {
            $result['errors'][] = 'Security check failed. Please try again.';
            return $result;
        }
    }
    
    // Check honeypot field
    if (!empty($_POST['website'])) {
        // Silently reject the submission
        $result['success'] = true;
        return $result;
    }
    
    // Check rate limiting
    $user_ip = $_SERVER['REMOTE_ADDR'];
    $transient_key = 'lbd_review_' . md5($user_ip . $business_id);
    if (get_transient($transient_key)) {
        $result['errors'][] = 'Please wait a few minutes before submitting another review.';
        return $result;
    }
    
    // Get and sanitize form data
    $reviewer_name = isset($_POST['reviewer_name']) ? sanitize_text_field($_POST['reviewer_name']) : '';
    $reviewer_email = isset($_POST['reviewer_email']) ? sanitize_email($_POST['reviewer_email']) : '';
    $review_text = isset($_POST['review_text']) ? sanitize_textarea_field($_POST['review_text']) : '';
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
    
    // Additional validation for reviewer name
    if (strlen($reviewer_name) < 2 || strlen($reviewer_name) > 50) {
        $result['errors'][] = 'Name must be between 2 and 50 characters.';
    }
    
    if (!preg_match('/^[a-zA-Z0-9\s\-\.]+$/', $reviewer_name)) {
        $result['errors'][] = 'Name can only contain letters, numbers, spaces, hyphens, and periods.';
    }
    
    // Validate email
    if (empty($reviewer_email) || !is_email($reviewer_email)) {
        $result['errors'][] = 'Please enter a valid email address.';
    }
    
    // Additional validation for review text
    if (empty($review_text)) {
        $result['errors'][] = 'Please enter your review.';
    } elseif (strlen($review_text) < 10) {
        $result['errors'][] = 'Review must be at least 10 characters long.';
    } elseif (strlen($review_text) > 1000) {
        $result['errors'][] = 'Review cannot exceed 1000 characters.';
    }
    
    // Validate rating
    if ($rating < 1 || $rating > 5) {
        $result['errors'][] = 'Please select a rating between 1 and 5 stars.';
    }
    
    // If there are errors, return them
    if (!empty($result['errors'])) {
        return $result;
    }
    
    // Set rate limiting to prevent spam
    set_transient($transient_key, true, 5 * MINUTE_IN_SECONDS);
    
    // Default to requiring approval
    $approved = false;
    
    // Add the review
    if (function_exists('lbd_add_review')) {
        $review_id = lbd_add_review(
            $business_id,
            $reviewer_name,
            $review_text,
            $rating,
            'manual', // Source is 'manual' for user-submitted reviews
            '', // No source ID for manual reviews
            $approved // Set to false to require approval
        );
        
        if ($review_id) {
            // Save the email as post meta if the review was added successfully
            update_post_meta($review_id, 'reviewer_email', $reviewer_email);
            $result['success'] = true;
            $result['review_id'] = $review_id;
        } else {
            $result['errors'][] = 'An error occurred while submitting your review. Please try again.';
        }
    } else {
        $result['errors'][] = 'Review system is not properly configured. Please contact the administrator.';
    }
    
    return $result;
}

/**
 * Generate HTML for review submission form
 * 
 * @param int $business_id The business ID
 * @param array $result Review processing result array
 * @return string HTML form or success message
 */
function lbd_get_review_form_html($business_id, $result = array()) {
    // Set default result if none provided
    if (empty($result)) {
        $result = array(
            'success' => false,
            'errors' => array(),
            'form_submitted' => false
        );
    }
    
    ob_start();
    
    if ($result['form_submitted'] && $result['success']) {
        ?>
        <div class="review-form-success">
            <h3>Thank you for your review!</h3>
            <p>Your review has been submitted and is pending approval. Once approved, it will appear on this page.</p>
        </div>
        <?php
    } else {
        if (!empty($result['errors'])) {
            echo '<div class="review-form-errors"><ul>';
            foreach ($result['errors'] as $error) {
                echo '<li>' . esc_html($error) . '</li>';
            }
            echo '</ul></div>';
        }
        ?>
        <form method="post" class="review-form">
            <?php wp_nonce_field('lbd_submit_review_action', 'lbd_review_nonce'); ?>
            <input type="hidden" name="business_id" value="<?php echo intval($business_id); ?>">
            
            <div class="form-field">
                <label for="reviewer_name">Your Name <span class="required">*</span></label>
                <input type="text" name="reviewer_name" id="reviewer_name" required value="<?php echo isset($_POST['reviewer_name']) ? esc_attr($_POST['reviewer_name']) : ''; ?>">
            </div>
            
            <div class="form-field">
                <label for="reviewer_email">Your Email <span class="required">*</span></label>
                <input type="email" name="reviewer_email" id="reviewer_email" required value="<?php echo isset($_POST['reviewer_email']) ? esc_attr($_POST['reviewer_email']) : ''; ?>">
                <small class="form-note">Your email won't be displayed publicly, but may be used to verify your review.</small>
            </div>
            
            <div class="form-field rating-field">
                <label>Rating <span class="required">*</span></label>
                <div class="star-rating">
                    <?php for ($i = 5; $i >= 1; $i--) : ?>
                        <input type="radio" name="rating" id="star<?php echo $i; ?>" value="<?php echo $i; ?>" <?php checked(isset($_POST['rating']) ? intval($_POST['rating']) : 5, $i); ?>>
                        <label for="star<?php echo $i; ?>" title="<?php echo $i; ?> star"><?php echo str_repeat('★', 1); ?></label>
                    <?php endfor; ?>
                </div>
            </div>
            
            <div class="form-field">
                <label for="review_text">Your Review <span class="required">*</span></label>
                <textarea name="review_text" id="review_text" rows="5" required><?php echo isset($_POST['review_text']) ? esc_textarea($_POST['review_text']) : ''; ?></textarea>
            </div>
            
            <!-- Honeypot field to prevent spam -->
            <div class="form-field" style="display:none">
                <label for="website">Website</label>
                <input type="text" name="website" id="website" value="">
            </div>
            
            <div class="form-submit">
                <button type="submit" name="lbd_submit_review" class="submit-review-button">Submit Review</button>
            </div>
        </form>
        <?php
    }
    
    return ob_get_clean();
}

/**
 * Generate HTML for star rating display
 * 
 * @param float $rating The rating value (0-5)
 * @param int $review_count Optional number of reviews
 * @param string $source Optional source of reviews (e.g., 'Google')
 * @return string HTML for star rating
 */
function lbd_get_star_rating_html($rating, $review_count = 0, $source = '') {
    if (empty($rating)) {
        return '';
    }
    
    $html = '<div class="business-rating">';
    
    // Add source label if provided
    if (!empty($source)) {
        $html .= '<strong>' . esc_html($source) . ' Rating: </strong>';
    } else {
        $html .= '<strong>Rating: </strong>';
    }
    
    // Add star icons
    $full_stars = floor($rating);
    $half_star = ($rating - $full_stars) >= 0.5;
    
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $full_stars) {
            $html .= '<span class="star full-star">★</span>'; // Full star
        } elseif ($i == $full_stars + 1 && $half_star) {
            $html .= '<span class="star half-star">½</span>'; // Half star
        } else {
            $html .= '<span class="star empty-star">☆</span>'; // Empty star
        }
    }
    
    // Add review count if provided
    if (!empty($review_count) && $review_count > 0) {
        $html .= ' <span class="rating-count">(' . intval($review_count) . ' reviews)</span>';
    }
    
    $html .= '</div>';
    
    return $html;
} 
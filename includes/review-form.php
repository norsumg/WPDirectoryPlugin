function lbd_review_form($business_id = null) {
    $form_submitted = false;
    $form_success = false;
    $form_errors = array();
    
    // Default business ID to current post if not specified
    if (!$business_id && is_singular('business')) {
        $business_id = get_the_ID();
    }
    
    // Process form submission
    if (isset($_POST['lbd_submit_review'])) {
        // Verify nonce
        if (!isset($_POST['lbd_review_nonce']) || !wp_verify_nonce($_POST['lbd_review_nonce'], 'lbd_submit_review_action')) {
            $form_errors[] = 'Security check failed. Please try again.';
        } else {
            // Get and sanitize form data
            $reviewer_name = isset($_POST['reviewer_name']) ? sanitize_text_field($_POST['reviewer_name']) : '';
            $reviewer_email = isset($_POST['reviewer_email']) ? sanitize_email($_POST['reviewer_email']) : '';
            $review_text = isset($_POST['review_text']) ? sanitize_textarea_field($_POST['review_text']) : '';
            $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
            
            // Validate data
            if (empty($reviewer_name)) {
                $form_errors[] = 'Please enter your name.';
            }
            
            if (empty($reviewer_email) || !is_email($reviewer_email)) {
                $form_errors[] = 'Please enter a valid email address.';
            }
            
            if (empty($review_text)) {
                $form_errors[] = 'Please enter your review.';
            }
            
            if ($rating < 1 || $rating > 5) {
                $form_errors[] = 'Please select a rating between 1 and 5 stars.';
            }
            
            // If no errors, submit the review
            if (empty($form_errors)) {
                $form_submitted = true;
                
                // Default to requiring approval
                $approved = false; 
                
                // Add the review (defined in activation.php)
                $result = lbd_add_review(
                    $business_id,
                    $reviewer_name,
                    $review_text,
                    $rating,
                    'manual', // Source is 'manual' for user-submitted reviews
                    '', // No source ID for manual reviews
                    $approved // Set to false to require approval
                );
                
                // Save the email as post meta if the review was added successfully
                if ($result) {
                    update_post_meta($result, 'reviewer_email', $reviewer_email);
                    $form_success = true;
                } else {
                    $form_errors[] = 'An error occurred while submitting your review. Please try again.';
                }
            }
        }
    }
    
    ob_start();
    
    if ($form_submitted && $form_success) {
        ?>
        <div class="review-form-success">
            <h3>Thank you for your review!</h3>
            <p>Your review has been submitted and is pending approval. Once approved, it will appear on this page.</p>
        </div>
        <?php
    } else {
        if (!empty($form_errors)) {
            echo '<div class="review-form-errors"><ul>';
            foreach ($form_errors as $error) {
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
                    <?php for ($i = 1; $i <= 5; $i++) : ?>
                        <input type="radio" name="rating" id="star<?php echo $i; ?>" value="<?php echo $i; ?>" <?php checked(isset($_POST['rating']) ? intval($_POST['rating']) : 5, $i); ?>>
                        <label for="star<?php echo $i; ?>" title="<?php echo $i; ?> star"><?php echo str_repeat('★', 1); ?></label>
                    <?php endfor; ?>
                </div>
            </div>
            
            <div class="form-field">
                <label for="review_text">Your Review <span class="required">*</span></label>
                <textarea name="review_text" id="review_text" rows="5" required><?php echo isset($_POST['review_text']) ? esc_textarea($_POST['review_text']) : ''; ?></textarea>
            </div>
            
            <div class="form-submit">
                <button type="submit" name="lbd_submit_review" class="submit-review-button">Submit Review</button>
            </div>
        </form>
        <?php
    }
    
    return ob_get_clean();
} 
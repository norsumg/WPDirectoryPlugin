<?php
// Shortcode for displaying specific categories
function lbd_custom_categories_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'ids' => '',
        'area' => '',  // Allow specifying area context
    ), $atts, 'custom_categories' );

    $terms = get_terms( array(
        'taxonomy' => 'business_category',
        'include' => ! empty( $atts['ids'] ) ? explode( ',', $atts['ids'] ) : array(),
        'hide_empty' => false,
    ) );

    if ( empty( $terms ) || is_wp_error( $terms ) ) {
        return '<p>No categories found.</p>';
    }

    // Check if we have an area context
    $area = null;
    if (!empty($atts['area'])) {
        $area = get_term_by('slug', $atts['area'], 'business_area');
    } elseif (is_tax('business_area')) {
        $area = get_queried_object();
    }

    $output = '<ul class="business-categories">';
    foreach ( $terms as $term ) {
        // Get the term link - make it area-specific if we have an area context
        if ( $area ) {
            $term_link = home_url('/directory/' . $area->slug . '/' . $term->slug . '/');
        } else {
            $term_link = get_term_link( $term );
        }
        
        $output .= '<li><a href="' . esc_url($term_link) . '">' . esc_html( $term->name ) . '</a></li>';
    }
    $output .= '</ul>';
    return $output;
}
add_shortcode( 'custom_categories', 'lbd_custom_categories_shortcode' );

// Shortcode for displaying areas
function lbd_areas_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'ids' => '',
    ), $atts, 'business_areas' );

    $terms = get_terms( array(
        'taxonomy' => 'business_area',
        'include' => ! empty( $atts['ids'] ) ? explode( ',', $atts['ids'] ) : array(),
        'hide_empty' => false,
    ) );

    if ( empty( $terms ) || is_wp_error( $terms ) ) {
        return '<p>No areas found.</p>';
    }

    $output = '<ul class="business-areas">';
    foreach ( $terms as $term ) {
        $output .= '<li><a href="' . get_term_link( $term ) . '">' . esc_html( $term->name ) . '</a></li>';
    }
    $output .= '</ul>';
    return $output;
}
add_shortcode( 'business_areas', 'lbd_areas_shortcode' );

/**
 * Shortcode for the business search form
 * [business_search_form layout="horizontal" button_style="pill" placeholder="Find businesses..."]
 */
function lbd_search_form_shortcode($atts) {
    // Parse attributes
    $atts = shortcode_atts(array(
        'layout' => 'vertical',
        'button_style' => 'default',
        'placeholder' => 'Search businesses...',
        'show_filters' => 'yes',
    ), $atts);
    
    // Set CSS classes based on attributes
    $form_classes = 'business-search-form';
    if ($atts['layout'] == 'horizontal') {
        $form_classes .= ' horizontal';
    }
    
    $button_classes = 'search-button';
    if ($atts['button_style'] == 'pill') {
        $button_classes .= ' pill-button';
    } else if ($atts['button_style'] == 'rounded') {
        $button_classes .= ' rounded-button';
    }
    
    // Current values
    $current_search = get_search_query();
    $current_area = isset($_GET['area']) ? sanitize_text_field($_GET['area']) : '';
    $current_category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
    
    // Start output buffering
    ob_start();
    ?>
    <div class="<?php echo esc_attr($form_classes); ?>">
        <form role="search" method="get" action="<?php echo esc_url(home_url('/')); ?>" class="search-form">
            <input type="hidden" name="post_type" value="business" />
            
            <div class="search-inputs">
                <div class="input-container search-field">
                    <input type="text" name="s" placeholder="<?php echo esc_attr($atts['placeholder']); ?>" value="<?php echo esc_attr($current_search); ?>" />
                </div>
                
                <?php if ($atts['show_filters'] !== 'no'): ?>
                    <div class="input-container area-field">
                        <select name="area">
                            <option value="">All Areas</option>
                            <?php
                            $areas = get_terms(array(
                                'taxonomy' => 'business_area',
                                'hide_empty' => false,
                            ));
                            
                            if (!empty($areas) && !is_wp_error($areas)) {
                                foreach ($areas as $area) {
                                    $selected = ($current_area === $area->slug) ? 'selected="selected"' : '';
                                    echo '<option value="' . esc_attr($area->slug) . '" ' . $selected . '>' . esc_html($area->name) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="input-container category-field">
                        <select name="category">
                            <option value="">All Categories</option>
                            <?php
                            $categories = get_terms(array(
                                'taxonomy' => 'business_category',
                                'hide_empty' => false,
                            ));
                            
                            if (!empty($categories) && !is_wp_error($categories)) {
                                foreach ($categories as $category) {
                                    $selected = ($current_category === $category->slug) ? 'selected="selected"' : '';
                                    echo '<option value="' . esc_attr($category->slug) . '" ' . $selected . '>' . esc_html($category->name) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                <?php endif; ?>
                
                <button type="submit" class="<?php echo esc_attr($button_classes); ?>">Search</button>
            </div>
        </form>
    </div>
    
    <style>
    /* Basic styles for the search form */
    .business-search-form {
        margin-bottom: 20px;
    }
    
    .business-search-form .search-inputs {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    
    .business-search-form.horizontal .search-inputs {
        flex-direction: row;
        flex-wrap: wrap;
        align-items: flex-start;
    }
    
    .business-search-form input[type="text"],
    .business-search-form select,
    .business-search-form button {
        height: 40px;
        box-sizing: border-box;
    }
    
    .business-search-form .input-container {
        flex-grow: 1;
    }
    
    .business-search-form .search-field {
        max-width: 300px;
    }
    
    .business-search-form .area-field,
    .business-search-form .category-field {
        max-width: 225px;
    }
    
    .business-search-form input[type="text"],
    .business-search-form select {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    
    .business-search-form button {
        background-color: #0073aa;
        color: white;
        border: none;
        padding: 8px 16px;
        cursor: pointer;
    }
    
    .business-search-form button:hover {
        background-color: #005a87;
    }
    
    /* Button styles */
    .business-search-form .pill-button {
        border-radius: 50px;
    }
    
    .business-search-form .rounded-button {
        border-radius: 8px;
    }
    </style>
    <?php
    
    return ob_get_clean();
}
add_shortcode('business_search_form', 'lbd_search_form_shortcode');

// Remove old functions related to search if they exist
if (function_exists('lbd_test_search_shortcode')) {
    remove_shortcode('test_business_search');
}

if (function_exists('lbd_search_results_shortcode')) {
    remove_shortcode('business_search_results');
    remove_shortcode('lbd_search_results');
}

// Shortcode for review submission form
function lbd_review_form_shortcode($atts) {
    // Parse attributes
    $atts = shortcode_atts(array(
        'business_id' => 0,
        'title' => '',
    ), $atts);
    
    ob_start();
    
    // Check if a business ID is provided in the URL first (higher priority)
    $business_id = isset($_GET['business_id']) ? intval($_GET['business_id']) : 0;
    
    // If not in URL, check if provided in shortcode attribute
    if (!$business_id && !empty($atts['business_id'])) {
        $business_id = intval($atts['business_id']);
    }
    
    $business = null;
    
    if ($business_id) {
        $business = get_post($business_id);
    }
    
    // Check if we have a valid business
    if (!$business || $business->post_type !== 'business') {
        echo '<p>Please select a business to review.</p>';
        
        // Show business selection dropdown
        $businesses = get_posts(array(
            'post_type' => 'business',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ));
        
        if (!empty($businesses)) {
            echo '<form method="get" action="" class="select-business-form">';
            echo '<select name="business_id">';
            echo '<option value="">Select a business</option>';
            
            foreach ($businesses as $b) {
                echo '<option value="' . esc_attr($b->ID) . '">' . esc_html($b->post_title) . '</option>';
            }
            
            echo '</select>';
            echo '<button type="submit" class="button">Select Business</button>';
            echo '</form>';
        }
        
        return ob_get_clean();
    }
    
    // Process form submission
    $form_submitted = false;
    $form_errors = array();
    $form_success = false;
    
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
    
    // Display success message if the form was submitted successfully
    if ($form_success) {
        echo '<div class="review-submitted">';
        echo '<h3>Thank you for your review!</h3>';
        echo '<p>Your review has been submitted and is pending approval.</p>';
        echo '<p><a href="' . get_permalink($business_id) . '">Return to ' . esc_html($business->post_title) . '</a></p>';
        echo '</div>';
    } else {
        // Display the form
        ?>
        <div class="review-form-container">
            <h2>Leave a Review for <?php echo esc_html($business->post_title); ?></h2>
            
            <?php if (!empty($form_errors)) : ?>
                <div class="form-errors">
                    <?php foreach ($form_errors as $error) : ?>
                        <p class="error-message"><?php echo esc_html($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <form method="post" action="" class="review-form">
                <?php wp_nonce_field('lbd_submit_review_action', 'lbd_review_nonce'); ?>
                <input type="hidden" name="business_id" value="<?php echo esc_attr($business_id); ?>">
                
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
                    <textarea name="review_text" id="review_text" rows="6" required><?php echo isset($_POST['review_text']) ? esc_textarea($_POST['review_text']) : ''; ?></textarea>
                </div>
                
                <div class="form-field submit-field">
                    <button type="submit" name="lbd_submit_review" class="submit-review-button">Submit Review</button>
                </div>
            </form>
        </div>
        <?php
    }
    
    return ob_get_clean();
}
add_shortcode('review_submission_form', 'lbd_review_form_shortcode');

// Add an additional alias shortcode for flexibility
function lbd_review_form_alias_shortcode($atts) {
    return lbd_review_form_shortcode($atts);
}
add_shortcode('submit_review_form', 'lbd_review_form_alias_shortcode');

/**
 * Shortcode to display a directory homepage with links to all areas and categories
 * [directory_home]
 */
function lbd_directory_home_shortcode($atts) {
    $atts = shortcode_atts( array(
        'show_areas' => 'true',
        'show_categories' => 'true',
    ), $atts );
    
    $show_areas = filter_var($atts['show_areas'], FILTER_VALIDATE_BOOLEAN);
    $show_categories = filter_var($atts['show_categories'], FILTER_VALIDATE_BOOLEAN);
    
    ob_start();
    ?>
    <div class="directory-home">
        <h2>Business Directory</h2>
        
        <?php if ($show_areas): ?>
        <div class="directory-areas">
            <h3>Browse by Area</h3>
            <?php
            $areas = get_terms(array(
                'taxonomy' => 'business_area',
                'hide_empty' => true,
            ));
            
            if (!empty($areas) && !is_wp_error($areas)) {
                echo '<ul class="directory-areas-list">';
                foreach ($areas as $area) {
                    echo '<li><a href="' . get_term_link($area) . '">' . esc_html($area->name) . '</a></li>';
                }
                echo '</ul>';
            } else {
                echo '<p>No business areas found.</p>';
            }
            ?>
        </div>
        <?php endif; ?>
        
        <?php if ($show_categories): ?>
        <div class="directory-categories">
            <h3>Browse by Category</h3>
            <?php
            $categories = get_terms(array(
                'taxonomy' => 'business_category',
                'hide_empty' => true,
            ));
            
            if (!empty($categories) && !is_wp_error($categories)) {
                echo '<ul class="directory-categories-list">';
                foreach ($categories as $category) {
                    echo '<li><a href="' . get_term_link($category) . '">' . esc_html($category->name) . '</a></li>';
                }
                echo '</ul>';
            } else {
                echo '<p>No business categories found.</p>';
            }
            ?>
        </div>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('directory_home', 'lbd_directory_home_shortcode');

// Add star rating styles to the head
function lbd_add_star_rating_styles() {
    ?>
    <style>
    /* Star rating hover and selection effects */
    .star-rating {
        display: flex;
        flex-direction: row-reverse;
        justify-content: flex-end;
    }
    .star-rating input {
        display: none;
    }
    .star-rating label {
        color: #ddd;
        font-size: 24px;
        padding: 0 2px;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    /* Selected state */
    .star-rating input:checked ~ label {
        color: #FFD700;
    }
    /* Hover state */
    .star-rating label:hover,
    .star-rating label:hover ~ label {
        color: #FFD700;
    }
    
    /* Form Styles */
    .business-search-form input[type="text"],
    .business-search-form input[type="email"],
    .review-form input[type="text"],
    .review-form input[type="email"],
    .business-search-form select {
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
        margin-bottom: 10px;
        width: 100%;
    }
    
    /* Search Form Layout Options */
    .business-search-form.horizontal {
        display: flex;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 10px;
    }
    
    .business-search-form.horizontal > div {
        flex: 1;
        margin: 0;
    }
    
    /* Specific field widths */
    .business-search-form.horizontal .search-field {
        max-width: 300px;
        flex: 2;
    }
    
    .business-search-form.horizontal .area-field,
    .business-search-form.horizontal .category-field {
        max-width: 225px;
    }
    
    .business-search-form.horizontal button {
        margin: 0;
        height: 40px;
        align-self: flex-start;
    }
    
    /* Form elements should have consistent height */
    .business-search-form input[type="text"],
    .business-search-form select,
    .business-search-form button {
        height: 40px;
        box-sizing: border-box;
    }
    
    /* Button Styles */
    .business-search-form button.rounded {
        border-radius: 20px;
    }
    
    .business-search-form button.square {
        border-radius: 0;
    }
    
    .business-search-form button.pill {
        border-radius: 50px;
        padding-left: 20px;
        padding-right: 20px;
    }
    
    /* Search Results Styles */
    .business-search-results h2 {
        margin-bottom: 1em;
    }
    
    .search-filters {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 20px;
    }
    
    .filter-item {
        background: #f5f5f5;
        padding: 5px 10px;
        border-radius: 4px;
        font-size: 0.9em;
    }
    
    .remove-filter {
        margin-left: 5px;
        color: #999;
        text-decoration: none;
        font-weight: bold;
    }
    
    .remove-filter:hover {
        color: #f44336;
    }
    
    .result-count {
        color: #666;
        margin-bottom: 20px;
    }
    
    .business-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
    }
    
    .business-card {
        border: 1px solid #eee;
        border-radius: 8px;
        overflow: hidden;
        transition: all 0.2s ease;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    
    .business-card:hover {
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        transform: translateY(-3px);
    }
    
    .business-card-inner {
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    
    .business-thumbnail {
        height: 150px;
        overflow: hidden;
    }
    
    .business-thumbnail img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s ease;
    }
    
    .business-card:hover .business-thumbnail img {
        transform: scale(1.05);
    }
    
    .business-details {
        padding: 15px;
        display: flex;
        flex-direction: column;
        flex-grow: 1;
    }
    
    .business-title {
        margin-top: 0;
        margin-bottom: 10px;
        font-size: 1.2em;
        line-height: 1.3;
    }
    
    .business-title a {
        text-decoration: none;
        color: #333;
    }
    
    .premium-badge {
        display: inline-block;
        background: #FFD700;
        color: #333;
        font-size: 0.7em;
        padding: 2px 6px;
        border-radius: 3px;
        margin-left: 8px;
        vertical-align: middle;
        font-weight: bold;
    }
    
    .business-meta {
        font-size: 0.85em;
        color: #666;
        margin-bottom: 10px;
    }
    
    .business-excerpt {
        font-size: 0.9em;
        margin-bottom: 15px;
        flex-grow: 1;
    }
    
    .view-business {
        display: inline-block;
        background: #4285f4;
        color: white;
        padding: 8px 15px;
        text-decoration: none;
        border-radius: 4px;
        font-size: 0.9em;
        text-align: center;
        transition: background 0.2s ease;
        align-self: flex-start;
    }
    
    .view-business:hover {
        background: #3367d6;
    }
    
    .business-pagination {
        margin-top: 30px;
        text-align: center;
    }
    
    .business-pagination .page-numbers {
        padding: 5px 10px;
        margin: 0 3px;
        border: 1px solid #ddd;
        text-decoration: none;
        display: inline-block;
    }
    
    .business-pagination .current {
        background: #4285f4;
        color: white;
        border-color: #4285f4;
    }
    
    .search-suggestions {
        margin-top: 20px;
        background: #f9f9f9;
        padding: 15px;
        border-radius: 8px;
    }
    
    .search-suggestions h3 {
        margin-top: 0;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .business-list {
            grid-template-columns: 1fr;
        }
    }
    </style>
    <?php
}
add_action('wp_head', 'lbd_add_star_rating_styles'); 
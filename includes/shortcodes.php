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
        $term_link = get_term_link( $term );
        
        // If we have an area context, create an area-specific link
        if ($area) {
            $term_link = home_url('/' . $area->slug . '/' . $term->slug . '/');
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

// Shortcode for search form
function lbd_search_form_shortcode() {
    ob_start();
    ?>
    <form method="get" action="<?php echo esc_url( home_url( '/search-results/' ) ); ?>" class="business-search-form">
        <input type="text" name="s" placeholder="Search businesses..." value="<?php echo esc_attr( get_query_var( 's' ) ); ?>">
        
        <select name="area">
            <option value="">All Areas</option>
            <?php
            $areas = get_terms( array( 'taxonomy' => 'business_area', 'hide_empty' => false ) );
            foreach ( $areas as $area ) {
                echo '<option value="' . esc_attr( $area->slug ) . '">' . esc_html( $area->name ) . '</option>';
            }
            ?>
        </select>
        
        <select name="category">
            <option value="">All Categories</option>
            <?php
            $categories = get_terms( array( 'taxonomy' => 'business_category', 'hide_empty' => false ) );
            foreach ( $categories as $category ) {
                echo '<option value="' . esc_attr( $category->slug ) . '">' . esc_html( $category->name ) . '</option>';
            }
            ?>
        </select>
        
        <button type="submit">Search</button>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode( 'business_search_form', 'lbd_search_form_shortcode' );

// Shortcode for search results
function lbd_search_results_shortcode() {
    $args = array(
        'post_type' => 'business',
        'posts_per_page' => 10,
        'meta_query' => array(
            'premium_clause' => array(
                'key' => 'lbd_premium',
                'compare' => 'EXISTS',
            ),
        ),
        'orderby' => array(
            'premium_clause' => 'DESC',
            'title' => 'ASC',
        ),
    );

    // Search term filter
    if ( isset( $_GET['s'] ) && ! empty( $_GET['s'] ) ) {
        $args['s'] = sanitize_text_field( $_GET['s'] );
    }

    // Category filter
    if ( isset( $_GET['category'] ) && ! empty( $_GET['category'] ) ) {
        $args['tax_query'][] = array(
            'taxonomy' => 'business_category',
            'field' => 'slug',
            'terms' => sanitize_text_field( $_GET['category'] ),
        );
    }
    
    // Area filter
    if ( isset( $_GET['area'] ) && ! empty( $_GET['area'] ) ) {
        $args['tax_query'][] = array(
            'taxonomy' => 'business_area',
            'field' => 'slug',
            'terms' => sanitize_text_field( $_GET['area'] ),
        );
    }

    $query = new WP_Query( $args );
    ob_start();

    if ( $query->have_posts() ) {
        echo '<ul class="business-list">';
        while ( $query->have_posts() ) {
            $query->the_post();
            ?>
            <li>
                <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                <?php if ( get_post_meta( get_the_ID(), 'lbd_premium', true ) ) : ?>
                    <span class="premium-label">Premium</span>
                <?php endif; ?>
                
                <?php 
                // Display area and category
                $areas = get_the_terms( get_the_ID(), 'business_area' );
                $categories = get_the_terms( get_the_ID(), 'business_category' );
                
                echo '<div class="business-meta">';
                if ( $areas && !is_wp_error( $areas ) ) {
                    echo '<span class="business-area">Area: <a href="' . get_term_link( $areas[0] ) . '">' . esc_html( $areas[0]->name ) . '</a></span> | ';
                }
                
                if ( $categories && !is_wp_error( $categories ) ) {
                    echo '<span class="business-category">Category: ' . get_the_term_list( get_the_ID(), 'business_category', '', ', ' ) . '</span>';
                }
                echo '</div>';
                ?>
                
                <p><?php the_excerpt(); ?></p>
            </li>
            <?php
        }
        echo '</ul>';
        wp_reset_postdata();
    } else {
        echo '<p>No businesses found.</p>';
    }

    return ob_get_clean();
}
add_shortcode( 'business_search_results', 'lbd_search_results_shortcode' );

// Shortcode for review submission form
function lbd_review_form_shortcode() {
    ob_start();
    
    // Check if a business ID is provided
    $business_id = isset($_GET['business_id']) ? intval($_GET['business_id']) : 0;
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
            $review_text = isset($_POST['review_text']) ? sanitize_textarea_field($_POST['review_text']) : '';
            $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
            
            // Validate data
            if (empty($reviewer_name)) {
                $form_errors[] = 'Please enter your name.';
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
                
                if ($result) {
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
                
                <div class="form-field rating-field">
                    <label>Rating <span class="required">*</span></label>
                    <div class="star-rating">
                        <?php for ($i = 5; $i >= 1; $i--) : ?>
                            <input type="radio" name="rating" id="star<?php echo $i; ?>" value="<?php echo $i; ?>" <?php checked(isset($_POST['rating']) ? intval($_POST['rating']) : 5, $i); ?>>
                            <label for="star<?php echo $i; ?>" title="<?php echo $i; ?> star"><?php echo str_repeat('★', $i); ?></label>
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
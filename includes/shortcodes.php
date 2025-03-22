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
 * Shortcode for a business search form
 * [business_search_form]
 */
function lbd_search_form_shortcode($atts) {
    $atts = shortcode_atts(array(
        'layout' => 'vertical',     // vertical or horizontal
        'placeholder' => 'Search businesses...',
        'button_style' => 'default', // default, rounded, square, pill
        'button_text' => 'Search',
    ), $atts);
    
    // Get areas and categories for dropdown
    $areas = get_terms(array(
        'taxonomy' => 'business_area',
        'hide_empty' => true,
    ));
    
    $categories = get_terms(array(
        'taxonomy' => 'business_category',
        'hide_empty' => true,
    ));
    
    // Get current search values (if any)
    $search_term = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    $selected_area = isset($_GET['area']) ? sanitize_text_field($_GET['area']) : '';
    $selected_category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
    
    // Start building the form
    $output = '<form role="search" method="get" class="business-search-form ' . esc_attr($atts['layout']) . '" action="' . esc_url(home_url('/')) . '">';
    
    // CSS styling
    $output .= '<style>
        .business-search-form {
            margin-bottom: 30px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .business-search-form.horizontal {
            flex-direction: row;
            align-items: flex-start;
        }
        
        .business-search-form.vertical {
            flex-direction: column;
        }
        
        .business-search-form input[type="text"],
        .business-search-form select,
        .business-search-form button {
            height: 40px;
            box-sizing: border-box;
        }
        
        .business-search-form.horizontal .input-container {
            flex: 1;
            min-width: 200px;
        }
        
        .business-search-form.vertical .input-container {
            width: 100%;
            margin-bottom: 10px;
        }
        
        .search-field {
            max-width: 300px;
        }
        
        .area-field, .category-field {
            max-width: 225px;
        }
        
        /* Button styles */
        .business-search-form button {
            cursor: pointer;
            padding: 8px 15px;
            background-color: #4a4a4a;
            color: white;
            border: none;
        }
        
        .business-search-form button.rounded {
            border-radius: 5px;
        }
        
        .business-search-form button.square {
            border-radius: 0;
        }
        
        .business-search-form button.pill {
            border-radius: 20px;
            padding-left: 20px;
            padding-right: 20px;
        }
        
        .business-search-form button:hover {
            background-color: #333;
        }
        
        .business-search-form.horizontal button {
            margin: 0;
            height: 40px;
            align-self: flex-start;
        }
    </style>';
    
    // Search input
    $output .= '<div class="input-container search-field">';
    $output .= '<input type="text" name="s" placeholder="' . esc_attr($atts['placeholder']) . '" value="' . esc_attr($search_term) . '" />';
    $output .= '</div>';
    
    // Area dropdown
    if (!empty($areas) && !is_wp_error($areas)) {
        $output .= '<div class="input-container area-field">';
        $output .= '<select name="area">';
        $output .= '<option value="">All Areas</option>';
        
        foreach ($areas as $area) {
            $selected = ($selected_area == $area->slug) ? 'selected="selected"' : '';
            $output .= '<option value="' . esc_attr($area->slug) . '" ' . $selected . '>' . esc_html($area->name) . '</option>';
        }
        
        $output .= '</select>';
        $output .= '</div>';
    }
    
    // Category dropdown
    if (!empty($categories) && !is_wp_error($categories)) {
        $output .= '<div class="input-container category-field">';
        $output .= '<select name="category">';
        $output .= '<option value="">All Categories</option>';
        
        foreach ($categories as $category) {
            $selected = ($selected_category == $category->slug) ? 'selected="selected"' : '';
            $output .= '<option value="' . esc_attr($category->slug) . '" ' . $selected . '>' . esc_html($category->name) . '</option>';
        }
        
        $output .= '</select>';
        $output .= '</div>';
    }
    
    // Search button
    $button_class = '';
    if (in_array($atts['button_style'], array('rounded', 'square', 'pill'))) {
        $button_class = $atts['button_style'];
    }
    
    $output .= '<button type="submit" class="' . esc_attr($button_class) . '">' . esc_html($atts['button_text']) . '</button>';
    
    // Optional search page ID - if set, we direct searches to that page
    $search_page_id = get_option('lbd_search_page_id');
    if ($search_page_id) {
        $output .= '<input type="hidden" name="page_id" value="' . esc_attr($search_page_id) . '" />';
    }
    
    $output .= '</form>';
    
    return $output;
}
add_shortcode('business_search_form', 'lbd_search_form_shortcode');

// Add search join function
function lbd_search_join($join) {
    global $wpdb;
    if (!strpos($join, "LEFT JOIN $wpdb->postmeta ON")) {
        $join .= " LEFT JOIN $wpdb->postmeta ON ($wpdb->posts.ID = $wpdb->postmeta.post_id) ";
    }
    return $join;
}

// Add search where function
function lbd_search_where($where, $search_term) {
    global $wpdb;
    // Ensure we're only searching in business post type
    $where .= " AND $wpdb->posts.post_type = 'business' ";
    
    // Search in post meta for broader matches
    $where .= $wpdb->prepare(
        " OR ($wpdb->posts.post_type = 'business' AND $wpdb->postmeta.meta_value LIKE %s) ",
        '%' . $wpdb->esc_like($search_term) . '%'
    );
    
    return $where;
}

// Add search distinct function
function lbd_search_distinct() {
    return "DISTINCT";
}

/**
 * Implementation of the [lbd_search_results] shortcode
 */
function lbd_search_results_shortcode($atts) {
    $atts = shortcode_atts(array(
        'per_page' => 10,
        'info_layout' => 'list',
    ), $atts);

    $output = '';
    $search_term = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    $area = isset($_GET['area']) ? sanitize_text_field($_GET['area']) : '';
    $category = isset($_GET['category']) ? sanitize_text_field($_GET['category']) : '';
    
    // Debug info in HTML comments
    $output .= "<!-- Search parameters: term='$search_term', area='$area', category='$category' -->\n";
    
    // Early exit with instructions if no search params
    if (empty($search_term) && empty($area) && empty($category)) {
        return '<div class="business-search-instructions">
            <p>Please use the search form above to find businesses.</p>
        </div>';
    }
    
    // Build search query
    global $wpdb;
    
    // Start with empty array for found post IDs
    $post_ids = array();
    
    // If we have a search term, perform a direct SQL search to find matches
    if (!empty($search_term)) {
        // Prepare the search term for SQL LIKE comparison
        $like_term = '%' . $wpdb->esc_like($search_term) . '%';
        
        // Search in post title and content
        $title_content_query = $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} 
            WHERE post_type = 'business' 
            AND post_status = 'publish' 
            AND (post_title LIKE %s OR post_content LIKE %s)",
            $like_term, $like_term
        );
        
        $title_content_ids = $wpdb->get_col($title_content_query);
        $post_ids = array_merge($post_ids, $title_content_ids);
        
        // Search in post meta
        $meta_query = $wpdb->prepare(
            "SELECT DISTINCT post_id FROM {$wpdb->postmeta} 
            WHERE meta_value LIKE %s",
            $like_term
        );
        
        $meta_ids = $wpdb->get_col($meta_query);
        
        // Filter to only include business post type
        if (!empty($meta_ids)) {
            $meta_ids_str = implode(',', array_map('intval', $meta_ids));
            if (!empty($meta_ids_str)) {
                $meta_post_type_query = "SELECT ID FROM {$wpdb->posts} 
                    WHERE ID IN ($meta_ids_str) 
                    AND post_type = 'business' 
                    AND post_status = 'publish'";
                
                $filtered_meta_ids = $wpdb->get_col($meta_post_type_query);
                $post_ids = array_merge($post_ids, $filtered_meta_ids);
            }
        }
        
        // Remove duplicates
        $post_ids = array_unique($post_ids);
    }
    
    // Debug info
    $output .= "<!-- Direct SQL search found " . count($post_ids) . " matching posts -->\n";
    
    if (!empty($post_ids)) {
        $output .= "<!-- Matching post IDs: " . implode(', ', $post_ids) . " -->\n";
    }
    
    // Create a query for the search results
    $args = array(
        'post_type' => 'business',
        'posts_per_page' => $atts['per_page'],
        'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
    );
    
    // Add post IDs if we have them from direct search
    if (!empty($post_ids)) {
        $args['post__in'] = $post_ids;
        $args['orderby'] = 'post__in';
    } elseif (!empty($search_term)) {
        // If direct search found nothing but we have a search term,
        // let WordPress try its built-in search as a fallback
        $args['s'] = $search_term;
    }
    
    // Add taxonomy queries if specified
    $tax_query = array();
    
    if (!empty($area)) {
        $tax_query[] = array(
            'taxonomy' => 'business_area',
            'field' => 'slug',
            'terms' => $area
        );
    }
    
    if (!empty($category)) {
        $tax_query[] = array(
            'taxonomy' => 'business_category',
            'field' => 'slug',
            'terms' => $category
        );
    }
    
    if (count($tax_query) > 0) {
        $args['tax_query'] = $tax_query;
        if (count($tax_query) > 1) {
            $args['tax_query']['relation'] = 'AND';
        }
    }
    
    // Debug the final query args
    $output .= "<!-- Final WP_Query args: " . json_encode($args) . " -->\n";
    
    // Run the query
    $businesses_query = new WP_Query($args);
    
    // Debug info
    $output .= "<!-- Final query found " . $businesses_query->found_posts . " posts -->\n";
    
    // Display search header
    $output .= '<div class="business-search-header">';
    $output .= '<h2 class="search-results-title">Search Results</h2>';
    
    if (!empty($search_term) || !empty($area) || !empty($category)) {
        $output .= '<p class="search-filters">';
        if (!empty($search_term)) {
            $output .= '<span class="search-term">Searching for: <strong>' . esc_html($search_term) . '</strong></span> ';
        }
        if (!empty($area)) {
            $area_term = get_term_by('slug', $area, 'business_area');
            if ($area_term) {
                $output .= '<span class="search-area">in <strong>' . esc_html($area_term->name) . '</strong></span> ';
            }
        }
        if (!empty($category)) {
            $category_term = get_term_by('slug', $category, 'business_category');
            if ($category_term) {
                $output .= '<span class="search-category">in category <strong>' . esc_html($category_term->name) . '</strong></span>';
            }
        }
        $output .= '</p>';
    }
    $output .= '</div>';
    
    // Display results
    if ($businesses_query->have_posts()) {
        $output .= '<div class="businesses ' . esc_attr($atts['info_layout']) . '-layout">';
        
        while ($businesses_query->have_posts()) {
            $businesses_query->the_post();
            $business_id = get_the_ID();
            
            // Use the existing business single view template
            ob_start();
            $template_file = LBD_PLUGIN_DIR . 'templates/business-' . $atts['info_layout'] . '-item.php';
            
            if (file_exists($template_file)) {
                include($template_file);
            } else {
                // Fallback to basic display if template doesn't exist
                ?>
                <div class="business-item">
                    <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                    <?php the_excerpt(); ?>
                </div>
                <?php
            }
            $output .= ob_get_clean();
        }
        
        $output .= '</div>';
        
        // Pagination
        $big = 999999999;
        $output .= '<div class="business-pagination">';
        $output .= paginate_links(array(
            'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
            'format' => '?paged=%#%',
            'current' => max(1, get_query_var('paged')),
            'total' => $businesses_query->max_num_pages
        ));
        $output .= '</div>';
    } else {
        $output .= '<div class="no-businesses-found">';
        $output .= '<p>No businesses found matching your search criteria.</p>';
        
        // Suggest recent businesses
        $recent_args = array(
            'post_type' => 'business',
            'posts_per_page' => 5,
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        $recent_query = new WP_Query($recent_args);
        
        if ($recent_query->have_posts()) {
            $output .= '<div class="recent-businesses">';
            $output .= '<h3>Recently Added Businesses</h3>';
            $output .= '<ul>';
            
            while ($recent_query->have_posts()) {
                $recent_query->the_post();
                $output .= '<li><a href="' . get_permalink() . '">' . get_the_title() . '</a></li>';
            }
            
            $output .= '</ul>';
            $output .= '</div>';
        }
        
        $output .= '</div>';
        wp_reset_postdata();
    }
    
    wp_reset_postdata();
    return $output;
}
add_shortcode('business_search_results', 'lbd_search_results_shortcode');
add_shortcode('lbd_search_results', 'lbd_search_results_shortcode');

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

/**
 * Shortcode for testing search functionality
 * [test_business_search term="search term"]
 */
function lbd_test_search_shortcode($atts) {
    $atts = shortcode_atts(array(
        'term' => '',
    ), $atts);
    
    if (empty($atts['term'])) {
        return '<p>Please specify a search term using the "term" attribute.</p>';
    }
    
    $search_term = sanitize_text_field($atts['term']);
    $output = "<h3>Search Test Results for: '{$search_term}'</h3>";
    
    global $wpdb;
    
    // 1. Test direct title search
    $title_sql = $wpdb->prepare(
        "SELECT ID, post_title FROM {$wpdb->posts} 
        WHERE post_type = 'business' AND post_status = 'publish' 
        AND post_title LIKE %s",
        '%' . $wpdb->esc_like($search_term) . '%'
    );
    
    $title_results = $wpdb->get_results($title_sql);
    $output .= "<h4>Title Matches (" . count($title_results) . ")</h4>";
    if (!empty($title_results)) {
        $output .= "<ul>";
        foreach ($title_results as $result) {
            $output .= "<li>ID: {$result->ID} - {$result->post_title}</li>";
        }
        $output .= "</ul>";
    } else {
        $output .= "<p>No matches in titles.</p>";
    }
    
    // 2. Test direct content search
    $content_sql = $wpdb->prepare(
        "SELECT ID, post_title FROM {$wpdb->posts} 
        WHERE post_type = 'business' AND post_status = 'publish' 
        AND post_content LIKE %s",
        '%' . $wpdb->esc_like($search_term) . '%'
    );
    
    $content_results = $wpdb->get_results($content_sql);
    $output .= "<h4>Content Matches (" . count($content_results) . ")</h4>";
    if (!empty($content_results)) {
        $output .= "<ul>";
        foreach ($content_results as $result) {
            $output .= "<li>ID: {$result->ID} - {$result->post_title}</li>";
        }
        $output .= "</ul>";
    } else {
        $output .= "<p>No matches in content.</p>";
    }
    
    // 3. Test meta value search
    $meta_sql = $wpdb->prepare(
        "SELECT p.ID, p.post_title, pm.meta_key, pm.meta_value 
        FROM {$wpdb->posts} p
        JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        WHERE p.post_type = 'business' AND p.post_status = 'publish' 
        AND pm.meta_value LIKE %s",
        '%' . $wpdb->esc_like($search_term) . '%'
    );
    
    $meta_results = $wpdb->get_results($meta_sql);
    $output .= "<h4>Meta Value Matches (" . count($meta_results) . ")</h4>";
    if (!empty($meta_results)) {
        $output .= "<ul>";
        foreach ($meta_results as $result) {
            $output .= "<li>ID: {$result->ID} - {$result->post_title} - Meta Key: {$result->meta_key} - Value: " . substr($result->meta_value, 0, 50) . "</li>";
        }
        $output .= "</ul>";
    } else {
        $output .= "<p>No matches in meta values.</p>";
    }
    
    // 4. Show all businesses for comparison
    $all_sql = "SELECT ID, post_title FROM {$wpdb->posts} 
        WHERE post_type = 'business' AND post_status = 'publish'
        LIMIT 10";
    
    $all_results = $wpdb->get_results($all_sql);
    $output .= "<h4>Sample of All Businesses (First 10)</h4>";
    if (!empty($all_results)) {
        $output .= "<ul>";
        foreach ($all_results as $result) {
            $output .= "<li>ID: {$result->ID} - {$result->post_title}</li>";
        }
        $output .= "</ul>";
    } else {
        $output .= "<p>No businesses found.</p>";
    }
    
    return $output;
}
add_shortcode('test_business_search', 'lbd_test_search_shortcode'); 
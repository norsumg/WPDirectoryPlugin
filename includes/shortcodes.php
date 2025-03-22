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

// Shortcode for search form
function lbd_search_form_shortcode($atts) {
    $atts = shortcode_atts( array(
        'layout' => 'vertical',
        'button_style' => 'default',
        'placeholder' => 'Search businesses...',
        'submit_text' => 'Search',
    ), $atts );
    
    // Get current values if any
    $search_term = isset($_GET['s']) ? esc_attr($_GET['s']) : '';
    $selected_area = isset($_GET['area']) ? esc_attr($_GET['area']) : '';
    $selected_category = isset($_GET['category']) ? esc_attr($_GET['category']) : '';
    
    ob_start();
    ?>
    <form method="get" id="business-search-form" class="business-search-form <?php echo esc_attr($atts['layout']); ?>" onsubmit="return handleSearchSubmit(this);">
        <div class="search-field">
            <input type="text" name="s" placeholder="<?php echo esc_attr($atts['placeholder']); ?>" value="<?php echo $search_term; ?>">
        </div>
        
        <div class="area-field">
            <select name="area" id="business-search-area">
                <option value="">All Areas</option>
                <?php
                $areas = get_terms( array( 'taxonomy' => 'business_area', 'hide_empty' => false ) );
                foreach ( $areas as $area ) {
                    $selected = ($selected_area === $area->slug) ? 'selected' : '';
                    echo '<option value="' . esc_attr( $area->slug ) . '" ' . $selected . '>' . esc_html( $area->name ) . '</option>';
                }
                ?>
            </select>
        </div>
        
        <div class="category-field">
            <select name="category" id="business-search-category">
                <option value="">All Categories</option>
                <?php
                $categories = get_terms( array( 'taxonomy' => 'business_category', 'hide_empty' => false ) );
                foreach ( $categories as $category ) {
                    $selected = ($selected_category === $category->slug) ? 'selected' : '';
                    echo '<option value="' . esc_attr( $category->slug ) . '" ' . $selected . '>' . esc_html( $category->name ) . '</option>';
                }
                ?>
            </select>
        </div>
        
        <button type="submit" class="<?php echo esc_attr($atts['button_style']); ?>"><?php echo esc_html($atts['submit_text']); ?></button>
        
        <script>
        function handleSearchSubmit(form) {
            var searchInput = form.querySelector('input[name="s"]');
            var areaSelect = document.getElementById('business-search-area');
            var categorySelect = document.getElementById('business-search-category');
            
            var searchTerm = searchInput.value.trim();
            var selectedArea = areaSelect.value;
            var selectedCategory = categorySelect.value;
            
            // If we have no search term but have area and/or category, redirect to taxonomy page
            if (searchTerm === '') {
                // Both area and category selected
                if (selectedArea && selectedCategory) {
                    window.location.href = '<?php echo esc_js(home_url("/directory/")); ?>' + selectedArea + '/' + selectedCategory + '/';
                    return false;
                }
                // Only area selected
                else if (selectedArea) {
                    window.location.href = '<?php echo esc_js(home_url("/directory/")); ?>' + selectedArea + '/';
                    return false;
                }
                // Only category selected - check if any businesses use this category
                else if (selectedCategory) {
                    // For category-only searches, we'll go to the search page instead of categories page
                    // This allows businesses in any area to be found
                    window.location.href = '<?php echo esc_js(home_url("/directory/search/")); ?>?category=' + encodeURIComponent(selectedCategory);
                    return false;
                }
            }
            
            // With search term, go to search results
            window.location.href = '<?php echo esc_js(home_url("/directory/search/")); ?>?s=' + 
                encodeURIComponent(searchTerm) + 
                (selectedArea ? '&area=' + encodeURIComponent(selectedArea) : '') +
                (selectedCategory ? '&category=' + encodeURIComponent(selectedCategory) : '');
            return false;
        }
        </script>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode( 'business_search_form', 'lbd_search_form_shortcode' );

/**
 * Shortcode for search results
 * [business_search_results]
 */
function lbd_search_results_shortcode() {
    // Early exit if not a search page or has no search params
    if (!isset($_GET['s']) && !isset($_GET['area']) && !isset($_GET['category'])) {
        return '<p>Use the search form to find businesses.</p>';
    }
    
    $args = array(
        'post_type' => 'business',
        'posts_per_page' => 20,
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
    if (isset($_GET['s']) && !empty($_GET['s'])) {
        $search_term = sanitize_text_field($_GET['s']);
        
        // Main search query
        $args['s'] = $search_term;
        
        // Enhanced search: Look in meta fields too for the search term
        add_filter('posts_join', 'lbd_search_join');
        add_filter('posts_where', function($where) use ($search_term) {
            global $wpdb;
            // Search in all post meta for broader matches
            $where .= $wpdb->prepare(
                " OR ($wpdb->postmeta.meta_value LIKE %s) ",
                '%' . $wpdb->esc_like($search_term) . '%'
            );
            return $where;
        });
        add_filter('posts_distinct', function($distinct) {
            return "DISTINCT";
        });
    }

    // Category filter
    if (isset($_GET['category']) && !empty($_GET['category'])) {
        $args['tax_query'][] = array(
            'taxonomy' => 'business_category',
            'field' => 'slug',
            'terms' => sanitize_text_field($_GET['category']),
        );
    }
    
    // Area filter
    if (isset($_GET['area']) && !empty($_GET['area'])) {
        $args['tax_query'][] = array(
            'taxonomy' => 'business_area',
            'field' => 'slug',
            'terms' => sanitize_text_field($_GET['area']),
        );
    }

    // Add tax_query relation if we have multiple conditions
    if (isset($args['tax_query']) && count($args['tax_query']) > 1) {
        $args['tax_query']['relation'] = 'AND';
    }

    $query = new WP_Query($args);
    
    // Clean up filters to avoid affecting other queries
    if (isset($_GET['s']) && !empty($_GET['s'])) {
        remove_all_filters('posts_join');
        remove_all_filters('posts_where');
        remove_all_filters('posts_distinct');
    }
    
    ob_start();
    
    // Build the search description
    $search_description = array();
    if (isset($_GET['s']) && !empty($_GET['s'])) {
        $search_description[] = '"' . esc_html($_GET['s']) . '"';
    }
    
    $category_name = '';
    if (isset($_GET['category']) && !empty($_GET['category'])) {
        $category = get_term_by('slug', sanitize_text_field($_GET['category']), 'business_category');
        if ($category) {
            $category_name = $category->name;
            $search_description[] = 'in category "' . esc_html($category_name) . '"';
        }
    }
    
    $area_name = '';
    if (isset($_GET['area']) && !empty($_GET['area'])) {
        $area = get_term_by('slug', sanitize_text_field($_GET['area']), 'business_area');
        if ($area) {
            $area_name = $area->name;
            $search_description[] = 'in ' . esc_html($area_name);
        }
    }
    
    $search_string = !empty($search_description) ? implode(' ', $search_description) : 'all businesses';

    ?>
    <div class="business-search-results">
        <h2>Search Results: <?php echo $search_string; ?></h2>
        
        <?php if ($area_name || $category_name): ?>
        <div class="search-filters">
            <?php if ($area_name): ?>
            <div class="filter-item">
                <strong>Area:</strong> <?php echo esc_html($area_name); ?>
                <a href="<?php echo esc_url(remove_query_arg('area')); ?>" class="remove-filter" title="Remove area filter">×</a>
            </div>
            <?php endif; ?>
            
            <?php if ($category_name): ?>
            <div class="filter-item">
                <strong>Category:</strong> <?php echo esc_html($category_name); ?>
                <a href="<?php echo esc_url(remove_query_arg('category')); ?>" class="remove-filter" title="Remove category filter">×</a>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($query->have_posts()): ?>
            <p class="result-count">Found <?php echo $query->found_posts; ?> business<?php echo $query->found_posts !== 1 ? 'es' : ''; ?>.</p>
            
            <div class="business-list">
            <?php while ($query->have_posts()): $query->the_post(); ?>
                <div class="business-card">
                    <div class="business-card-inner">
                        <?php if (has_post_thumbnail()): ?>
                        <div class="business-thumbnail">
                            <a href="<?php the_permalink(); ?>">
                                <?php the_post_thumbnail('medium'); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <div class="business-details">
                            <h3 class="business-title">
                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                <?php if (get_post_meta(get_the_ID(), 'lbd_premium', true)): ?>
                                <span class="premium-badge">Premium</span>
                                <?php endif; ?>
                            </h3>
                            
                            <?php 
                            // Display area and category
                            $areas = get_the_terms(get_the_ID(), 'business_area');
                            $categories = get_the_terms(get_the_ID(), 'business_category');
                            
                            echo '<div class="business-meta">';
                            if ($areas && !is_wp_error($areas)) {
                                echo '<span class="business-area">Area: <a href="' . get_term_link($areas[0]) . '">' . esc_html($areas[0]->name) . '</a></span>';
                            }
                            
                            if ($categories && !is_wp_error($categories)) {
                                echo '<span class="business-category"> | Category: ';
                                $cat_links = array();
                                foreach ($categories as $category) {
                                    $cat_links[] = '<a href="' . get_term_link($category) . '">' . esc_html($category->name) . '</a>';
                                }
                                echo implode(', ', $cat_links);
                                echo '</span>';
                            }
                            echo '</div>';
                            ?>
                            
                            <div class="business-excerpt">
                                <?php the_excerpt(); ?>
                            </div>
                            
                            <a href="<?php the_permalink(); ?>" class="view-business">View Business</a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
            </div>
            
            <?php 
            // Pagination
            $big = 999999999;
            echo '<div class="business-pagination">';
            echo paginate_links(array(
                'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                'format' => '?paged=%#%',
                'current' => max(1, get_query_var('paged')),
                'total' => $query->max_num_pages,
                'prev_text' => '&laquo; Previous',
                'next_text' => 'Next &raquo;',
            ));
            echo '</div>';
            ?>
            
        <?php else: ?>
            <p class="no-results">No businesses found matching your search. Please try different search terms or browse all businesses.</p>
            
            <div class="search-suggestions">
                <h3>Suggestions:</h3>
                <ul>
                    <li>Check your spelling</li>
                    <li>Try more general keywords</li>
                    <li>Try different keywords</li>
                    <li><a href="<?php echo esc_url(home_url('/directory/')); ?>">Browse all business areas</a></li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
    <?php
    
    wp_reset_postdata();
    return ob_get_clean();
}
add_shortcode( 'business_search_results', 'lbd_search_results_shortcode' );

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

// Add search join function
function lbd_search_join($join) {
    global $wpdb;
    if (!strpos($join, "LEFT JOIN $wpdb->postmeta ON")) {
        $join .= " LEFT JOIN $wpdb->postmeta ON ($wpdb->posts.ID = $wpdb->postmeta.post_id) ";
    }
    return $join;
}

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
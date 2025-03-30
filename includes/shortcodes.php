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
 * [business_search_form layout="horizontal" button_style="pill" placeholder="Find businesses..."]
 * Consolidated search form shortcode (combines functionality from both previous implementations)
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
                            // Use cached terms if available
                            $areas = function_exists('lbd_get_cached_terms') 
                                ? lbd_get_cached_terms('business_area')
                                : get_terms(array(
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
                            // Use cached terms if available
                            $categories = function_exists('lbd_get_cached_terms') 
                                ? lbd_get_cached_terms('business_category')
                                : get_terms(array(
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
    
    <?php
    
    return ob_get_clean();
}
// Register both shortcode names for backward compatibility
add_shortcode('business_search_form', 'lbd_search_form_shortcode');
add_shortcode('business_search', 'lbd_search_form_shortcode');

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
    
    // Process the form submission if available
    if (function_exists('lbd_process_review_submission')) {
        $result = lbd_process_review_submission($business_id);
        
        if (function_exists('lbd_get_review_form_html')) {
            echo lbd_get_review_form_html($business_id, $result);
        } else {
            // Fallback if the HTML generation function is not available
            if ($result['form_submitted'] && $result['success']) {
                echo '<p>Thank you for your review! It has been submitted for approval.</p>';
            } else {
                echo '<p>Error: Review form HTML generator is not available.</p>';
            }
        }
    } else {
        // Fall back to legacy form (this should not happen if plugins are properly loaded)
        echo '<p>Review submission system is not properly loaded. Please contact the administrator.</p>';
    }
    
    return ob_get_clean();
}
add_shortcode('business_review_form', 'lbd_review_form_shortcode');

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
    // Styles moved to assets/css/directory.css
}
// Function is now empty but kept for backwards compatibility
add_action('wp_head', 'lbd_add_star_rating_styles'); 
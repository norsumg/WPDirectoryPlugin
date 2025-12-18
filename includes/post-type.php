<?php
function lbd_register_post_type() {
    register_post_type( 'business', array(
        'labels' => array(
            'name' => 'Businesses',
            'singular_name' => 'Business',
        ),
        'public' => true,
        'supports' => array( 'title', 'editor', 'thumbnail', 'comments' ),
        'rewrite' => array( 'slug' => 'directory/%business_area%/%business_category%', 'with_front' => false ),
        'has_archive' => true,
    ) );
}
add_action( 'init', 'lbd_register_post_type' );

function lbd_register_taxonomy() {
    // Business Category taxonomy
    register_taxonomy( 'business_category', 'business', array(
        'hierarchical' => true,
        'labels' => array(
            'name' => 'Business Categories',
            'singular_name' => 'Business Category',
        ),
        'rewrite' => array( 'slug' => 'directory/categories', 'with_front' => false ),
        'query_var' => true,
    ) );
    
    // Business Area taxonomy
    register_taxonomy( 'business_area', 'business', array(
        'hierarchical' => true,
        'labels' => array(
            'name' => 'Business Areas',
            'singular_name' => 'Business Area',
        ),
        'rewrite' => array( 'slug' => 'directory/area', 'with_front' => false ),
        'query_var' => true,
    ) );
}
add_action( 'init', 'lbd_register_taxonomy' );

// Add custom rewrite rules for business areas
function lbd_add_rewrite_rules() {
    // Check to make sure the business area taxonomy exists
    if (!taxonomy_exists('business_area')) {
        return;
    }
    
    // Generic rewrite rules that will match any slug
    // Validation of actual term slugs will happen in pre_get_posts
    
    // Rewrite for category-only pages - with directory namespace (MUST COME FIRST)
    add_rewrite_rule(
        '^directory/categories/([^/]+)/?$',
        'index.php?business_category=$matches[1]',
        'top'
    );
    
    // Rewrite for area-specific category pages - with directory namespace
    add_rewrite_rule(
        '^directory/([^/]+)/([^/]+)/?$',
        'index.php?business_area=$matches[1]&business_category=$matches[2]',
        'top'
    );
    
    // Rewrite for area pages - with directory namespace (LEAST SPECIFIC)
    add_rewrite_rule(
        '^directory/([^/]+)/?$',
        'index.php?business_area=$matches[1]',
        'top'
    );
    
    // Add rewrite tags
    add_rewrite_tag('%business_area%', '([^/]+)');
    add_rewrite_tag('%business_category%', '([^/]+)');
    
    // NOTE: Do NOT flush rewrite rules here - this function runs on every page load.
    // Flushing should only happen on plugin activation or when explicitly requested.
    // The old code was flushing on every page load which caused image upload corruption.
}
add_action('init', 'lbd_add_rewrite_rules');

/**
 * Validate business area and category slugs in the query
 * This runs after the rewrite rules have been processed
 */
function lbd_validate_taxonomy_slugs($query) {
    // Only run on the main query and if we're not in the admin
    if (!$query->is_main_query() || is_admin()) {
        return;
    }
    
    // Get the area slug from the query
    $area_slug = $query->get('business_area');
    $category_slug = $query->get('business_category');
    
    // Debug logging for troubleshooting
    if (current_user_can('manage_options') && (isset($_GET['lbd_debug']) || WP_DEBUG)) {
        error_log("LBD Debug - Area slug: " . ($area_slug ?: 'none') . ", Category slug: " . ($category_slug ?: 'none'));
    }
    
    // If we have an area slug, validate it
    if ($area_slug) {
        $area_term = get_term_by('slug', $area_slug, 'business_area');
        if (!$area_term || is_wp_error($area_term)) {
            if (current_user_can('manage_options') && (isset($_GET['lbd_debug']) || WP_DEBUG)) {
                error_log("LBD Debug - Invalid area slug: " . $area_slug);
            }
            $query->set_404();
            return;
        }
    }
    
    // If we have a category slug, validate it
    if ($category_slug) {
        $category_term = get_term_by('slug', $category_slug, 'business_category');
        if (!$category_term || is_wp_error($category_term)) {
            if (current_user_can('manage_options') && (isset($_GET['lbd_debug']) || WP_DEBUG)) {
                error_log("LBD Debug - Invalid category slug: " . $category_slug);
            }
            $query->set_404();
            return;
        }
    }
}
add_action('pre_get_posts', 'lbd_validate_taxonomy_slugs');

// Filter to modify permalinks to include area and category
function lbd_business_permalink( $permalink, $post, $leavename ) {
    if ( $post->post_type !== 'business' ) {
        return $permalink;
    }
    
    // Get business area
    $areas = get_the_terms( $post->ID, 'business_area' );
    if ( $areas && ! is_wp_error( $areas ) ) {
        $area_slug = $areas[0]->slug;
    } else {
        $area_slug = 'uncategorized';
    }
    
    // Get business category
    $categories = get_the_terms( $post->ID, 'business_category' );
    if ( $categories && ! is_wp_error( $categories ) ) {
        $category_slug = $categories[0]->slug;
    } else {
        $category_slug = 'uncategorized';
    }
    
    $permalink = str_replace( '%business_area%', $area_slug, $permalink );
    $permalink = str_replace( '%business_category%', $category_slug, $permalink );
    
    return $permalink;
}
add_filter( 'post_type_link', 'lbd_business_permalink', 10, 3 );

// Custom permalink for business areas
function lbd_business_area_permalink($permalink, $term, $taxonomy) {
    if ($taxonomy !== 'business_area') {
        return $permalink;
    }
    
    // Use directory namespace for area URLs
    return home_url('/directory/' . $term->slug . '/');
}
add_filter('term_link', 'lbd_business_area_permalink', 10, 3);

// Custom permalink for business categories
function lbd_business_category_permalink($permalink, $term, $taxonomy) {
    if ($taxonomy !== 'business_category') {
        return $permalink;
    }
    
    // Get current area context
    $current_area = null;
    
    // If we're on a business area page
    if (is_tax('business_area')) {
        $current_area = get_queried_object();
    } 
    // If we're on a single business page
    elseif (is_singular('business')) {
        $areas = get_the_terms(get_the_ID(), 'business_area');
        if ($areas && !is_wp_error($areas)) {
            $current_area = $areas[0];
        }
    }
    
    // If we have an area context, use it in the permalink with directory namespace
    if ($current_area) {
        return home_url('/directory/' . $current_area->slug . '/' . $term->slug . '/');
    }
    
    // Default: keep original permalink (should already include directory/categories/ from registration)
    return $permalink;
}
add_filter('term_link', 'lbd_business_category_permalink', 10, 3); 
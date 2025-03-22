<?php
function lbd_register_post_type() {
    register_post_type( 'business', array(
        'labels' => array(
            'name' => 'Businesses',
            'singular_name' => 'Business',
        ),
        'public' => true,
        'supports' => array( 'title', 'editor', 'thumbnail', 'comments' ),
        'rewrite' => array( 'slug' => '%business_area%/%business_category%', 'with_front' => false ),
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
        'rewrite' => array( 'slug' => 'directory', 'with_front' => false ),
    ) );
    
    // Business Area taxonomy
    register_taxonomy( 'business_area', 'business', array(
        'hierarchical' => true,
        'labels' => array(
            'name' => 'Business Areas',
            'singular_name' => 'Business Area',
        ),
        'rewrite' => array( 'slug' => 'area', 'with_front' => false ),
        'query_var' => true,
    ) );
}
add_action( 'init', 'lbd_register_taxonomy' );

// Add custom rewrite rules for business areas
function lbd_add_rewrite_rules() {
    // List of known WordPress pages/paths that should be excluded from business area rules
    $excluded_paths = array(
        'submit-review',    // Review submission page
        'wp-admin',         // Admin area
        'wp-content',       // Content directory
        'wp-includes',      // Includes directory
        'sitemap',          // Sitemap
        'feed',             // RSS feeds
        'wp-json',          // REST API
    );
    
    // Get any published WordPress pages to exclude their slugs
    $wp_pages = get_posts(array(
        'post_type' => 'page',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
    ));
    
    // Get their slugs
    foreach ($wp_pages as $page_id) {
        $excluded_paths[] = get_post_field('post_name', $page_id);
    }
    
    // Get all business areas for specific matching
    $business_areas = get_terms(array(
        'taxonomy' => 'business_area',
        'hide_empty' => false,
        'fields' => 'slugs',
    ));

    if (!empty($business_areas) && !is_wp_error($business_areas)) {
        // Filter out any business areas that match excluded paths
        $business_areas = array_diff($business_areas, $excluded_paths);
        
        if (!empty($business_areas)) {
            // Create regex pattern from area slugs: (area1|area2|area3)
            $areas_pattern = '(' . implode('|', array_map('preg_quote', $business_areas)) . ')';
    
            // Rewrite for area pages - only match known business areas
            add_rewrite_rule(
                '^' . $areas_pattern . '/?$',
                'index.php?business_area=$matches[1]',
                'top'
            );
            
            // Rewrite for area-specific category pages - only for known areas
            add_rewrite_rule(
                '^' . $areas_pattern . '/([^/]+)/?$',
                'index.php?business_area=$matches[1]&business_category=$matches[2]',
                'top'
            );
        }
    }
    
    // Add rewrite tags
    add_rewrite_tag('%business_area%', '([^/]+)');
    add_rewrite_tag('%business_category%', '([^/]+)');
}
add_action('init', 'lbd_add_rewrite_rules');

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
    
    // Replace /area/term-slug/ with just /term-slug/
    return home_url('/' . $term->slug . '/');
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
    
    // If we have an area context, use it in the permalink
    if ($current_area) {
        return home_url('/' . $current_area->slug . '/' . $term->slug . '/');
    }
    
    // Default: keep original permalink
    return $permalink;
}
add_filter('term_link', 'lbd_business_category_permalink', 10, 3); 
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
        'rewrite' => array( 'slug' => '', 'with_front' => false ),
    ) );
}
add_action( 'init', 'lbd_register_taxonomy' );

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
<?php
function lbd_single_template( $template ) {
    if ( is_singular( 'business' ) ) {
        $plugin_template = plugin_dir_path( __FILE__ ) . '../templates/single-business.php';
        if ( file_exists( $plugin_template ) ) {
            return $plugin_template;
        }
    }
    return $template;
}
add_filter( 'single_template', 'lbd_single_template' );

function lbd_taxonomy_template( $template ) {
    if ( is_tax( 'business_category' ) || is_tax( 'business_area' ) ) {
        $term = get_queried_object();
        $taxonomy = $term->taxonomy;
        
        $plugin_template = plugin_dir_path( __FILE__ ) . '../templates/taxonomy-' . $taxonomy . '.php';
        if ( file_exists( $plugin_template ) ) {
            return $plugin_template;
        }
        
        // Fallback to generic taxonomy template
        $generic_template = plugin_dir_path( __FILE__ ) . '../templates/taxonomy.php';
        if ( file_exists( $generic_template ) ) {
            return $generic_template;
        }
    }
    return $template;
}
add_filter( 'taxonomy_template', 'lbd_taxonomy_template' );

// Pre-get posts filter to handle both business areas and categories
function lbd_pre_get_posts( $query ) {
    // Only target the main query & not admin
    if ( !$query->is_main_query() || is_admin() ) {
        return;
    }
    
    // Handle category taxonomy pages
    if ( $query->is_tax( 'business_category' ) ) {
        $query->set( 'post_type', 'business' );
        return;
    }
    
    // Handle area taxonomy with custom permalink structure
    if ( !is_admin() && $query->is_main_query() && isset( $query->query['pagename'] ) ) {
        $slug = $query->query['pagename'];
        
        // Check if this matches a business area term
        $term = get_term_by( 'slug', $slug, 'business_area' );
        
        if ( $term ) {
            // Set the query to show the business area archive
            $query->is_home = false;
            $query->is_tax = true;
            $query->is_archive = true;
            $query->is_page = false;
            $query->set( 'business_area', $slug );
            $query->set( 'post_type', 'business' );
            $query->set( 'pagename', '' );
            
            // Set queried object to the term
            $query->queried_object = $term;
            $query->queried_object_id = $term->term_id;
        }
    }
}
add_action( 'pre_get_posts', 'lbd_pre_get_posts' );

function lbd_get_template_part( $slug, $name = null ) {
    $template = '';
    $base = $slug . ( $name ? '-' . $name : '' ) . '.php';
    $theme_template = locate_template( $base );
    $plugin_template = plugin_dir_path( __FILE__ ) . '../templates/' . $base;

    $template = $theme_template ? $theme_template : ( file_exists( $plugin_template ) ? $plugin_template : '' );

    if ( $template ) {
        load_template( $template, false );
    }
} 
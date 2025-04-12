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

// Pre-get posts filter to ensure the correct post type is set for taxonomy archives
function lbd_pre_get_posts( $query ) {
    // Only target the main query & not admin
    if ( !$query->is_main_query() || is_admin() ) {
        return;
    }
    
    // Set post type for business taxonomy pages
    if ( $query->is_tax( 'business_area' ) || $query->is_tax( 'business_category' ) ) {
        $query->set( 'post_type', 'business' );
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

/**
 * Modify the pagination display to add per-page options
 */
function lbd_add_pagination_options() {
    // Only add to relevant pages
    if (!is_tax('business_category') && !is_tax('business_area') && !is_post_type_archive('business')) {
        return;
    }
    
    // Display pagination options before standard pagination
    echo '<div class="lbd-pagination-wrapper">';
    lbd_pagination_options();
    echo '</div>';
}
add_action('lbd_before_pagination', 'lbd_add_pagination_options'); 
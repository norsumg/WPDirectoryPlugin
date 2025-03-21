<?php
// Shortcode for displaying specific categories
function lbd_custom_categories_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'ids' => '',
    ), $atts, 'custom_categories' );

    $terms = get_terms( array(
        'taxonomy' => 'business_category',
        'include' => ! empty( $atts['ids'] ) ? explode( ',', $atts['ids'] ) : array(),
        'hide_empty' => false,
    ) );

    if ( empty( $terms ) || is_wp_error( $terms ) ) {
        return '<p>No categories found.</p>';
    }

    $output = '<ul class="business-categories">';
    foreach ( $terms as $term ) {
        $output .= '<li><a href="' . get_term_link( $term ) . '">' . esc_html( $term->name ) . '</a></li>';
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
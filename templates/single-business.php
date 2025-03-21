<?php get_header(); ?>
<div class="business-profile">
    <?php while ( have_posts() ) : the_post(); ?>
        <h1><?php the_title(); ?></h1>
        
        <div class="business-taxonomy-links">
            <?php 
            // Display area and category links
            $areas = get_the_terms( get_the_ID(), 'business_area' );
            $categories = get_the_terms( get_the_ID(), 'business_category' );
            
            if ( $areas && !is_wp_error( $areas ) ) {
                echo '<span class="business-area-link"><strong>Area:</strong> <a href="' . get_term_link( $areas[0] ) . '">' . esc_html( $areas[0]->name ) . '</a></span>';
            }
            
            if ( $categories && !is_wp_error( $categories ) ) {
                echo ' <span class="business-category-link"><strong>Category:</strong> ' . get_the_term_list( get_the_ID(), 'business_category', '', ', ' ) . '</span>';
            }
            ?>
        </div>
        
        <?php if ( has_post_thumbnail() ) : ?>
            <div class="business-featured-image">
                <?php the_post_thumbnail( 'large' ); ?>
            </div>
        <?php endif; ?>
        
        <div class="business-details">
            <p><strong>Phone:</strong> <?php echo esc_html( get_post_meta( get_the_ID(), 'lbd_phone', true ) ); ?></p>
            <p><strong>Address:</strong> <?php echo esc_html( get_post_meta( get_the_ID(), 'lbd_address', true ) ); ?></p>
            <p><strong>Website:</strong> <a href="<?php echo esc_url( get_post_meta( get_the_ID(), 'lbd_website', true ) ); ?>"><?php echo esc_html( get_post_meta( get_the_ID(), 'lbd_website', true ) ); ?></a></p>
        </div>
        
        <div class="business-description">
            <?php the_content(); ?>
        </div>
        
        <div class="business-reviews">
            <h3>Reviews</h3>
            <?php comments_template(); ?>
        </div>
    <?php endwhile; ?>
</div>
<?php get_footer(); ?> 
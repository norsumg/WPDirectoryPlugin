<?php get_header(); ?>
<div class="business-area">
    <h1><?php single_term_title(); ?> Businesses</h1>
    
    <?php if ( term_description() ) : ?>
        <div class="term-description">
            <?php echo term_description(); ?>
        </div>
    <?php endif; ?>
    
    <?php
    // Get all categories in this area
    $categories = get_terms(array(
        'taxonomy' => 'business_category',
        'hide_empty' => true,
    ));
    
    if ( !empty($categories) ) : 
    ?>
        <div class="area-categories">
            <h2>Browse by Category</h2>
            <ul class="business-categories">
                <?php foreach ( $categories as $category ) : ?>
                    <li>
                        <a href="<?php echo get_term_link( $category ); ?>">
                            <?php echo esc_html( $category->name ); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <div class="business-listing">
        <h2>All Businesses in <?php single_term_title(); ?></h2>
        <?php if ( have_posts() ) : ?>
            <div class="business-grid">
                <?php while ( have_posts() ) : the_post(); ?>
                    <?php lbd_get_template_part( 'content', 'business' ); ?>
                <?php endwhile; ?>
            </div>
            
            <?php if ( function_exists('wp_pagenavi') ) : ?>
                <?php wp_pagenavi(); ?>
            <?php else : ?>
                <div class="pagination">
                    <?php echo paginate_links(); ?>
                </div>
            <?php endif; ?>
            
        <?php else : ?>
            <p class="no-businesses">No businesses found in this area. Check back soon or <a href="<?php echo home_url(); ?>">browse other areas</a>.</p>
        <?php endif; ?>
    </div>
</div>
<?php get_footer(); ?> 
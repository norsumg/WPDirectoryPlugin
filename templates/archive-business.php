<?php get_header(); ?>
<div class="business-archive">
    <h1>Business Directory</h1>
    
    <div class="business-listing">
        <?php if ( have_posts() ) : ?>
            <div class="business-grid">
                <?php while ( have_posts() ) : the_post(); ?>
                    <?php lbd_get_template_part( 'content', 'business' ); ?>
                <?php endwhile; ?>
            </div>
            
            <?php 
            // Add pagination options before the pagination
            do_action('lbd_before_pagination');
            
            if ( function_exists('wp_pagenavi') ) : 
                wp_pagenavi();
            else : ?>
                <div class="pagination">
                    <?php echo paginate_links(); ?>
                </div>
            <?php endif; ?>
            
        <?php else : ?>
            <p>No businesses found. Please check back soon.</p>
        <?php endif; ?>
    </div>
</div>
<?php get_footer(); ?> 
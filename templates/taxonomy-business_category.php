<?php get_header(); ?>
<div class="business-category">
    <h1><?php single_term_title(); ?></h1>
    
    <?php if ( term_description() ) : ?>
        <div class="term-description">
            <?php echo term_description(); ?>
        </div>
    <?php endif; ?>

    <div class="business-listing">
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
            <p class="no-businesses">No businesses found in this category. Check back soon or <a href="<?php echo home_url('/directory/'); ?>">browse other categories</a>.</p>
        <?php endif; ?>
    </div>
</div>
<?php get_footer(); ?> 
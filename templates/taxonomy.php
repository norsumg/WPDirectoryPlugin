<?php get_header(); ?>
<div class="business-taxonomy">
    <h1><?php single_term_title(); ?></h1>
    
    <?php if ( term_description() ) : ?>
        <div class="term-description">
            <?php echo term_description(); ?>
        </div>
    <?php endif; ?>
    
    <div class="business-listing">
        <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
            <?php lbd_get_template_part( 'content', 'business' ); ?>
        <?php endwhile; else: ?>
            <p>No businesses found.</p>
        <?php endif; ?>
    </div>
</div>
<?php get_footer(); ?> 
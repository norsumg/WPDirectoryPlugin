<?php get_header(); ?>
<h1><?php single_term_title(); ?></h1>
<div class="business-listing">
    <?php while ( have_posts() ) : the_post(); ?>
        <?php lbd_get_template_part( 'content', 'business' ); ?>
    <?php endwhile; ?>
</div>
<?php get_footer(); ?> 
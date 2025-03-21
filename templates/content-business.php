<div class="business-item">
    <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
    <?php if ( has_post_thumbnail() ) : ?>
        <?php the_post_thumbnail( 'thumbnail' ); ?>
    <?php endif; ?>
    <p><?php the_excerpt(); ?></p>
</div> 
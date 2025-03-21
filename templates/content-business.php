<div class="business-item">
    <?php 
    $is_premium = get_post_meta(get_the_ID(), 'business_premium', true);
    $phone = get_post_meta(get_the_ID(), 'business_phone', true);
    $website = get_post_meta(get_the_ID(), 'business_website', true);
    ?>
    
    <?php if ($is_premium) : ?>
    <span class="premium-label">Premium</span>
    <?php endif; ?>
    
    <?php if (has_post_thumbnail()) : ?>
    <div class="business-thumbnail">
        <a href="<?php the_permalink(); ?>">
            <?php the_post_thumbnail('medium'); ?>
        </a>
    </div>
    <?php endif; ?>
    
    <h3 class="business-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
    
    <div class="business-excerpt">
        <?php echo wp_trim_words(get_the_excerpt(), 20); ?>
    </div>
    
    <?php if ($phone) : ?>
    <div class="business-contact">
        <span class="phone-label">Phone:</span> <?php echo esc_html($phone); ?>
    </div>
    <?php endif; ?>
    
    <div class="business-link">
        <a href="<?php the_permalink(); ?>" class="view-details">View Details</a>
        <?php if ($website) : ?>
        <a href="<?php echo esc_url($website); ?>" class="visit-website" target="_blank">Visit Website</a>
        <?php endif; ?>
    </div>
</div> 
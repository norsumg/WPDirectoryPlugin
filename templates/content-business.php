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
    
    <?php 
    // Display business attributes if any are set
    $black_owned = get_post_meta(get_the_ID(), 'lbd_black_owned', true);
    $women_owned = get_post_meta(get_the_ID(), 'lbd_women_owned', true);
    $lgbtq_friendly = get_post_meta(get_the_ID(), 'lbd_lgbtq_friendly', true);

    if ($black_owned || $women_owned || $lgbtq_friendly) : ?>
        <div class="business-attributes-small">
            <?php if ($black_owned) : ?>
                <span class="attribute-badge black-owned" title="Black Owned">B</span>
            <?php endif; ?>
            
            <?php if ($women_owned) : ?>
                <span class="attribute-badge women-owned" title="Women Owned">W</span>
            <?php endif; ?>
            
            <?php if ($lgbtq_friendly) : ?>
                <span class="attribute-badge lgbtq-friendly" title="LGBTQ+ Friendly">L</span>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
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
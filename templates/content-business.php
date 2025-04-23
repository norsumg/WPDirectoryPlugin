<div class="business-item">
    <?php 
    $is_premium = get_post_meta(get_the_ID(), 'business_premium', true);
    $phone = get_post_meta(get_the_ID(), 'business_phone', true);
    $website = get_post_meta(get_the_ID(), 'business_website', true);
    
    // Get review data
    $review_average = get_post_meta(get_the_ID(), 'lbd_review_average', true);
    $review_count = get_post_meta(get_the_ID(), 'lbd_review_count', true);
    
    // If no native reviews, check for Google reviews as fallback
    $review_source = 'Native';
    if (empty($review_average)) {
        // Look for various possible Google review field names
        $google_rating = get_post_meta(get_the_ID(), 'google_rating', true);
        if (empty($google_rating)) {
            $google_rating = get_post_meta(get_the_ID(), 'lbd_google_rating', true);
        }
        
        $google_review_count = get_post_meta(get_the_ID(), 'google_review_count', true);
        if (empty($google_review_count)) {
            $google_review_count = get_post_meta(get_the_ID(), 'lbd_google_review_count', true);
        }
        
        // If we found Google reviews, use them
        if (!empty($google_rating)) {
            $review_average = $google_rating;
            $review_count = $google_review_count;
            $review_source = 'Google';
        }
    }
    
    // Get categories
    $categories = get_the_terms(get_the_ID(), 'business_category');
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
    
    <?php 
    // Display star rating if available
    if (!empty($review_average) && function_exists('lbd_get_star_rating_html')) {
        echo lbd_get_star_rating_html($review_average, $review_count, $review_source);
    }
    ?>
    
    <div class="business-excerpt">
        <?php echo wp_trim_words(get_the_excerpt(), 20); ?>
    </div>
    
    <?php if (!empty($categories) && !is_wp_error($categories)) : ?>
    <div class="business-categories-list">
        <?php foreach($categories as $category) : ?>
        <span><?php echo esc_html($category->name); ?></span>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    
    <?php 
    // Display business attributes if any are set
    $black_owned = get_post_meta(get_the_ID(), 'lbd_black_owned', true);
    $women_owned = get_post_meta(get_the_ID(), 'lbd_women_owned', true);
    $lgbtq_friendly = get_post_meta(get_the_ID(), 'lbd_lgbtq_friendly', true);

    if ($black_owned || $women_owned || $lgbtq_friendly) : ?>
        <div class="business-attributes-small">
            <?php if ($black_owned) : ?>
                <span class="attribute-badge black-owned" title="Black Owned">✓</span>
            <?php endif; ?>
            
            <?php if ($women_owned) : ?>
                <span class="attribute-badge women-owned" title="Women Owned">✓</span>
            <?php endif; ?>
            
            <?php if ($lgbtq_friendly) : ?>
                <span class="attribute-badge lgbtq-friendly" title="LGBTQ+ Friendly">✓</span>
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
        <?php if ($website) : 
            // Add UTM parameters to external links
            $utm_website = add_query_arg('utm_source', 'kentlocal', $website);
        ?>
        <a href="<?php echo esc_url($utm_website); ?>" class="visit-website" target="_blank" rel="nofollow">Visit Website</a>
        <?php endif; ?>
    </div>
</div> 
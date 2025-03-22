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
                echo ' <span class="business-category-link"><strong>Category:</strong> ';
                
                // If we have an area, create area-specific category links
                if ($areas && !is_wp_error($areas)) {
                    $links = array();
                    foreach ($categories as $category) {
                        $area_category_link = home_url('/' . $areas[0]->slug . '/' . $category->slug . '/');
                        $links[] = '<a href="' . esc_url($area_category_link) . '">' . esc_html($category->name) . '</a>';
                    }
                    echo implode(', ', $links);
                } else {
                    // Default to regular category links
                    echo get_the_term_list( get_the_ID(), 'business_category', '', ', ' );
                }
                
                echo '</span>';
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
            
            <?php 
            $email = get_post_meta( get_the_ID(), 'lbd_email', true );
            if ($email) : ?>
                <p><strong>Email:</strong> <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a></p>
            <?php endif; ?>
            
            <p><strong>Website:</strong> <a href="<?php echo esc_url( get_post_meta( get_the_ID(), 'lbd_website', true ) ); ?>" target="_blank"><?php echo esc_html( get_post_meta( get_the_ID(), 'lbd_website', true ) ); ?></a></p>
            
            <?php 
            // Social Media
            $facebook = get_post_meta( get_the_ID(), 'lbd_facebook', true );
            $instagram = get_post_meta( get_the_ID(), 'lbd_instagram', true );
            
            if ($facebook || $instagram) : ?>
            <div class="business-social">
                <p><strong>Follow us:</strong></p>
                <div class="social-links">
                    <?php if ($facebook) : ?>
                        <a href="<?php echo esc_url($facebook); ?>" class="social-link facebook" target="_blank" title="Facebook">
                            <span class="social-icon">f</span>
                        </a>
                    <?php endif; ?>
                    
                    <?php if ($instagram) : ?>
                        <a href="https://instagram.com/<?php echo esc_attr($instagram); ?>" class="social-link instagram" target="_blank" title="Instagram">
                            <span class="social-icon">ig</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php
            // Display business attributes if any are set
            $black_owned = get_post_meta(get_the_ID(), 'lbd_black_owned', true);
            $women_owned = get_post_meta(get_the_ID(), 'lbd_women_owned', true);
            $lgbtq_friendly = get_post_meta(get_the_ID(), 'lbd_lgbtq_friendly', true);
            
            if ($black_owned || $women_owned || $lgbtq_friendly) {
                echo '<div class="business-attributes">';
                
                if ($black_owned) {
                    echo '<div class="attribute-item black-owned"><span class="attribute-icon">✓</span> Black Owned</div>';
                }
                
                if ($women_owned) {
                    echo '<div class="attribute-item women-owned"><span class="attribute-icon">✓</span> Women Owned</div>';
                }
                
                if ($lgbtq_friendly) {
                    echo '<div class="attribute-item lgbtq-friendly"><span class="attribute-icon">✓</span> LGBTQ+ Friendly</div>';
                }
                
                echo '</div>';
            }
            ?>
        </div>
        
        <?php
        // Opening Hours
        $is_24_hours = get_post_meta(get_the_ID(), 'lbd_hours_24', true);
        $has_hours = $is_24_hours;

        if (!$is_24_hours) {
            $days = array(
                'monday' => 'Monday',
                'tuesday' => 'Tuesday',
                'wednesday' => 'Wednesday',
                'thursday' => 'Thursday',
                'friday' => 'Friday',
                'saturday' => 'Saturday',
                'sunday' => 'Sunday'
            );

            foreach ($days as $day_id => $day_name) {
                if (get_post_meta(get_the_ID(), 'lbd_hours_' . $day_id, true)) {
                    $has_hours = true;
                    break;
                }
            }
        }

        if ($has_hours) : ?>
        <div class="business-hours">
            <h3>Opening Hours</h3>
            
            <?php if ($is_24_hours) : ?>
                <p class="hours-24"><strong>Open 24 Hours, 7 days a week</strong></p>
            <?php else : ?>
                <table class="hours-table">
                    <?php foreach ($days as $day_id => $day_name) : 
                        $hours = get_post_meta(get_the_ID(), 'lbd_hours_' . $day_id, true);
                        if (!$hours) {
                            $hours = 'Closed';
                        }
                    ?>
                        <tr>
                            <th><?php echo esc_html($day_name); ?></th>
                            <td><?php echo esc_html($hours); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <?php
        // Additional Information Section
        $payments = get_post_meta(get_the_ID(), 'lbd_payments', true);
        $parking = get_post_meta(get_the_ID(), 'lbd_parking', true);
        $amenities = get_post_meta(get_the_ID(), 'lbd_amenities', true);
        $accessibility = get_post_meta(get_the_ID(), 'lbd_accessibility', true);

        if ($payments || $parking || $amenities || $accessibility) : ?>
        <div class="business-additional-info">
            <h3>Additional Information</h3>
            
            <?php if ($payments) : ?>
                <div class="info-item">
                    <h4>Payments Accepted</h4>
                    <p><?php echo esc_html($payments); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if ($parking) : ?>
                <div class="info-item">
                    <h4>Parking</h4>
                    <p><?php echo esc_html($parking); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if ($amenities) : ?>
                <div class="info-item">
                    <h4>Amenities</h4>
                    <p><?php echo nl2br(esc_html($amenities)); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if ($accessibility) : ?>
                <div class="info-item">
                    <h4>Accessibility</h4>
                    <p><?php echo nl2br(esc_html($accessibility)); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="business-description">
            <?php the_content(); ?>
        </div>
        
        <div class="business-reviews">
            <h3>Reviews</h3>
            
            <?php
            // Get the reviews if the function exists
            if (function_exists('lbd_get_business_reviews')) {
                $reviews = lbd_get_business_reviews(get_the_ID());
                $average_rating = lbd_get_business_average_rating(get_the_ID());
                $review_count = lbd_get_business_review_count(get_the_ID());
                
                if ($average_rating) {
                    echo '<div class="review-summary">';
                    echo '<div class="average-rating">' . esc_html($average_rating) . ' / 5</div>';
                    echo '<div class="rating-stars">';
                    // Display stars based on average rating
                    $full_stars = floor($average_rating);
                    $half_star = $average_rating - $full_stars >= 0.5;
                    $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
                    
                    for ($i = 0; $i < $full_stars; $i++) {
                        echo '<span class="star full-star">★</span>';
                    }
                    
                    if ($half_star) {
                        echo '<span class="star half-star">★</span>';
                    }
                    
                    for ($i = 0; $i < $empty_stars; $i++) {
                        echo '<span class="star empty-star">☆</span>';
                    }
                    echo '</div>';
                    echo '<div class="review-count">Based on ' . esc_html($review_count) . ' reviews</div>';
                    echo '</div>';
                }
                
                if ($reviews) {
                    echo '<div class="reviews-list">';
                    foreach ($reviews as $review) {
                        echo '<div class="review-item">';
                        echo '<div class="review-header">';
                        echo '<span class="reviewer-name">' . esc_html($review->reviewer_name) . '</span>';
                        echo '<span class="review-date">' . esc_html(date('F j, Y', strtotime($review->review_date))) . '</span>';
                        echo '</div>';
                        
                        echo '<div class="review-rating">';
                        for ($i = 1; $i <= 5; $i++) {
                            echo '<span class="star ' . ($i <= $review->rating ? 'full-star' : 'empty-star') . '">';
                            echo $i <= $review->rating ? '★' : '☆';
                            echo '</span>';
                        }
                        echo '</div>';
                        
                        echo '<div class="review-text">' . esc_html($review->review_text) . '</div>';
                        
                        if ($review->source !== 'manual') {
                            echo '<div class="review-source">Source: ' . esc_html(ucfirst($review->source)) . '</div>';
                        }
                        
                        echo '</div>';
                    }
                    echo '</div>';
                } else {
                    echo '<p>No reviews yet. Be the first to leave a review!</p>';
                }
            } else {
                // Fallback to WordPress comments
                comments_template();
            }
            ?>
        </div>
    <?php endwhile; ?>
</div>
<?php get_footer(); ?> 
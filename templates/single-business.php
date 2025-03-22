<?php get_header(); ?>
<div class="business-profile">
    <?php while ( have_posts() ) : the_post(); ?>
        <!-- Cover Photo -->
        <div class="business-cover-photo">
            <?php 
            $cover_photo_id = get_post_meta(get_the_ID(), 'lbd_cover_photo', true);
            if ($cover_photo_id) {
                // Check if it's an attachment ID or direct URL
                if (is_numeric($cover_photo_id)) {
                    $cover_photo_url = wp_get_attachment_image_url($cover_photo_id, 'full');
                } else {
                    $cover_photo_url = $cover_photo_id; // It's already a URL
                }
                
                if ($cover_photo_url) {
                    echo '<div class="cover-photo-image" style="background-image: url(' . esc_url($cover_photo_url) . ');"></div>';
                } else {
                    echo '<div class="cover-photo-placeholder"></div>';
                }
            } else {
                echo '<div class="cover-photo-placeholder"></div>';
            }
            ?>
        </div>
        
        <h1 class="business-title"><?php the_title(); ?></h1>
        
        <!-- Tab Navigation -->
        <div class="business-tabs-container">
            <ul class="business-tabs">
                <li class="tab-item active"><a href="#overview">Overview</a></li>
                <li class="tab-item"><a href="#services">Services</a></li>
                <li class="tab-item"><a href="#reviews">Reviews</a></li>
                <li class="tab-item"><a href="#photos">Photos</a></li>
                <li class="tab-item"><a href="#more-info">Company info</a></li>
                <li class="tab-item"><a href="#accreditations">Accreditations</a></li>
            </ul>
        </div>
        
        <!-- Overview Section -->
        <section id="overview" class="business-section">
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
            </div>
            
            <div class="business-description">
                <?php the_content(); ?>
            </div>
        </section>
        
        <!-- Services Section -->
        <section id="services" class="business-section">
            <h2 class="section-title">Services</h2>
            <?php
            // You can add custom logic here to display services
            // For now, displaying category as services
            if ($categories && !is_wp_error($categories)) {
                echo '<div class="business-services">';
                echo '<ul class="services-list">';
                foreach ($categories as $category) {
                    echo '<li class="service-item">' . esc_html($category->name) . '</li>';
                }
                echo '</ul>';
                echo '</div>';
            } else {
                echo '<p>No services listed.</p>';
            }
            ?>
        </section>
        
        <!-- Hours & Additional Info -->
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
                // Check if there are hours set or closed status
                if (get_post_meta(get_the_ID(), 'lbd_hours_' . $day_id . '_open', true) || 
                    get_post_meta(get_the_ID(), 'lbd_hours_' . $day_id . '_close', true) || 
                    get_post_meta(get_the_ID(), 'lbd_hours_' . $day_id . '_closed', true)) {
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
                        $is_closed = get_post_meta(get_the_ID(), 'lbd_hours_' . $day_id . '_closed', true);
                        $opening = get_post_meta(get_the_ID(), 'lbd_hours_' . $day_id . '_open', true);
                        $closing = get_post_meta(get_the_ID(), 'lbd_hours_' . $day_id . '_close', true);
                        
                        // Format the hours display
                        if ($is_closed) {
                            $hours_display = 'Closed';
                        } elseif ($opening && $closing) {
                            $hours_display = esc_html($opening) . ' - ' . esc_html($closing);
                        } elseif ($opening) {
                            $hours_display = 'From ' . esc_html($opening);
                        } elseif ($closing) {
                            $hours_display = 'Until ' . esc_html($closing);
                        } else {
                            $hours_display = 'Hours not specified';
                        }
                    ?>
                        <tr>
                            <th><?php echo esc_html($day_name); ?></th>
                            <td><?php echo $hours_display; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- Reviews Section -->
        <section id="reviews" class="business-section">
            <h2 class="section-title">Reviews</h2>
            
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
                    // Check for Google Reviews as a fallback
                    $google_rating = get_post_meta(get_the_ID(), 'lbd_google_rating', true);
                    $google_review_count = get_post_meta(get_the_ID(), 'lbd_google_review_count', true);
                    $google_reviews_url = get_post_meta(get_the_ID(), 'lbd_google_reviews_url', true);
                    
                    if ($google_rating && $google_review_count) {
                        echo '<div class="google-reviews-fallback">';
                        echo '<div class="review-summary">';
                        echo '<div class="google-badge"><span class="google-icon">G</span> Google</div>';
                        echo '<div class="average-rating">' . esc_html($google_rating) . ' / 5</div>';
                        echo '<div class="rating-stars">';
                        
                        // Display stars based on Google rating
                        $full_stars = floor($google_rating);
                        $half_star = $google_rating - $full_stars >= 0.5;
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
                        echo '<div class="review-count">Based on ' . esc_html($google_review_count) . ' Google reviews</div>';
                        
                        if ($google_reviews_url) {
                            echo '<div class="google-reviews-link"><a href="' . esc_url($google_reviews_url) . '" target="_blank" rel="noopener">Read reviews on Google</a></div>';
                        }
                        
                        echo '</div>';
                        echo '<p class="site-reviews-cta">Be the first to leave a review on our site!</p>';
                        echo '</div>';
                    } else {
                        echo '<p>No reviews yet. Be the first to leave a review!</p>';
                    }
                }
            } else {
                // Fallback to WordPress comments
                comments_template();
            }
            ?>
        </section>
        
        <!-- Photos Section -->
        <section id="photos" class="business-section">
            <h2 class="section-title">Photos</h2>
            <?php
            // Get business photos
            $photos = get_post_meta(get_the_ID(), 'lbd_business_photos', true);
            
            if (!empty($photos) && is_array($photos)) {
                echo '<div class="business-photos-gallery">';
                foreach ($photos as $attachment_id => $image_url) {
                    // Get proper image URLs
                    $full_img_url = wp_get_attachment_image_url($attachment_id, 'full');
                    if (!$full_img_url) $full_img_url = $image_url; // Fallback to the stored URL if attachment ID doesn't work
                    
                    $thumb_img_url = wp_get_attachment_image_url($attachment_id, 'medium');
                    if (!$thumb_img_url) $thumb_img_url = $image_url; // Fallback to the stored URL
                    
                    $caption = wp_get_attachment_caption($attachment_id) ?: '';
                    
                    echo '<div class="gallery-item">';
                    echo '<a href="' . esc_url($full_img_url) . '" class="lightbox-trigger" data-caption="' . esc_attr($caption) . '">';
                    echo '<img src="' . esc_url($thumb_img_url) . '" alt="' . esc_attr($caption ?: 'Business photo') . '" loading="lazy">';
                    echo '</a>';
                    echo '</div>';
                }
                echo '</div>';
            } else {
                echo '<p>No photos available for this business.</p>';
            }
            ?>
        </section>
        
        <!-- More Info Section -->
        <section id="more-info" class="business-section">
            <h2 class="section-title">More Information</h2>
            
            <?php
            // Display business attributes if any are set
            $black_owned = get_post_meta(get_the_ID(), 'lbd_black_owned', true);
            $women_owned = get_post_meta(get_the_ID(), 'lbd_women_owned', true);
            $lgbtq_friendly = get_post_meta(get_the_ID(), 'lbd_lgbtq_friendly', true);
            
            if ($black_owned || $women_owned || $lgbtq_friendly) {
                echo '<div class="business-attributes">';
                echo '<h3>Business Attributes</h3>';
                
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
        </section>
        
        <!-- Accreditations Section -->
        <section id="accreditations" class="business-section">
            <h2 class="section-title">Accreditations</h2>
            <?php
            // Get business accreditations
            $accreditations = get_post_meta(get_the_ID(), 'lbd_accreditations', true);
            
            if (!empty($accreditations) && is_array($accreditations)) {
                echo '<div class="accreditations-list">';
                foreach ($accreditations as $accreditation) {
                    echo '<div class="accreditation-item">';
                    
                    // Display logo if available
                    if (!empty($accreditation['logo'])) {
                        $logo_url = wp_get_attachment_image_url($accreditation['logo'], 'medium');
                        if ($logo_url) {
                            echo '<div class="accreditation-logo">';
                            if (!empty($accreditation['link'])) {
                                echo '<a href="' . esc_url($accreditation['link']) . '" target="_blank">';
                            }
                            echo '<img src="' . esc_url($logo_url) . '" alt="' . esc_attr($accreditation['name']) . '">';
                            if (!empty($accreditation['link'])) {
                                echo '</a>';
                            }
                            echo '</div>';
                        }
                    }
                    
                    echo '<div class="accreditation-details">';
                    echo '<h3 class="accreditation-name">';
                    if (!empty($accreditation['link'])) {
                        echo '<a href="' . esc_url($accreditation['link']) . '" target="_blank">';
                    }
                    echo esc_html($accreditation['name']);
                    if (!empty($accreditation['link'])) {
                        echo '</a>';
                    }
                    echo '</h3>';
                    
                    if (!empty($accreditation['description'])) {
                        echo '<p class="accreditation-description">' . esc_html($accreditation['description']) . '</p>';
                    }
                    
                    echo '</div>';
                    echo '</div>';
                }
                echo '</div>';
            } else {
                echo '<p>No accreditations listed for this business.</p>';
            }
            ?>
        </section>
    <?php endwhile; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Sticky tabs functionality
    var tabsContainer = $('.business-tabs-container');
    var tabsOffset = tabsContainer.offset().top;
    var tabsHeight = tabsContainer.outerHeight();
    
    // Initial check in case page loads already scrolled
    handleStickyTabs();
    
    // Smooth scroll to section when clicking on tab
    $('.business-tabs a').on('click', function(e) {
        e.preventDefault();
        var target = $(this).attr('href');
        
        // Set active tab
        $('.business-tabs a').parent().removeClass('active');
        $(this).parent().addClass('active');
        
        // Calculate scroll position accounting for sticky tabs
        var scrollTo = $(target).offset().top - tabsHeight;
        
        // Smooth scroll to section
        $('html, body').animate({
            scrollTop: scrollTo
        }, 500);
    });
    
    // Handle sticky tabs on scroll
    $(window).on('scroll', handleStickyTabs);
    
    // Function to handle sticky tabs
    function handleStickyTabs() {
        if ($(window).scrollTop() > tabsOffset) {
            if (!tabsContainer.hasClass('sticky')) {
                tabsContainer.addClass('sticky');
                $('.business-profile').css('padding-top', tabsHeight);
            }
        } else {
            tabsContainer.removeClass('sticky');
            $('.business-profile').css('padding-top', 0);
        }
        
        // Update active tab based on scroll position
        var scrollPosition = $(window).scrollTop() + tabsHeight + 20;
        
        // Find the current visible section
        $('.business-section').each(function() {
            var target = $(this);
            var sectionId = target.attr('id');
            
            // Check if this section is currently in view
            if (target.offset().top <= scrollPosition && 
                target.offset().top + target.outerHeight() > scrollPosition) {
                $('.business-tabs a').parent().removeClass('active');
                $('.business-tabs a[href="#' + sectionId + '"]').parent().addClass('active');
            }
        });
    }
    
    // Re-calculate on window resize
    $(window).on('resize', function() {
        tabsHeight = tabsContainer.outerHeight();
        if (tabsContainer.hasClass('sticky')) {
            $('.business-profile').css('padding-top', tabsHeight);
        }
    });
    
    // Initialize lightbox for photo gallery (if using)
    if (typeof $.fn.lightbox === 'function') {
        $('.lightbox-trigger').lightbox();
    }
});
</script>

<?php get_footer(); ?> 
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
                            $area_category_link = home_url('/directory/' . $areas[0]->slug . '/' . $category->slug . '/');
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
                <?php 
                $logo_url = get_post_meta(get_the_ID(), 'lbd_logo', true);
                if ($logo_url) : ?>
                    <div class="business-logo">
                        <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr(get_the_title()); ?> Logo">
                    </div>
                <?php endif; ?>

                <p><strong>Phone:</strong> <?php echo esc_html(get_post_meta(get_the_ID(), 'lbd_phone', true)); ?></p>
                
                <?php
                // Get all address components
                $street = get_post_meta(get_the_ID(), 'lbd_street_address', true);
                $city = get_post_meta(get_the_ID(), 'lbd_city', true);
                $postcode = get_post_meta(get_the_ID(), 'lbd_postcode', true);
                $old_address = get_post_meta(get_the_ID(), 'lbd_address', true);

                // Use new fields if available, fall back to old address field
                $address_parts = array();
                if ($street) {
                    $address_parts[] = $street;
                }
                if ($city) {
                    $address_parts[] = $city;
                }
                if ($postcode) {
                    $address_parts[] = $postcode;
                }
                $full_address = !empty($address_parts) ? implode(', ', $address_parts) : $old_address;
                ?>
                <p><strong>Address:</strong> <?php echo esc_html($full_address); ?></p>

                <?php 
                // Display extra categories if available
                $extra_categories = get_post_meta(get_the_ID(), 'lbd_extra_categories', true);
                if ($extra_categories) : ?>
                    <p><strong>Additional Services:</strong> <?php echo esc_html($extra_categories); ?></p>
                <?php endif; ?>

                <?php 
                // Display service options if available
                $service_options = get_post_meta(get_the_ID(), 'lbd_service_options', true);
                if ($service_options) : ?>
                    <p><strong>Service Options:</strong> <?php echo esc_html($service_options); ?></p>
                <?php endif; ?>
                
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
                // Check if there are hours set for this day using the helper function
                $day_group = function_exists('lbd_get_business_hours') 
                    ? lbd_get_business_hours(get_the_ID(), $day_id) 
                    : get_post_meta(get_the_ID(), 'lbd_hours_' . $day_id . '_group', true);
                
                if (!empty($day_group)) {
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
                        // Get hours data using the helper function
                        $day_group = function_exists('lbd_get_business_hours') 
                            ? lbd_get_business_hours(get_the_ID(), $day_id) 
                            : get_post_meta(get_the_ID(), 'lbd_hours_' . $day_id . '_group', true);
                        
                        if (empty($day_group)) {
                            $hours_display = 'Hours not specified';
                        } else {
                            $is_closed = isset($day_group[0]['closed']) ? $day_group[0]['closed'] : false;
                            $opening = isset($day_group[0]['open']) ? $day_group[0]['open'] : '';
                            $closing = isset($day_group[0]['close']) ? $day_group[0]['close'] : '';
                            
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
                    
                    // Use the consolidated function if available
                    if (function_exists('lbd_get_star_rating_html')) {
                        echo lbd_get_star_rating_html($average_rating, $review_count);
                    } else {
                        // Fallback to original code
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
                    }
                    
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
                        echo '<p class="site-reviews-cta"><a href="' . esc_url(home_url('/submit-review/?business_id=' . get_the_ID())) . '">Be the first to review ' . esc_html(get_the_title()) . ' on our site!</a></p>';
                        echo '</div>';
                    } else {
                        echo '<p>No reviews yet. <a href="' . esc_url(home_url('/submit-review/?business_id=' . get_the_ID())) . '">Be the first to review ' . esc_html(get_the_title()) . '!</a></p>';
                    }
                }
            } else {
                // Fallback to WordPress comments
                comments_template();
            }
            ?>
            
            <?php if ($reviews && !empty($reviews)) : ?>
                <div class="leave-review-cta">
                    <a href="<?php echo esc_url(home_url('/submit-review/?business_id=' . get_the_ID())); ?>" class="btn-leave-review">
                        Leave <?php echo esc_html(get_the_title()); ?> a review!
                    </a>
                </div>
            <?php endif; ?>
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
                    // Only add the title attribute if there's a real caption
                    if (!empty($caption)) {
                        echo '<a href="' . esc_url($full_img_url) . '" class="glightbox" data-gallery="business-gallery" data-glightbox="title: ' . esc_attr($caption) . '">';
                    } else {
                        echo '<a href="' . esc_url($full_img_url) . '" class="glightbox" data-gallery="business-gallery">';
                    }
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

<?php
// Enqueue Lightbox resources
wp_enqueue_style('glightbox', plugin_dir_url(dirname(__FILE__)) . 'assets/vendor/glightbox/glightbox.min.css', array(), '1.0.0');
wp_enqueue_script('glightbox', plugin_dir_url(dirname(__FILE__)) . 'assets/vendor/glightbox/glightbox.min.js', array(), '1.0.0', true);

// Add custom styles for lightbox captions
wp_add_inline_style('glightbox', '
    .glightbox-container .gslide-description {
        background: rgba(0, 0, 0, 0.7);
    }
    .glightbox-container .gslide-title {
        font-size: 16px;
        color: white;
        font-weight: 400;
        padding: 12px 15px;
        margin: 0;
    }
');

// Initialize Lightbox
wp_add_inline_script('glightbox', '
    document.addEventListener("DOMContentLoaded", function() {
        const lightbox = GLightbox({
            selector: ".glightbox",
            touchNavigation: true,
            loop: true,
            autoplayVideos: true,
            moreText: "+",
            descPosition: "bottom",
            closeButton: true
        });
    });
');

get_footer(); ?> 
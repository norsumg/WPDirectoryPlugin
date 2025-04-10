<?php
/**
 * Rank Math Schema Integration
 * 
 * Provides integration between the Local Business Directory plugin and Rank Math Pro's Schema module.
 * This enhances the LocalBusiness schema with data from LBD custom fields.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Debug function to help troubleshoot schema issues
 */
function lbd_debug_schema_to_file($schema, $message = '') {
    $debug = get_option('lbd_debug_schema', false);
    if (!$debug) {
        return;
    }
    
    $log_file = WP_CONTENT_DIR . '/lbd-schema-debug.log';
    $data = date('[Y-m-d H:i:s]') . ' ' . $message . "\n";
    $data .= print_r($schema, true) . "\n\n";
    
    file_put_contents($log_file, $data, FILE_APPEND);
}

/**
 * Enhance LocalBusiness schema with LBD data
 * 
 * @param array $schema The existing schema data from Rank Math
 * @param object $data Rank Math data object
 * @return array Modified schema data
 */
function lbd_enhance_rankmath_schema($schema, $data) {
    // Check for valid post types - check both singular and plural forms
    $post_type = get_post_type();
    lbd_debug_schema_to_file(['post_type' => $post_type], 'Post type check');
    
    if ($post_type !== 'business' && $post_type !== 'businesses') {
        return $schema;
    }
    
    $post_id = get_the_ID();
    lbd_debug_schema_to_file(['schema_before' => $schema], 'Schema before enhancement');
    
    // Always set the type to LocalBusiness or appropriate subtype
    // This ensures we have the right schema type even if it was initially Service
    $schema['@type'] = 'LocalBusiness';
    
    // Always add the name property (from post title)
    $schema['name'] = get_the_title($post_id);
    
    // Always add the description property (from excerpt or content)
    $excerpt = get_the_excerpt($post_id);
    if (empty($excerpt)) {
        $post = get_post($post_id);
        $excerpt = wp_trim_words($post->post_content, 55, '...');
    }
    $schema['description'] = $excerpt;
    
    // ===== ADDRESS HANDLING =====
    $address = get_post_meta($post_id, 'lbd_address', true);
    if (!empty($address)) {
        // Create PostalAddress structure
        $schema['address'] = [
            '@type' => 'PostalAddress',
            'streetAddress' => $address
        ];
        
        // Add area/location data if available
        $areas = get_the_terms($post_id, 'business_area');
        if ($areas && !is_wp_error($areas)) {
            $schema['address']['addressLocality'] = $areas[0]->name;
        }
    }
    
    // Add phone number
    $phone = get_post_meta($post_id, 'lbd_phone', true);
    if (!empty($phone)) {
        $schema['telephone'] = $phone;
    }
    
    // Add website URL
    $website = get_post_meta($post_id, 'lbd_website', true);
    if (!empty($website)) {
        $schema['url'] = $website;
    }
    
    // ===== OPENING HOURS =====
    $opening_hours = [];
    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    $day_map = [
        'monday' => 'Monday', 
        'tuesday' => 'Tuesday', 
        'wednesday' => 'Wednesday',
        'thursday' => 'Thursday', 
        'friday' => 'Friday', 
        'saturday' => 'Saturday', 
        'sunday' => 'Sunday'
    ];
    
    foreach ($days as $day) {
        $hours_group = get_post_meta($post_id, "lbd_hours_{$day}_group", true);
        
        if (!empty($hours_group) && is_array($hours_group)) {
            // CMB2 returns a single array, not an array of arrays for non-repeatable groups
            if (isset($hours_group[0]) && is_array($hours_group[0])) {
                // Handle each period
                foreach ($hours_group as $period) {
                    // Skip if marked as closed
                    if (!empty($period['closed'])) {
                        continue;
                    }
                    
                    if (!empty($period['open']) && !empty($period['close'])) {
                        $opening_hours[] = [
                            '@type' => 'OpeningHoursSpecification',
                            'dayOfWeek' => $day_map[$day],
                            'opens' => lbd_format_schema_time($period['open']),
                            'closes' => lbd_format_schema_time($period['close'])
                        ];
                    }
                }
            } else {
                // Direct access, single period
                if (empty($hours_group['closed']) && !empty($hours_group['open']) && !empty($hours_group['close'])) {
                    $opening_hours[] = [
                        '@type' => 'OpeningHoursSpecification',
                        'dayOfWeek' => $day_map[$day],
                        'opens' => lbd_format_schema_time($hours_group['open']),
                        'closes' => lbd_format_schema_time($hours_group['close'])
                    ];
                }
            }
        }
    }
    
    if (!empty($opening_hours)) {
        $schema['openingHoursSpecification'] = $opening_hours;
    }
    
    // ===== REVIEWS & RATINGS =====
    // Get average rating and review count
    $review_average = get_post_meta($post_id, 'lbd_review_average', true);
    $review_count = get_post_meta($post_id, 'lbd_review_count', true);
    
    // Try Google reviews if no native reviews
    if (empty($review_average)) {
        $review_average = get_post_meta($post_id, 'lbd_google_rating', true);
        $review_count = get_post_meta($post_id, 'lbd_google_review_count', true);
    }
    
    if (!empty($review_average) && !empty($review_count)) {
        $schema['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => $review_average,
            'reviewCount' => $review_count,
            'bestRating' => 5,
            'worstRating' => 1
        ];
        
        // Add individual reviews - limited to 3 for best SEO practice
        if (function_exists('lbd_get_business_reviews')) {
            $reviews = lbd_get_business_reviews($post_id, true);
            $schema_reviews = [];
            
            // Limit to 3 reviews for optimal schema display
            $reviews = array_slice($reviews, 0, 3);
            
            foreach ($reviews as $review) {
                $schema_reviews[] = [
                    '@type' => 'Review',
                    'reviewRating' => [
                        '@type' => 'Rating',
                        'ratingValue' => $review->rating,
                        'bestRating' => 5,
                        'worstRating' => 1
                    ],
                    'author' => [
                        '@type' => 'Person',
                        'name' => $review->reviewer_name
                    ],
                    'reviewBody' => $review->review_text,
                    'datePublished' => $review->review_date
                ];
            }
            
            if (!empty($schema_reviews)) {
                $schema['review'] = $schema_reviews;
            }
        }
    }
    
    // ===== ADDITIONAL PROPERTIES =====
    // Add payments accepted
    $payments = get_post_meta($post_id, 'lbd_payments', true);
    if (!empty($payments)) {
        $schema['paymentAccepted'] = $payments;
    }
    
    // Add accessibility features
    $accessibility = get_post_meta($post_id, 'lbd_accessibility', true);
    if (!empty($accessibility)) {
        $schema['accessibilityFeature'] = $accessibility;
    }
    
    // Add business category as a specific LocalBusiness subtype if applicable
    $categories = get_the_terms($post_id, 'business_category');
    if ($categories && !is_wp_error($categories)) {
        // Map common category slugs to schema.org business types
        $category_map = [
            'restaurants' => 'Restaurant',
            'restaurant' => 'Restaurant', 
            'cafe' => 'CafeOrCoffeeShop',
            'coffee-shop' => 'CafeOrCoffeeShop',
            'hotel' => 'Hotel',
            'lodging' => 'LodgingBusiness',
            'store' => 'Store',
            'retail' => 'Store',
            'shop' => 'Store',
            'salon' => 'BeautySalon',
            'beauty-salon' => 'BeautySalon',
            'hair-salon' => 'HairSalon',
            'doctor' => 'Physician',
            'medical' => 'MedicalBusiness',
            'dental' => 'Dentist',
            'dentist' => 'Dentist',
            'law' => 'LegalService',
            'attorney' => 'Attorney',
            'legal' => 'LegalService',
            'bar' => 'BarOrPub',
            'pub' => 'BarOrPub'
            // Add more mappings as needed
        ];
        
        foreach ($categories as $category) {
            $cat_slug = $category->slug;
            if (isset($category_map[$cat_slug])) {
                $schema['@type'] = $category_map[$cat_slug];
                break; // Use the first matching category
            }
        }
    }
    
    // Add image if available
    $image_id = get_post_thumbnail_id($post_id);
    if ($image_id) {
        $image_url = wp_get_attachment_image_url($image_id, 'full');
        if ($image_url) {
            $schema['image'] = $image_url;
        }
    }
    
    // Add a test property to confirm our hook is working
    $schema['testProperty'] = 'LBD Integration is working';
    
    lbd_debug_schema_to_file(['schema_after' => $schema], 'Schema after enhancement');
    return $schema;
}

/**
 * Format time for schema.org (convert from AM/PM to 24-hour format)
 * 
 * @param string $time_string Time string in any format strtotime understands
 * @return string Time in HH:MM:SS format for Schema.org
 */
function lbd_format_schema_time($time_string) {
    // Remove any leading/trailing whitespace
    $time_string = trim($time_string);
    
    // Parse the time string
    $timestamp = strtotime($time_string);
    if ($timestamp === false) {
        return $time_string; // Return original if parsing fails
    }
    
    // Format in 24-hour format for schema.org
    return date('H:i:s', $timestamp);
}

/**
 * Hook into Rank Math's schema filter
 */
function lbd_register_rankmath_hooks() {
    // Enable debug mode temporarily to troubleshoot
    update_option('lbd_debug_schema', true);
    
    if (class_exists('\\RankMath\\Schema\\Schema')) {
        // Hook into Service schema type (since that's what's set in the screenshot)
        add_filter('rank_math/schema/Service', 'lbd_enhance_rankmath_schema', 30, 2);
        
        // Also hook into LocalBusiness schema type
        add_filter('rank_math/schema/LocalBusiness', 'lbd_enhance_rankmath_schema', 30, 2);
        
        // Hook into the main snippet filter as a last resort
        add_filter('rank_math/json_ld', function($data, $jsonld) {
            lbd_debug_schema_to_file($data, 'rank_math/json_ld filter data before');
            
            // Check if we're on a business post
            $post_type = get_post_type();
            if ($post_type === 'business' || $post_type === 'businesses') {
                // Look for any schema type object to modify
                foreach ($data as $id => $schema) {
                    if (isset($schema['@type'])) {
                        $data[$id] = lbd_enhance_rankmath_schema($schema, null);
                    }
                }
            }
            
            lbd_debug_schema_to_file($data, 'rank_math/json_ld filter data after');
            return $data;
        }, 30, 2);
    }
}
add_action('plugins_loaded', 'lbd_register_rankmath_hooks'); 
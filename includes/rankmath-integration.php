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
 * Enhance LocalBusiness schema with LBD data
 * 
 * @param array $schema The existing schema data from Rank Math
 * @param object $data Rank Math data object
 * @return array Modified schema data
 */
function lbd_enhance_rankmath_schema($schema, $data) {
    // Only proceed if we're on a business post type
    if (get_post_type() !== 'business') {
        return $schema;
    }
    
    $post_id = get_the_ID();
    
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
    if (class_exists('\\RankMath\\Schema\\Schema')) {
        // Hook into LocalBusiness schema type
        add_filter('rank_math/schema/LocalBusiness', 'lbd_enhance_rankmath_schema', 10, 2);
        
        // Also hook into all potential subtypes that might be used
        $subtypes = [
            'Restaurant', 'FoodEstablishment', 'CafeOrCoffeeShop', 
            'Store', 'Hotel', 'LodgingBusiness', 
            'BeautySalon', 'HairSalon', 'MedicalBusiness', 
            'Physician', 'Dentist', 'LegalService'
        ];
        
        foreach ($subtypes as $type) {
            add_filter("rank_math/schema/{$type}", 'lbd_enhance_rankmath_schema', 10, 2);
        }
    }
}
add_action('plugins_loaded', 'lbd_register_rankmath_hooks'); 
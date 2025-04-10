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
    
    // Remove invalid properties that may come from WordPress or Rank Math
    unset($schema['datePublished']);
    unset($schema['dateModified']);
    unset($schema['inLanguage']);
    
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
    $street_address = get_post_meta($post_id, 'lbd_street_address', true);
    $city = get_post_meta($post_id, 'lbd_city', true);
    $postcode = get_post_meta($post_id, 'lbd_postcode', true);
    
    // Create PostalAddress structure
    $schema['address'] = [
        '@type' => 'PostalAddress',
        'streetAddress' => $street_address ?: get_post_meta($post_id, 'lbd_address', true), // Fallback to old address field
    ];
    
    if (!empty($city)) {
        $schema['address']['addressLocality'] = $city;
    } else {
        // Fallback to area taxonomy
        $areas = get_the_terms($post_id, 'business_area');
        if ($areas && !is_wp_error($areas)) {
            $schema['address']['addressLocality'] = $areas[0]->name;
        }
    }
    
    if (!empty($postcode)) {
        $schema['address']['postalCode'] = $postcode;
    }
    
    // Add coordinates if available
    $latitude = get_post_meta($post_id, 'lbd_latitude', true);
    $longitude = get_post_meta($post_id, 'lbd_longitude', true);
    if (!empty($latitude) && !empty($longitude)) {
        $schema['geo'] = [
            '@type' => 'GeoCoordinates',
            'latitude' => $latitude,
            'longitude' => $longitude
        ];
    }
    
    // Add logo if available
    $logo_url = get_post_meta($post_id, 'lbd_logo', true);
    if (!empty($logo_url)) {
        $schema['logo'] = $logo_url;
    }
    
    // Add extra service categories
    $extra_categories = get_post_meta($post_id, 'lbd_extra_categories', true);
    if (!empty($extra_categories)) {
        $schema['additionalType'] = array_map('trim', explode(',', $extra_categories));
    }
    
    // Add service options
    $service_options = get_post_meta($post_id, 'lbd_service_options', true);
    if (!empty($service_options)) {
        $schema['serviceType'] = array_map('trim', explode(',', $service_options));
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
                // Look for existing LocalBusiness schema
                $found_business = false;
                foreach ($data as $id => $schema) {
                    if (isset($schema['@type']) && 
                        (($schema['@type'] === 'LocalBusiness') || 
                         ($schema['@type'] === 'Service'))) {
                        // Enhance existing schema
                        $data[$id] = lbd_enhance_rankmath_schema($schema, null);
                        $found_business = true;
                        break; // Only enhance one schema object
                    }
                }
                
                // If no LocalBusiness schema found, add one
                if (!$found_business) {
                    $new_schema = [
                        '@type' => 'LocalBusiness',
                        '@id' => get_permalink() . '#localbusiness'
                    ];
                    $data[] = lbd_enhance_rankmath_schema($new_schema, null);
                }
            }
            
            lbd_debug_schema_to_file($data, 'rank_math/json_ld filter data after');
            return $data;
        }, 30, 2);
    }
}
add_action('plugins_loaded', 'lbd_register_rankmath_hooks'); 
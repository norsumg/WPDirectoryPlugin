/**
 * Local Business Directory - Admin JavaScript
 * 
 * Contains scripts for:
 * - Business hours metabox
 * - CSV sample download
 */
jQuery(document).ready(function($) {
    // Only run on business post edit screen
    if (typeof pagenow !== 'undefined' && pagenow === 'business') {
        // Business hours functionality
        var daysOfWeek = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        
        // Function to update hours fields based on 24-hour checkbox
        function update24HoursFields() {
            var is24Hours = $('#lbd_hours_24').prop('checked');
            
            // Loop through each day and update the fields
            daysOfWeek.forEach(function(day) {
                // Get the group fields
                var groupContainer = $('#lbd_hours_' + day + '_group_repeat');
                var closedCheckbox = groupContainer.find('[name*="[closed]"]');
                var openField = groupContainer.find('[name*="[open]"]');
                var closeField = groupContainer.find('[name*="[close]"]');
                
                if (is24Hours) {
                    // Store original values
                    if (!groupContainer.data('original-values')) {
                        groupContainer.data('original-values', {
                            closed: closedCheckbox.prop('checked'),
                            open: openField.val(),
                            close: closeField.val()
                        });
                    }
                    
                    // Set to 24 hours and disable fields
                    closedCheckbox.prop('checked', false).prop('disabled', true);
                    openField.val('12:00 AM').prop('disabled', true);
                    closeField.val('11:59 PM').prop('disabled', true);
                    
                    // Hide the fields for better UX
                    groupContainer.css('opacity', '0.5');
                } else {
                    // Restore original values if they exist
                    var originalValues = groupContainer.data('original-values');
                    if (originalValues) {
                        closedCheckbox.prop('checked', originalValues.closed);
                        openField.val(originalValues.open);
                        closeField.val(originalValues.close);
                    }
                    
                    // Re-enable fields
                    closedCheckbox.prop('disabled', false);
                    openField.prop('disabled', false);
                    closeField.prop('disabled', false);
                    
                    // Update visibility
                    groupContainer.css('opacity', '1');
                    
                    // Apply closed state if needed
                    if (closedCheckbox.prop('checked')) {
                        openField.prop('disabled', true);
                        closeField.prop('disabled', true);
                    }
                }
            });
        }
        
        // Function to handle "Closed" checkbox within each day group
        function setupClosedCheckboxHandlers() {
            daysOfWeek.forEach(function(day) {
                var groupContainer = $('#lbd_hours_' + day + '_group_repeat');
                var closedCheckbox = groupContainer.find('[name*="[closed]"]');
                var openField = groupContainer.find('[name*="[open]"]');
                var closeField = groupContainer.find('[name*="[close]"]');
                
                closedCheckbox.on('change', function() {
                    var isChecked = $(this).prop('checked');
                    
                    if (isChecked) {
                        // Store values if not already stored
                        if (!groupContainer.data('closed-values')) {
                            groupContainer.data('closed-values', {
                                open: openField.val(),
                                close: closeField.val()
                            });
                        }
                        
                        // Disable time fields
                        openField.prop('disabled', true);
                        closeField.prop('disabled', true);
                    } else {
                        // Restore values if they were saved
                        var closedValues = groupContainer.data('closed-values');
                        if (closedValues) {
                            openField.val(closedValues.open);
                            closeField.val(closedValues.close);
                        }
                        
                        // Re-enable time fields
                        openField.prop('disabled', false);
                        closeField.prop('disabled', false);
                    }
                });
                
                // Set initial state
                if (closedCheckbox.prop('checked')) {
                    openField.prop('disabled', true);
                    closeField.prop('disabled', true);
                }
            });
        }
        
        // Set initial state for 24-hour checkbox
        update24HoursFields();
        
        // Handle 24-hour checkbox change
        $('#lbd_hours_24').on('change', function() {
            update24HoursFields();
        });
        
        // Setup closed checkbox handlers
        setupClosedCheckboxHandlers();
    }
    
    // CSV Import page functionality - Generate and download sample CSV
    if ($('#lbd-sample-csv').length > 0) {
        $('#lbd-sample-csv').on('click', function(e) {
            e.preventDefault();
            
            const headers = 'business_name,business_description,business_excerpt,business_area,business_category,business_phone,business_address,business_website,business_email,business_facebook,business_instagram,business_hours_24,business_hours_monday,business_hours_tuesday,business_hours_wednesday,business_hours_thursday,business_hours_friday,business_hours_saturday,business_hours_sunday,business_payments,business_parking,business_amenities,business_accessibility,business_premium,business_image_url,business_black_owned,business_women_owned,business_lgbtq_friendly,business_google_rating,business_google_review_count,business_google_reviews_url\n';
            const sampleRow1 = 'ACME Web Design,"We create beautiful websites for small businesses. Our team has over 10 years of experience designing responsive websites that convert visitors into customers.",Web design experts in Ashford area,Ashford,Web Design,01234 567890,"123 Main St, Ashford",https://example.com,info@example.com,https://facebook.com/acmewebdesign,acmewebdesign,no,"9:00 AM - 5:00 PM","9:00 AM - 5:00 PM","9:00 AM - 5:00 PM","9:00 AM - 5:00 PM","9:00 AM - 5:00 PM","10:00 AM - 2:00 PM",Closed,"Cash, Credit Cards, PayPal","Free parking available","Free WiFi, Coffee, Meeting room","Wheelchair accessible entrance, Elevator",yes,https://example.com/sample-image1.jpg,yes,no,yes,4.7,23,https://g.page/acme-web-design\n';
            const sampleRow2 = 'Smith & Co Accountants,"Professional accounting services for small businesses and individuals. We provide tax preparation, bookkeeping, and financial planning.",Trusted local accountants serving Canterbury since 2005,Canterbury,Accountants,01234 123456,"45 High Street, Canterbury",https://example-accountants.com,contact@example-accountants.com,https://facebook.com/smithcoaccountants,smithcoaccountants,yes,"24 Hours","24 Hours","24 Hours","24 Hours","24 Hours","24 Hours","24 Hours","All major credit cards","Street parking","Private consultation rooms, Tea and coffee","Wheelchair accessible",no,https://example.com/sample-image2.jpg,no,yes,no,4.2,17,https://g.page/smith-co-accountants\n';
            
            const csvContent = headers + sampleRow1 + sampleRow2;
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            
            const a = document.createElement('a');
            a.setAttribute('hidden', '');
            a.setAttribute('href', url);
            a.setAttribute('download', 'sample_businesses.csv');
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        });
    }
}); 
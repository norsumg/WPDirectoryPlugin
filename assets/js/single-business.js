/**
 * Local Business Directory - Single Business Page JS
 * 
 * Controls tab functionality and scrolling behavior on single business pages
 */
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
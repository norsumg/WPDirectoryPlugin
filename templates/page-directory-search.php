<?php
/**
 * Template Name: Directory Search
 * 
 * Template for displaying directory search results
 */

get_header(); ?>

<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">
        <div class="container">
            <div class="directory-search-container">
                <div class="directory-search-form">
                    <h2>Find Local Businesses</h2>
                    <?php echo do_shortcode('[business_search_form layout="horizontal"]'); ?>
                </div>
                
                <div class="directory-search-results">
                    <?php 
                    // Use the enhanced search results shortcode
                    echo do_shortcode('[lbd_search_results per_page="10" info_layout="list"]'); 
                    ?>
                </div>
            </div>
        </div>
    </main><!-- #main -->
</div><!-- #primary -->

<?php get_footer(); ?> 
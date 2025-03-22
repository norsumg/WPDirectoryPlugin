<?php
/**
 * Template Name: Directory Search
 * 
 * This template is used for the directory search results page
 */

get_header(); ?>

<div class="directory-search-container">
    <div class="directory-search-header">
        <h1 class="page-title">Business Directory Search</h1>
        
        <?php echo do_shortcode('[business_search_form layout="horizontal" button_style="pill" placeholder="Find businesses..."]'); ?>
    </div>

    <div class="directory-search-results">
        <?php echo do_shortcode('[business_search_results]'); ?>
    </div>
</div>

<?php get_footer(); ?> 
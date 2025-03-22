<?php
/**
 * Template Name: Review Submission Form
 * Description: A template for the review submission form
 */

// Check if we're accessing via the template_redirect (URL routing) or as a page template
$is_direct_template = !defined('ABSPATH');

// If we're being included from the template_redirect hook, we need to load WordPress
if ($is_direct_template) {
    // Define a constant to prevent direct access
    define('ABSPATH', dirname(dirname(dirname(dirname(__FILE__)))) . '/');
    require_once(ABSPATH . 'wp-load.php');
}

get_header();
?>

<div class="review-page-container">
    <div class="review-page-content">
        <?php if (!$is_direct_template && have_posts()) : while (have_posts()) : the_post(); ?>
            <h1 class="page-title"><?php the_title(); ?></h1>
            <?php the_content(); ?>
        <?php endwhile; else: ?>
            <h1 class="page-title">Submit a Review</h1>
        <?php endif; ?>
        
        <?php 
        // Add the review form shortcode
        echo do_shortcode('[review_submission_form]'); 
        ?>
    </div>
</div>

<?php get_footer(); ?> 
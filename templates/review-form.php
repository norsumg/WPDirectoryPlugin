<?php
/**
 * Template Name: Review Submission Form
 * Description: A template for the review submission form
 */

get_header();
?>

<div class="review-page-container">
    <div class="review-page-content">
        <?php if (have_posts()) : while (have_posts()) : the_post(); ?>
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
<?php get_header(); ?>
<div class="business-category">
    <?php
    // Get the current category
    $term = get_queried_object();
    
    // Check if we have an area context
    $area_slug = get_query_var('business_area');
    $area = null;
    
    if (!empty($area_slug)) {
        $area = get_term_by('slug', $area_slug, 'business_area');
    }
    
    // Display the heading with area name if available
    if ($area) {
        echo '<h1>' . esc_html($term->name) . ' in ' . esc_html($area->name) . '</h1>';
    } else {
        echo '<h1>' . esc_html($term->name) . '</h1>';
    }
    ?>
    
    <?php if ( term_description() ) : ?>
        <div class="term-description">
            <?php echo term_description(); ?>
        </div>
    <?php endif; ?>

    <div class="business-listing">
        <?php if ( have_posts() ) : ?>
            <div class="business-grid">
                <?php while ( have_posts() ) : the_post(); ?>
                    <?php lbd_get_template_part( 'content', 'business' ); ?>
                <?php endwhile; ?>
            </div>
            
            <?php 
            // Debug info for admins
            if (current_user_can('manage_options') && isset($_GET['lbd_debug'])) : 
                global $wp_query;
                $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 0;
                ?>
                <div style="background: #f5f5f5; border: 1px solid #ddd; padding: 15px; margin: 15px 0; font-family: monospace;">
                    <h3>Pagination Debug Info</h3>
                    <p>Found posts: <?php echo $wp_query->found_posts; ?></p>
                    <p>Posts per page: <?php echo $wp_query->get('posts_per_page'); ?></p>
                    <p>Current page: <?php echo max( 1, get_query_var('paged') ); ?></p>
                    <p>Requested per_page: <?php echo $per_page; ?></p>
                    <p>Max num pages: <?php echo $wp_query->max_num_pages; ?></p>
                </div>
            <?php endif; ?>
            
            <?php 
            // Add pagination options before the pagination
            do_action('lbd_before_pagination');
            
            if ( function_exists('wp_pagenavi') ) : 
                wp_pagenavi();
            else : ?>
                <div class="pagination">
                    <?php echo paginate_links(); ?>
                </div>
            <?php endif; ?>
            
        <?php else : ?>
            <p class="no-businesses">No businesses found in this category. Check back soon or <a href="<?php echo home_url('/directory/'); ?>">browse other categories</a>.</p>
        <?php endif; ?>
    </div>
</div>
<?php get_footer(); ?> 
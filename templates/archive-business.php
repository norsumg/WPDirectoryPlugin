<?php get_header(); ?>
<div class="business-archive">
    <h1>Business Directory</h1>
    
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
            <p>No businesses found. Please check back soon.</p>
        <?php endif; ?>
    </div>
</div>
<?php get_footer(); ?> 
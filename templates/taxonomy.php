<?php get_header(); ?>
<div class="business-taxonomy">
    <h1><?php single_term_title(); ?></h1>
    
    <?php 
    // Display breadcrumb for area & category
    $term = get_queried_object();
    if ($term->taxonomy === 'business_category') {
        // See if we have an area context
        $area_slug = get_query_var('business_area');
        if (!empty($area_slug)) {
            $area = get_term_by('slug', $area_slug, 'business_area');
            if ($area) {
                echo '<div class="taxonomy-breadcrumb">';
                echo '<a href="' . esc_url(home_url('/')) . '">Home</a> &raquo; ';
                echo '<a href="' . esc_url(home_url('/directory/' . $area->slug . '/')) . '">' . esc_html($area->name) . '</a> &raquo; ';
                echo esc_html($term->name);
                echo '</div>';
            }
        }
    }
    ?>
    
    <?php if ( term_description() ) : ?>
        <div class="term-description">
            <?php echo term_description(); ?>
        </div>
    <?php endif; ?>
    
    <div class="business-listing">
        <?php if ( have_posts() ) : ?>
            <div class="business-items">
                <?php while ( have_posts() ) : the_post(); ?>
                    <?php lbd_get_template_part( 'content', 'business' ); ?>
                <?php endwhile; ?>
            </div>
            
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
            
        <?php else: ?>
            <p>No businesses found.</p>
        <?php endif; ?>
    </div>
</div>
<?php get_footer(); ?> 
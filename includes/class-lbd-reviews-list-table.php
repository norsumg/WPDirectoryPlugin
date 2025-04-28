<?php
/**
 * LBD Reviews List Table Class.
 *
 * Extends WP_List_Table to display reviews from the custom table.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Ensure WP_List_Table is available
if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class LBD_Reviews_List_Table extends WP_List_Table {

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct(array(
            'singular' => __('Review', 'local-business-directory'), // Singular name of the listed records
            'plural'   => __('Reviews', 'local-business-directory'), // Plural name of the listed records
            'ajax'     => false // Does this table support ajax?
        ));
    }

    /**
     * Get columns to display in the table.
     *
     * @return array
     */
    public function get_columns() {
        $columns = array(
            'cb'            => '<input type="checkbox" />', // Checkbox for bulk actions
            'business_name' => __('Business', 'local-business-directory'),
            'reviewer_name' => __('Reviewer', 'local-business-directory'),
            'review_text'   => __('Review', 'local-business-directory'),
            'rating'        => __('Rating', 'local-business-directory'),
            'approved'      => __('Status', 'local-business-directory'),
            'review_date'   => __('Date', 'local-business-directory'),
            'source'        => __('Source', 'local-business-directory'),
        );
        return $columns;
    }

    /**
     * Get sortable columns.
     *
     * @return array
     */
    public function get_sortable_columns() {
        $sortable_columns = array(
            'business_name' => array('business_name', false), // True means it's sorted by default
            'reviewer_name' => array('reviewer_name', false),
            'rating'        => array('rating', false),
            'approved'      => array('approved', false),
            'review_date'   => array('review_date', true), // Default sort by date
            'source'        => array('source', false),
        );
        return $sortable_columns;
    }

    /**
     * Prepare the items for the table to process.
     */
    public function prepare_items() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'lbd_reviews';
        $posts_table = $wpdb->posts;

        $per_page     = $this->get_items_per_page('reviews_per_page', 20);
        $current_page = $this->get_pagenum();
        $total_items  = $this->get_total_reviews();

        $this->set_pagination_args(array(
            'total_items' => $total_items, // Total number of items
            'per_page'    => $per_page    // We have to determine how many items to show on a page
        ));

        $columns  = $this->get_columns();
        $hidden   = array(); // Hidden columns
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array($columns, $hidden, $sortable);

        // Get orderby and order parameters
        $orderby = (!empty($_REQUEST['orderby'])) ? sanitize_sql_orderby( $_REQUEST['orderby'] ) : 'review_date';
        $order   = (!empty($_REQUEST['order'])) ? strtoupper( $_REQUEST['order'] ) : 'DESC';

        // Validate orderby parameter against sortable columns
        if (!array_key_exists($orderby, $this->get_sortable_columns())) {
            $orderby = 'review_date'; // Default to review_date if invalid
        }
        // Validate order parameter
        if (!in_array($order, ['ASC', 'DESC'])) {
            $order = 'DESC'; // Default to DESC if invalid
        }

        // Map sortable column slugs to actual database columns
        $orderby_db_column = $orderby;
        if ($orderby === 'business_name') {
            $orderby_db_column = 'p.post_title';
        } elseif ($orderby === 'status') {
            $orderby_db_column = 'r.approved';
        } elseif (in_array($orderby, ['reviewer_name', 'rating', 'review_date', 'source', 'approved'])) {
             $orderby_db_column = 'r.' . $orderby; // Prefix with table alias
        } else {
             $orderby_db_column = 'r.review_date'; // Fallback
        }


        // Calculate offset
        $offset = ($current_page - 1) * $per_page;

        // Prepare the query
        // Using LEFT JOIN to still show reviews even if the business post was deleted (though unlikely use case)
        $query = $wpdb->prepare(
            "SELECT r.*, p.post_title AS business_name
             FROM {$table_name} r
             LEFT JOIN {$posts_table} p ON r.business_id = p.ID
             ORDER BY {$orderby_db_column} {$order}
             LIMIT %d OFFSET %d",
            $per_page,
            $offset
        );

        // Fetch the data
        $this->items = $wpdb->get_results($query, ARRAY_A);
    }

    /**
     * Get the total number of reviews.
     *
     * @return int
     */
    protected function get_total_reviews() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'lbd_reviews';
        return (int) $wpdb->get_var("SELECT COUNT(id) FROM {$table_name}");
    }

    /**
     * Render the checkbox column.
     *
     * @param array $item
     * @return string
     */
    public function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="review_id[]" value="%s" />',
            $item['id']
        );
    }

    /**
     * Render the reviewer name column with actions.
     *
     * @param array $item
     * @return string
     */
    public function column_reviewer_name($item) {
        $page = wp_unslash($_REQUEST['page']); // WPCS: Input var ok.

        // Build Approve/Unapprove action link
        if ($item['approved']) {
            $action_url = wp_nonce_url(admin_url('admin.php?page=' . $page . '&action=unapprove&review_id=' . $item['id']), 'lbd_review_action_' . $item['id']);
            $actions['unapprove'] = '<a href="' . esc_url($action_url) . '">' . __('Unapprove', 'local-business-directory') . '</a>';
        } else {
            $action_url = wp_nonce_url(admin_url('admin.php?page=' . $page . '&action=approve&review_id=' . $item['id']), 'lbd_review_action_' . $item['id']);
            $actions['approve'] = '<a href="' . esc_url($action_url) . '" style="color:#00a32a;">' . __('Approve', 'local-business-directory') . '</a>';
        }

        // Build Delete action link
        $delete_url = wp_nonce_url(admin_url('admin.php?page=' . $page . '&action=delete&review_id=' . $item['id']), 'lbd_review_action_' . $item['id']);
        $actions['delete'] = '<a href="' . esc_url($delete_url) . '" style="color:#a00;" onclick="return confirm(\'Are you sure you want to delete this review?\')">' . __('Delete', 'local-business-directory') . '</a>';

        // Return the review name along with the action links
        return sprintf('%1$s %2$s',
            esc_html($item['reviewer_name']),
            $this->row_actions($actions)
        );
    }

    /**
     * Render the business name column.
     *
     * @param array $item
     * @return string
     */
    public function column_business_name($item) {
        $business_name = !empty($item['business_name']) ? $item['business_name'] : __('Business Deleted', 'local-business-directory');
        $business_id = (int)$item['business_id'];

        if ($business_id > 0 && get_post_status($business_id)) {
             // Link to edit business page if it exists
             $edit_link = get_edit_post_link($business_id);
             return sprintf('<a href="%s">%s</a>', esc_url($edit_link), esc_html($business_name));
        }
        // Just display name if post doesn't exist
        return esc_html($business_name);
    }

    /**
     * Render the review text column (trimmed).
     *
     * @param array $item
     * @return string
     */
    public function column_review_text($item) {
        return esc_html(wp_trim_words($item['review_text'], 20, '...'));
    }

    /**
     * Render the rating column.
     *
     * @param array $item
     * @return string
     */
    public function column_rating($item) {
         $rating = (int)$item['rating'];
         $stars = str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
         return sprintf('<span style="color:#ffb400;">%s</span> (%d/5)', $stars, $rating);
    }

     /**
      * Render the status column.
      *
      * @param array $item
      * @return string
      */
     public function column_approved($item) {
         if ($item['approved']) {
             return '<span style="color:green;">' . __('Approved', 'local-business-directory') . '</span>';
         } else {
             return '<span style="color:red;">' . __('Pending', 'local-business-directory') . '</span>';
         }
     }

     /**
      * Render the date column.
      *
      * @param array $item
      * @return string
      */
     public function column_review_date($item) {
         return date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($item['review_date']));
     }


    /**
     * Render other columns.
     *
     * @param array $item
     * @param string $column_name
     * @return mixed
     */
    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'source':
                return esc_html(ucfirst($item[$column_name]));
            // Add cases for other columns if needed
            default:
                return isset($item[$column_name]) ? esc_html($item[$column_name]) : '';
        }
    }

    /**
     * Define bulk actions.
     *
     * @return array
     */
    public function get_bulk_actions() {
        $actions = array(
            'bulk-approve'   => __('Approve', 'local-business-directory'),
            'bulk-unapprove' => __('Unapprove', 'local-business-directory'),
            'bulk-delete'    => __('Delete', 'local-business-directory')
        );
        return $actions;
    }

    /**
     * Message to display when no items are found.
     */
    public function no_items() {
        _e('No reviews found.', 'local-business-directory');
    }
}
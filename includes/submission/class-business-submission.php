<?php
/**
 * Business Submission Class
 * 
 * Handles the custom post type and core functionality for business submissions
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class LBD_Business_Submission {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_meta_fields'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_fields'));
        add_filter('manage_business_submission_posts_columns', array($this, 'add_admin_columns'));
        add_action('manage_business_submission_posts_custom_column', array($this, 'populate_admin_columns'), 10, 2);
        add_filter('manage_edit-business_submission_sortable_columns', array($this, 'make_columns_sortable'));
    }
    
    /**
     * Register the business_submission post type
     */
    public function register_post_type() {
        $labels = array(
            'name' => 'Business Submissions',
            'singular_name' => 'Business Submission',
            'menu_name' => 'Business Submissions',
            'add_new' => 'Add New Submission',
            'add_new_item' => 'Add New Business Submission',
            'edit_item' => 'Edit Business Submission',
            'new_item' => 'New Business Submission',
            'view_item' => 'View Business Submission',
            'search_items' => 'Search Business Submissions',
            'not_found' => 'No business submissions found',
            'not_found_in_trash' => 'No business submissions found in trash',
        );
        
        $args = array(
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'edit.php?post_type=business',
            'capability_type' => 'post',
            'hierarchical' => false,
            'rewrite' => false,
            'supports' => array('title', 'editor'),
            'menu_position' => 20,
        );
        
        register_post_type('business_submission', $args);
    }
    
    /**
     * Register meta fields
     */
    public function register_meta_fields() {
        // Submission status
        register_post_meta('business_submission', 'submission_status', array(
            'type' => 'string',
            'default' => 'pending',
            'single' => true,
            'show_in_rest' => false,
        ));
        
        // Submission type
        register_post_meta('business_submission', 'submission_type', array(
            'type' => 'string',
            'default' => 'new_business',
            'single' => true,
            'show_in_rest' => false,
        ));
        
        // Business owner info
        register_post_meta('business_submission', 'business_owner_name', array(
            'type' => 'string',
            'single' => true,
            'show_in_rest' => false,
        ));
        
        register_post_meta('business_submission', 'business_owner_email', array(
            'type' => 'string',
            'single' => true,
            'show_in_rest' => false,
        ));
        
        register_post_meta('business_submission', 'business_owner_phone', array(
            'type' => 'string',
            'single' => true,
            'show_in_rest' => false,
        ));
        
        // Submission tracking
        register_post_meta('business_submission', 'submission_date', array(
            'type' => 'string',
            'single' => true,
            'show_in_rest' => false,
        ));
        
        register_post_meta('business_submission', 'reviewed_date', array(
            'type' => 'string',
            'single' => true,
            'show_in_rest' => false,
        ));
        
        register_post_meta('business_submission', 'reviewer_id', array(
            'type' => 'integer',
            'single' => true,
            'show_in_rest' => false,
        ));
        
        register_post_meta('business_submission', 'reviewer_notes', array(
            'type' => 'string',
            'single' => true,
            'show_in_rest' => false,
        ));
        
        // Claim specific
        register_post_meta('business_submission', 'claimed_business_id', array(
            'type' => 'integer',
            'single' => true,
            'show_in_rest' => false,
        ));
        
        // Original submission data (JSON)
        register_post_meta('business_submission', 'original_submission_data', array(
            'type' => 'string',
            'single' => true,
            'show_in_rest' => false,
        ));
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'submission_details',
            'Submission Details',
            array($this, 'submission_details_meta_box'),
            'business_submission',
            'normal',
            'high'
        );
        
        add_meta_box(
            'business_owner_info',
            'Business Owner Information',
            array($this, 'business_owner_meta_box'),
            'business_submission',
            'side',
            'high'
        );
        
        add_meta_box(
            'submission_actions',
            'Submission Actions',
            array($this, 'submission_actions_meta_box'),
            'business_submission',
            'side',
            'high'
        );
    }
    
    /**
     * Submission details meta box
     */
    public function submission_details_meta_box($post) {
        wp_nonce_field('lbd_submission_meta_box', 'lbd_submission_meta_box_nonce');
        
        $status = get_post_meta($post->ID, 'submission_status', true);
        $type = get_post_meta($post->ID, 'submission_type', true);
        $submission_date = get_post_meta($post->ID, 'submission_date', true);
        $reviewed_date = get_post_meta($post->ID, 'reviewed_date', true);
        $reviewer_notes = get_post_meta($post->ID, 'reviewer_notes', true);
        $claimed_business_id = get_post_meta($post->ID, 'claimed_business_id', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="submission_status">Status</label></th>
                <td>
                    <select name="submission_status" id="submission_status">
                        <option value="pending" <?php selected($status, 'pending'); ?>>Pending</option>
                        <option value="approved" <?php selected($status, 'approved'); ?>>Approved</option>
                        <option value="rejected" <?php selected($status, 'rejected'); ?>>Rejected</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="submission_type">Type</label></th>
                <td>
                    <select name="submission_type" id="submission_type">
                        <option value="new_business" <?php selected($type, 'new_business'); ?>>New Business</option>
                        <option value="claim_business" <?php selected($type, 'claim_business'); ?>>Claim Business</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="submission_date">Submitted</label></th>
                <td>
                    <input type="text" name="submission_date" id="submission_date" value="<?php echo esc_attr($submission_date); ?>" readonly />
                </td>
            </tr>
            <tr>
                <th><label for="reviewed_date">Reviewed</label></th>
                <td>
                    <input type="text" name="reviewed_date" id="reviewed_date" value="<?php echo esc_attr($reviewed_date); ?>" readonly />
                </td>
            </tr>
            <?php if ($type === 'claim_business' && $claimed_business_id): ?>
            <tr>
                <th><label for="claimed_business_id">Claimed Business</label></th>
                <td>
                    <?php 
                    $claimed_business = get_post($claimed_business_id);
                    if ($claimed_business) {
                        echo '<a href="' . get_edit_post_link($claimed_business_id) . '">' . esc_html($claimed_business->post_title) . '</a>';
                    } else {
                        echo 'Business not found (ID: ' . esc_html($claimed_business_id) . ')';
                    }
                    ?>
                </td>
            </tr>
            <?php endif; ?>
            <tr>
                <th><label for="reviewer_notes">Reviewer Notes</label></th>
                <td>
                    <textarea name="reviewer_notes" id="reviewer_notes" rows="4" cols="50"><?php echo esc_textarea($reviewer_notes); ?></textarea>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Business owner meta box
     */
    public function business_owner_meta_box($post) {
        $owner_name = get_post_meta($post->ID, 'business_owner_name', true);
        $owner_email = get_post_meta($post->ID, 'business_owner_email', true);
        $owner_phone = get_post_meta($post->ID, 'business_owner_phone', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th><label for="business_owner_name">Name</label></th>
                <td>
                    <input type="text" name="business_owner_name" id="business_owner_name" value="<?php echo esc_attr($owner_name); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th><label for="business_owner_email">Email</label></th>
                <td>
                    <input type="email" name="business_owner_email" id="business_owner_email" value="<?php echo esc_attr($owner_email); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th><label for="business_owner_phone">Phone</label></th>
                <td>
                    <input type="text" name="business_owner_phone" id="business_owner_phone" value="<?php echo esc_attr($owner_phone); ?>" class="regular-text" />
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Submission actions meta box
     */
    public function submission_actions_meta_box($post) {
        $status = get_post_meta($post->ID, 'submission_status', true);
        $type = get_post_meta($post->ID, 'submission_type', true);
        
        ?>
        <div class="submission-actions">
            <?php if ($status === 'pending'): ?>
                <p><strong>Actions:</strong></p>
                <p>
                    <button type="button" class="button button-primary approve-submission" data-id="<?php echo $post->ID; ?>">
                        Approve & Create Business
                    </button>
                </p>
                <p>
                    <button type="button" class="button button-secondary reject-submission" data-id="<?php echo $post->ID; ?>">
                        Reject Submission
                    </button>
                </p>
            <?php elseif ($status === 'approved'): ?>
                <p><strong>Status:</strong> Approved</p>
                <?php if ($type === 'claim_business'): ?>
                    <p>The business has been claimed successfully.</p>
                <?php else: ?>
                    <p>A new business has been created from this submission.</p>
                <?php endif; ?>
            <?php elseif ($status === 'rejected'): ?>
                <p><strong>Status:</strong> Rejected</p>
                <p>This submission was rejected.</p>
            <?php endif; ?>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('.approve-submission').on('click', function() {
                if (confirm('Are you sure you want to approve this submission?')) {
                    // TODO: Implement approval action
                    alert('Approval functionality will be implemented next.');
                }
            });
            
            $('.reject-submission').on('click', function() {
                if (confirm('Are you sure you want to reject this submission?')) {
                    // TODO: Implement rejection action
                    alert('Rejection functionality will be implemented next.');
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Save meta fields
     */
    public function save_meta_fields($post_id) {
        // Check nonce
        if (!isset($_POST['lbd_submission_meta_box_nonce']) || 
            !wp_verify_nonce($_POST['lbd_submission_meta_box_nonce'], 'lbd_submission_meta_box')) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save fields
        $fields = array(
            'submission_status',
            'submission_type',
            'business_owner_name',
            'business_owner_email',
            'business_owner_phone',
            'reviewer_notes'
        );
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }
        
        // Update reviewed date if status changed
        $old_status = get_post_meta($post_id, 'submission_status', true);
        $new_status = $_POST['submission_status'] ?? $old_status;
        
        if ($old_status !== $new_status && $new_status !== 'pending') {
            update_post_meta($post_id, 'reviewed_date', current_time('mysql'));
            update_post_meta($post_id, 'reviewer_id', get_current_user_id());
        }
    }
    
    /**
     * Add admin columns
     */
    public function add_admin_columns($columns) {
        $new_columns = array();
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['submission_type'] = 'Type';
                $new_columns['business_owner'] = 'Business Owner';
                $new_columns['submission_date'] = 'Submitted';
                $new_columns['status'] = 'Status';
            }
        }
        
        return $new_columns;
    }
    
    /**
     * Populate admin columns
     */
    public function populate_admin_columns($column, $post_id) {
        switch ($column) {
            case 'submission_type':
                $type = get_post_meta($post_id, 'submission_type', true);
                echo $type === 'claim_business' ? 'Claim Business' : 'New Business';
                break;
                
            case 'business_owner':
                $name = get_post_meta($post_id, 'business_owner_name', true);
                $email = get_post_meta($post_id, 'business_owner_email', true);
                echo esc_html($name) . '<br><small>' . esc_html($email) . '</small>';
                break;
                
            case 'submission_date':
                $date = get_post_meta($post_id, 'submission_date', true);
                echo $date ? date('M j, Y', strtotime($date)) : 'N/A';
                break;
                
            case 'status':
                $status = get_post_meta($post_id, 'submission_status', true);
                $status_labels = array(
                    'pending' => '<span style="color: #f0ad4e;">Pending</span>',
                    'approved' => '<span style="color: #5cb85c;">Approved</span>',
                    'rejected' => '<span style="color: #d9534f;">Rejected</span>'
                );
                echo $status_labels[$status] ?? $status;
                break;
        }
    }
    
    /**
     * Make columns sortable
     */
    public function make_columns_sortable($columns) {
        $columns['submission_type'] = 'submission_type';
        $columns['submission_date'] = 'submission_date';
        $columns['status'] = 'status';
        return $columns;
    }
}

// Initialize the class
new LBD_Business_Submission(); 
<?php
/**
 * Duplicate Business Manager
 * 
 * Helps identify and manage duplicate business listings
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add the Duplicate Manager submenu page
 */
function lbd_add_duplicate_manager_page() {
    add_submenu_page(
        'edit.php?post_type=business',
        'Duplicate Manager',
        'Duplicate Manager',
        'manage_options',
        'lbd-duplicate-manager',
        'lbd_duplicate_manager_page'
    );
}
add_action('admin_menu', 'lbd_add_duplicate_manager_page');

/**
 * Render the Duplicate Manager admin page
 */
function lbd_duplicate_manager_page() {
    // Security check
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    
    // Process deletion if requested
    if (isset($_POST['action']) && $_POST['action'] === 'delete_duplicates') {
        lbd_process_duplicate_deletion();
    }
    
    // Check if scan was requested
    $scan_results = array();
    $scan_requested = isset($_POST['action']) && $_POST['action'] === 'scan_duplicates';
    
    if ($scan_requested) {
        // Verify nonce
        check_admin_referer('lbd_scan_duplicates_nonce');
        
        // Get scan criteria
        $criteria = isset($_POST['duplicate_criteria']) ? sanitize_text_field($_POST['duplicate_criteria']) : 'title_postcode';
        
        // Run the scan
        $scan_results = lbd_scan_for_duplicates($criteria);
    }
    
    ?>
    <div class="wrap">
        <h1>Business Directory: Duplicate Manager</h1>
        
        <div class="card">
            <h2>Scan for Duplicate Businesses</h2>
            <p>Use this tool to identify potential duplicate business listings in your directory.</p>
            
            <form method="post" action="">
                <?php wp_nonce_field('lbd_scan_duplicates_nonce'); ?>
                <input type="hidden" name="action" value="scan_duplicates">
                
                <p>
                    <label for="duplicate_criteria"><strong>Define duplicates by:</strong></label>
                    <select name="duplicate_criteria" id="duplicate_criteria">
                        <option value="title_postcode">Same Title + Same Postcode</option>
                        <option value="title_postcode_street">Same Title + Same Postcode + Same Street Address</option>
                        <option value="title_only">Same Title Only (Less Accurate)</option>
                    </select>
                </p>
                
                <p>
                    <button type="submit" class="button button-primary">Scan for Duplicates</button>
                </p>
            </form>
        </div>
        
        <?php if ($scan_requested): ?>
            <div class="scan-results">
                <h2>Scan Results</h2>
                
                <?php if (empty($scan_results)): ?>
                    <div class="notice notice-success">
                        <p>Great news! No duplicate businesses were found.</p>
                    </div>
                <?php else: ?>
                    <div class="notice notice-warning">
                        <p>Found <?php echo count($scan_results); ?> sets of potential duplicate businesses.</p>
                        <p><strong>Warning:</strong> Deleting duplicate businesses is permanent and cannot be undone. Please review carefully.</p>
                    </div>
                    
                    <form method="post" action="" id="duplicate-form">
                        <?php wp_nonce_field('lbd_delete_duplicates_nonce'); ?>
                        <input type="hidden" name="action" value="delete_duplicates">
                        
                        <div class="tablenav top">
                            <div class="alignleft actions">
                                <label style="margin-right: 15px;">
                                    <input type="checkbox" id="select-all-duplicates" /> <strong>Select All Duplicates</strong>
                                </label>
                                <button type="submit" class="button button-secondary delete-duplicates-button">Delete Selected Duplicates</button>
                            </div>
                            <div class="tablenav-pages">
                                <span class="displaying-num"><?php echo count($scan_results); ?> duplicate groups</span>
                            </div>
                            <br class="clear">
                        </div>
                        
                        <?php foreach ($scan_results as $group_index => $group): ?>
                            <div class="duplicate-group card">
                                <h3>Duplicate Group #<?php echo $group_index + 1; ?></h3>
                                
                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th class="check-column"><input type="checkbox" class="group-select-all" /></th>
                                            <th width="5%">ID</th>
                                            <th width="30%">Business Name</th>
                                            <th width="20%">Address</th>
                                            <th width="15%">Postcode</th>
                                            <th width="15%">Published Date</th>
                                            <th width="15%">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        // Sort by date (oldest first)
                                        usort($group, function($a, $b) {
                                            return strtotime($a->post_date) - strtotime($b->post_date);
                                        });
                                        
                                        $oldest_post = $group[0];
                                        
                                        foreach ($group as $index => $post): 
                                            $post_id = $post->ID;
                                            $is_oldest = ($post->ID === $oldest_post->ID);
                                            $street_address = get_post_meta($post_id, 'lbd_street_address', true);
                                            $postcode = get_post_meta($post_id, 'lbd_postcode', true);
                                            $edit_link = get_edit_post_link($post_id);
                                            $view_link = get_permalink($post_id);
                                        ?>
                                            <tr <?php if ($is_oldest) echo 'class="original-post"'; ?>>
                                                <td>
                                                    <?php if (!$is_oldest): ?>
                                                        <input type="checkbox" name="delete_posts[]" value="<?php echo $post_id; ?>" />
                                                    <?php else: ?>
                                                        <span title="This is the oldest post, likely the original">👑</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $post_id; ?></td>
                                                <td>
                                                    <strong><?php echo esc_html($post->post_title); ?></strong>
                                                    <?php if ($is_oldest): ?>
                                                        <span class="original-badge">Oldest</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo esc_html($street_address); ?></td>
                                                <td><?php echo esc_html($postcode); ?></td>
                                                <td><?php echo date('Y-m-d', strtotime($post->post_date)); ?></td>
                                                <td>
                                                    <a href="<?php echo $edit_link; ?>" class="button button-small">Edit</a>
                                                    <a href="<?php echo $view_link; ?>" class="button button-small" target="_blank">View</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="tablenav bottom">
                            <div class="alignleft actions">
                                <label style="margin-right: 15px;">
                                    <input type="checkbox" id="select-all-duplicates-bottom" class="select-all-duplicates" /> <strong>Select All Duplicates</strong>
                                </label>
                                <button type="submit" class="button button-secondary delete-duplicates-button">Delete Selected Duplicates</button>
                            </div>
                        </div>
                    </form>
                    
                    <div id="delete-confirm-dialog" title="Confirm Deletion" style="display:none;">
                        <p><span class="ui-icon ui-icon-alert" style="float:left; margin:12px 12px 20px 0;"></span>
                        This action will permanently delete the selected businesses. This cannot be undone. Are you sure?</p>
                    </div>
                    
                    <style>
                        .original-post {
                            background-color: #f7f9fa;
                        }
                        .original-badge {
                            display: inline-block;
                            background: #f0f5fa;
                            color: #0073aa;
                            padding: 2px 6px;
                            border-radius: 3px;
                            font-size: 0.8em;
                            margin-left: 5px;
                        }
                        .duplicate-group {
                            margin-bottom: 20px;
                        }
                        .duplicate-group h3 {
                            margin: 0 0 10px 0;
                        }
                    </style>
                    
                    <script>
                        jQuery(document).ready(function($) {
                            // Enable jQuery UI dialog
                            $("#delete-confirm-dialog").dialog({
                                autoOpen: false,
                                modal: true,
                                buttons: {
                                    "Delete Selected": function() {
                                        $("#duplicate-form").submit();
                                    },
                                    "Cancel": function() {
                                        $(this).dialog("close");
                                    }
                                }
                            });
                            
                            // Show confirmation dialog on delete
                            $(".delete-duplicates-button").click(function(e) {
                                e.preventDefault();
                                
                                var checkedCount = $("input[name='delete_posts[]']:checked").length;
                                if (checkedCount === 0) {
                                    alert("Please select at least one duplicate to delete.");
                                    return;
                                }
                                
                                $("#delete-confirm-dialog").dialog("open");
                            });
                            
                            // Global Select All checkbox
                            $("#select-all-duplicates").click(function() {
                                var isChecked = $(this).prop("checked");
                                $("input[name='delete_posts[]']").prop("checked", isChecked);
                                
                                // Also update the group select-all checkboxes to match
                                $(".group-select-all").prop("checked", isChecked);
                                
                                // Keep bottom select-all in sync
                                $("#select-all-duplicates-bottom").prop("checked", isChecked);
                            });
                            
                            // Bottom select all checkbox
                            $("#select-all-duplicates-bottom").click(function() {
                                var isChecked = $(this).prop("checked");
                                $("input[name='delete_posts[]']").prop("checked", isChecked);
                                
                                // Also update the group select-all checkboxes to match
                                $(".group-select-all").prop("checked", isChecked);
                                
                                // Keep top select-all in sync
                                $("#select-all-duplicates").prop("checked", isChecked);
                            });
                            
                            // Group select all checkboxes
                            $(".group-select-all").click(function() {
                                var isChecked = $(this).prop("checked");
                                $(this).closest("table").find("input[name='delete_posts[]']").prop("checked", isChecked);
                                
                                // Update the global select-all checkbox if necessary
                                updateGlobalSelectAll();
                            });
                            
                            // Update the global select-all checkbox based on individual selections
                            $("input[name='delete_posts[]']").click(function() {
                                // Update the group checkbox
                                var table = $(this).closest("table");
                                var allChecked = table.find("input[name='delete_posts[]']").length === 
                                                table.find("input[name='delete_posts[]']:checked").length;
                                table.find(".group-select-all").prop("checked", allChecked);
                                
                                // Update the global checkbox
                                updateGlobalSelectAll();
                            });
                            
                            // Helper function to update the global select-all checkbox
                            function updateGlobalSelectAll() {
                                var totalCheckboxes = $("input[name='delete_posts[]']").length;
                                var checkedCheckboxes = $("input[name='delete_posts[]']:checked").length;
                                var allChecked = (totalCheckboxes > 0 && totalCheckboxes === checkedCheckboxes);
                                
                                // Update both top and bottom select-all checkboxes
                                $("#select-all-duplicates").prop("checked", allChecked);
                                $("#select-all-duplicates-bottom").prop("checked", allChecked);
                            }
                        });
                    </script>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Scan for duplicate businesses
 *
 * @param string $criteria Criteria for determining duplicates
 * @return array Groups of duplicate posts
 */
function lbd_scan_for_duplicates($criteria = 'title_postcode') {
    global $wpdb;
    
    // Determine the SQL based on criteria
    switch ($criteria) {
        case 'title_postcode_street':
            // Using title + postcode + street address
            $sql = $wpdb->prepare(
                "SELECT p.ID, p.post_title, p.post_date
                FROM {$wpdb->posts} p
                JOIN {$wpdb->postmeta} pm_postcode ON p.ID = pm_postcode.post_id AND pm_postcode.meta_key = 'lbd_postcode'
                JOIN {$wpdb->postmeta} pm_street ON p.ID = pm_street.post_id AND pm_street.meta_key = 'lbd_street_address'
                WHERE p.post_type = 'business' 
                AND p.post_status = 'publish'
                AND (pm_postcode.meta_value != '' OR pm_street.meta_value != '')
                ORDER BY p.post_title, pm_postcode.meta_value, pm_street.meta_value"
            );
            break;
            
        case 'title_only':
            // Using title only - less accurate but catches more potential duplicates
            $sql = $wpdb->prepare(
                "SELECT p.ID, p.post_title, p.post_date
                FROM {$wpdb->posts} p
                WHERE p.post_type = 'business' 
                AND p.post_status = 'publish'
                ORDER BY p.post_title"
            );
            break;
            
        case 'title_postcode':
        default:
            // Using title + postcode (default)
            $sql = $wpdb->prepare(
                "SELECT p.ID, p.post_title, p.post_date
                FROM {$wpdb->posts} p
                JOIN {$wpdb->postmeta} pm_postcode ON p.ID = pm_postcode.post_id AND pm_postcode.meta_key = 'lbd_postcode'
                WHERE p.post_type = 'business' 
                AND p.post_status = 'publish'
                AND pm_postcode.meta_value != ''
                ORDER BY p.post_title, pm_postcode.meta_value"
            );
            break;
    }
    
    // Get all businesses
    $posts = $wpdb->get_results($sql);
    
    // Group by duplicate criteria
    $groups = array();
    $current_group = array();
    $prev_title = '';
    $prev_postcode = '';
    $prev_street = '';
    
    foreach ($posts as $post) {
        $post_id = $post->ID;
        $title = $post->post_title;
        $postcode = get_post_meta($post_id, 'lbd_postcode', true);
        $street = get_post_meta($post_id, 'lbd_street_address', true);
        
        $is_duplicate = false;
        
        switch ($criteria) {
            case 'title_postcode_street':
                $is_duplicate = ($title === $prev_title && $postcode === $prev_postcode && $street === $prev_street && !empty($current_group));
                break;
                
            case 'title_only':
                $is_duplicate = ($title === $prev_title && !empty($current_group));
                break;
                
            case 'title_postcode':
            default:
                $is_duplicate = ($title === $prev_title && $postcode === $prev_postcode && !empty($current_group));
                break;
        }
        
        if ($is_duplicate) {
            // Add to current group
            $current_group[] = $post;
        } else {
            // Start a new group
            if (count($current_group) > 1) {
                // Only store groups with multiple posts (actual duplicates)
                $groups[] = $current_group;
            }
            
            $current_group = array($post);
        }
        
        // Update previous values
        $prev_title = $title;
        $prev_postcode = $postcode;
        $prev_street = $street;
    }
    
    // Check the last group
    if (count($current_group) > 1) {
        $groups[] = $current_group;
    }
    
    return $groups;
}

/**
 * Process the deletion of selected duplicate posts
 */
function lbd_process_duplicate_deletion() {
    // Security checks
    check_admin_referer('lbd_delete_duplicates_nonce');
    
    if (!current_user_can('delete_posts') || !current_user_can('delete_others_posts')) {
        wp_die(__('You do not have sufficient permissions to delete posts.'));
    }
    
    // Get posts to delete
    $post_ids = isset($_POST['delete_posts']) ? $_POST['delete_posts'] : array();
    
    if (empty($post_ids)) {
        // No posts selected
        add_settings_error(
            'lbd_duplicate_manager',
            'no_posts_selected',
            'No duplicate businesses were selected for deletion.',
            'error'
        );
        return;
    }
    
    // Track results
    $success_count = 0;
    $error_count = 0;
    
    // Process deletions
    foreach ($post_ids as $post_id) {
        $post_id = intval($post_id);
        
        // Verify it's a business post
        $post_type = get_post_type($post_id);
        if ($post_type !== 'business') {
            $error_count++;
            continue;
        }
        
        // Delete the post permanently
        $result = wp_delete_post($post_id, true);
        
        if ($result) {
            $success_count++;
        } else {
            $error_count++;
        }
    }
    
    // Show results
    if ($success_count > 0) {
        add_settings_error(
            'lbd_duplicate_manager',
            'posts_deleted',
            sprintf('%d duplicate businesses were successfully deleted.', $success_count),
            'success'
        );
    }
    
    if ($error_count > 0) {
        add_settings_error(
            'lbd_duplicate_manager',
            'deletion_errors',
            sprintf('Failed to delete %d businesses. Please try again.', $error_count),
            'error'
        );
    }
}

/**
 * Enqueue jQuery UI for the confirmation dialog
 */
function lbd_duplicate_manager_scripts($hook) {
    if ($hook !== 'business_page_lbd-duplicate-manager') {
        return;
    }
    
    wp_enqueue_script('jquery-ui-dialog');
    wp_enqueue_style('wp-jquery-ui-dialog');
}
add_action('admin_enqueue_scripts', 'lbd_duplicate_manager_scripts'); 
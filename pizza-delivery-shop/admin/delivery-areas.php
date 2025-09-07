<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Delivery areas management page
 */
function pds_delivery_areas_page() {
    global $wpdb;
    
    $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
    $area_id = isset($_GET['area_id']) ? intval($_GET['area_id']) : 0;
    
    // Handle form submissions
    if (isset($_POST['save_area']) && wp_verify_nonce($_POST['pds_nonce'], 'save_area')) {
        pds_save_delivery_area();
    }
    
    switch ($action) {
        case 'add':
            pds_add_delivery_area_form();
            break;
        case 'edit':
            pds_edit_delivery_area_form($area_id);
            break;
        case 'delete':
            pds_delete_delivery_area($area_id);
            pds_list_delivery_areas();
            break;
        default:
            pds_list_delivery_areas();
            break;
    }
}

/**
 * List all delivery areas
 */
function pds_list_delivery_areas() {
    global $wpdb;
    
    $areas = $wpdb->get_results(
        "SELECT a.*, COUNT(o.order_id) as order_count 
         FROM {$wpdb->prefix}pizza_delivery_areas a 
         LEFT JOIN {$wpdb->prefix}pizza_orders o ON a.area_id = o.delivery_area_id
         GROUP BY a.area_id 
         ORDER BY a.area_name"
    );
    
    ?>
    <div class="wrap">
        <h1>Delivery Areas Management 
            <a href="<?php echo admin_url('admin.php?page=pds-delivery-areas&action=add'); ?>" class="page-title-action">Add New Area</a>
        </h1>
        
        <?php if ($areas): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Area Name</th>
                        <th>Delivery Charge</th>
                        <th>Minimum Order</th>
                        <th>Orders Count</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($areas as $area): ?>
                        <tr>
                            <td><strong><?php echo $area->area_id; ?></strong></td>
                            <td><?php echo esc_html($area->area_name); ?></td>
                            <td>$<?php echo number_format($area->delivery_charge, 2); ?></td>
                            <td>$<?php echo number_format($area->minimum_order, 2); ?></td>
                            <td><?php echo $area->order_count; ?> orders</td>
                            <td>
                                <span class="pds-status-<?php echo $area->status; ?>">
                                    <?php echo ucfirst($area->status); ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=pds-delivery-areas&action=edit&area_id=' . $area->area_id); ?>" class="button button-small">Edit</a>
                                <?php if ($area->order_count == 0): ?>
                                    <a href="<?php echo admin_url('admin.php?page=pds-delivery-areas&action=delete&area_id=' . $area->area_id); ?>" 
                                       class="button button-small" 
                                       onclick="return confirm('Are you sure you want to delete this delivery area?')">Delete</a>
                                <?php else: ?>
                                    <span class="button button-small button-disabled" title="Cannot delete area with existing orders">Delete</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No delivery areas found. <a href="<?php echo admin_url('admin.php?page=pds-delivery-areas&action=add'); ?>">Add your first delivery area</a>.</p>
        <?php endif; ?>
        
        <div class="pds-help-section">
            <h3>Delivery Areas Management Tips</h3>
            <ul>
                <li><strong>Delivery Charge:</strong> Fixed fee added to delivery orders in this area</li>
                <li><strong>Minimum Order:</strong> Minimum order amount required for delivery to this area</li>
                <li><strong>Status:</strong> Inactive areas won't be available for selection</li>
                <li><strong>Deletion:</strong> Areas with existing orders cannot be deleted</li>
            </ul>
        </div>
    </div>
    <?php
}

/**
 * Add delivery area form
 */
function pds_add_delivery_area_form() {
    ?>
    <div class="wrap">
        <h1>Add New Delivery Area</h1>
        
        <form method="post">
            <?php wp_nonce_field('save_area', 'pds_nonce'); ?>
            <input type="hidden" name="action" value="add">
            
            <table class="form-table">
                <tr>
                    <th scope="row">Area Name *</th>
                    <td>
                        <input type="text" name="area_name" required class="regular-text" placeholder="e.g., Downtown, Suburbs North" />
                        <p class="description">Enter a descriptive name for the delivery area</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Delivery Charge *</th>
                    <td>
                        $<input type="number" name="delivery_charge" step="0.01" min="0" required class="small-text" placeholder="5.00" />
                        <p class="description">Fixed delivery fee for this area</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Minimum Order *</th>
                    <td>
                        $<input type="number" name="minimum_order" step="0.01" min="0" required class="small-text" placeholder="20.00" />
                        <p class="description">Minimum order amount required for delivery to this area</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Status</th>
                    <td>
                        <select name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="save_area" class="button-primary" value="Add Delivery Area" />
                <a href="<?php echo admin_url('admin.php?page=pds-delivery-areas'); ?>" class="button">Cancel</a>
            </p>
        </form>
    </div>
    <?php
}

/**
 * Edit delivery area form
 */
function pds_edit_delivery_area_form($area_id) {
    global $wpdb;
    
    $area = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}pizza_delivery_areas WHERE area_id = %d",
        $area_id
    ));
    
    if (!$area) {
        echo '<div class="wrap"><h1>Delivery Area not found</h1><p>The requested delivery area could not be found.</p></div>';
        return;
    }
    
    ?>
    <div class="wrap">
        <h1>Edit Delivery Area</h1>
        
        <form method="post">
            <?php wp_nonce_field('save_area', 'pds_nonce'); ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="area_id" value="<?php echo $area->area_id; ?>">
            
            <table class="form-table">
                <tr>
                    <th scope="row">Area ID</th>
                    <td><strong><?php echo $area->area_id; ?></strong></td>
                </tr>
                <tr>
                    <th scope="row">Area Name *</th>
                    <td>
                        <input type="text" name="area_name" value="<?php echo esc_attr($area->area_name); ?>" required class="regular-text" />
                        <p class="description">Enter a descriptive name for the delivery area</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Delivery Charge *</th>
                    <td>
                        $<input type="number" name="delivery_charge" value="<?php echo $area->delivery_charge; ?>" step="0.01" min="0" required class="small-text" />
                        <p class="description">Fixed delivery fee for this area</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Minimum Order *</th>
                    <td>
                        $<input type="number" name="minimum_order" value="<?php echo $area->minimum_order; ?>" step="0.01" min="0" required class="small-text" />
                        <p class="description">Minimum order amount required for delivery to this area</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Status</th>
                    <td>
                        <select name="status">
                            <option value="active" <?php selected($area->status, 'active'); ?>>Active</option>
                            <option value="inactive" <?php selected($area->status, 'inactive'); ?>>Inactive</option>
                        </select>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="save_area" class="button-primary" value="Update Delivery Area" />
                <a href="<?php echo admin_url('admin.php?page=pds-delivery-areas'); ?>" class="button">Cancel</a>
            </p>
        </form>
    </div>
    <?php
}

/**
 * Save delivery area (add or edit)
 */
function pds_save_delivery_area() {
    global $wpdb;
    
    if (!wp_verify_nonce($_POST['pds_nonce'], 'save_area')) {
        wp_die('Security check failed');
    }
    
    $action = sanitize_text_field($_POST['action']);
    $area_id = isset($_POST['area_id']) ? intval($_POST['area_id']) : 0;
    $area_name = sanitize_text_field($_POST['area_name']);
    $delivery_charge = floatval($_POST['delivery_charge']);
    $minimum_order = floatval($_POST['minimum_order']);
    $status = sanitize_text_field($_POST['status']);
    
    // Validate required fields
    if (empty($area_name) || $delivery_charge < 0 || $minimum_order < 0) {
        echo '<div class="notice notice-error"><p>Please fill in all required fields with valid values.</p></div>';
        return;
    }
    
    // Check for duplicate area name
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT area_id FROM {$wpdb->prefix}pizza_delivery_areas 
         WHERE area_name = %s AND area_id != %d",
        $area_name,
        $area_id
    ));
    
    if ($existing) {
        echo '<div class="notice notice-error"><p>A delivery area with this name already exists.</p></div>';
        return;
    }
    
    $area_data = array(
        'area_name' => $area_name,
        'delivery_charge' => $delivery_charge,
        'minimum_order' => $minimum_order,
        'status' => $status
    );
    
    if ($action === 'add') {
        // Insert new delivery area
        $result = $wpdb->insert(
            $wpdb->prefix . 'pizza_delivery_areas',
            $area_data
        );
        
        if ($result) {
            $new_area_id = $wpdb->insert_id;
            echo '<div class="notice notice-success"><p>Delivery area added successfully! Area ID: ' . $new_area_id . '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Error adding delivery area.</p></div>';
        }
    } else {
        // Update existing delivery area
        $result = $wpdb->update(
            $wpdb->prefix . 'pizza_delivery_areas',
            $area_data,
            array('area_id' => $area_id)
        );
        
        if ($result !== false) {
            echo '<div class="notice notice-success"><p>Delivery area updated successfully!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Error updating delivery area.</p></div>';
        }
    }
}

/**
 * Delete delivery area
 */
function pds_delete_delivery_area($area_id) {
    global $wpdb;
    
    if (!$area_id) {
        return;
    }
    
    // Check if area has orders
    $order_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}pizza_orders WHERE delivery_area_id = %d",
        $area_id
    ));
    
    if ($order_count > 0) {
        echo '<div class="notice notice-error"><p>Cannot delete delivery area that has existing orders.</p></div>';
        return;
    }
    
    // Delete delivery area
    $result = $wpdb->delete(
        $wpdb->prefix . 'pizza_delivery_areas',
        array('area_id' => $area_id)
    );
    
    if ($result) {
        echo '<div class="notice notice-success"><p>Delivery area deleted successfully!</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>Error deleting delivery area.</p></div>';
    }
}

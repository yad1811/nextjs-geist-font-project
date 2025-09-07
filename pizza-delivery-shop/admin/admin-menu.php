<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add admin menu for Pizza Delivery Shop
 */
function pds_admin_menu() {
    // Main menu page
    add_menu_page(
        'Pizza Delivery Shop',
        'Pizza Shop',
        'manage_options',
        'pizza-delivery-shop',
        'pds_admin_dashboard',
        'dashicons-store',
        30
    );
    
    // Dashboard submenu (same as main page)
    add_submenu_page(
        'pizza-delivery-shop',
        'Dashboard',
        'Dashboard',
        'manage_options',
        'pizza-delivery-shop',
        'pds_admin_dashboard'
    );
    
    // Products submenu
    add_submenu_page(
        'pizza-delivery-shop',
        'Products',
        'Products',
        'manage_options',
        'pds-products',
        'pds_products_page'
    );
    
    // Categories submenu
    add_submenu_page(
        'pizza-delivery-shop',
        'Categories',
        'Categories',
        'manage_options',
        'pds-categories',
        'pds_categories_page'
    );
    
    // Delivery Areas submenu
    add_submenu_page(
        'pizza-delivery-shop',
        'Delivery Areas',
        'Delivery Areas',
        'manage_options',
        'pds-delivery-areas',
        'pds_delivery_areas_page'
    );
    
    // Orders submenu
    add_submenu_page(
        'pizza-delivery-shop',
        'Orders',
        'Orders',
        'manage_options',
        'pds-orders',
        'pds_orders_page'
    );
    
    // Settings submenu
    add_submenu_page(
        'pizza-delivery-shop',
        'Settings',
        'Settings',
        'manage_options',
        'pds-settings',
        'pds_settings_page'
    );
}
add_action('admin_menu', 'pds_admin_menu');

/**
 * Admin dashboard page
 */
function pds_admin_dashboard() {
    global $wpdb;
    
    // Get statistics
    $total_products = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}pizza_products WHERE status = 'active'");
    $total_categories = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}pizza_categories WHERE status = 'active'");
    $total_areas = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}pizza_delivery_areas WHERE status = 'active'");
    $total_orders = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}pizza_orders");
    $pending_orders = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}pizza_orders WHERE order_status = 'pending'");
    
    ?>
    <div class="wrap">
        <h1>Pizza Delivery Shop Dashboard</h1>
        
        <div class="pds-dashboard-stats">
            <div class="pds-stat-box">
                <h3>Total Products</h3>
                <div class="pds-stat-number"><?php echo $total_products; ?></div>
            </div>
            
            <div class="pds-stat-box">
                <h3>Categories</h3>
                <div class="pds-stat-number"><?php echo $total_categories; ?></div>
            </div>
            
            <div class="pds-stat-box">
                <h3>Delivery Areas</h3>
                <div class="pds-stat-number"><?php echo $total_areas; ?></div>
            </div>
            
            <div class="pds-stat-box">
                <h3>Total Orders</h3>
                <div class="pds-stat-number"><?php echo $total_orders; ?></div>
            </div>
            
            <div class="pds-stat-box pending">
                <h3>Pending Orders</h3>
                <div class="pds-stat-number"><?php echo $pending_orders; ?></div>
            </div>
        </div>
        
        <div class="pds-dashboard-content">
            <div class="pds-dashboard-section">
                <h2>Quick Actions</h2>
                <div class="pds-quick-actions">
                    <a href="<?php echo admin_url('admin.php?page=pds-products&action=add'); ?>" class="button button-primary">Add New Product</a>
                    <a href="<?php echo admin_url('admin.php?page=pds-categories&action=add'); ?>" class="button">Add Category</a>
                    <a href="<?php echo admin_url('admin.php?page=pds-delivery-areas&action=add'); ?>" class="button">Add Delivery Area</a>
                    <a href="<?php echo admin_url('admin.php?page=pds-orders'); ?>" class="button">View Orders</a>
                </div>
            </div>
            
            <div class="pds-dashboard-section">
                <h2>Recent Orders</h2>
                <?php
                $recent_orders = $wpdb->get_results(
                    "SELECT * FROM {$wpdb->prefix}pizza_orders 
                     ORDER BY order_date DESC 
                     LIMIT 5"
                );
                
                if ($recent_orders) {
                    echo '<table class="wp-list-table widefat fixed striped">';
                    echo '<thead><tr><th>Order ID</th><th>Customer</th><th>Type</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>';
                    echo '<tbody>';
                    
                    foreach ($recent_orders as $order) {
                        echo '<tr>';
                        echo '<td>#' . $order->order_id . '</td>';
                        echo '<td>' . esc_html($order->customer_name) . '</td>';
                        echo '<td>' . ucfirst($order->order_type) . '</td>';
                        echo '<td>$' . number_format($order->total_amount, 2) . '</td>';
                        echo '<td><span class="pds-status-' . $order->order_status . '">' . ucfirst($order->order_status) . '</span></td>';
                        echo '<td>' . date('M j, Y g:i A', strtotime($order->order_date)) . '</td>';
                        echo '</tr>';
                    }
                    
                    echo '</tbody></table>';
                } else {
                    echo '<p>No orders yet.</p>';
                }
                ?>
            </div>
            
            <div class="pds-dashboard-section">
                <h2>How to Use</h2>
                <div class="pds-instructions">
                    <ol>
                        <li><strong>Set up your products:</strong> Go to Products → Add products with different sizes and prices</li>
                        <li><strong>Configure categories:</strong> Organize your products into categories (Pizza, Finger Food, etc.)</li>
                        <li><strong>Set delivery areas:</strong> Define delivery zones with charges and minimum orders</li>
                        <li><strong>Add the shortcode:</strong> Use <code>[pizza_shop_catalog]</code> on any page to display the ordering system</li>
                        <li><strong>Manage orders:</strong> View and update customer orders from the Orders page</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Orders page
 */
function pds_orders_page() {
    global $wpdb;
    
    // Handle order status updates
    if (isset($_POST['update_order_status']) && wp_verify_nonce($_POST['pds_nonce'], 'update_order_status')) {
        $order_id = intval($_POST['order_id']);
        $new_status = sanitize_text_field($_POST['order_status']);
        
        $wpdb->update(
            $wpdb->prefix . 'pizza_orders',
            array('order_status' => $new_status),
            array('order_id' => $order_id),
            array('%s'),
            array('%d')
        );
        
        echo '<div class="notice notice-success"><p>Order status updated successfully!</p></div>';
    }
    
    // Get all orders
    $orders = $wpdb->get_results(
        "SELECT o.*, a.area_name 
         FROM {$wpdb->prefix}pizza_orders o 
         LEFT JOIN {$wpdb->prefix}pizza_delivery_areas a ON o.delivery_area_id = a.area_id 
         ORDER BY o.order_date DESC"
    );
    
    ?>
    <div class="wrap">
        <h1>Orders Management</h1>
        
        <?php if ($orders): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer Info</th>
                        <th>Order Type</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><strong>#<?php echo $order->order_id; ?></strong></td>
                            <td>
                                <strong><?php echo esc_html($order->customer_name); ?></strong><br>
                                <?php echo esc_html($order->customer_phone); ?><br>
                                <?php if ($order->customer_email): ?>
                                    <?php echo esc_html($order->customer_email); ?><br>
                                <?php endif; ?>
                                <?php if ($order->order_type === 'delivery'): ?>
                                    <em>Area: <?php echo esc_html($order->area_name); ?></em><br>
                                    <em><?php echo esc_html($order->delivery_address); ?></em>
                                <?php endif; ?>
                            </td>
                            <td><?php echo ucfirst($order->order_type); ?></td>
                            <td>
                                <?php
                                $items = json_decode($order->order_items, true);
                                if ($items) {
                                    foreach ($items as $item) {
                                        echo esc_html($item['name']) . ' (' . ucfirst($item['size']) . ') x' . $item['quantity'] . '<br>';
                                    }
                                }
                                ?>
                            </td>
                            <td>
                                Subtotal: $<?php echo number_format($order->subtotal, 2); ?><br>
                                <?php if ($order->delivery_charge > 0): ?>
                                    Delivery: $<?php echo number_format($order->delivery_charge, 2); ?><br>
                                <?php endif; ?>
                                <strong>Total: $<?php echo number_format($order->total_amount, 2); ?></strong>
                            </td>
                            <td>
                                <span class="pds-status-<?php echo $order->order_status; ?>">
                                    <?php echo ucfirst($order->order_status); ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y g:i A', strtotime($order->order_date)); ?></td>
                            <td>
                                <form method="post" style="display: inline;">
                                    <?php wp_nonce_field('update_order_status', 'pds_nonce'); ?>
                                    <input type="hidden" name="order_id" value="<?php echo $order->order_id; ?>">
                                    <select name="order_status" onchange="this.form.submit()">
                                        <option value="pending" <?php selected($order->order_status, 'pending'); ?>>Pending</option>
                                        <option value="confirmed" <?php selected($order->order_status, 'confirmed'); ?>>Confirmed</option>
                                        <option value="preparing" <?php selected($order->order_status, 'preparing'); ?>>Preparing</option>
                                        <option value="ready" <?php selected($order->order_status, 'ready'); ?>>Ready</option>
                                        <option value="delivered" <?php selected($order->order_status, 'delivered'); ?>>Delivered</option>
                                        <option value="cancelled" <?php selected($order->order_status, 'cancelled'); ?>>Cancelled</option>
                                    </select>
                                    <input type="hidden" name="update_order_status" value="1">
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No orders found.</p>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Settings page
 */
function pds_settings_page() {
    // Handle settings save
    if (isset($_POST['save_settings']) && wp_verify_nonce($_POST['pds_nonce'], 'save_settings')) {
        update_option('pds_shop_name', sanitize_text_field($_POST['shop_name']));
        update_option('pds_shop_phone', sanitize_text_field($_POST['shop_phone']));
        update_option('pds_shop_email', sanitize_email($_POST['shop_email']));
        update_option('pds_shop_address', sanitize_textarea_field($_POST['shop_address']));
        update_option('pds_currency_symbol', sanitize_text_field($_POST['currency_symbol']));
        
        echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
    }
    
    // Get current settings
    $shop_name = get_option('pds_shop_name', 'Pizza Delivery Shop');
    $shop_phone = get_option('pds_shop_phone', '');
    $shop_email = get_option('pds_shop_email', '');
    $shop_address = get_option('pds_shop_address', '');
    $currency_symbol = get_option('pds_currency_symbol', '$');
    
    ?>
    <div class="wrap">
        <h1>Settings</h1>
        
        <form method="post">
            <?php wp_nonce_field('save_settings', 'pds_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">Shop Name</th>
                    <td><input type="text" name="shop_name" value="<?php echo esc_attr($shop_name); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row">Phone Number</th>
                    <td><input type="text" name="shop_phone" value="<?php echo esc_attr($shop_phone); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row">Email Address</th>
                    <td><input type="email" name="shop_email" value="<?php echo esc_attr($shop_email); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row">Address</th>
                    <td><textarea name="shop_address" rows="3" class="large-text"><?php echo esc_textarea($shop_address); ?></textarea></td>
                </tr>
                <tr>
                    <th scope="row">Currency Symbol</th>
                    <td><input type="text" name="currency_symbol" value="<?php echo esc_attr($currency_symbol); ?>" class="small-text" /></td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="save_settings" class="button-primary" value="Save Settings" />
            </p>
        </form>
        
        <hr>
        
        <h2>Shortcode Usage</h2>
        <p>Use the following shortcode to display the pizza ordering system on any page or post:</p>
        <code>[pizza_shop_catalog]</code>
        
        <h2>Plugin Information</h2>
        <p><strong>Version:</strong> <?php echo PDS_VERSION; ?></p>
        <p><strong>Plugin Path:</strong> <?php echo PDS_PLUGIN_PATH; ?></p>
    </div>
    <?php
}

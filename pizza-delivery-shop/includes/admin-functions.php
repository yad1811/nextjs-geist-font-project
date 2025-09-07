<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin functions for Pizza Delivery Shop
 */

/**
 * Add admin notices
 */
function pds_admin_notices() {
    // Check if tables exist
    global $wpdb;
    
    $tables_exist = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}pizza_products'");
    
    if (!$tables_exist) {
        echo '<div class="notice notice-warning"><p><strong>Pizza Delivery Shop:</strong> Database tables not found. Please deactivate and reactivate the plugin.</p></div>';
    }
}
add_action('admin_notices', 'pds_admin_notices');

/**
 * Add plugin action links
 */
function pds_plugin_action_links($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=pizza-delivery-shop') . '">Dashboard</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(PDS_PLUGIN_PATH . 'pizza-delivery-shop.php'), 'pds_plugin_action_links');

/**
 * Add admin bar menu
 */
function pds_admin_bar_menu($wp_admin_bar) {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    global $wpdb;
    
    // Get pending orders count
    $pending_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}pizza_orders WHERE order_status = 'pending'");
    
    $title = 'Pizza Shop';
    if ($pending_count > 0) {
        $title .= ' <span class="awaiting-mod count-' . $pending_count . '"><span class="pending-count">' . $pending_count . '</span></span>';
    }
    
    $wp_admin_bar->add_menu(array(
        'id' => 'pds-admin-bar',
        'title' => $title,
        'href' => admin_url('admin.php?page=pizza-delivery-shop'),
        'meta' => array(
            'title' => 'Pizza Delivery Shop Dashboard'
        )
    ));
    
    $wp_admin_bar->add_menu(array(
        'parent' => 'pds-admin-bar',
        'id' => 'pds-orders',
        'title' => 'Orders' . ($pending_count > 0 ? ' (' . $pending_count . ')' : ''),
        'href' => admin_url('admin.php?page=pds-orders')
    ));
    
    $wp_admin_bar->add_menu(array(
        'parent' => 'pds-admin-bar',
        'id' => 'pds-products',
        'title' => 'Products',
        'href' => admin_url('admin.php?page=pds-products')
    ));
}
add_action('admin_bar_menu', 'pds_admin_bar_menu', 100);

/**
 * Dashboard widgets
 */
function pds_add_dashboard_widgets() {
    wp_add_dashboard_widget(
        'pds_dashboard_widget',
        'Pizza Shop Overview',
        'pds_dashboard_widget_content'
    );
}
add_action('wp_dashboard_setup', 'pds_add_dashboard_widgets');

/**
 * Dashboard widget content
 */
function pds_dashboard_widget_content() {
    global $wpdb;
    
    $today_orders = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}pizza_orders WHERE DATE(order_date) = CURDATE()");
    $pending_orders = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}pizza_orders WHERE order_status = 'pending'");
    $total_revenue = $wpdb->get_var("SELECT SUM(total_amount) FROM {$wpdb->prefix}pizza_orders WHERE order_status != 'cancelled'");
    
    ?>
    <div class="pds-dashboard-widget">
        <div class="pds-widget-stats">
            <div class="pds-stat">
                <span class="pds-stat-number"><?php echo $today_orders; ?></span>
                <span class="pds-stat-label">Today's Orders</span>
            </div>
            <div class="pds-stat">
                <span class="pds-stat-number"><?php echo $pending_orders; ?></span>
                <span class="pds-stat-label">Pending Orders</span>
            </div>
            <div class="pds-stat">
                <span class="pds-stat-number">$<?php echo number_format($total_revenue, 2); ?></span>
                <span class="pds-stat-label">Total Revenue</span>
            </div>
        </div>
        <div class="pds-widget-actions">
            <a href="<?php echo admin_url('admin.php?page=pds-orders'); ?>" class="button">View Orders</a>
            <a href="<?php echo admin_url('admin.php?page=pizza-delivery-shop'); ?>" class="button button-primary">Dashboard</a>
        </div>
    </div>
    
    <style>
    .pds-dashboard-widget .pds-widget-stats {
        display: flex;
        justify-content: space-between;
        margin-bottom: 15px;
    }
    .pds-dashboard-widget .pds-stat {
        text-align: center;
        flex: 1;
    }
    .pds-dashboard-widget .pds-stat-number {
        display: block;
        font-size: 24px;
        font-weight: bold;
        color: #0073aa;
    }
    .pds-dashboard-widget .pds-stat-label {
        display: block;
        font-size: 12px;
        color: #666;
    }
    .pds-dashboard-widget .pds-widget-actions {
        text-align: center;
    }
    .pds-dashboard-widget .pds-widget-actions .button {
        margin: 0 5px;
    }
    </style>
    <?php
}

/**
 * Export orders to CSV
 */
function pds_export_orders_csv() {
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
    }
    
    global $wpdb;
    
    $orders = $wpdb->get_results(
        "SELECT o.*, a.area_name 
         FROM {$wpdb->prefix}pizza_orders o 
         LEFT JOIN {$wpdb->prefix}pizza_delivery_areas a ON o.delivery_area_id = a.area_id 
         ORDER BY o.order_date DESC"
    );
    
    $filename = 'pizza-orders-' . date('Y-m-d') . '.csv';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // CSV headers
    fputcsv($output, array(
        'Order ID',
        'Date',
        'Customer Name',
        'Phone',
        'Email',
        'Order Type',
        'Delivery Area',
        'Address',
        'Items',
        'Subtotal',
        'Delivery Charge',
        'Total',
        'Status',
        'Notes'
    ));
    
    // CSV data
    foreach ($orders as $order) {
        $items = json_decode($order->order_items, true);
        $items_text = '';
        if ($items) {
            $item_strings = array();
            foreach ($items as $item) {
                $item_strings[] = $item['name'] . ' (' . $item['size'] . ') x' . $item['quantity'];
            }
            $items_text = implode('; ', $item_strings);
        }
        
        fputcsv($output, array(
            $order->order_id,
            $order->order_date,
            $order->customer_name,
            $order->customer_phone,
            $order->customer_email,
            ucfirst($order->order_type),
            $order->area_name,
            $order->delivery_address,
            $items_text,
            $order->subtotal,
            $order->delivery_charge,
            $order->total_amount,
            ucfirst($order->order_status),
            $order->notes
        ));
    }
    
    fclose($output);
    exit;
}

// Handle CSV export
if (isset($_GET['pds_export']) && $_GET['pds_export'] === 'orders' && current_user_can('manage_options')) {
    add_action('init', 'pds_export_orders_csv');
}

/**
 * Add custom columns to posts list (for shortcode reference)
 */
function pds_add_shortcode_column($columns) {
    $columns['pds_shortcode'] = 'Pizza Shop Shortcode';
    return $columns;
}

function pds_show_shortcode_column($column, $post_id) {
    if ($column === 'pds_shortcode') {
        $content = get_post_field('post_content', $post_id);
        if (strpos($content, '[pizza_shop_catalog]') !== false) {
            echo '<span style="color: green;">✓ Has Pizza Shop</span>';
        } else {
            echo '<code>[pizza_shop_catalog]</code>';
        }
    }
}
add_filter('manage_pages_columns', 'pds_add_shortcode_column');
add_filter('manage_posts_columns', 'pds_add_shortcode_column');
add_action('manage_pages_custom_column', 'pds_show_shortcode_column', 10, 2);
add_action('manage_posts_custom_column', 'pds_show_shortcode_column', 10, 2);

/**
 * Add help tabs
 */
function pds_add_help_tabs() {
    $screen = get_current_screen();
    
    if (strpos($screen->id, 'pizza-delivery-shop') !== false) {
        $screen->add_help_tab(array(
            'id' => 'pds_help_overview',
            'title' => 'Overview',
            'content' => '
                <h3>Pizza Delivery Shop Plugin</h3>
                <p>This plugin allows you to manage a complete pizza delivery system with:</p>
                <ul>
                    <li>Product management with unique IDs</li>
                    <li>Category organization</li>
                    <li>Size-based pricing (Single, Jumbo, Family, Party)</li>
                    <li>Delivery area management</li>
                    <li>Order processing</li>
                </ul>
            '
        ));
        
        $screen->add_help_tab(array(
            'id' => 'pds_help_shortcode',
            'title' => 'Shortcode Usage',
            'content' => '
                <h3>Using the Shortcode</h3>
                <p>To display the pizza ordering system on any page or post, use:</p>
                <code>[pizza_shop_catalog]</code>
                <p>This will display the complete ordering interface including:</p>
                <ul>
                    <li>Order type selection (Takeaway/Delivery)</li>
                    <li>Product catalog by categories</li>
                    <li>Shopping cart</li>
                    <li>Checkout form</li>
                </ul>
            '
        ));
        
        $screen->add_help_tab(array(
            'id' => 'pds_help_setup',
            'title' => 'Setup Guide',
            'content' => '
                <h3>Getting Started</h3>
                <ol>
                    <li><strong>Categories:</strong> Create product categories (Pizza, Finger Food, etc.)</li>
                    <li><strong>Products:</strong> Add products with different size pricing</li>
                    <li><strong>Delivery Areas:</strong> Set up delivery zones with charges</li>
                    <li><strong>Settings:</strong> Configure shop information</li>
                    <li><strong>Display:</strong> Add the shortcode to a page</li>
                </ol>
            '
        ));
    }
}
add_action('admin_head', 'pds_add_help_tabs');

/**
 * Plugin cleanup on uninstall
 */
function pds_uninstall_cleanup() {
    global $wpdb;
    
    // Drop tables
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}pizza_orders");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}pizza_pricing");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}pizza_delivery_areas");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}pizza_products");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}pizza_categories");
    
    // Delete options
    delete_option('pds_shop_name');
    delete_option('pds_shop_phone');
    delete_option('pds_shop_email');
    delete_option('pds_shop_address');
    delete_option('pds_currency_symbol');
}

// Register uninstall hook
register_uninstall_hook(PDS_PLUGIN_PATH . 'pizza-delivery-shop.php', 'pds_uninstall_cleanup');

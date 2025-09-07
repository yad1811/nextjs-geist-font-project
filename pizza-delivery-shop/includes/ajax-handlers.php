<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle AJAX order submission
 */
function pds_handle_order_submission() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'pds_nonce')) {
        wp_die('Security check failed');
    }
    
    global $wpdb;
    
    // Get form data
    $customer_name = sanitize_text_field($_POST['customer_name']);
    $customer_phone = sanitize_text_field($_POST['customer_phone']);
    $customer_email = sanitize_email($_POST['customer_email']);
    $order_type = sanitize_text_field($_POST['order_type']);
    $delivery_area_id = $order_type === 'delivery' ? intval($_POST['delivery_area_id']) : null;
    $delivery_address = $order_type === 'delivery' ? sanitize_textarea_field($_POST['delivery_address']) : null;
    $order_notes = sanitize_textarea_field($_POST['order_notes']);
    $cart_items = json_decode(stripslashes($_POST['cart_items']), true);
    
    // Validate required fields
    if (empty($customer_name) || empty($customer_phone) || empty($cart_items)) {
        wp_send_json_error('Please fill in all required fields.');
        return;
    }
    
    if ($order_type === 'delivery' && (empty($delivery_area_id) || empty($delivery_address))) {
        wp_send_json_error('Please select delivery area and provide address.');
        return;
    }
    
    // Calculate totals
    $subtotal = 0;
    $delivery_charge = 0;
    $processed_items = array();
    
    foreach ($cart_items as $item) {
        $product_id = intval($item['productId']);
        $size = sanitize_text_field($item['size']);
        $quantity = intval($item['quantity']);
        $price = floatval($item['price']);
        
        // Verify pricing from database
        $db_price = $wpdb->get_var($wpdb->prepare(
            "SELECT " . ($order_type === 'delivery' ? 'delivery_price' : 'takeaway_price') . " 
             FROM {$wpdb->prefix}pizza_pricing 
             WHERE product_id = %d AND size_type = %s",
            $product_id, $size
        ));
        
        if ($db_price != $price) {
            wp_send_json_error('Price mismatch detected. Please refresh and try again.');
            return;
        }
        
        $item_total = $price * $quantity;
        $subtotal += $item_total;
        
        $processed_items[] = array(
            'product_id' => $product_id,
            'name' => sanitize_text_field($item['name']),
            'size' => $size,
            'quantity' => $quantity,
            'price' => $price,
            'total' => $item_total
        );
    }
    
    // Get delivery charge and minimum order
    if ($order_type === 'delivery' && $delivery_area_id) {
        $area_info = $wpdb->get_row($wpdb->prepare(
            "SELECT delivery_charge, minimum_order FROM {$wpdb->prefix}pizza_delivery_areas WHERE area_id = %d",
            $delivery_area_id
        ));
        
        if ($area_info) {
            $delivery_charge = $area_info->delivery_charge;
            $minimum_order = $area_info->minimum_order;
            
            if ($subtotal < $minimum_order) {
                wp_send_json_error("Minimum order for delivery is $" . number_format($minimum_order, 2));
                return;
            }
        }
    }
    
    $total_amount = $subtotal + $delivery_charge;
    
    // Insert order into database
    $order_data = array(
        'customer_name' => $customer_name,
        'customer_phone' => $customer_phone,
        'customer_email' => $customer_email,
        'order_type' => $order_type,
        'delivery_area_id' => $delivery_area_id,
        'delivery_address' => $delivery_address,
        'order_items' => json_encode($processed_items),
        'subtotal' => $subtotal,
        'delivery_charge' => $delivery_charge,
        'total_amount' => $total_amount,
        'notes' => $order_notes
    );
    
    $result = $wpdb->insert(
        $wpdb->prefix . 'pizza_orders',
        $order_data
    );
    
    if ($result) {
        $order_id = $wpdb->insert_id;
        
        // Send confirmation email (if email provided)
        if (!empty($customer_email)) {
            pds_send_order_confirmation_email($order_id, $customer_email);
        }
        
        wp_send_json_success(array(
            'order_id' => $order_id,
            'message' => 'Order placed successfully!',
            'order_details' => array(
                'order_id' => $order_id,
                'customer_name' => $customer_name,
                'order_type' => $order_type,
                'subtotal' => $subtotal,
                'delivery_charge' => $delivery_charge,
                'total_amount' => $total_amount,
                'items' => $processed_items
            )
        ));
    } else {
        wp_send_json_error('Failed to place order. Please try again.');
    }
}
add_action('wp_ajax_pds_place_order', 'pds_handle_order_submission');
add_action('wp_ajax_nopriv_pds_place_order', 'pds_handle_order_submission');

/**
 * Send order confirmation email
 */
function pds_send_order_confirmation_email($order_id, $customer_email) {
    global $wpdb;
    
    $order = $wpdb->get_row($wpdb->prepare(
        "SELECT o.*, a.area_name 
         FROM {$wpdb->prefix}pizza_orders o 
         LEFT JOIN {$wpdb->prefix}pizza_delivery_areas a ON o.delivery_area_id = a.area_id 
         WHERE o.order_id = %d",
        $order_id
    ));
    
    if (!$order) return;
    
    $shop_name = get_option('pds_shop_name', 'Pizza Delivery Shop');
    $shop_phone = get_option('pds_shop_phone', '');
    $currency_symbol = get_option('pds_currency_symbol', '$');
    
    $subject = "Order Confirmation - #{$order_id} from {$shop_name}";
    
    $message = "Dear {$order->customer_name},\n\n";
    $message .= "Thank you for your order! Here are the details:\n\n";
    $message .= "Order ID: #{$order_id}\n";
    $message .= "Order Type: " . ucfirst($order->order_type) . "\n";
    
    if ($order->order_type === 'delivery') {
        $message .= "Delivery Area: {$order->area_name}\n";
        $message .= "Delivery Address: {$order->delivery_address}\n";
    }
    
    $message .= "\nOrder Items:\n";
    $items = json_decode($order->order_items, true);
    foreach ($items as $item) {
        $message .= "- {$item['name']} ({$item['size']}) x{$item['quantity']} = {$currency_symbol}" . number_format($item['total'], 2) . "\n";
    }
    
    $message .= "\nSubtotal: {$currency_symbol}" . number_format($order->subtotal, 2) . "\n";
    if ($order->delivery_charge > 0) {
        $message .= "Delivery Charge: {$currency_symbol}" . number_format($order->delivery_charge, 2) . "\n";
    }
    $message .= "Total: {$currency_symbol}" . number_format($order->total_amount, 2) . "\n";
    
    if ($order->notes) {
        $message .= "\nSpecial Instructions: {$order->notes}\n";
    }
    
    $message .= "\nWe'll contact you shortly to confirm your order.\n\n";
    $message .= "Thank you for choosing {$shop_name}!\n";
    
    if ($shop_phone) {
        $message .= "Contact us: {$shop_phone}\n";
    }
    
    $headers = array('Content-Type: text/plain; charset=UTF-8');
    
    wp_mail($customer_email, $subject, $message, $headers);
}

/**
 * Get product pricing via AJAX
 */
function pds_get_product_pricing_ajax() {
    if (!wp_verify_nonce($_POST['nonce'], 'pds_nonce')) {
        wp_die('Security check failed');
    }
    
    $product_id = intval($_POST['product_id']);
    $pricing = pds_get_product_pricing($product_id);
    
    wp_send_json_success($pricing);
}
add_action('wp_ajax_pds_get_pricing', 'pds_get_product_pricing_ajax');
add_action('wp_ajax_nopriv_pds_get_pricing', 'pds_get_product_pricing_ajax');

/**
 * Get delivery area info via AJAX
 */
function pds_get_delivery_area_info() {
    if (!wp_verify_nonce($_POST['nonce'], 'pds_nonce')) {
        wp_die('Security check failed');
    }
    
    global $wpdb;
    
    $area_id = intval($_POST['area_id']);
    $area = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}pizza_delivery_areas WHERE area_id = %d",
        $area_id
    ));
    
    if ($area) {
        wp_send_json_success($area);
    } else {
        wp_send_json_error('Area not found');
    }
}
add_action('wp_ajax_pds_get_area_info', 'pds_get_delivery_area_info');
add_action('wp_ajax_nopriv_pds_get_area_info', 'pds_get_delivery_area_info');

/**
 * Admin AJAX: Update order status
 */
function pds_update_order_status() {
    if (!wp_verify_nonce($_POST['nonce'], 'pds_admin_nonce')) {
        wp_die('Security check failed');
    }
    
    if (!current_user_can('manage_options')) {
        wp_die('Insufficient permissions');
    }
    
    global $wpdb;
    
    $order_id = intval($_POST['order_id']);
    $new_status = sanitize_text_field($_POST['status']);
    
    $valid_statuses = array('pending', 'confirmed', 'preparing', 'ready', 'delivered', 'cancelled');
    if (!in_array($new_status, $valid_statuses)) {
        wp_send_json_error('Invalid status');
        return;
    }
    
    $result = $wpdb->update(
        $wpdb->prefix . 'pizza_orders',
        array('order_status' => $new_status),
        array('order_id' => $order_id),
        array('%s'),
        array('%d')
    );
    
    if ($result !== false) {
        wp_send_json_success('Order status updated');
    } else {
        wp_send_json_error('Failed to update status');
    }
}
add_action('wp_ajax_pds_update_order_status', 'pds_update_order_status');

/**
 * Validate cart items
 */
function pds_validate_cart_items() {
    if (!wp_verify_nonce($_POST['nonce'], 'pds_nonce')) {
        wp_die('Security check failed');
    }
    
    global $wpdb;
    
    $cart_items = json_decode(stripslashes($_POST['cart_items']), true);
    $order_type = sanitize_text_field($_POST['order_type']);
    
    $validated_items = array();
    $total = 0;
    
    foreach ($cart_items as $item) {
        $product_id = intval($item['productId']);
        $size = sanitize_text_field($item['size']);
        
        // Get current price from database
        $current_price = $wpdb->get_var($wpdb->prepare(
            "SELECT " . ($order_type === 'delivery' ? 'delivery_price' : 'takeaway_price') . " 
             FROM {$wpdb->prefix}pizza_pricing 
             WHERE product_id = %d AND size_type = %s",
            $product_id, $size
        ));
        
        if ($current_price) {
            $validated_items[] = array(
                'productId' => $product_id,
                'name' => $item['name'],
                'size' => $size,
                'quantity' => intval($item['quantity']),
                'price' => floatval($current_price),
                'originalPrice' => floatval($item['price'])
            );
            $total += $current_price * intval($item['quantity']);
        }
    }
    
    wp_send_json_success(array(
        'items' => $validated_items,
        'total' => $total
    ));
}
add_action('wp_ajax_pds_validate_cart', 'pds_validate_cart_items');
add_action('wp_ajax_nopriv_pds_validate_cart', 'pds_validate_cart_items');

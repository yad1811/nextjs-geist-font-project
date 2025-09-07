<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create database tables for the pizza delivery shop
 */
function pds_create_tables() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Products table with unique IDs
    $products_table = $wpdb->prefix . 'pizza_products';
    $products_sql = "CREATE TABLE $products_table (
        product_id int(11) NOT NULL AUTO_INCREMENT,
        name varchar(255) NOT NULL,
        category_id int(11) NOT NULL,
        description text,
        image_url varchar(500),
        status enum('active','inactive') DEFAULT 'active',
        created_at timestamp DEFAULT CURRENT_TIMESTAMP,
        updated_at timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (product_id),
        KEY category_id (category_id)
    ) $charset_collate;";
    
    // Categories table
    $categories_table = $wpdb->prefix . 'pizza_categories';
    $categories_sql = "CREATE TABLE $categories_table (
        category_id int(11) NOT NULL AUTO_INCREMENT,
        category_name varchar(100) NOT NULL,
        display_order int(11) DEFAULT 0,
        status enum('active','inactive') DEFAULT 'active',
        created_at timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (category_id)
    ) $charset_collate;";
    
    // Pricing table with size variations
    $pricing_table = $wpdb->prefix . 'pizza_pricing';
    $pricing_sql = "CREATE TABLE $pricing_table (
        pricing_id int(11) NOT NULL AUTO_INCREMENT,
        product_id int(11) NOT NULL,
        size_type enum('single','jumbo','family','party') NOT NULL,
        takeaway_price decimal(10,2) NOT NULL,
        delivery_price decimal(10,2) NOT NULL,
        created_at timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (pricing_id),
        KEY product_id (product_id),
        UNIQUE KEY unique_product_size (product_id, size_type)
    ) $charset_collate;";
    
    // Delivery areas table
    $areas_table = $wpdb->prefix . 'pizza_delivery_areas';
    $areas_sql = "CREATE TABLE $areas_table (
        area_id int(11) NOT NULL AUTO_INCREMENT,
        area_name varchar(100) NOT NULL,
        delivery_charge decimal(10,2) NOT NULL,
        minimum_order decimal(10,2) NOT NULL,
        status enum('active','inactive') DEFAULT 'active',
        created_at timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (area_id)
    ) $charset_collate;";
    
    // Orders table for tracking customer orders
    $orders_table = $wpdb->prefix . 'pizza_orders';
    $orders_sql = "CREATE TABLE $orders_table (
        order_id int(11) NOT NULL AUTO_INCREMENT,
        customer_name varchar(255) NOT NULL,
        customer_phone varchar(20) NOT NULL,
        customer_email varchar(255),
        order_type enum('takeaway','delivery') NOT NULL,
        delivery_area_id int(11) NULL,
        delivery_address text NULL,
        order_items longtext NOT NULL,
        subtotal decimal(10,2) NOT NULL,
        delivery_charge decimal(10,2) DEFAULT 0,
        total_amount decimal(10,2) NOT NULL,
        order_status enum('pending','confirmed','preparing','ready','delivered','cancelled') DEFAULT 'pending',
        order_date timestamp DEFAULT CURRENT_TIMESTAMP,
        notes text,
        PRIMARY KEY (order_id),
        KEY delivery_area_id (delivery_area_id),
        KEY order_status (order_status),
        KEY order_date (order_date)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($products_sql);
    dbDelta($categories_sql);
    dbDelta($pricing_sql);
    dbDelta($areas_sql);
    dbDelta($orders_sql);
}

/**
 * Insert sample data for testing
 */
function pds_insert_sample_data() {
    global $wpdb;
    
    // Check if data already exists
    $existing_categories = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}pizza_categories");
    if ($existing_categories > 0) {
        return; // Data already exists
    }
    
    // Insert categories
    $categories = array(
        array('Pizza', 1),
        array('Finger Food', 2),
        array('Beverages', 3),
        array('Desserts', 4)
    );
    
    $category_ids = array();
    foreach ($categories as $cat) {
        $wpdb->insert(
            $wpdb->prefix . 'pizza_categories',
            array(
                'category_name' => $cat[0],
                'display_order' => $cat[1]
            )
        );
        $category_ids[$cat[0]] = $wpdb->insert_id;
    }
    
    // Insert sample products with unique IDs
    $products = array(
        array('Margherita Pizza', 'Pizza', 'Classic tomato base with fresh mozzarella cheese and basil'),
        array('Pepperoni Pizza', 'Pizza', 'Tomato base with mozzarella cheese and spicy pepperoni'),
        array('Hawaiian Pizza', 'Pizza', 'Tomato base with mozzarella, ham, and pineapple'),
        array('Meat Lovers Pizza', 'Pizza', 'Loaded with pepperoni, sausage, ham, and bacon'),
        array('Vegetarian Pizza', 'Pizza', 'Fresh vegetables with mozzarella on tomato base'),
        array('Chicken Wings', 'Finger Food', 'Crispy chicken wings with your choice of sauce'),
        array('Garlic Bread', 'Finger Food', 'Fresh bread with garlic butter and herbs'),
        array('Mozzarella Sticks', 'Finger Food', 'Golden fried mozzarella with marinara sauce'),
        array('Coca Cola', 'Beverages', 'Classic Coca Cola - 330ml can'),
        array('Orange Juice', 'Beverages', 'Fresh orange juice - 250ml'),
        array('Chocolate Cake', 'Desserts', 'Rich chocolate cake slice'),
        array('Ice Cream', 'Desserts', 'Vanilla ice cream scoop')
    );
    
    foreach ($products as $product) {
        $category_id = $category_ids[$product[1]];
        
        $wpdb->insert(
            $wpdb->prefix . 'pizza_products',
            array(
                'name' => $product[0],
                'category_id' => $category_id,
                'description' => $product[2]
            )
        );
        
        $product_id = $wpdb->insert_id;
        
        // Insert pricing for each size (only for Pizza and Finger Food)
        if ($product[1] === 'Pizza' || $product[1] === 'Finger Food') {
            $base_prices = array(
                'Pizza' => array(
                    'single' => array('takeaway' => 12.99, 'delivery' => 14.99),
                    'jumbo' => array('takeaway' => 18.99, 'delivery' => 20.99),
                    'family' => array('takeaway' => 24.99, 'delivery' => 26.99),
                    'party' => array('takeaway' => 32.99, 'delivery' => 34.99)
                ),
                'Finger Food' => array(
                    'single' => array('takeaway' => 8.99, 'delivery' => 10.99),
                    'jumbo' => array('takeaway' => 12.99, 'delivery' => 14.99),
                    'family' => array('takeaway' => 16.99, 'delivery' => 18.99),
                    'party' => array('takeaway' => 22.99, 'delivery' => 24.99)
                )
            );
            
            $sizes = $base_prices[$product[1]];
            
            foreach ($sizes as $size => $prices) {
                $wpdb->insert(
                    $wpdb->prefix . 'pizza_pricing',
                    array(
                        'product_id' => $product_id,
                        'size_type' => $size,
                        'takeaway_price' => $prices['takeaway'],
                        'delivery_price' => $prices['delivery']
                    )
                );
            }
        } else {
            // For beverages and desserts, only single size
            $single_prices = array(
                'Beverages' => array('takeaway' => 2.99, 'delivery' => 3.99),
                'Desserts' => array('takeaway' => 5.99, 'delivery' => 6.99)
            );
            
            $prices = $single_prices[$product[1]];
            $wpdb->insert(
                $wpdb->prefix . 'pizza_pricing',
                array(
                    'product_id' => $product_id,
                    'size_type' => 'single',
                    'takeaway_price' => $prices['takeaway'],
                    'delivery_price' => $prices['delivery']
                )
            );
        }
    }
    
    // Insert delivery areas
    $areas = array(
        array('Downtown', 3.50, 15.00),
        array('Suburbs North', 5.00, 20.00),
        array('Suburbs South', 5.00, 20.00),
        array('East Side', 4.50, 18.00),
        array('West Side', 4.50, 18.00),
        array('Industrial Area', 7.50, 25.00)
    );
    
    foreach ($areas as $area) {
        $wpdb->insert(
            $wpdb->prefix . 'pizza_delivery_areas',
            array(
                'area_name' => $area[0],
                'delivery_charge' => $area[1],
                'minimum_order' => $area[2]
            )
        );
    }
}

/**
 * Get all products by category
 */
function pds_get_products_by_category($category_id = null) {
    global $wpdb;
    
    if ($category_id) {
        return $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, c.category_name 
             FROM {$wpdb->prefix}pizza_products p 
             JOIN {$wpdb->prefix}pizza_categories c ON p.category_id = c.category_id 
             WHERE p.category_id = %d AND p.status = 'active' 
             ORDER BY p.name",
            $category_id
        ));
    } else {
        return $wpdb->get_results(
            "SELECT p.*, c.category_name 
             FROM {$wpdb->prefix}pizza_products p 
             JOIN {$wpdb->prefix}pizza_categories c ON p.category_id = c.category_id 
             WHERE p.status = 'active' 
             ORDER BY c.display_order, p.name"
        );
    }
}

/**
 * Get product pricing
 */
function pds_get_product_pricing($product_id) {
    global $wpdb;
    
    return $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}pizza_pricing WHERE product_id = %d ORDER BY 
         CASE size_type 
         WHEN 'single' THEN 1 
         WHEN 'jumbo' THEN 2 
         WHEN 'family' THEN 3 
         WHEN 'party' THEN 4 
         END",
        $product_id
    ));
}

/**
 * Get all categories
 */
function pds_get_categories() {
    global $wpdb;
    
    return $wpdb->get_results(
        "SELECT * FROM {$wpdb->prefix}pizza_categories 
         WHERE status = 'active' 
         ORDER BY display_order"
    );
}

/**
 * Get all delivery areas
 */
function pds_get_delivery_areas() {
    global $wpdb;
    
    return $wpdb->get_results(
        "SELECT * FROM {$wpdb->prefix}pizza_delivery_areas 
         WHERE status = 'active' 
         ORDER BY area_name"
    );
}

<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Products management page
 */
function pds_products_page() {
    global $wpdb;
    
    $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
    $product_id = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
    
    switch ($action) {
        case 'add':
            pds_add_product_form();
            break;
        case 'edit':
            pds_edit_product_form($product_id);
            break;
        case 'delete':
            pds_delete_product($product_id);
            pds_list_products();
            break;
        default:
            pds_list_products();
            break;
    }
}

/**
 * List all products
 */
function pds_list_products() {
    global $wpdb;
    
    // Handle form submissions
    if (isset($_POST['save_product']) && wp_verify_nonce($_POST['pds_nonce'], 'save_product')) {
        pds_save_product();
    }
    
    $products = $wpdb->get_results(
        "SELECT p.*, c.category_name 
         FROM {$wpdb->prefix}pizza_products p 
         LEFT JOIN {$wpdb->prefix}pizza_categories c ON p.category_id = c.category_id 
         ORDER BY c.display_order, p.name"
    );
    
    ?>
    <div class="wrap">
        <h1>Products Management 
            <a href="<?php echo admin_url('admin.php?page=pds-products&action=add'); ?>" class="page-title-action">Add New Product</a>
        </h1>
        
        <?php if ($products): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Pricing</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><strong><?php echo $product->product_id; ?></strong></td>
                            <td><?php echo esc_html($product->name); ?></td>
                            <td><?php echo esc_html($product->category_name); ?></td>
                            <td><?php echo esc_html(wp_trim_words($product->description, 10)); ?></td>
                            <td>
                                <span class="pds-status-<?php echo $product->status; ?>">
                                    <?php echo ucfirst($product->status); ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                $pricing = pds_get_product_pricing($product->product_id);
                                if ($pricing) {
                                    foreach ($pricing as $price) {
                                        echo ucfirst($price->size_type) . ': $' . $price->takeaway_price . '/$' . $price->delivery_price . '<br>';
                                    }
                                }
                                ?>
                            </td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=pds-products&action=edit&product_id=' . $product->product_id); ?>" class="button button-small">Edit</a>
                                <a href="<?php echo admin_url('admin.php?page=pds-products&action=delete&product_id=' . $product->product_id); ?>" 
                                   class="button button-small" 
                                   onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No products found. <a href="<?php echo admin_url('admin.php?page=pds-products&action=add'); ?>">Add your first product</a>.</p>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Add product form
 */
function pds_add_product_form() {
    $categories = pds_get_categories();
    
    ?>
    <div class="wrap">
        <h1>Add New Product</h1>
        
        <form method="post">
            <?php wp_nonce_field('save_product', 'pds_nonce'); ?>
            <input type="hidden" name="action" value="add">
            
            <table class="form-table">
                <tr>
                    <th scope="row">Product Name *</th>
                    <td><input type="text" name="product_name" required class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row">Category *</th>
                    <td>
                        <select name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category->category_id; ?>">
                                    <?php echo esc_html($category->category_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Description</th>
                    <td><textarea name="description" rows="3" class="large-text"></textarea></td>
                </tr>
                <tr>
                    <th scope="row">Image URL</th>
                    <td><input type="url" name="image_url" class="regular-text" placeholder="https://example.com/image.jpg" /></td>
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
            
            <h2>Pricing</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">Single Size</th>
                    <td>
                        Takeaway: $<input type="number" name="pricing[single][takeaway]" step="0.01" min="0" class="small-text" />
                        Delivery: $<input type="number" name="pricing[single][delivery]" step="0.01" min="0" class="small-text" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">Jumbo Size</th>
                    <td>
                        Takeaway: $<input type="number" name="pricing[jumbo][takeaway]" step="0.01" min="0" class="small-text" />
                        Delivery: $<input type="number" name="pricing[jumbo][delivery]" step="0.01" min="0" class="small-text" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">Family Size</th>
                    <td>
                        Takeaway: $<input type="number" name="pricing[family][takeaway]" step="0.01" min="0" class="small-text" />
                        Delivery: $<input type="number" name="pricing[family][delivery]" step="0.01" min="0" class="small-text" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">Party Size</th>
                    <td>
                        Takeaway: $<input type="number" name="pricing[party][takeaway]" step="0.01" min="0" class="small-text" />
                        Delivery: $<input type="number" name="pricing[party][delivery]" step="0.01" min="0" class="small-text" />
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="save_product" class="button-primary" value="Add Product" />
                <a href="<?php echo admin_url('admin.php?page=pds-products'); ?>" class="button">Cancel</a>
            </p>
        </form>
    </div>
    <?php
}

/**
 * Edit product form
 */
function pds_edit_product_form($product_id) {
    global $wpdb;
    
    $product = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}pizza_products WHERE product_id = %d",
        $product_id
    ));
    
    if (!$product) {
        echo '<div class="wrap"><h1>Product not found</h1><p>The requested product could not be found.</p></div>';
        return;
    }
    
    $categories = pds_get_categories();
    $pricing = pds_get_product_pricing($product_id);
    
    // Convert pricing to associative array for easier access
    $prices = array();
    foreach ($pricing as $price) {
        $prices[$price->size_type] = array(
            'takeaway' => $price->takeaway_price,
            'delivery' => $price->delivery_price
        );
    }
    
    ?>
    <div class="wrap">
        <h1>Edit Product</h1>
        
        <form method="post">
            <?php wp_nonce_field('save_product', 'pds_nonce'); ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="product_id" value="<?php echo $product->product_id; ?>">
            
            <table class="form-table">
                <tr>
                    <th scope="row">Product ID</th>
                    <td><strong><?php echo $product->product_id; ?></strong></td>
                </tr>
                <tr>
                    <th scope="row">Product Name *</th>
                    <td><input type="text" name="product_name" value="<?php echo esc_attr($product->name); ?>" required class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row">Category *</th>
                    <td>
                        <select name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category->category_id; ?>" <?php selected($product->category_id, $category->category_id); ?>>
                                    <?php echo esc_html($category->category_name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Description</th>
                    <td><textarea name="description" rows="3" class="large-text"><?php echo esc_textarea($product->description); ?></textarea></td>
                </tr>
                <tr>
                    <th scope="row">Image URL</th>
                    <td><input type="url" name="image_url" value="<?php echo esc_attr($product->image_url); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row">Status</th>
                    <td>
                        <select name="status">
                            <option value="active" <?php selected($product->status, 'active'); ?>>Active</option>
                            <option value="inactive" <?php selected($product->status, 'inactive'); ?>>Inactive</option>
                        </select>
                    </td>
                </tr>
            </table>
            
            <h2>Pricing</h2>
            <table class="form-table">
                <?php foreach (array('single', 'jumbo', 'family', 'party') as $size): ?>
                    <tr>
                        <th scope="row"><?php echo ucfirst($size); ?> Size</th>
                        <td>
                            Takeaway: $<input type="number" 
                                             name="pricing[<?php echo $size; ?>][takeaway]" 
                                             value="<?php echo isset($prices[$size]) ? $prices[$size]['takeaway'] : ''; ?>" 
                                             step="0.01" min="0" class="small-text" />
                            Delivery: $<input type="number" 
                                            name="pricing[<?php echo $size; ?>][delivery]" 
                                            value="<?php echo isset($prices[$size]) ? $prices[$size]['delivery'] : ''; ?>" 
                                            step="0.01" min="0" class="small-text" />
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
            
            <p class="submit">
                <input type="submit" name="save_product" class="button-primary" value="Update Product" />
                <a href="<?php echo admin_url('admin.php?page=pds-products'); ?>" class="button">Cancel</a>
            </p>
        </form>
    </div>
    <?php
}

/**
 * Save product (add or edit)
 */
function pds_save_product() {
    global $wpdb;
    
    if (!wp_verify_nonce($_POST['pds_nonce'], 'save_product')) {
        wp_die('Security check failed');
    }
    
    $action = sanitize_text_field($_POST['action']);
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $product_name = sanitize_text_field($_POST['product_name']);
    $category_id = intval($_POST['category_id']);
    $description = sanitize_textarea_field($_POST['description']);
    $image_url = esc_url_raw($_POST['image_url']);
    $status = sanitize_text_field($_POST['status']);
    $pricing = $_POST['pricing'];
    
    // Validate required fields
    if (empty($product_name) || empty($category_id)) {
        echo '<div class="notice notice-error"><p>Please fill in all required fields.</p></div>';
        return;
    }
    
    $product_data = array(
        'name' => $product_name,
        'category_id' => $category_id,
        'description' => $description,
        'image_url' => $image_url,
        'status' => $status
    );
    
    if ($action === 'add') {
        // Insert new product
        $result = $wpdb->insert(
            $wpdb->prefix . 'pizza_products',
            $product_data
        );
        
        if ($result) {
            $product_id = $wpdb->insert_id;
            echo '<div class="notice notice-success"><p>Product added successfully! Product ID: ' . $product_id . '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Error adding product.</p></div>';
            return;
        }
    } else {
        // Update existing product
        $result = $wpdb->update(
            $wpdb->prefix . 'pizza_products',
            $product_data,
            array('product_id' => $product_id)
        );
        
        if ($result !== false) {
            echo '<div class="notice notice-success"><p>Product updated successfully!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Error updating product.</p></div>';
            return;
        }
    }
    
    // Handle pricing
    if ($pricing && is_array($pricing)) {
        // Delete existing pricing
        $wpdb->delete(
            $wpdb->prefix . 'pizza_pricing',
            array('product_id' => $product_id)
        );
        
        // Insert new pricing
        foreach ($pricing as $size => $prices) {
            if (!empty($prices['takeaway']) && !empty($prices['delivery'])) {
                $wpdb->insert(
                    $wpdb->prefix . 'pizza_pricing',
                    array(
                        'product_id' => $product_id,
                        'size_type' => $size,
                        'takeaway_price' => floatval($prices['takeaway']),
                        'delivery_price' => floatval($prices['delivery'])
                    )
                );
            }
        }
    }
}

/**
 * Delete product
 */
function pds_delete_product($product_id) {
    global $wpdb;
    
    if (!$product_id) {
        return;
    }
    
    // Delete pricing first
    $wpdb->delete(
        $wpdb->prefix . 'pizza_pricing',
        array('product_id' => $product_id)
    );
    
    // Delete product
    $result = $wpdb->delete(
        $wpdb->prefix . 'pizza_products',
        array('product_id' => $product_id)
    );
    
    if ($result) {
        echo '<div class="notice notice-success"><p>Product deleted successfully!</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>Error deleting product.</p></div>';
    }
}

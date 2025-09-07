<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Categories management page
 */
function pds_categories_page() {
    global $wpdb;
    
    $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
    $category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
    
    // Handle form submissions
    if (isset($_POST['save_category']) && wp_verify_nonce($_POST['pds_nonce'], 'save_category')) {
        pds_save_category();
    }
    
    switch ($action) {
        case 'add':
            pds_add_category_form();
            break;
        case 'edit':
            pds_edit_category_form($category_id);
            break;
        case 'delete':
            pds_delete_category($category_id);
            pds_list_categories();
            break;
        default:
            pds_list_categories();
            break;
    }
}

/**
 * List all categories
 */
function pds_list_categories() {
    global $wpdb;
    
    $categories = $wpdb->get_results(
        "SELECT c.*, COUNT(p.product_id) as product_count 
         FROM {$wpdb->prefix}pizza_categories c 
         LEFT JOIN {$wpdb->prefix}pizza_products p ON c.category_id = p.category_id AND p.status = 'active'
         GROUP BY c.category_id 
         ORDER BY c.display_order"
    );
    
    ?>
    <div class="wrap">
        <h1>Categories Management 
            <a href="<?php echo admin_url('admin.php?page=pds-categories&action=add'); ?>" class="page-title-action">Add New Category</a>
        </h1>
        
        <?php if ($categories): ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Category Name</th>
                        <th>Display Order</th>
                        <th>Products Count</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><strong><?php echo $category->category_id; ?></strong></td>
                            <td><?php echo esc_html($category->category_name); ?></td>
                            <td><?php echo $category->display_order; ?></td>
                            <td><?php echo $category->product_count; ?> products</td>
                            <td>
                                <span class="pds-status-<?php echo $category->status; ?>">
                                    <?php echo ucfirst($category->status); ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=pds-categories&action=edit&category_id=' . $category->category_id); ?>" class="button button-small">Edit</a>
                                <?php if ($category->product_count == 0): ?>
                                    <a href="<?php echo admin_url('admin.php?page=pds-categories&action=delete&category_id=' . $category->category_id); ?>" 
                                       class="button button-small" 
                                       onclick="return confirm('Are you sure you want to delete this category?')">Delete</a>
                                <?php else: ?>
                                    <span class="button button-small button-disabled" title="Cannot delete category with products">Delete</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No categories found. <a href="<?php echo admin_url('admin.php?page=pds-categories&action=add'); ?>">Add your first category</a>.</p>
        <?php endif; ?>
        
        <div class="pds-help-section">
            <h3>Category Management Tips</h3>
            <ul>
                <li><strong>Display Order:</strong> Lower numbers appear first in the catalog</li>
                <li><strong>Status:</strong> Inactive categories won't show in the frontend</li>
                <li><strong>Deletion:</strong> Categories with products cannot be deleted</li>
            </ul>
        </div>
    </div>
    <?php
}

/**
 * Add category form
 */
function pds_add_category_form() {
    global $wpdb;
    
    // Get next display order
    $next_order = $wpdb->get_var("SELECT MAX(display_order) + 1 FROM {$wpdb->prefix}pizza_categories");
    if (!$next_order) $next_order = 1;
    
    ?>
    <div class="wrap">
        <h1>Add New Category</h1>
        
        <form method="post">
            <?php wp_nonce_field('save_category', 'pds_nonce'); ?>
            <input type="hidden" name="action" value="add">
            
            <table class="form-table">
                <tr>
                    <th scope="row">Category Name *</th>
                    <td><input type="text" name="category_name" required class="regular-text" placeholder="e.g., Pizza, Finger Food, Beverages" /></td>
                </tr>
                <tr>
                    <th scope="row">Display Order</th>
                    <td>
                        <input type="number" name="display_order" value="<?php echo $next_order; ?>" min="1" class="small-text" />
                        <p class="description">Lower numbers appear first in the catalog</p>
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
                <input type="submit" name="save_category" class="button-primary" value="Add Category" />
                <a href="<?php echo admin_url('admin.php?page=pds-categories'); ?>" class="button">Cancel</a>
            </p>
        </form>
    </div>
    <?php
}

/**
 * Edit category form
 */
function pds_edit_category_form($category_id) {
    global $wpdb;
    
    $category = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}pizza_categories WHERE category_id = %d",
        $category_id
    ));
    
    if (!$category) {
        echo '<div class="wrap"><h1>Category not found</h1><p>The requested category could not be found.</p></div>';
        return;
    }
    
    ?>
    <div class="wrap">
        <h1>Edit Category</h1>
        
        <form method="post">
            <?php wp_nonce_field('save_category', 'pds_nonce'); ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="category_id" value="<?php echo $category->category_id; ?>">
            
            <table class="form-table">
                <tr>
                    <th scope="row">Category ID</th>
                    <td><strong><?php echo $category->category_id; ?></strong></td>
                </tr>
                <tr>
                    <th scope="row">Category Name *</th>
                    <td><input type="text" name="category_name" value="<?php echo esc_attr($category->category_name); ?>" required class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row">Display Order</th>
                    <td>
                        <input type="number" name="display_order" value="<?php echo $category->display_order; ?>" min="1" class="small-text" />
                        <p class="description">Lower numbers appear first in the catalog</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Status</th>
                    <td>
                        <select name="status">
                            <option value="active" <?php selected($category->status, 'active'); ?>>Active</option>
                            <option value="inactive" <?php selected($category->status, 'inactive'); ?>>Inactive</option>
                        </select>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="save_category" class="button-primary" value="Update Category" />
                <a href="<?php echo admin_url('admin.php?page=pds-categories'); ?>" class="button">Cancel</a>
            </p>
        </form>
    </div>
    <?php
}

/**
 * Save category (add or edit)
 */
function pds_save_category() {
    global $wpdb;
    
    if (!wp_verify_nonce($_POST['pds_nonce'], 'save_category')) {
        wp_die('Security check failed');
    }
    
    $action = sanitize_text_field($_POST['action']);
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $category_name = sanitize_text_field($_POST['category_name']);
    $display_order = intval($_POST['display_order']);
    $status = sanitize_text_field($_POST['status']);
    
    // Validate required fields
    if (empty($category_name)) {
        echo '<div class="notice notice-error"><p>Please enter a category name.</p></div>';
        return;
    }
    
    // Check for duplicate category name
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT category_id FROM {$wpdb->prefix}pizza_categories 
         WHERE category_name = %s AND category_id != %d",
        $category_name,
        $category_id
    ));
    
    if ($existing) {
        echo '<div class="notice notice-error"><p>A category with this name already exists.</p></div>';
        return;
    }
    
    $category_data = array(
        'category_name' => $category_name,
        'display_order' => $display_order,
        'status' => $status
    );
    
    if ($action === 'add') {
        // Insert new category
        $result = $wpdb->insert(
            $wpdb->prefix . 'pizza_categories',
            $category_data
        );
        
        if ($result) {
            $new_category_id = $wpdb->insert_id;
            echo '<div class="notice notice-success"><p>Category added successfully! Category ID: ' . $new_category_id . '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Error adding category.</p></div>';
        }
    } else {
        // Update existing category
        $result = $wpdb->update(
            $wpdb->prefix . 'pizza_categories',
            $category_data,
            array('category_id' => $category_id)
        );
        
        if ($result !== false) {
            echo '<div class="notice notice-success"><p>Category updated successfully!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>Error updating category.</p></div>';
        }
    }
}

/**
 * Delete category
 */
function pds_delete_category($category_id) {
    global $wpdb;
    
    if (!$category_id) {
        return;
    }
    
    // Check if category has products
    $product_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}pizza_products WHERE category_id = %d",
        $category_id
    ));
    
    if ($product_count > 0) {
        echo '<div class="notice notice-error"><p>Cannot delete category that contains products. Please move or delete the products first.</p></div>';
        return;
    }
    
    // Delete category
    $result = $wpdb->delete(
        $wpdb->prefix . 'pizza_categories',
        array('category_id' => $category_id)
    );
    
    if ($result) {
        echo '<div class="notice notice-success"><p>Category deleted successfully!</p></div>';
    } else {
        echo '<div class="notice notice-error"><p>Error deleting category.</p></div>';
    }
}

<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Display pizza shop catalog shortcode
 */
function pds_display_pizza_catalog($atts) {
    global $wpdb;
    
    // Get shop settings
    $shop_name = get_option('pds_shop_name', 'Pizza Delivery Shop');
    $currency_symbol = get_option('pds_currency_symbol', '$');
    
    // Get categories and products
    $categories = pds_get_categories();
    $delivery_areas = pds_get_delivery_areas();
    
    if (empty($categories)) {
        return '<div class="pds-error">No categories found. Please contact the administrator.</div>';
    }
    
    ob_start();
    ?>
    <div id="pizza-shop-catalog" class="pds-catalog">
        <div class="pds-header">
            <h2><?php echo esc_html($shop_name); ?></h2>
            <p class="pds-subtitle">Choose your favorites and place your order!</p>
        </div>
        
        <!-- Order Type Selection -->
        <div class="pds-order-type-section">
            <h3>Order Type</h3>
            <div class="pds-order-type-selector">
                <label class="pds-radio-label">
                    <input type="radio" name="order_type" value="takeaway" checked>
                    <span class="pds-radio-custom"></span>
                    <span class="pds-radio-text">Takeaway</span>
                </label>
                <label class="pds-radio-label">
                    <input type="radio" name="order_type" value="delivery">
                    <span class="pds-radio-custom"></span>
                    <span class="pds-radio-text">Delivery</span>
                </label>
            </div>
        </div>
        
        <!-- Delivery Area Selection -->
        <div id="pds-delivery-area-section" class="pds-delivery-section" style="display:none;">
            <h3>Select Delivery Area</h3>
            <select id="pds-delivery-area" class="pds-select">
                <option value="">Choose your area...</option>
                <?php foreach ($delivery_areas as $area): ?>
                    <option value="<?php echo $area->area_id; ?>" 
                            data-charge="<?php echo $area->delivery_charge; ?>" 
                            data-minimum="<?php echo $area->minimum_order; ?>">
                        <?php echo esc_html($area->area_name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div id="pds-delivery-info" class="pds-delivery-info"></div>
        </div>
        
        <!-- Product Categories -->
        <div class="pds-categories">
            <?php foreach ($categories as $category): ?>
                <?php
                $products = pds_get_products_by_category($category->category_id);
                if (empty($products)) continue;
                ?>
                
                <div class="pds-category" data-category-id="<?php echo $category->category_id; ?>">
                    <h3 class="pds-category-title"><?php echo esc_html($category->category_name); ?></h3>
                    
                    <div class="pds-products-grid">
                        <?php foreach ($products as $product): ?>
                            <?php
                            $pricing = pds_get_product_pricing($product->product_id);
                            if (empty($pricing)) continue;
                            ?>
                            
                            <div class="pds-product-item" data-product-id="<?php echo $product->product_id; ?>">
                                <?php if ($product->image_url): ?>
                                    <div class="pds-product-image">
                                        <img src="<?php echo esc_url($product->image_url); ?>" 
                                             alt="<?php echo esc_attr($product->name); ?>"
                                             onerror="this.style.display='none'">
                                    </div>
                                <?php endif; ?>
                                
                                <div class="pds-product-content">
                                    <h4 class="pds-product-name"><?php echo esc_html($product->name); ?></h4>
                                    
                                    <?php if ($product->description): ?>
                                        <p class="pds-product-description"><?php echo esc_html($product->description); ?></p>
                                    <?php endif; ?>
                                    
                                    <div class="pds-size-options">
                                        <?php foreach ($pricing as $price): ?>
                                            <div class="pds-size-option">
                                                <label class="pds-size-label">
                                                    <input type="radio" 
                                                           name="size_<?php echo $product->product_id; ?>" 
                                                           value="<?php echo $price->size_type; ?>"
                                                           data-takeaway-price="<?php echo $price->takeaway_price; ?>"
                                                           data-delivery-price="<?php echo $price->delivery_price; ?>"
                                                           data-product-name="<?php echo esc_attr($product->name); ?>">
                                                    <span class="pds-size-info">
                                                        <span class="pds-size-name"><?php echo ucfirst($price->size_type); ?></span>
                                                        <span class="pds-price-display"><?php echo $currency_symbol; ?><?php echo number_format($price->takeaway_price, 2); ?></span>
                                                    </span>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <div class="pds-quantity-section">
                                        <label for="qty_<?php echo $product->product_id; ?>">Quantity:</label>
                                        <input type="number" 
                                               id="qty_<?php echo $product->product_id; ?>" 
                                               class="pds-quantity-input" 
                                               min="1" 
                                               max="10" 
                                               value="1">
                                    </div>
                                    
                                    <button class="pds-add-to-cart" data-product-id="<?php echo $product->product_id; ?>">
                                        Add to Cart
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Cart Summary -->
        <div id="pds-cart-summary" class="pds-cart-summary">
            <h3>Your Order</h3>
            <div id="pds-cart-items" class="pds-cart-items">
                <p class="pds-empty-cart">Your cart is empty</p>
            </div>
            <div id="pds-cart-totals" class="pds-cart-totals" style="display:none;">
                <div class="pds-subtotal">
                    <span>Subtotal:</span>
                    <span id="pds-subtotal-amount"><?php echo $currency_symbol; ?>0.00</span>
                </div>
                <div id="pds-delivery-charge" class="pds-delivery-charge" style="display:none;">
                    <span>Delivery Charge:</span>
                    <span id="pds-delivery-amount"><?php echo $currency_symbol; ?>0.00</span>
                </div>
                <div class="pds-total">
                    <span><strong>Total:</strong></span>
                    <span id="pds-total-amount"><strong><?php echo $currency_symbol; ?>0.00</strong></span>
                </div>
                <div id="pds-minimum-order-warning" class="pds-warning" style="display:none;"></div>
            </div>
            <div id="pds-cart-actions" class="pds-cart-actions" style="display:none;">
                <button id="pds-clear-cart" class="pds-button pds-button-secondary">Clear Cart</button>
                <button id="pds-checkout" class="pds-button pds-button-primary">Proceed to Checkout</button>
            </div>
        </div>
        
        <!-- Checkout Form -->
        <div id="pds-checkout-form" class="pds-checkout-form" style="display:none;">
            <h3>Customer Information</h3>
            <form id="pds-order-form">
                <div class="pds-form-row">
                    <div class="pds-form-group">
                        <label for="customer_name">Full Name *</label>
                        <input type="text" id="customer_name" name="customer_name" required>
                    </div>
                    <div class="pds-form-group">
                        <label for="customer_phone">Phone Number *</label>
                        <input type="tel" id="customer_phone" name="customer_phone" required>
                    </div>
                </div>
                
                <div class="pds-form-group">
                    <label for="customer_email">Email Address</label>
                    <input type="email" id="customer_email" name="customer_email">
                </div>
                
                <div id="pds-delivery-address-section" class="pds-form-group" style="display:none;">
                    <label for="delivery_address">Delivery Address *</label>
                    <textarea id="delivery_address" name="delivery_address" rows="3" placeholder="Enter your complete delivery address"></textarea>
                </div>
                
                <div class="pds-form-group">
                    <label for="order_notes">Special Instructions</label>
                    <textarea id="order_notes" name="order_notes" rows="2" placeholder="Any special requests or notes..."></textarea>
                </div>
                
                <div class="pds-form-actions">
                    <button type="button" id="pds-back-to-cart" class="pds-button pds-button-secondary">Back to Cart</button>
                    <button type="submit" id="pds-place-order" class="pds-button pds-button-primary">Place Order</button>
                </div>
            </form>
        </div>
        
        <!-- Order Confirmation -->
        <div id="pds-order-confirmation" class="pds-order-confirmation" style="display:none;">
            <div class="pds-success-message">
                <h3>Order Placed Successfully!</h3>
                <p>Thank you for your order. We'll contact you shortly to confirm the details.</p>
                <div id="pds-order-details"></div>
                <button id="pds-new-order" class="pds-button pds-button-primary">Place New Order</button>
            </div>
        </div>
    </div>
    
    <script>
        // Pass PHP data to JavaScript
        window.pdsData = {
            currencySymbol: '<?php echo $currency_symbol; ?>',
            ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
            nonce: '<?php echo wp_create_nonce('pds_nonce'); ?>'
        };
    </script>
    <?php
    
    return ob_get_clean();
}
add_shortcode('pizza_shop_catalog', 'pds_display_pizza_catalog');

/**
 * Admin functions include
 */
function pds_admin_functions() {
    // This function is called from the main plugin file
    // to include admin-specific functionality
}

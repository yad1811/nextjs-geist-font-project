jQuery(document).ready(function($) {
    'use strict';
    
    // Global variables
    let cart = [];
    let orderType = 'takeaway';
    let selectedArea = null;
    let deliveryCharge = 0;
    let minimumOrder = 0;
    
    // Initialize
    init();
    
    function init() {
        bindEvents();
        updateCartDisplay();
    }
    
    function bindEvents() {
        // Order type change
        $('input[name="order_type"]').on('change', handleOrderTypeChange);
        
        // Delivery area change
        $('#pds-delivery-area').on('change', handleDeliveryAreaChange);
        
        // Size selection change
        $(document).on('change', '.pds-size-option input[type="radio"]', handleSizeChange);
        
        // Add to cart
        $(document).on('click', '.pds-add-to-cart', handleAddToCart);
        
        // Remove from cart
        $(document).on('click', '.pds-item-remove', handleRemoveFromCart);
        
        // Clear cart
        $('#pds-clear-cart').on('click', handleClearCart);
        
        // Checkout
        $('#pds-checkout').on('click', handleCheckout);
        
        // Back to cart
        $('#pds-back-to-cart').on('click', handleBackToCart);
        
        // Place order
        $('#pds-order-form').on('submit', handlePlaceOrder);
        
        // New order
        $('#pds-new-order').on('click', handleNewOrder);
        
        // Quantity change
        $(document).on('change', '.pds-quantity-input', function() {
            const max = parseInt($(this).attr('max'));
            const min = parseInt($(this).attr('min'));
            let val = parseInt($(this).val());
            
            if (val > max) $(this).val(max);
            if (val < min) $(this).val(min);
        });
    }
    
    function handleOrderTypeChange() {
        orderType = $(this).val();
        
        if (orderType === 'delivery') {
            $('#pds-delivery-area-section').slideDown();
            $('#pds-delivery-address-section').show();
        } else {
            $('#pds-delivery-area-section').slideUp();
            $('#pds-delivery-address-section').hide();
            selectedArea = null;
            deliveryCharge = 0;
            minimumOrder = 0;
            $('#pds-delivery-info').empty();
        }
        
        updateAllPrices();
        updateCartTotals();
    }
    
    function handleDeliveryAreaChange() {
        const $selected = $(this).find(':selected');
        
        if ($selected.val()) {
            selectedArea = {
                id: $selected.val(),
                name: $selected.text(),
                charge: parseFloat($selected.data('charge')),
                minimum: parseFloat($selected.data('minimum'))
            };
            
            deliveryCharge = selectedArea.charge;
            minimumOrder = selectedArea.minimum;
            
            $('#pds-delivery-info').html(
                `<strong>Delivery Charge:</strong> ${window.pdsData.currencySymbol}${deliveryCharge.toFixed(2)} | 
                 <strong>Minimum Order:</strong> ${window.pdsData.currencySymbol}${minimumOrder.toFixed(2)}`
            );
        } else {
            selectedArea = null;
            deliveryCharge = 0;
            minimumOrder = 0;
            $('#pds-delivery-info').empty();
        }
        
        updateCartTotals();
    }
    
    function handleSizeChange() {
        updatePriceDisplay($(this));
    }
    
    function updateAllPrices() {
        $('.pds-size-option input[type="radio"]').each(function() {
            updatePriceDisplay($(this));
        });
    }
    
    function updatePriceDisplay($input) {
        const price = orderType === 'takeaway' ? 
            $input.data('takeaway-price') : 
            $input.data('delivery-price');
        
        $input.closest('.pds-size-option').find('.pds-price-display')
            .text(window.pdsData.currencySymbol + parseFloat(price).toFixed(2));
    }
    
    function handleAddToCart() {
        const productId = $(this).data('product-id');
        const $selectedSize = $(`input[name="size_${productId}"]:checked`);
        const quantity = parseInt($(`#qty_${productId}`).val()) || 1;
        
        if ($selectedSize.length === 0) {
            alert('Please select a size');
            return;
        }
        
        const price = orderType === 'takeaway' ? 
            $selectedSize.data('takeaway-price') : 
            $selectedSize.data('delivery-price');
        
        const item = {
            productId: productId,
            name: $selectedSize.data('product-name'),
            size: $selectedSize.val(),
            quantity: quantity,
            price: parseFloat(price),
            total: parseFloat(price) * quantity
        };
        
        // Check if item already exists in cart
        const existingIndex = cart.findIndex(cartItem => 
            cartItem.productId === item.productId && cartItem.size === item.size
        );
        
        if (existingIndex !== -1) {
            cart[existingIndex].quantity += item.quantity;
            cart[existingIndex].total = cart[existingIndex].price * cart[existingIndex].quantity;
        } else {
            cart.push(item);
        }
        
        // Reset form
        $(`input[name="size_${productId}"]`).prop('checked', false);
        $(`#qty_${productId}`).val(1);
        
        updateCartDisplay();
        
        // Show success message
        showMessage('Item added to cart!', 'success');
    }
    
    function handleRemoveFromCart() {
        const index = $(this).data('index');
        cart.splice(index, 1);
        updateCartDisplay();
        showMessage('Item removed from cart', 'info');
    }
    
    function handleClearCart() {
        if (confirm('Are you sure you want to clear your cart?')) {
            cart = [];
            updateCartDisplay();
            showMessage('Cart cleared', 'info');
        }
    }
    
    function updateCartDisplay() {
        const $cartItems = $('#pds-cart-items');
        const $cartTotals = $('#pds-cart-totals');
        const $cartActions = $('#pds-cart-actions');
        
        if (cart.length === 0) {
            $cartItems.html('<p class="pds-empty-cart">Your cart is empty</p>');
            $cartTotals.hide();
            $cartActions.hide();
            return;
        }
        
        let html = '';
        cart.forEach((item, index) => {
            html += `
                <div class="pds-cart-item">
                    <div class="pds-item-info">
                        <div class="pds-item-name">${item.name}</div>
                        <div class="pds-item-details">${item.size.charAt(0).toUpperCase() + item.size.slice(1)} × ${item.quantity}</div>
                    </div>
                    <div class="pds-item-price">${window.pdsData.currencySymbol}${item.total.toFixed(2)}</div>
                    <button class="pds-item-remove" data-index="${index}">×</button>
                </div>
            `;
        });
        
        $cartItems.html(html);
        $cartTotals.show();
        $cartActions.show();
        
        updateCartTotals();
    }
    
    function updateCartTotals() {
        const subtotal = cart.reduce((sum, item) => sum + item.total, 0);
        let total = subtotal;
        
        $('#pds-subtotal-amount').text(window.pdsData.currencySymbol + subtotal.toFixed(2));
        
        if (orderType === 'delivery' && selectedArea) {
            total += deliveryCharge;
            $('#pds-delivery-charge').show();
            $('#pds-delivery-amount').text(window.pdsData.currencySymbol + deliveryCharge.toFixed(2));
            
            // Check minimum order
            if (subtotal < minimumOrder) {
                $('#pds-minimum-order-warning').show().html(
                    `<strong>Note:</strong> Minimum order for delivery is ${window.pdsData.currencySymbol}${minimumOrder.toFixed(2)}. 
                     Add ${window.pdsData.currencySymbol}${(minimumOrder - subtotal).toFixed(2)} more to proceed.`
                );
                $('#pds-checkout').prop('disabled', true);
            } else {
                $('#pds-minimum-order-warning').hide();
                $('#pds-checkout').prop('disabled', false);
            }
        } else {
            $('#pds-delivery-charge').hide();
            $('#pds-minimum-order-warning').hide();
            $('#pds-checkout').prop('disabled', false);
        }
        
        $('#pds-total-amount').html(`<strong>${window.pdsData.currencySymbol}${total.toFixed(2)}</strong>`);
    }
    
    function handleCheckout() {
        if (cart.length === 0) {
            alert('Your cart is empty');
            return;
        }
        
        if (orderType === 'delivery' && !selectedArea) {
            alert('Please select a delivery area');
            return;
        }
        
        // Validate minimum order for delivery
        const subtotal = cart.reduce((sum, item) => sum + item.total, 0);
        if (orderType === 'delivery' && selectedArea && subtotal < minimumOrder) {
            alert(`Minimum order for delivery is ${window.pdsData.currencySymbol}${minimumOrder.toFixed(2)}`);
            return;
        }
        
        $('#pds-cart-summary').hide();
        $('#pds-checkout-form').slideDown();
        
        // Scroll to checkout form
        $('html, body').animate({
            scrollTop: $('#pds-checkout-form').offset().top - 50
        }, 500);
    }
    
    function handleBackToCart() {
        $('#pds-checkout-form').slideUp();
        $('#pds-cart-summary').slideDown();
    }
    
    function handlePlaceOrder(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        // Validate required fields
        const customerName = formData.get('customer_name');
        const customerPhone = formData.get('customer_phone');
        
        if (!customerName || !customerPhone) {
            alert('Please fill in all required fields');
            return;
        }
        
        if (orderType === 'delivery') {
            const deliveryAddress = formData.get('delivery_address');
            if (!deliveryAddress) {
                alert('Please provide delivery address');
                return;
            }
        }
        
        // Prepare order data
        const orderData = {
            action: 'pds_place_order',
            nonce: window.pdsData.nonce,
            customer_name: customerName,
            customer_phone: customerPhone,
            customer_email: formData.get('customer_email'),
            order_type: orderType,
            delivery_area_id: selectedArea ? selectedArea.id : null,
            delivery_address: formData.get('delivery_address'),
            order_notes: formData.get('order_notes'),
            cart_items: JSON.stringify(cart)
        };
        
        // Show loading
        $('#pds-place-order').prop('disabled', true).html('Placing Order... <span class="pds-spinner"></span>');
        
        // Submit order
        $.post(window.pdsData.ajaxUrl, orderData)
            .done(function(response) {
                if (response.success) {
                    showOrderConfirmation(response.data);
                } else {
                    alert('Error: ' + response.data);
                }
            })
            .fail(function() {
                alert('Failed to place order. Please try again.');
            })
            .always(function() {
                $('#pds-place-order').prop('disabled', false).text('Place Order');
            });
    }
    
    function showOrderConfirmation(data) {
        $('#pds-checkout-form').hide();
        
        let orderDetailsHtml = `
            <div class="pds-order-summary">
                <h4>Order #${data.order_id}</h4>
                <p><strong>Customer:</strong> ${data.order_details.customer_name}</p>
                <p><strong>Order Type:</strong> ${data.order_details.order_type.charAt(0).toUpperCase() + data.order_details.order_type.slice(1)}</p>
                
                <h5>Items:</h5>
                <ul>
        `;
        
        data.order_details.items.forEach(item => {
            orderDetailsHtml += `<li>${item.name} (${item.size}) × ${item.quantity} = ${window.pdsData.currencySymbol}${item.total.toFixed(2)}</li>`;
        });
        
        orderDetailsHtml += `
                </ul>
                <p><strong>Subtotal:</strong> ${window.pdsData.currencySymbol}${data.order_details.subtotal.toFixed(2)}</p>
        `;
        
        if (data.order_details.delivery_charge > 0) {
            orderDetailsHtml += `<p><strong>Delivery Charge:</strong> ${window.pdsData.currencySymbol}${data.order_details.delivery_charge.toFixed(2)}</p>`;
        }
        
        orderDetailsHtml += `
                <p><strong>Total:</strong> ${window.pdsData.currencySymbol}${data.order_details.total_amount.toFixed(2)}</p>
            </div>
        `;
        
        $('#pds-order-details').html(orderDetailsHtml);
        $('#pds-order-confirmation').slideDown();
        
        // Scroll to confirmation
        $('html, body').animate({
            scrollTop: $('#pds-order-confirmation').offset().top - 50
        }, 500);
    }
    
    function handleNewOrder() {
        // Reset everything
        cart = [];
        orderType = 'takeaway';
        selectedArea = null;
        deliveryCharge = 0;
        minimumOrder = 0;
        
        // Reset form
        $('#pds-order-form')[0].reset();
        $('input[name="order_type"][value="takeaway"]').prop('checked', true);
        $('#pds-delivery-area').val('');
        $('#pds-delivery-area-section').hide();
        $('#pds-delivery-info').empty();
        
        // Reset displays
        updateAllPrices();
        updateCartDisplay();
        
        // Show cart, hide others
        $('#pds-order-confirmation').hide();
        $('#pds-checkout-form').hide();
        $('#pds-cart-summary').show();
        
        // Scroll to top
        $('html, body').animate({
            scrollTop: $('#pizza-shop-catalog').offset().top - 50
        }, 500);
        
        showMessage('Ready for new order!', 'success');
    }
    
    function showMessage(message, type) {
        // Remove existing messages
        $('.pds-message').remove();
        
        const messageClass = type === 'success' ? 'pds-success-message' : 
                           type === 'error' ? 'pds-error-message' : 'pds-info-message';
        
        const $message = $(`<div class="pds-message ${messageClass}" style="
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#d4edda' : type === 'error' ? '#f8d7da' : '#d1ecf1'};
            color: ${type === 'success' ? '#155724' : type === 'error' ? '#721c24' : '#0c5460'};
            padding: 15px 20px;
            border-radius: 4px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 9999;
            max-width: 300px;
            border: 1px solid ${type === 'success' ? '#c3e6cb' : type === 'error' ? '#f5c6cb' : '#bee5eb'};
        ">${message}</div>`);
        
        $('body').append($message);
        
        // Auto remove after 3 seconds
        setTimeout(() => {
            $message.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    // Utility function to format currency
    function formatCurrency(amount) {
        return window.pdsData.currencySymbol + parseFloat(amount).toFixed(2);
    }
    
    // Initialize prices on page load
    updateAllPrices();
});

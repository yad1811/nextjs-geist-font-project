jQuery(document).ready(function($) {
    'use strict';
    
    // Initialize admin functionality
    init();
    
    function init() {
        bindEvents();
        initializeComponents();
    }
    
    function bindEvents() {
        // Order status updates
        $('.pds-status-dropdown').on('change', handleOrderStatusUpdate);
        
        // Product form enhancements
        $('#product_form').on('submit', validateProductForm);
        
        // Category form enhancements
        $('#category_form').on('submit', validateCategoryForm);
        
        // Delivery area form enhancements
        $('#delivery_area_form').on('submit', validateDeliveryAreaForm);
        
        // Bulk actions
        $('#bulk-action-selector-top, #bulk-action-selector-bottom').on('change', handleBulkActionChange);
        
        // Export functionality
        $('.pds-export-button').on('click', handleExport);
        
        // Delete confirmations
        $('.pds-delete-link').on('click', handleDeleteConfirmation);
        
        // Image URL preview
        $('input[name="image_url"]').on('blur', handleImagePreview);
        
        // Pricing calculator
        $('.pds-pricing-input').on('input', calculatePricingTotals);
        
        // Auto-save drafts
        if ($('#product_form, #category_form, #delivery_area_form').length) {
            setInterval(autoSaveDraft, 30000); // Auto-save every 30 seconds
        }
    }
    
    function initializeComponents() {
        // Initialize tooltips
        initTooltips();
        
        // Initialize sortable tables
        initSortableTables();
        
        // Initialize dashboard widgets
        initDashboardWidgets();
        
        // Load dashboard stats
        loadDashboardStats();
    }
    
    function handleOrderStatusUpdate() {
        const $select = $(this);
        const orderId = $select.data('order-id');
        const newStatus = $select.val();
        const originalStatus = $select.data('original-status');
        
        if (!orderId || newStatus === originalStatus) {
            return;
        }
        
        // Show loading
        $select.prop('disabled', true);
        
        const data = {
            action: 'pds_update_order_status',
            nonce: window.pdsAdminAjax.nonce,
            order_id: orderId,
            status: newStatus
        };
        
        $.post(window.pdsAdminAjax.ajaxUrl, data)
            .done(function(response) {
                if (response.success) {
                    showAdminMessage('Order status updated successfully', 'success');
                    $select.data('original-status', newStatus);
                    
                    // Update status display
                    const $statusCell = $select.closest('tr').find('.pds-status-display');
                    $statusCell.removeClass().addClass('pds-status-' + newStatus);
                    $statusCell.text(newStatus.charAt(0).toUpperCase() + newStatus.slice(1));
                } else {
                    showAdminMessage('Failed to update order status: ' + response.data, 'error');
                    $select.val(originalStatus);
                }
            })
            .fail(function() {
                showAdminMessage('Network error. Please try again.', 'error');
                $select.val(originalStatus);
            })
            .always(function() {
                $select.prop('disabled', false);
            });
    }
    
    function validateProductForm(e) {
        const productName = $('input[name="product_name"]').val().trim();
        const categoryId = $('select[name="category_id"]').val();
        
        if (!productName) {
            e.preventDefault();
            showAdminMessage('Product name is required', 'error');
            $('input[name="product_name"]').focus();
            return false;
        }
        
        if (!categoryId) {
            e.preventDefault();
            showAdminMessage('Please select a category', 'error');
            $('select[name="category_id"]').focus();
            return false;
        }
        
        // Validate pricing
        let hasPricing = false;
        $('.pds-pricing-input').each(function() {
            if ($(this).val() && parseFloat($(this).val()) > 0) {
                hasPricing = true;
                return false;
            }
        });
        
        if (!hasPricing) {
            e.preventDefault();
            showAdminMessage('Please enter at least one price', 'error');
            $('.pds-pricing-input').first().focus();
            return false;
        }
        
        return true;
    }
    
    function validateCategoryForm(e) {
        const categoryName = $('input[name="category_name"]').val().trim();
        
        if (!categoryName) {
            e.preventDefault();
            showAdminMessage('Category name is required', 'error');
            $('input[name="category_name"]').focus();
            return false;
        }
        
        return true;
    }
    
    function validateDeliveryAreaForm(e) {
        const areaName = $('input[name="area_name"]').val().trim();
        const deliveryCharge = parseFloat($('input[name="delivery_charge"]').val());
        const minimumOrder = parseFloat($('input[name="minimum_order"]').val());
        
        if (!areaName) {
            e.preventDefault();
            showAdminMessage('Area name is required', 'error');
            $('input[name="area_name"]').focus();
            return false;
        }
        
        if (isNaN(deliveryCharge) || deliveryCharge < 0) {
            e.preventDefault();
            showAdminMessage('Please enter a valid delivery charge', 'error');
            $('input[name="delivery_charge"]').focus();
            return false;
        }
        
        if (isNaN(minimumOrder) || minimumOrder < 0) {
            e.preventDefault();
            showAdminMessage('Please enter a valid minimum order amount', 'error');
            $('input[name="minimum_order"]').focus();
            return false;
        }
        
        return true;
    }
    
    function handleBulkActionChange() {
        const action = $(this).val();
        const $applyButton = $(this).siblings('.button');
        
        if (action === '-1') {
            $applyButton.prop('disabled', true);
        } else {
            $applyButton.prop('disabled', false);
        }
    }
    
    function handleExport() {
        const exportType = $(this).data('export-type');
        
        if (confirm('Export ' + exportType + ' data to CSV?')) {
            showAdminMessage('Preparing export...', 'info');
            
            // The actual export is handled by PHP
            return true;
        }
        
        return false;
    }
    
    function handleDeleteConfirmation(e) {
        const itemType = $(this).data('item-type') || 'item';
        const itemName = $(this).data('item-name') || '';
        
        const message = itemName ? 
            `Are you sure you want to delete "${itemName}"?` : 
            `Are you sure you want to delete this ${itemType}?`;
        
        if (!confirm(message)) {
            e.preventDefault();
            return false;
        }
        
        return true;
    }
    
    function handleImagePreview() {
        const imageUrl = $(this).val();
        const $preview = $(this).siblings('.pds-image-preview');
        
        if (imageUrl && isValidUrl(imageUrl)) {
            if ($preview.length === 0) {
                $(this).after('<div class="pds-image-preview" style="margin-top: 10px;"></div>');
            }
            
            $('.pds-image-preview').html(`
                <img src="${imageUrl}" 
                     style="max-width: 200px; max-height: 150px; border: 1px solid #ddd; border-radius: 4px;"
                     onerror="this.style.display='none'">
            `);
        } else {
            $preview.remove();
        }
    }
    
    function calculatePricingTotals() {
        // Calculate profit margins, etc.
        const $row = $(this).closest('tr');
        const takeawayPrice = parseFloat($row.find('.takeaway-price').val()) || 0;
        const deliveryPrice = parseFloat($row.find('.delivery-price').val()) || 0;
        
        if (takeawayPrice > 0 && deliveryPrice > 0) {
            const margin = deliveryPrice - takeawayPrice;
            const marginPercent = ((margin / takeawayPrice) * 100).toFixed(1);
            
            $row.find('.price-margin').text(`+$${margin.toFixed(2)} (${marginPercent}%)`);
        }
    }
    
    function autoSaveDraft() {
        const $form = $('#product_form, #category_form, #delivery_area_form');
        
        if ($form.length && $form.find('input, select, textarea').filter(function() {
            return $(this).val() !== '';
        }).length > 0) {
            
            const formData = $form.serialize();
            
            // Save to localStorage as backup
            const formType = $form.attr('id');
            localStorage.setItem('pds_draft_' + formType, formData);
            
            // Show auto-save indicator
            showAutoSaveIndicator();
        }
    }
    
    function showAutoSaveIndicator() {
        $('.pds-autosave-indicator').remove();
        
        const $indicator = $('<span class="pds-autosave-indicator" style="color: #666; font-size: 12px; margin-left: 10px;">Draft saved</span>');
        $('.page-title-action').after($indicator);
        
        setTimeout(() => {
            $indicator.fadeOut(2000, function() {
                $(this).remove();
            });
        }, 2000);
    }
    
    function initTooltips() {
        // Add tooltips to help icons
        $('.pds-help-icon').each(function() {
            const helpText = $(this).data('help');
            if (helpText) {
                $(this).attr('title', helpText);
            }
        });
    }
    
    function initSortableTables() {
        // Make category display order sortable
        if ($('.pds-sortable-table').length) {
            $('.pds-sortable-table tbody').sortable({
                handle: '.pds-sort-handle',
                update: function(event, ui) {
                    updateDisplayOrder();
                }
            });
        }
    }
    
    function updateDisplayOrder() {
        const orders = [];
        $('.pds-sortable-table tbody tr').each(function(index) {
            const id = $(this).data('item-id');
            if (id) {
                orders.push({
                    id: id,
                    order: index + 1
                });
            }
        });
        
        // Send AJAX request to update order
        const data = {
            action: 'pds_update_display_order',
            nonce: window.pdsAdminAjax.nonce,
            orders: orders
        };
        
        $.post(window.pdsAdminAjax.ajaxUrl, data)
            .done(function(response) {
                if (response.success) {
                    showAdminMessage('Display order updated', 'success');
                }
            });
    }
    
    function initDashboardWidgets() {
        // Refresh dashboard data every 5 minutes
        if ($('.pds-dashboard-stats').length) {
            setInterval(loadDashboardStats, 300000);
        }
    }
    
    function loadDashboardStats() {
        const data = {
            action: 'pds_get_dashboard_stats',
            nonce: window.pdsAdminAjax.nonce
        };
        
        $.post(window.pdsAdminAjax.ajaxUrl, data)
            .done(function(response) {
                if (response.success) {
                    updateDashboardStats(response.data);
                }
            });
    }
    
    function updateDashboardStats(stats) {
        $('.pds-stat-number').each(function() {
            const statType = $(this).closest('.pds-stat-box').data('stat-type');
            if (stats[statType] !== undefined) {
                $(this).text(stats[statType]);
            }
        });
    }
    
    function showAdminMessage(message, type) {
        // Remove existing messages
        $('.pds-admin-message').remove();
        
        const messageClass = `pds-admin-message ${type}`;
        const $message = $(`<div class="${messageClass}">${message}</div>`);
        
        // Insert after page title
        $('.wrap h1').after($message);
        
        // Auto-hide success messages
        if (type === 'success') {
            setTimeout(() => {
                $message.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        }
        
        // Scroll to message
        $('html, body').animate({
            scrollTop: $message.offset().top - 50
        }, 300);
    }
    
    function isValidUrl(string) {
        try {
            new URL(string);
            return true;
        } catch (_) {
            return false;
        }
    }
    
    // Utility functions
    function formatCurrency(amount) {
        return '$' + parseFloat(amount).toFixed(2);
    }
    
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
    }
    
    // Initialize on page load
    $(window).on('load', function() {
        // Restore drafts if available
        restoreDrafts();
        
        // Focus first input
        $('.form-table input[type="text"]:first').focus();
    });
    
    function restoreDrafts() {
        const $form = $('#product_form, #category_form, #delivery_area_form');
        
        if ($form.length) {
            const formType = $form.attr('id');
            const savedData = localStorage.getItem('pds_draft_' + formType);
            
            if (savedData && confirm('Restore previously saved draft?')) {
                // Parse and restore form data
                const params = new URLSearchParams(savedData);
                
                params.forEach((value, key) => {
                    const $field = $form.find(`[name="${key}"]`);
                    if ($field.length) {
                        $field.val(value);
                    }
                });
                
                showAdminMessage('Draft restored', 'info');
            }
        }
    }
    
    // Clear drafts on successful form submission
    $(document).on('submit', 'form', function() {
        const formType = $(this).attr('id');
        if (formType) {
            localStorage.removeItem('pds_draft_' + formType);
        }
    });
    
    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl+S to save form
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            const $submitButton = $('.button-primary[type="submit"]');
            if ($submitButton.length) {
                $submitButton.click();
            }
        }
        
        // Escape to cancel/go back
        if (e.key === 'Escape') {
            const $cancelButton = $('.button:contains("Cancel")');
            if ($cancelButton.length) {
                $cancelButton.click();
            }
        }
    });
});

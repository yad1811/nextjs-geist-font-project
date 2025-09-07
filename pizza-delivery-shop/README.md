# Pizza Delivery Shop WordPress Plugin

A comprehensive WordPress plugin for managing a pizza delivery business with complete ordering system, product management, and customer interface.

## 🍕 Features

### Admin Features
- **Product Management**: Add/edit products with unique IDs, multiple sizes, and different pricing for takeaway/delivery
- **Category System**: Organize products into categories (Pizza, Finger Food, Beverages, Desserts)
- **Delivery Areas**: Set up delivery zones with custom charges and minimum order requirements
- **Order Management**: Track and manage customer orders with status updates
- **Dashboard**: Real-time statistics and quick actions
- **Export**: Export orders to CSV for reporting
- **Settings**: Configure shop information and currency

### Customer Features
- **Product Catalog**: Browse products by category with images and descriptions
- **Order Types**: Choose between takeaway and delivery
- **Dynamic Pricing**: Automatic price updates based on size and order type
- **Shopping Cart**: Add multiple items with quantity selection
- **Delivery Areas**: Select delivery area with automatic charge calculation
- **Checkout**: Complete customer information form
- **Order Confirmation**: Email notifications and order tracking

### Technical Features
- **Responsive Design**: Works on all devices
- **AJAX Processing**: Smooth user experience without page reloads
- **Security**: WordPress nonces, input sanitization, and validation
- **Database Optimization**: Proper indexing and relationships
- **Clean Code**: Follows WordPress coding standards

## 📋 Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

## 🚀 Installation

1. **Upload Plugin**
   - Download the plugin files
   - Upload the `pizza-delivery-shop` folder to `/wp-content/plugins/`
   - Or install via WordPress admin: Plugins → Add New → Upload Plugin

2. **Activate Plugin**
   - Go to Plugins in WordPress admin
   - Find "Pizza Delivery Shop" and click Activate
   - The plugin will automatically create database tables and sample data

3. **Configure Settings**
   - Go to Pizza Shop → Settings
   - Enter your shop information (name, phone, email, address)
   - Set currency symbol if needed

4. **Add to Page**
   - Create a new page or edit an existing one
   - Add the shortcode: `[pizza_shop_catalog]`
   - Publish the page

## 🛠️ Usage

### Setting Up Your Menu

1. **Categories**
   - Go to Pizza Shop → Categories
   - Add categories like "Pizza", "Finger Food", "Beverages", "Desserts"
   - Set display order (lower numbers appear first)

2. **Products**
   - Go to Pizza Shop → Products → Add New Product
   - Enter product name, description, and select category
   - Set pricing for different sizes:
     - Single, Jumbo, Family, Party
     - Different prices for takeaway vs delivery
   - Add product image URL (optional)

3. **Delivery Areas**
   - Go to Pizza Shop → Delivery Areas
   - Add delivery zones with:
     - Area name (e.g., "Downtown", "Suburbs North")
     - Delivery charge
     - Minimum order amount

### Managing Orders

1. **View Orders**
   - Go to Pizza Shop → Orders
   - See all customer orders with details
   - Update order status (Pending → Confirmed → Preparing → Ready → Delivered)

2. **Export Data**
   - Click "Export to CSV" on the Orders page
   - Download order data for reporting

### Customer Experience

1. **Ordering Process**
   - Customer visits page with `[pizza_shop_catalog]` shortcode
   - Selects order type (Takeaway or Delivery)
   - If delivery, selects area and sees charges/minimums
   - Browses products by category
   - Selects size and quantity for each item
   - Adds items to cart
   - Reviews cart with totals
   - Proceeds to checkout
   - Fills customer information
   - Places order and receives confirmation

## 🎨 Customization

### Styling
- Frontend styles: `/assets/css/frontend-style.css`
- Admin styles: `/assets/css/admin-style.css`
- Override styles in your theme's CSS

### Functionality
- Frontend JavaScript: `/assets/js/frontend-script.js`
- Admin JavaScript: `/assets/js/admin-script.js`

### Hooks and Filters
The plugin provides various WordPress hooks for customization:

```php
// Modify product display
add_filter('pds_product_display', 'your_custom_function');

// Customize order email
add_filter('pds_order_email_content', 'your_email_function');

// Add custom order statuses
add_filter('pds_order_statuses', 'your_status_function');
```

## 📊 Database Structure

The plugin creates 5 main tables:

1. **wp_pizza_categories** - Product categories
2. **wp_pizza_products** - Products with unique IDs
3. **wp_pizza_pricing** - Size-based pricing for each product
4. **wp_pizza_delivery_areas** - Delivery zones and charges
5. **wp_pizza_orders** - Customer orders and details

## 🔧 Troubleshooting

### Common Issues

**Plugin not working after activation**
- Check if database tables were created
- Deactivate and reactivate the plugin
- Check WordPress error logs

**Shortcode not displaying**
- Ensure you're using `[pizza_shop_catalog]` exactly
- Check if the page has the shortcode
- Verify plugin is activated

**Orders not saving**
- Check AJAX functionality (browser console for errors)
- Verify nonce security tokens
- Check database permissions

**Styling issues**
- Clear any caching plugins
- Check for theme CSS conflicts
- Verify CSS files are loading

### Debug Mode
Add this to wp-config.php for debugging:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## 🔒 Security

The plugin implements several security measures:
- WordPress nonces for all forms
- Input sanitization and validation
- Prepared database statements
- Capability checks for admin functions
- CSRF protection

## 📈 Performance

- Optimized database queries with proper indexing
- AJAX for smooth user interactions
- Minimal external dependencies
- Responsive images and lazy loading support
- Caching-friendly structure

## 🆘 Support

### Documentation
- Admin help tabs available in WordPress admin
- Contextual help on each admin page
- Shortcode usage examples

### Getting Help
1. Check the troubleshooting section above
2. Review WordPress error logs
3. Test with default WordPress theme
4. Disable other plugins to check for conflicts

## 📝 Changelog

### Version 1.0
- Initial release
- Complete product management system
- Order processing and management
- Responsive frontend interface
- Admin dashboard and statistics
- Email notifications
- CSV export functionality

## 🤝 Contributing

This plugin follows WordPress coding standards. When contributing:
1. Follow WordPress PHP coding standards
2. Test thoroughly before submitting
3. Document any new features
4. Maintain backward compatibility

## 📄 License

This plugin is licensed under the GPL v2 or later.

## 🙏 Credits

Built with WordPress best practices and modern web technologies:
- WordPress Plugin API
- jQuery for frontend interactions
- CSS Grid and Flexbox for responsive design
- WordPress database abstraction layer

---

**Made with ❤️ for pizza lovers everywhere!** 🍕

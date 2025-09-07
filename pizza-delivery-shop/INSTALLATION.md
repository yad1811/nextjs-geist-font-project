# Pizza Delivery Shop Plugin - Installation Guide

## Quick Start Guide

### Step 1: Install the Plugin

**Method 1: Manual Upload**
1. Download all plugin files
2. Create a ZIP file of the `pizza-delivery-shop` folder
3. In WordPress admin, go to Plugins → Add New → Upload Plugin
4. Choose the ZIP file and click "Install Now"
5. Click "Activate Plugin"

**Method 2: FTP Upload**
1. Upload the entire `pizza-delivery-shop` folder to `/wp-content/plugins/`
2. In WordPress admin, go to Plugins
3. Find "Pizza Delivery Shop" and click "Activate"

### Step 2: Initial Setup

After activation, the plugin will automatically:
- Create 5 database tables
- Insert sample categories (Pizza, Finger Food, Beverages, Desserts)
- Insert sample products with pricing
- Insert sample delivery areas
- Create default settings

### Step 3: Configure Your Shop

1. **Go to Pizza Shop → Settings**
   ```
   Shop Name: Your Pizza Shop Name
   Phone: (555) 123-4567
   Email: orders@yourshop.com
   Address: 123 Main St, Your City
   Currency Symbol: $ (or your local currency)
   ```

2. **Customize Categories** (Pizza Shop → Categories)
   - Edit existing categories or add new ones
   - Set display order (1 = first, 2 = second, etc.)

3. **Add Your Products** (Pizza Shop → Products)
   - Edit sample products or add new ones
   - Set pricing for each size (Single, Jumbo, Family, Party)
   - Different prices for takeaway vs delivery
   - Add product images (optional)

4. **Set Up Delivery Areas** (Pizza Shop → Delivery Areas)
   - Edit sample areas or add your actual delivery zones
   - Set delivery charges and minimum order amounts

### Step 4: Add to Your Website

1. **Create or Edit a Page**
   - Go to Pages → Add New (or edit existing page)
   - Add this shortcode where you want the shop to appear:
   ```
   [pizza_shop_catalog]
   ```
   - Publish the page

2. **Test the System**
   - Visit the page with the shortcode
   - Try placing a test order
   - Check if orders appear in Pizza Shop → Orders

## Sample Data Included

The plugin comes with sample data to help you get started:

### Categories
- Pizza (Display Order: 1)
- Finger Food (Display Order: 2)
- Beverages (Display Order: 3)
- Desserts (Display Order: 4)

### Sample Products
- **Margherita Pizza** - Classic tomato base with mozzarella cheese
- **Pepperoni Pizza** - Tomato base with mozzarella and pepperoni
- **Chicken Wings** - Crispy chicken wings with your choice of sauce
- **Garlic Bread** - Fresh bread with garlic butter

### Sample Pricing (per product)
- Single: $12.99 (takeaway) / $14.99 (delivery)
- Jumbo: $18.99 (takeaway) / $20.99 (delivery)
- Family: $24.99 (takeaway) / $26.99 (delivery)
- Party: $32.99 (takeaway) / $34.99 (delivery)

### Sample Delivery Areas
- Downtown: $3.50 delivery, $15.00 minimum
- Suburbs North: $5.00 delivery, $20.00 minimum
- Suburbs South: $5.00 delivery, $20.00 minimum
- Industrial Area: $7.50 delivery, $25.00 minimum

## Customization Tips

### 1. Styling
The plugin uses clean, modern CSS that works with most themes. To customize:

**Override in your theme's style.css:**
```css
/* Change primary color */
.pds-add-to-cart {
    background-color: #your-color !important;
}

/* Customize product cards */
.pds-product-item {
    border: 2px solid #your-color;
    border-radius: 10px;
}
```

### 2. Product Images
Add product images by entering image URLs in the product form:
```
https://your-website.com/images/margherita-pizza.jpg
```

### 3. Email Notifications
The plugin sends order confirmation emails. Customize the email template by adding this to your theme's functions.php:
```php
add_filter('pds_order_email_content', 'custom_order_email');
function custom_order_email($content) {
    // Customize email content here
    return $content;
}
```

## Admin Features Overview

### Dashboard
- Order statistics (total, pending, today's orders)
- Quick actions (add product, view orders)
- Recent orders summary

### Product Management
- Add/edit products with unique IDs
- Set multiple size pricing
- Organize by categories
- Upload product images
- Set active/inactive status

### Order Management
- View all customer orders
- Update order status
- Export orders to CSV
- Customer contact information
- Order details and totals

### Settings
- Shop information
- Currency settings
- Email templates
- System configuration

## Customer Experience

### Ordering Process
1. **Choose Order Type**: Takeaway or Delivery
2. **Select Delivery Area** (if delivery): Shows charges and minimums
3. **Browse Products**: Organized by categories
4. **Select Size & Quantity**: Dynamic pricing updates
5. **Add to Cart**: Real-time cart calculations
6. **Review Cart**: See totals including delivery charges
7. **Checkout**: Enter customer information
8. **Place Order**: Receive confirmation email

### Mobile Experience
- Fully responsive design
- Touch-friendly interface
- Optimized for small screens
- Fast loading on mobile networks

## Troubleshooting

### Plugin Not Working
1. Check WordPress version (5.0+ required)
2. Check PHP version (7.4+ required)
3. Deactivate and reactivate plugin
4. Check error logs in WordPress admin

### Database Issues
If tables aren't created:
1. Go to Plugins → Deactivate "Pizza Delivery Shop"
2. Go to Plugins → Activate "Pizza Delivery Shop"
3. Check if sample data appears in admin

### Shortcode Not Displaying
1. Verify shortcode spelling: `[pizza_shop_catalog]`
2. Check if plugin is activated
3. Try on a different page/post
4. Check for theme conflicts

### Orders Not Saving
1. Check browser console for JavaScript errors
2. Verify AJAX is working (check Network tab in browser dev tools)
3. Check WordPress nonce security
4. Test with default WordPress theme

### Styling Issues
1. Clear any caching plugins
2. Check for CSS conflicts with theme
3. Verify CSS files are loading (check page source)
4. Try with default WordPress theme

## Security Notes

The plugin implements WordPress security best practices:
- Nonce verification for all forms
- Input sanitization and validation
- Prepared database statements
- Capability checks for admin functions
- CSRF protection

## Performance Optimization

- Database queries are optimized with proper indexing
- AJAX reduces page reloads
- CSS and JS files are minified
- Images can be lazy-loaded (theme dependent)
- Compatible with caching plugins

## Support

### Getting Help
1. Check this installation guide
2. Review the main README.md file
3. Check WordPress error logs
4. Test with default theme and no other plugins

### Common Solutions
- **White screen**: Check PHP error logs
- **Database errors**: Check MySQL permissions
- **AJAX not working**: Check for JavaScript conflicts
- **Styling broken**: Check for CSS conflicts

## Next Steps

After installation:
1. Replace sample data with your actual products
2. Set up your real delivery areas
3. Test the complete ordering process
4. Configure email settings
5. Customize styling to match your brand
6. Train staff on order management system

---

**Congratulations! Your pizza delivery shop is now ready to take orders online!** 🍕

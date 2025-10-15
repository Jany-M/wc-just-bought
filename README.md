# WC Just Bought

A WordPress plugin that displays a popup notification in the bottom-right corner showing recent WooCommerce purchases. The popup cycles through the latest 10 orders, showing customer initials with dots, country flags, product name and image (both clickable), and how long ago the purchase was made.

## Features

- 🛒 Displays real-time notifications of recent WooCommerce purchases
- 👤 Shows customer initials with dots (e.g., "J.M." for John Miller)
- 🌍 Displays shipping country with flag icons
- 🔗 Clickable product names and images that open in new tabs
- 📦 Shows product name and image
- ⏰ Displays time elapsed since purchase (e.g., "2 hours ago", "3 days ago")
- 🔄 Automatically cycles through the latest 10 orders
- 📱 Fully responsive design
- 🎨 Modern, sleek UI with smooth animations and hover effects
- ⚡ Lightweight and performance-optimized
- 🐛 Debug mode for easy troubleshooting
- 🛡️ Error handling for refunds and invalid orders
- 🎯 Natural language display: "J.M. from 🇺🇸 United States bought"

## Requirements

- WordPress 5.0 or higher
- WooCommerce 3.0 or higher
- PHP 7.2 or higher

## Installation

1. **Upload the plugin files** to the `/wp-content/plugins/wc-just-bought` directory, or install the plugin through the WordPress plugins screen directly.

2. **Activate the plugin** through the 'Plugins' screen in WordPress.

3. **Ensure WooCommerce is installed and activated** - the plugin requires WooCommerce to function.

4. The popup will automatically start displaying on your website's frontend!

## How It Works

1. The plugin fetches the latest 10 completed or processing orders from WooCommerce.
2. Automatically filters out refund orders to prevent errors.
3. For each order, it extracts:
   - Customer's first and last name initials (formatted with dots, e.g., "J.M.")
   - Shipping country name and country code
   - Country flag from flagcdn.com
   - First product from the order
   - Product URL (for clickable links)
   - Product image
   - Time since the order was placed
4. The popup appears 3 seconds after page load and cycles through orders every 3 seconds.
5. Each popup is displayed for 10 seconds before transitioning to the next one.
6. Users can click the product name or image to view the product page in a new tab.
7. Users can close the popup by clicking the × button.

## Display Format

The popup displays purchases in a natural, readable format:

```
[J.M.] from Italy bought
Premium Leather Bag
2 hours ago
```

Both the product image and product name are clickable links that open the product page in a new tab.

## Customization

### Debug Mode

Enable or disable console logging by editing the DEBUG constant in `assets/js/script.js`:

```javascript
const DEBUG = false; // Set to true to enable debug logging
```

When enabled, you'll see detailed information about:
- AJAX requests and responses
- Order data being loaded
- Any errors that occur
- Flag loading status

### Timing Settings

You can modify the timing by editing the JavaScript constants in `assets/js/script.js`:

```javascript
const DISPLAY_DURATION = 5000; // Show each popup for 5 seconds
const CYCLE_INTERVAL = 10000; // Wait 10 seconds between popups
const INITIAL_DELAY = 3000; // Wait 3 seconds after page load
```

### Styling

Customize the appearance by editing `assets/css/style.css`. The popup uses modern CSS with:
- Flexbox layout
- CSS animations (slide in/out)
- Hover effects on clickable elements
- Responsive design
- Customizable colors and dimensions
- Country flag styling

Key elements you can customize:
- `.wc-just-bought-initials` - The initials badge
- `.wc-just-bought-flag` - Country flag styling
- `.wc-just-bought-product-name` - Product name styling
- `.wc-just-bought-product-link` - Link hover effects

### Number of Orders

To change the number of orders displayed, edit the `limit` argument in `wc-just-bought.php` (default is 10).

## Privacy Considerations

The plugin is designed with privacy in mind:
- Only shows customer initials with dots (e.g., "J.M." - not full names)
- Uses shipping/billing country (public information)
- No sensitive customer data is exposed
- Product information is already public on your store

## Technical Details

### Error Handling

The plugin includes comprehensive error handling:
- Filters out refund orders automatically
- Validates WooCommerce availability
- Handles missing product data gracefully
- Logs errors to WordPress error log (when available)
- Flag images have fallback handling

### AJAX Security

- Uses WordPress nonces for AJAX security
- Verifies requests on both frontend and backend
- Available for both logged-in and non-logged-in users

### External Resources

- Country flags loaded from [flagcdn.com](https://flagcdn.com) CDN
- Flags are cached by the browser for performance
- Fallback handling if flags fail to load

## Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Changelog

### Version 1.0.0
- Initial release
- Display recent WooCommerce purchases
- Cycle through latest 10 orders
- Responsive design
- Time ago functionality
- Customer initials with dots formatting (e.g., "J.M.")
- Country flags integration via flagcdn.com
- Clickable product names and images (open in new tab)
- Natural language display: "J.M. from 🇺🇸 United States bought"
- Comprehensive error handling and refund filtering
- Debug mode for troubleshooting
- Hover effects on clickable elements
- AJAX security with WordPress nonces
- Graceful fallbacks for missing data

## Support

For support, feature requests, or bug reports, please create an issue on GitHub or contact the plugin author at info@shambix.com .

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed with ❤️ for WooCommerce store owners who want to showcase social proof and recent purchases.

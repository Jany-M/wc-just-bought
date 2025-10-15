<?php
/**
 * Plugin Name: WC Just Bought
 * Plugin URI: https://github.com/jany-m/wc-just-bought
 * Description: Displays a discrete popup in the bottom right corner of the site, showing latest 10 WooCommerce purchases with customer initials, country, product details, and time since purchase. The popup is not configurable (no options or customizations), contact info@shambix.com for a quote.
 * Version: 1.0.0
 * Author: Shambix
 * Author URI: https://www.shambix.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Domain Path: /languages
 * Text Domain: wc-just-bought
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * WC requires at least: 3.0
 * WC tested up to: 8.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WC_JUST_BOUGHT_VERSION', '1.0.0');
define('WC_JUST_BOUGHT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WC_JUST_BOUGHT_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Check if WooCommerce is active
 */
function wc_just_bought_check_woocommerce() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'wc_just_bought_woocommerce_notice');
        deactivate_plugins(plugin_basename(__FILE__));
        return false;
    }
    return true;
}

/**
 * Display admin notice if WooCommerce is not active
 */
function wc_just_bought_woocommerce_notice() {
    ?>
    <div class="error">
        <p><?php esc_html_e('WC Just Bought requires WooCommerce to be installed and activated.', 'wc-just-bought'); ?></p>
    </div>
    <?php
}

/**
 * Initialize the plugin
 */
function wc_just_bought_init() {
    if (!wc_just_bought_check_woocommerce()) {
        return;
    }

    // Enqueue scripts and styles on frontend
    add_action('wp_enqueue_scripts', 'wc_just_bought_enqueue_assets');
    
    // Add popup HTML to footer
    add_action('wp_footer', 'wc_just_bought_add_popup_html');
}
add_action('plugins_loaded', 'wc_just_bought_init');

/**
 * Register AJAX handlers
 * Register these early to ensure they're available
 */
function wc_just_bought_register_ajax() {
    // Add AJAX endpoint for fetching orders
    add_action('wp_ajax_wc_just_bought_get_orders', 'wc_just_bought_get_orders');
    add_action('wp_ajax_nopriv_wc_just_bought_get_orders', 'wc_just_bought_get_orders');
}
add_action('init', 'wc_just_bought_register_ajax');

/**
 * Enqueue frontend assets
 */
function wc_just_bought_enqueue_assets() {
    // Only load on frontend
    if (is_admin()) {
        return;
    }

    // Check if WooCommerce is available
    if (!class_exists('WooCommerce')) {
        return;
    }

    // Enqueue CSS
    wp_enqueue_style(
        'wc-just-bought-style',
        WC_JUST_BOUGHT_PLUGIN_URL . 'assets/css/style.css',
        array(),
        WC_JUST_BOUGHT_VERSION
    );

    // Enqueue JavaScript
    wp_enqueue_script(
        'wc-just-bought-script',
        WC_JUST_BOUGHT_PLUGIN_URL . 'assets/js/script.js',
        array('jquery'),
        WC_JUST_BOUGHT_VERSION,
        true
    );

    // Localize script with AJAX URL and translatable strings
    wp_localize_script('wc-just-bought-script', 'wcJustBought', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('wc_just_bought_nonce'),
        'i18n' => array(
            'from' => esc_html__('from', 'wc-just-bought'),
            'bought' => esc_html__('bought', 'wc-just-bought'),
            'previous' => esc_attr__('Previous', 'wc-just-bought'),
            'next' => esc_attr__('Next', 'wc-just-bought'),
            'close' => esc_attr__('Close', 'wc-just-bought'),
        )
    ));
}

/**
 * Add popup HTML structure to footer
 */
function wc_just_bought_add_popup_html() {
    // Only load on frontend
    if (is_admin()) {
        return;
    }

    // Check if WooCommerce is available
    if (!class_exists('WooCommerce')) {
        return;
    }
    ?>
    <div id="wc-just-bought-popup" class="wc-just-bought-popup" style="display: none;">
        <div class="wc-just-bought-content">
            <button class="wc-just-bought-close" title="<?php esc_attr_e('Close', 'wc-just-bought'); ?>">&times;</button>
            <button class="wc-just-bought-nav wc-just-bought-prev" title="<?php esc_attr_e('Previous', 'wc-just-bought'); ?>">&#8249;</button>
            <button class="wc-just-bought-nav wc-just-bought-next" title="<?php esc_attr_e('Next', 'wc-just-bought'); ?>">&#8250;</button>
            <a href="#" class="wc-just-bought-image-link" target="_blank" rel="noopener noreferrer">
                <div class="wc-just-bought-image">
                    <img src="" alt="<?php esc_attr_e('Product', 'wc-just-bought'); ?>">
                </div>
            </a>
            <div class="wc-just-bought-details">
                <div class="wc-just-bought-customer">
                    <span class="wc-just-bought-initials"></span>
                    <span class="wc-just-bought-text">
                        <?php esc_html_e('from', 'wc-just-bought'); ?>
                        <span class="wc-just-bought-country">
                            <img class="wc-just-bought-flag" src="" alt="" />
                            <span class="wc-just-bought-country-name"></span>
                        </span>
                        <?php esc_html_e('bought', 'wc-just-bought'); ?>
                    </span>
                </div>
                <a href="#" class="wc-just-bought-product-link" target="_blank" rel="noopener noreferrer">
                    <div class="wc-just-bought-product-name"></div>
                </a>
                <div class="wc-just-bought-time"></div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * AJAX handler to get recent orders
 */
function wc_just_bought_get_orders() {
    try {
        // Verify nonce
        $nonce = isset($_POST['nonce']) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
        if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'wc_just_bought_nonce' ) ) {
            wp_send_json_error(array('message' => 'Invalid nonce'));
            return;
        }

        // Check if WooCommerce is available
        if (!function_exists('wc_get_orders')) {
            wp_send_json_error(array('message' => 'WooCommerce is not available'));
            return;
        }

        // Check if WC() is initialized
        if (!function_exists('WC') || !WC()) {
            wp_send_json_error(array('message' => 'WooCommerce not initialized'));
            return;
        }

        // Get the last 10 completed orders
        $args = array(
            'limit' => 10,
            'status' => array('wc-completed', 'wc-processing'),
            'type' => 'shop_order', // Explicitly get only orders, not refunds
            'orderby' => 'date',
            'order' => 'DESC',
        );

        $orders = wc_get_orders($args);
        $orders_data = array();

        foreach ($orders as $order) {
            try {
                // Skip if this is a refund object instead of an order
                if (!is_a($order, 'WC_Order')) {
                    continue;
                }

                // Skip refunds
                if ($order->get_type() === 'shop_order_refund') {
                    continue;
                }

                // Get first order item (product)
                $items = $order->get_items();
                if (empty($items)) {
                    continue;
                }

                $first_item = reset($items);
                $product = $first_item->get_product();

                if (!$product) {
                    continue;
                }

                // Get customer information
                $first_name = $order->get_shipping_first_name() ?: $order->get_billing_first_name();
                $last_name = $order->get_shipping_last_name() ?: $order->get_billing_last_name();
                $country_code = $order->get_shipping_country() ?: $order->get_billing_country();
                
                // Generate initials
                $initials = '';
                if ($first_name) {
                    $initials .= strtoupper(substr($first_name, 0, 1));
                }
                if ($last_name) {
                    $initials .= strtoupper(substr($last_name, 0, 1));
                }
                if (empty($initials)) {
                    $initials = '??';
                }

                // Get country name
                $countries = WC()->countries->get_countries();
                $country_name = isset($countries[$country_code]) ? $countries[$country_code] : $country_code;

                // Get product image
                $image_id = $product->get_image_id();
                $image_url = $image_id ? wp_get_attachment_image_url($image_id, 'thumbnail') : wc_placeholder_img_src('thumbnail');

                // Calculate time ago
                $order_date = $order->get_date_created();
                $time_ago = wc_just_bought_time_ago($order_date->getTimestamp());

                // Get product URL
                $product_url = get_permalink($product->get_id());

                $orders_data[] = array(
                    'initials' => $initials,
                    'country' => $country_name,
                    'country_code' => strtolower($country_code), // Add country code for flag
                    'product_name' => $product->get_name(),
                    'product_url' => $product_url,
                    'product_image' => $image_url,
                    'time_ago' => $time_ago,
                );
            } catch (Exception $e) {
                // Skip this order if there's an error processing it
                if ( function_exists( 'wc_get_logger' ) ) {
                    $logger = wc_get_logger();
                    $logger->error(
                        sprintf(
                            'WC Just Bought: Error processing order ID %d: %s',
                            is_object( $order ) && method_exists( $order, 'get_id' ) ? $order->get_id() : 0,
                            $e->getMessage()
                        ),
                        array( 'source' => 'wc-just-bought' )
                    );
                }
                continue;
            }
        }

        // Return success even if no orders found
        if (empty($orders_data)) {
            wp_send_json_success(array());
        } else {
            wp_send_json_success($orders_data);
        }
    } catch (Exception $e) {
        wp_send_json_error(array(
            'message' => 'Error fetching orders: ' . $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ));
    }
}

/**
 * Calculate time ago from timestamp
 */
function wc_just_bought_time_ago($timestamp) {
    $time_ago = time() - $timestamp;
    
    if ($time_ago < 60) {
        return __('Just now', 'wc-just-bought');
    } elseif ($time_ago < 3600) {
        $minutes = floor($time_ago / 60);
        /* translators: %s: number of minutes */
        return sprintf(_n('%s minute ago', '%s minutes ago', $minutes, 'wc-just-bought'), $minutes);
    } elseif ($time_ago < 86400) {
        $hours = floor($time_ago / 3600);
        /* translators: %s: number of hours */
        return sprintf(_n('%s hour ago', '%s hours ago', $hours, 'wc-just-bought'), $hours);
    } elseif ($time_ago < 604800) {
        $days = floor($time_ago / 86400);
        /* translators: %s: number of days */
        return sprintf(_n('%s day ago', '%s days ago', $days, 'wc-just-bought'), $days);
    } elseif ($time_ago < 2592000) {
        $weeks = floor($time_ago / 604800);
        /* translators: %s: number of weeks */
        return sprintf(_n('%s week ago', '%s weeks ago', $weeks, 'wc-just-bought'), $weeks);
    } else {
        $months = floor($time_ago / 2592000);
        /* translators: %s: number of months */
        return sprintf(_n('%s month ago', '%s months ago', $months, 'wc-just-bought'), $months);
    }
}

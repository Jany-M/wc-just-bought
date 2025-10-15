/**
 * WC Just Bought - Frontend JavaScript
 */
(function($) {
    'use strict';

    // Debug mode - set to false to disable console logging
    const DEBUG = false;

    let orders = [];
    let currentIndex = 0;
    let popupTimer = null;
    let cycleTimer = null;
    let isPopupVisible = false;
    let isManuallyHidden = false;

    // Configuration
    const DISPLAY_DURATION = 10000; // Show each popup for 10 seconds
    const CYCLE_INTERVAL = 3000; // Wait 3 seconds between popups (after popup closes)
    const INITIAL_DELAY = 3000; // Wait 3 seconds after page load

    /**
     * Initialize the plugin
     */
    function init() {
        fetchOrders();
        
        // Close button handler
        $('.wc-just-bought-close').on('click', function() {
            hidePopup(true);
            isManuallyHidden = true;
        });

        // Previous button handler
        $('.wc-just-bought-prev').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            navigatePrevious();
        });

        // Next button handler
        $('.wc-just-bought-next').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            navigateNext();
        });

        // Optional: Click on popup to close
        $('.wc-just-bought-popup').on('click', function(e) {
            if ($(e.target).hasClass('wc-just-bought-popup')) {
                hidePopup(true);
            }
        });
    }

    /**
     * Fetch orders from server
     */
    function fetchOrders() {
        // Check if wcJustBought object exists
        if (typeof wcJustBought === 'undefined') {
            if (DEBUG) console.error('WC Just Bought: wcJustBought object is not defined');
            return;
        }

        if (DEBUG) {
            console.log('WC Just Bought: Fetching orders...', {
                ajaxUrl: wcJustBought.ajaxUrl,
                hasNonce: !!wcJustBought.nonce
            });
        }

        $.ajax({
            url: wcJustBought.ajaxUrl,
            type: 'POST',
            data: {
                action: 'wc_just_bought_get_orders',
                nonce: wcJustBought.nonce
            },
            success: function(response) {
                if (DEBUG) console.log('WC Just Bought: Response received', response);
                
                if (response.success && response.data && response.data.length > 0) {
                    orders = response.data;
                    currentIndex = 0;
                    if (DEBUG) console.log('WC Just Bought: Orders loaded', orders.length);
                    
                    // Start cycling through orders after initial delay
                    setTimeout(function() {
                        if (!isManuallyHidden) {
                            showNextOrder();
                        }
                    }, INITIAL_DELAY);
                } else if (response.success && (!response.data || response.data.length === 0)) {
                    if (DEBUG) console.log('WC Just Bought: No orders found');
                } else {
                    if (DEBUG) console.error('WC Just Bought: Invalid response', response);
                }
            },
            error: function(xhr, status, error) {
                if (DEBUG) {
                    console.error('WC Just Bought: Failed to fetch orders', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText,
                        statusCode: xhr.status
                    });
                    
                    // Try to parse error response
                    try {
                        const errorData = JSON.parse(xhr.responseText);
                        console.error('WC Just Bought: Error details', errorData);
                    } catch(e) {
                        console.error('WC Just Bought: Raw error response', xhr.responseText);
                    }
                }
            }
        });
    }

    /**
     * Show the next order in the cycle
     */
    function showNextOrder() {
        if (orders.length === 0 || isManuallyHidden) {
            return;
        }

        // Get current order
        const order = orders[currentIndex];
        
        // Update popup content
        updatePopupContent(order);
        
        // Show popup
        showPopup();

        // Move to next order
        currentIndex = (currentIndex + 1) % orders.length;

        // Schedule the next popup
        scheduleNextPopup();
    }

    /**
     * Update popup content with order data
     */
    function updatePopupContent(order) {
        const $popup = $('#wc-just-bought-popup');
        
        // Format initials with dots (e.g., "JM" becomes "J.M.")
        const formattedInitials = order.initials.split('').join('.') + '.';
        $popup.find('.wc-just-bought-initials').text(formattedInitials);
        
        // Prepare flag URL
        let flagUrl = '';
        if (order.country_code) {
            flagUrl = `https://flagcdn.com/w40/${order.country_code}.png`;
        }

        // Update translatable static text (from/bought) with flag
        $popup.find('.wc-just-bought-text').html(
            `${wcJustBought.i18n.from} <span class="wc-just-bought-country"><img class="wc-just-bought-flag" src="${flagUrl}" alt="" /><span class="wc-just-bought-country-name">${order.country}</span></span> ${wcJustBought.i18n.bought}`
        );

        // Handle flag error (hide if it fails to load)
        if (flagUrl) {
            $popup.find('.wc-just-bought-flag').on('error', function() {
                $(this).hide();
            }).show();
        }

        $popup.find('.wc-just-bought-product-name').text(order.product_name);
        $popup.find('.wc-just-bought-product-link').attr('href', order.product_url);
        $popup.find('.wc-just-bought-image-link').attr('href', order.product_url);
        $popup.find('.wc-just-bought-time').text(order.time_ago);
        $popup.find('.wc-just-bought-image img').attr({
            'src': order.product_image,
            'alt': order.product_name
        });
    }

    /**
     * Show the popup
     */
    function showPopup() {
        const $popup = $('#wc-just-bought-popup');
        
        $popup.removeClass('wc-just-bought-hidden');
        $popup.fadeIn(300);
        isPopupVisible = true;
    }

    /**
     * Hide the popup
     */
    function hidePopup(immediate = false) {
        const $popup = $('#wc-just-bought-popup');
        
        if (immediate) {
            $popup.addClass('wc-just-bought-hidden');
            setTimeout(function() {
                $popup.hide();
            }, 500);
        } else {
            $popup.addClass('wc-just-bought-hidden');
            setTimeout(function() {
                $popup.fadeOut(300, function() {
                    $popup.removeClass('wc-just-bought-hidden');
                });
            }, 500);
        }
        
        isPopupVisible = false;
        
        // Clear timers
        if (popupTimer) {
            clearTimeout(popupTimer);
            popupTimer = null;
        }
        if (cycleTimer) {
            clearTimeout(cycleTimer);
            cycleTimer = null;
        }
    }

    /**
     * Navigate to the previous order
     */
    function navigatePrevious() {
        if (orders.length === 0) {
            return;
        }

        // Clear any scheduled timers
        if (popupTimer) {
            clearTimeout(popupTimer);
            popupTimer = null;
        }
        if (cycleTimer) {
            clearTimeout(cycleTimer);
            cycleTimer = null;
        }

        // Move to previous order (with wrap-around)
        currentIndex = (currentIndex - 1 + orders.length) % orders.length;
        
        // Update and show the popup
        updatePopupContent(orders[currentIndex]);
        
        // Restart the auto-cycle timer
        scheduleNextPopup();
    }

    /**
     * Navigate to the next order
     */
    function navigateNext() {
        if (orders.length === 0) {
            return;
        }

        // Clear any scheduled timers
        if (popupTimer) {
            clearTimeout(popupTimer);
            popupTimer = null;
        }
        if (cycleTimer) {
            clearTimeout(cycleTimer);
            cycleTimer = null;
        }

        // Move to next order
        currentIndex = (currentIndex + 1) % orders.length;
        
        // Update and show the popup
        updatePopupContent(orders[currentIndex]);
        
        // Restart the auto-cycle timer
        scheduleNextPopup();
    }

    /**
     * Schedule the next popup transition
     */
    function scheduleNextPopup() {
        popupTimer = setTimeout(function() {
            hidePopup();
            
            cycleTimer = setTimeout(function() {
                if (!isManuallyHidden) {
                    showNextOrder();
                }
            }, CYCLE_INTERVAL);
        }, DISPLAY_DURATION);
    }

    /**
     * Reset manually hidden state (optional - could be triggered by user interaction)
     */
    function resetManualHide() {
        isManuallyHidden = false;
        if (orders.length > 0) {
            showNextOrder();
        }
    }

    // Initialize when document is ready
    $(document).ready(function() {
        init();
    });

})(jQuery);

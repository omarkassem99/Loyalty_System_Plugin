<?php
// Add points after order completion
function wp_loyalty_system_add_order_points($order_id) {
    // Check if loyalty system is enabled
    if (get_option('wp_loyalty_system_enabled', 'yes') != 'yes') {
        return;
    }
    
    $order = wc_get_order($order_id);
    
    // Only proceed if we have a valid order and user
    if (!$order || $order->get_customer_id() == 0) {
        return;
    }
    
    $user_id = $order->get_customer_id();
    $order_total = $order->get_total();
    
    // Calculate points
    $points = wp_loyalty_system_calculate_order_points($order_total);
    
    // Add points to user
    if ($points > 0) {
        $description = sprintf('Points earned from order #%s', $order->get_order_number());
        wp_loyalty_system_update_points($user_id, $points, $description, $order_id);
        
        // Add note to the order
        $order->add_order_note(sprintf(
            'Added %d loyalty points to customer account.',
            $points
        ));
    }
}
add_action('woocommerce_order_status_completed', 'wp_loyalty_system_add_order_points');

// Display points that will be earned on checkout
function wp_loyalty_system_display_checkout_points() {
    // Check if loyalty system is enabled
    if (get_option('wp_loyalty_system_enabled', 'yes') != 'yes') {
        return;
    }
    
    $cart_total = WC()->cart->total;
    $points = wp_loyalty_system_calculate_order_points($cart_total);
    
    if ($points > 0) {
        echo '<tr class="loyalty-points-checkout">
            <th>You\'ll Earn</th>
            <td>' . esc_html($points) . ' Loyalty Points</td>
        </tr>';
    }
}
add_action('woocommerce_review_order_after_order_total', 'wp_loyalty_system_display_checkout_points');
add_action('woocommerce_cart_totals_after_order_total', 'wp_loyalty_system_display_checkout_points');
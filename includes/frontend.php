<?php
// Add points display to navigation menu
function wp_loyalty_system_nav_menu_items($items, $args) {
    // Check if loyalty system is enabled
    if (get_option('wp_loyalty_system_enabled', 'yes') != 'yes') {
        return $items;
    }
    
    // Only add to primary menu and for logged in users
    if (!is_user_logged_in() || $args->theme_location != 'primary') {
        return $items;
    }
    
    $user_id = get_current_user_id();
    $points = wp_loyalty_system_get_points($user_id);
    
    // Create menu item HTML
    $points_item = '<li class="menu-item loyalty-points-display">';
    $points_item .= '<a href="' . esc_url(wc_get_account_endpoint_url('loyalty-points')) . '">';
    $points_item .= 'My Points: ' . $points;
    $points_item .= '</a></li>';
    
    // Append to the end of the menu
    $items .= $points_item;
    
    return $items;
}
add_filter('wp_nav_menu_items', 'wp_loyalty_system_nav_menu_items', 10, 2);

// Alternative approach using wp_footer for themes without easily hookable nav
function wp_loyalty_system_footer_points_display() {
    // Check if loyalty system is enabled
    if (get_option('wp_loyalty_system_enabled', 'yes') != 'yes') {
        return;
    }
    
    // Only for logged in users
    if (!is_user_logged_in()) {
        return;
    }
    
    $user_id = get_current_user_id();
    $points = wp_loyalty_system_get_points($user_id);
    
    ?>
    <div class="loyalty-points-floating">
        <a href="<?php echo esc_url(wc_get_account_endpoint_url('loyalty-points')); ?>">
            My Points: <?php echo $points; ?>
        </a>
    </div>
    <?php
}
add_action('wp_footer', 'wp_loyalty_system_footer_points_display');

// Add loyalty points endpoint to My Account
function wp_loyalty_system_add_endpoint() {
    add_rewrite_endpoint('loyalty-points', EP_ROOT | EP_PAGES);
}
add_action('init', 'wp_loyalty_system_add_endpoint');

// Add menu item to My Account
function wp_loyalty_system_my_account_menu_items($items) {
    // Insert loyalty points after dashboard
    $new_items = array();
    
    foreach ($items as $key => $value) {
        $new_items[$key] = $value;
        
        if ($key === 'dashboard') {
            $new_items['loyalty-points'] = 'Loyalty Points';
        }
    }
    
    return $new_items;
}
add_filter('woocommerce_account_menu_items', 'wp_loyalty_system_my_account_menu_items');

// Display loyalty points content
function wp_loyalty_system_endpoint_content() {
    global $wpdb;
    
    $user_id = get_current_user_id();
    $points = wp_loyalty_system_get_points($user_id);
    
    // Get transaction history
    $transactions_table = $wpdb->prefix . 'loyalty_transactions';
    $transactions = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $transactions_table WHERE user_id = %d ORDER BY created_at DESC LIMIT 20",
        $user_id
    ));
    
    ?>
    <h2>My Loyalty Points</h2>
    <p>Your current balance: <strong><?php echo $points; ?> points</strong></p>
    
    <h3>Recent Activity</h3>
    <table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Description</th>
                <th>Points</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($transactions)) : ?>
                <tr>
                    <td colspan="3">No point transactions found.</td>
                </tr>
            <?php else : ?>
                <?php foreach ($transactions as $transaction) : ?>
                    <tr>
                        <td><?php echo date_i18n(get_option('date_format'), strtotime($transaction->created_at)); ?></td>
                        <td>
                            <?php 
                            echo esc_html($transaction->description);
                            if ($transaction->order_id) {
                                echo ' <a href="' . esc_url(wc_get_account_endpoint_url('view-order') . $transaction->order_id) . '">(View Order)</a>';
                            }
                            ?>
                        </td>
                        <td><?php echo ($transaction->points > 0 ? '+' : '') . $transaction->points; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    <?php
}
add_action('woocommerce_account_loyalty-points_endpoint', 'wp_loyalty_system_endpoint_content');

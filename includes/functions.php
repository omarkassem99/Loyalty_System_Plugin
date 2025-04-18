<?php
// Get user points
function wp_loyalty_system_get_points($user_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'loyalty_points';
    $points = $wpdb->get_var($wpdb->prepare(
        "SELECT points FROM $table_name WHERE user_id = %d",
        $user_id
    ));
    
    return $points ? $points : 0;
}

// Update user points
function wp_loyalty_system_update_points($user_id, $points_to_add, $description = '', $order_id = null) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'loyalty_points';
    $transactions_table = $wpdb->prefix . 'loyalty_transactions';
    
    // Get current points
    $current_points = wp_loyalty_system_get_points($user_id);
    $new_points = $current_points + $points_to_add;
    
    // Insert or update user points
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE user_id = %d",
        $user_id
    ));
    
    if ($exists) {
        $wpdb->update(
            $table_name,
            array(
                'points' => $new_points,
                'updated_at' => current_time('mysql')
            ),
            array('user_id' => $user_id)
        );
    } else {
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'points' => $new_points,
                'updated_at' => current_time('mysql')
            )
        );
    }
    
    // Record transaction
    $wpdb->insert(
        $transactions_table,
        array(
            'user_id' => $user_id,
            'order_id' => $order_id,
            'points' => $points_to_add,
            'transaction_type' => $points_to_add >= 0 ? 'credit' : 'debit',
            'description' => $description,
            'created_at' => current_time('mysql')
        )
    );
    
    return $new_points;
}

// Calculate points for an order
function wp_loyalty_system_calculate_order_points($order_total) {
    $points_per_dollar = get_option('wp_loyalty_system_points_per_dollar', 1);
    return round($order_total * $points_per_dollar);
}
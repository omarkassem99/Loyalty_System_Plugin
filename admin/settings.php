<?php
// Add admin menu
function wp_loyalty_system_admin_menu() {
    add_menu_page(
        'Loyalty System',
        'Loyalty System',
        'manage_options',
        'wp-loyalty-system',
        'wp_loyalty_system_settings_page',
        'dashicons-awards',
        30
    );
    
    add_submenu_page(
        'wp-loyalty-system',
        'Settings',
        'Settings',
        'manage_options',
        'wp-loyalty-system',
        'wp_loyalty_system_settings_page'
    );
    
    add_submenu_page(
        'wp-loyalty-system',
        'Manage Points',
        'Manage Points',
        'manage_options',
        'wp-loyalty-system-manage',
        'wp_loyalty_system_manage_page'
    );
}
add_action('admin_menu', 'wp_loyalty_system_admin_menu');

// Settings page
function wp_loyalty_system_settings_page() {
    // Save settings if form was submitted
    if (isset($_POST['wp_loyalty_system_settings_nonce']) && 
        wp_verify_nonce($_POST['wp_loyalty_system_settings_nonce'], 'wp_loyalty_system_settings')) {
        
        update_option('wp_loyalty_system_enabled', isset($_POST['wp_loyalty_system_enabled']) ? 'yes' : 'no');
        update_option('wp_loyalty_system_points_per_dollar', absint($_POST['wp_loyalty_system_points_per_dollar']));
        
        echo '<div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>';
    }
    
    $enabled = get_option('wp_loyalty_system_enabled', 'yes');
    $points_per_dollar = get_option('wp_loyalty_system_points_per_dollar', 1);
    ?>
    <div class="wrap">
        <h1>Loyalty System Settings</h1>
        
        <form method="post" action="">
            <?php wp_nonce_field('wp_loyalty_system_settings', 'wp_loyalty_system_settings_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">Enable Loyalty System</th>
                    <td>
                        <input type="checkbox" name="wp_loyalty_system_enabled" value="1" <?php checked($enabled, 'yes'); ?>>
                        <p class="description">Enable or disable the loyalty points system for future orders</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Points per dollar spent</th>
                    <td>
                        <input type="number" name="wp_loyalty_system_points_per_dollar" value="<?php echo esc_attr($points_per_dollar); ?>" min="1" step="1">
                        <p class="description">How many points should be awarded per dollar spent?</p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
            </p>
        </form>
    </div>
    <?php
}

// Manage points page
function wp_loyalty_system_manage_page() {
    global $wpdb;
    
    // Handle form submission for adding/subtracting points
    if (isset($_POST['wp_loyalty_system_manage_nonce']) && 
        wp_verify_nonce($_POST['wp_loyalty_system_manage_nonce'], 'wp_loyalty_system_manage')) {
        
        $user_id = absint($_POST['user_id']);
        $points = intval($_POST['points']);
        $description = sanitize_text_field($_POST['description']);
        
        if ($user_id > 0 && $points != 0) {
            wp_loyalty_system_update_points($user_id, $points, $description);
            echo '<div class="notice notice-success is-dismissible"><p>Points updated successfully.</p></div>';
        }
    }
    
    // Get all users with their points
    $users = get_users(array('fields' => array('ID', 'user_login', 'user_email')));
    ?>
    <div class="wrap">
        <h1>Manage User Points</h1>
        
        <form method="post" action="">
            <?php wp_nonce_field('wp_loyalty_system_manage', 'wp_loyalty_system_manage_nonce'); ?>
            
            <table class="form-table">
                <tr>
                    <th scope="row">Select User</th>
                    <td>
                        <select name="user_id" required>
                            <option value="">-- Select User --</option>
                            <?php foreach ($users as $user) : ?>
                                <?php 
                                $points = wp_loyalty_system_get_points($user->ID);
                                ?>
                                <option value="<?php echo $user->ID; ?>">
                                    <?php echo esc_html($user->user_login); ?> (<?php echo esc_html($user->user_email); ?>) - 
                                    Current Points: <?php echo $points; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Points to Add/Subtract</th>
                    <td>
                        <input type="number" name="points" required>
                        <p class="description">Use positive values to add points, negative values to subtract points</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Description</th>
                    <td>
                        <input type="text" name="description" class="regular-text">
                        <p class="description">Reason for adjusting points (optional)</p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="Update Points">
            </p>
        </form>
    </div>
    <?php
}
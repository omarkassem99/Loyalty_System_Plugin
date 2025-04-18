<?php
/**
 * Plugin Name: WP Loyalty System
 * Description: A simple loyalty points system for WordPress
 * Version: 1.0.0
 * Author: Omar
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WP_LOYALTY_SYSTEM_PATH', plugin_dir_path(__FILE__));
define('WP_LOYALTY_SYSTEM_URL', plugin_dir_url(__FILE__));

// Include files
require_once WP_LOYALTY_SYSTEM_PATH . 'includes/functions.php';
require_once WP_LOYALTY_SYSTEM_PATH . 'includes/woocommerce.php';
require_once WP_LOYALTY_SYSTEM_PATH . 'includes/frontend.php';
require_once WP_LOYALTY_SYSTEM_PATH . 'admin/settings.php';

// Create database tables on plugin activation
register_activation_hook(__FILE__, 'wp_loyalty_system_activate');

function wp_loyalty_system_activate() {
    global $wpdb;
    
    $charset_collate = $wpdb->get_charset_collate();
    
    // Table for storing user points
    $table_name = $wpdb->prefix . 'loyalty_points';
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        points int(11) NOT NULL DEFAULT 0,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        UNIQUE KEY user_id (user_id)
    ) $charset_collate;";
    
    // Table for storing points transactions
    $transactions_table = $wpdb->prefix . 'loyalty_transactions';
    
    $sql .= "CREATE TABLE $transactions_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        order_id bigint(20),
        points int(11) NOT NULL,
        transaction_type varchar(20) NOT NULL,
        description text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Add default settings
    add_option('wp_loyalty_system_enabled', 'yes');
    add_option('wp_loyalty_system_points_per_dollar', 1);
    
    // Flush rewrite rules
    wp_loyalty_system_add_endpoint();
    flush_rewrite_rules();
}

// Add CSS for frontend
function wp_loyalty_system_enqueue_styles() {
    wp_enqueue_style(
        'wp-loyalty-system',
        WP_LOYALTY_SYSTEM_URL . 'assets/css/style.css',
        array(),
        '1.0.0'
    );
}
add_action('wp_enqueue_scripts', 'wp_loyalty_system_enqueue_styles');

// Create CSS folder and file
function wp_loyalty_system_create_css_file() {
    $css_dir = WP_LOYALTY_SYSTEM_PATH . 'assets/css';
    
    // Create directories if they don't exist
    if (!file_exists($css_dir)) {
        mkdir($css_dir, 0755, true);
    }
    
    // Create CSS file if it doesn't exist
    $css_file = $css_dir . '/style.css';
    if (!file_exists($css_file)) {
        file_put_contents($css_file, '/* WP Loyalty System Styles */
.loyalty-points-display a {
    font-weight: bold;
}

.loyalty-points-floating {
    transition: all 0.3s ease;
}

.loyalty-points-floating:hover {
    background: #e3e3e3;
}');
    }
}
register_activation_hook(__FILE__, 'wp_loyalty_system_create_css_file');
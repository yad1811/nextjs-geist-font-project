<?php
/*
Plugin Name: Pizza Delivery Shop
Description: Complete pizza delivery management system with unique product IDs, categories, and dynamic pricing
Version: 1.0
Author: Pizza Shop Developer
Text Domain: pizza-delivery-shop
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('PDS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PDS_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('PDS_VERSION', '1.0');

// Include required files
require_once PDS_PLUGIN_PATH . 'includes/database.php';
require_once PDS_PLUGIN_PATH . 'includes/admin-functions.php';
require_once PDS_PLUGIN_PATH . 'includes/frontend-functions.php';
require_once PDS_PLUGIN_PATH . 'includes/ajax-handlers.php';

// Include admin files
if (is_admin()) {
    require_once PDS_PLUGIN_PATH . 'admin/admin-menu.php';
    require_once PDS_PLUGIN_PATH . 'admin/product-management.php';
    require_once PDS_PLUGIN_PATH . 'admin/category-management.php';
    require_once PDS_PLUGIN_PATH . 'admin/delivery-areas.php';
}

// Plugin activation hook
register_activation_hook(__FILE__, 'pds_activate_plugin');

// Plugin deactivation hook
register_deactivation_hook(__FILE__, 'pds_deactivate_plugin');

// Enqueue scripts and styles
add_action('wp_enqueue_scripts', 'pds_enqueue_frontend_scripts');
add_action('admin_enqueue_scripts', 'pds_enqueue_admin_scripts');

/**
 * Plugin activation function
 */
function pds_activate_plugin() {
    // Create database tables
    pds_create_tables();
    
    // Insert sample data
    pds_insert_sample_data();
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Plugin deactivation function
 */
function pds_deactivate_plugin() {
    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Enqueue frontend scripts and styles
 */
function pds_enqueue_frontend_scripts() {
    wp_enqueue_style('pds-frontend-style', PDS_PLUGIN_URL . 'assets/css/frontend-style.css', array(), PDS_VERSION);
    wp_enqueue_script('pds-frontend-script', PDS_PLUGIN_URL . 'assets/js/frontend-script.js', array('jquery'), PDS_VERSION, true);
    
    // Localize script for AJAX
    wp_localize_script('pds-frontend-script', 'pds_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('pds_nonce')
    ));
}

/**
 * Enqueue admin scripts and styles
 */
function pds_enqueue_admin_scripts($hook) {
    // Only load on our plugin pages
    if (strpos($hook, 'pizza-delivery-shop') === false) {
        return;
    }
    
    wp_enqueue_style('pds-admin-style', PDS_PLUGIN_URL . 'assets/css/admin-style.css', array(), PDS_VERSION);
    wp_enqueue_script('pds-admin-script', PDS_PLUGIN_URL . 'assets/js/admin-script.js', array('jquery'), PDS_VERSION, true);
    
    // Localize script for AJAX
    wp_localize_script('pds-admin-script', 'pds_admin_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('pds_admin_nonce')
    ));
}

// Initialize the plugin
add_action('init', 'pds_init');

/**
 * Initialize plugin
 */
function pds_init() {
    // Load text domain for translations
    load_plugin_textdomain('pizza-delivery-shop', false, dirname(plugin_basename(__FILE__)) . '/languages');
}

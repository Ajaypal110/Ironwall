<?php
/**
 * Ironwall Uninstall
 *
 * This file clears all plugin data upon deletion.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Drop custom tables
$tables = [
    $wpdb->prefix . 'irw_logs',
    $wpdb->prefix . 'irw_blocked_ips',
    $wpdb->prefix . 'irw_scan_results',
    $wpdb->prefix . 'irw_live_traffic'
];

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS $table");
}

// Delete options
$options = [
    'irw_login_protection',
    'irw_xmlrpc_disable',
    'irw_security_headers',
    'irw_login_slug',
    'irw_last_scan',
    'irw_last_scan_total',
    'irw_last_scan_progress'
];

foreach ($options as $option) {
    delete_option($option);
}

// Clean transients
$wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '_transient_irw_%'");
$wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '_transient_timeout_irw_%'");

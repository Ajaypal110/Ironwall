<?php
if(!defined('ABSPATH')){ exit; }

add_action('admin_post_wsg_export_logs', 'wsg_export_logs_csv_handler');
function wsg_export_logs_csv_handler() {
    if (!current_user_can('manage_options')) { wp_die('Unauthorized'); }
    global $wpdb;
    $table = $wpdb->prefix . 'wsg_logs';
    $logs = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC LIMIT 5000", ARRAY_A);
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=wsg-security-logs-' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, array('ID', 'IP', 'Event', 'Username', 'Details', 'Timestamp'));
    
    foreach ($logs as $log) {
        fputcsv($output, array($log['id'], $log['ip'], $log['event'], $log['username'], $log['details'], $log['created']));
    }
    fclose($output);
    exit;
}

add_action('admin_post_wsg_export_traffic', 'wsg_export_traffic_csv_handler');
function wsg_export_traffic_csv_handler() {
    if (!current_user_can('manage_options')) { wp_die('Unauthorized'); }
    global $wpdb;
    $table = $wpdb->prefix . 'wsg_live_traffic';
    $traffic = $wpdb->get_results("SELECT * FROM $table ORDER BY created DESC LIMIT 5000", ARRAY_A);
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=wsg-live-traffic-' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, array('ID', 'IP', 'URL', 'User Agent', 'Method', 'Status', 'Bot', 'Timestamp'));
    
    foreach ($traffic as $t) {
        $bot_status = $t['is_bot'] ? 'Bot' : 'Human';
        fputcsv($output, array($t['id'], $t['ip'], $t['url'], $t['user_agent'], $t['method'], $t['status_code'], $bot_status, $t['created']));
    }
    fclose($output);
    exit;
}

<?php
namespace Ironwall\Core;

if (!defined('ABSPATH')) exit;

/**
 * Ironwall CSV Exporter
 * 
 * Exports security logs and live traffic data to CSV format.
 */
class Exporter {

    public function __construct() {
        add_action('admin_post_irw_export_logs', [$this, 'handle_export_logs']);
        add_action('admin_post_irw_export_traffic', [$this, 'handle_export_traffic']);
    }

    public function handle_export_logs() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized', 'ironwall'));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'irw_logs';
        $logs = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC LIMIT 5000", ARRAY_A);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=wsg-security-logs-' . gmdate('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, [
            __('ID', 'ironwall'),
            __('IP', 'ironwall'),
            __('Event', 'ironwall'),
            __('Username', 'ironwall'),
            __('Details', 'ironwall'),
            __('Timestamp', 'ironwall')
        ]);

        foreach ($logs as $log) {
            fputcsv($output, [
                $log['id'],
                $log['ip'],
                $log['event'],
                $log['username'],
                $log['details'],
                $log['created']
            ]);
        }
        fclose($output);
        exit;
    }

    public function handle_export_traffic() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Unauthorized', 'ironwall'));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'irw_live_traffic';
        $traffic = $wpdb->get_results("SELECT * FROM $table ORDER BY created DESC LIMIT 5000", ARRAY_A);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=wsg-live-traffic-' . gmdate('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');
        fputcsv($output, [
            __('ID', 'ironwall'),
            __('IP', 'ironwall'),
            __('URL', 'ironwall'),
            __('User Agent', 'ironwall'),
            __('Method', 'ironwall'),
            __('Bot', 'ironwall'),
            __('Timestamp', 'ironwall')
        ]);

        foreach ($traffic as $t) {
            $bot_status = $t['is_bot'] ? __('Bot', 'ironwall') : __('Human', 'ironwall');
            fputcsv($output, [$t['id'], $t['ip'], $t['requested_url'], $t['ua'], $t['method'], $bot_status, $t['created']]);
        }
        fclose($output);
        exit;
    }
}

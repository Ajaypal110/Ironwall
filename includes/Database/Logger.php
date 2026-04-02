<?php
namespace Ironwall\Database;

if (!defined('ABSPATH')) exit;

/**
 * Ironwall Database Logger
 * 
 * Handles all security event logging, IP blocking, and traffic tracking.
 */
class Logger {
    
    /**
     * Insert a security event into the audit log.
     */
    public static function log($event, $user = '', $details = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'irw_logs';
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '127.0.0.1';
        
        return $wpdb->insert($table, array(
            'ip'            => $ip,
            'event'         => $event,
            'username'      => $user,
            'details'       => is_array($details) ? wp_json_encode($details) : $details,
            'created'       => current_time('mysql')
        ));
    }

    /**
     * Block an IP address for a specific duration.
     */
    public static function block_ip($ip, $reason, $duration_seconds = 86400) {
        global $wpdb;
        $table = $wpdb->prefix . 'irw_blocked_ips';
        
        // Check if already blocked to avoid duplicates
        $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE ip = %s AND blocked_until > NOW()", $ip));
        
        if (!$exists) {
            $until = gmdate('Y-m-d H:i:s', current_time('timestamp') + $duration_seconds);
            $wpdb->insert($table, array(
                'ip'            => $ip,
                'reason'        => $reason,
                'blocked_until' => $until,
                'created'       => current_time('mysql')
            ));
            
            // Log the blocking event
            self::log("IP Blocked", "", "IP $ip blocked for: $reason");
        }
    }

    /**
     * Check if an IP address is currently blocked.
     */
    public static function is_blocked($ip) {
        global $wpdb;
        $table = $wpdb->prefix . 'irw_blocked_ips';
        $blocked = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE ip = %s AND blocked_until > NOW()", $ip));
        return (bool) $blocked;
    }

    /**
     * Get the number of failed login attempts from a specific IP in the last hour.
     */
    public static function get_failed_attempts($ip) {
        global $wpdb;
        $table = $wpdb->prefix . 'irw_logs';
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(id) FROM $table WHERE ip = %s AND event = 'Login Failed' AND created > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
            $ip
        ));
        return (int) $count;
    }

    /**
     * Log a live traffic request.
     */
    public static function log_traffic() {
        // Optimization: Skip logging for known heavy loads
        if ((defined('DOING_AJAX') && DOING_AJAX) || (defined('DOING_CRON') && DOING_CRON)) return;
        if (strpos($_SERVER['REQUEST_URI'] ?? '', 'wp-admin/load-') !== false) return;

        global $wpdb;
        $table = $wpdb->prefix . 'irw_live_traffic';
        
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '127.0.0.1';
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : 'Unknown';
        $url = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '/';
        $method = isset($_SERVER['REQUEST_METHOD']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_METHOD'])) : 'GET';
        
        $is_bot = 0;
        $bots = array('bot', 'spider', 'crawler', 'curl', 'wget', 'slurp', 'mediapartners');
        foreach ($bots as $bot) {
            if (stripos($user_agent, $bot) !== false) {
                $is_bot = 1;
                break;
            }
        }

        $wpdb->insert($table, array(
            'ip'            => $ip,
            'requested_url' => substr($url, 0, 255),
            'ua'            => $user_agent,
            'method'        => $method,
            'is_bot'        => $is_bot,
            'created'       => current_time('mysql')
        ));

        // Periodic maintenance: Prune logs older than 7 days
        if (mt_rand(1, 100) === 1) {
            $wpdb->query("DELETE FROM $table WHERE created < (NOW() - INTERVAL 7 DAY)");
        }
    }
}

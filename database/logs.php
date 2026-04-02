<?php
if(!defined('ABSPATH')){
    exit;
}

function wsg_create_tables(){
    global $wpdb;
    
    $charset=$wpdb->get_charset_collate();
    require_once(ABSPATH.'wp-admin/includes/upgrade.php');

    // Traffic and Security Events Log
    $table_logs=$wpdb->prefix.'wsg_logs';
    $sql_logs="CREATE TABLE $table_logs (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        ip varchar(100) NOT NULL,
        event varchar(200) NOT NULL,
        username varchar(100),
        details text,
        created datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY ip (ip)
    ) $charset;";
    dbDelta($sql_logs);

    // Blocked IPs Table (for Brute Force and WAF)
    $table_blocked=$wpdb->prefix.'wsg_blocked_ips';
    $sql_blocked="CREATE TABLE $table_blocked (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        ip varchar(100) NOT NULL,
        reason varchar(200) NOT NULL,
        blocked_until datetime NOT NULL,
        created datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY ip (ip)
    ) $charset;";
    dbDelta($sql_blocked);

    // Scan Results Table
    $table_scans=$wpdb->prefix.'wsg_scan_results';
    $sql_scans="CREATE TABLE $table_scans (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        file_path text NOT NULL,
        issue_type varchar(100) NOT NULL,
        severity varchar(50) NOT NULL,
        details text,
        status varchar(50) DEFAULT 'unresolved',
        created datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset;";
    dbDelta($sql_scans);

    // Live Traffic Table
    $table_traffic=$wpdb->prefix.'wsg_live_traffic';
    $sql_traffic="CREATE TABLE $table_traffic (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        ip varchar(100) NOT NULL,
        url varchar(255) NOT NULL,
        user_agent text,
        method varchar(10),
        status_code int(4) DEFAULT 200,
        is_bot tinyint(1) DEFAULT 0,
        created datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY ip (ip),
        KEY created (created)
    ) $charset;";
    dbDelta($sql_traffic);
}

// Hook to plugin activation (assuming wp-security-guard.php handles this)
register_activation_hook(WSG_PATH.'wp-security-guard.php', 'wsg_create_tables');

// Helper functions
function wsg_insert_log($event, $user='', $details=''){
    global $wpdb;
    $table=$wpdb->prefix.'wsg_logs';
    $ip=isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '127.0.0.1';
    
    $wpdb->insert($table, array(
        'ip'=>$ip,
        'event'=>$event,
        'username'=>$user,
        'details'=>is_array($details) ? wp_json_encode($details) : $details,
        'created'=>current_time('mysql')
    ));
}

function wsg_block_ip($ip, $reason, $duration_seconds = 86400) {
    global $wpdb;
    $table=$wpdb->prefix.'wsg_blocked_ips';
    
    // Check if already blocked
    $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE ip = %s AND blocked_until > NOW()", $ip));
    
    if (!$exists) {
        $until = gmdate('Y-m-d H:i:s', time() + $duration_seconds);
        $wpdb->insert($table, array(
            'ip'=>$ip,
            'reason'=>$reason,
            'blocked_until'=>$until
        ));
        wsg_insert_log("IP Blocked", "", "IP $ip blocked for: $reason");
    }
}

function wsg_is_ip_blocked($ip) {
    global $wpdb;
    $table=$wpdb->prefix.'wsg_blocked_ips';
    $blocked = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE ip = %s AND blocked_until > NOW()", $ip));
    return $blocked ? true : false;
}

// Live Traffic Logger
function wsg_log_live_traffic() {
    // Only log if not doing AJAX or cron to save DB bloat
    if (defined('DOING_AJAX') && DOING_AJAX) return;
    if (defined('DOING_CRON') && DOING_CRON) return;
    // Don't log WP-Admin assets
    if (strpos($_SERVER['REQUEST_URI'] ?? '', 'wp-admin/load-') !== false) return;

    global $wpdb;
    $table_traffic = $wpdb->prefix . 'wsg_live_traffic';
    
    $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '127.0.0.1';
    $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : 'Unknown';
    $url = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field($_SERVER['REQUEST_URI']) : '/';
    $method = isset($_SERVER['REQUEST_METHOD']) ? sanitize_text_field($_SERVER['REQUEST_METHOD']) : 'GET';
    
    $is_bot = 0;
    $bots = array('bot', 'spider', 'crawler', 'curl', 'wget', 'slurp', 'mediapartners');
    foreach ($bots as $bot) {
        if (stripos($user_agent, $bot) !== false) {
            $is_bot = 1;
            break;
        }
    }

    $wpdb->insert($table_traffic, array(
        'ip' => $ip,
        'url' => substr($url, 0, 255),
        'user_agent' => $user_agent,
        'method' => $method,
        'is_bot' => $is_bot,
        'created' => current_time('mysql')
    ));

    // Prune logs occasionally (1% chance)
    if (mt_rand(1, 100) === 1) {
        $wpdb->query("DELETE FROM $table_traffic WHERE created < (NOW() - INTERVAL 7 DAY)");
    }
}
// Hook very early
if(isset($_SERVER['REMOTE_ADDR'])) {
    add_action('plugins_loaded', 'wsg_log_live_traffic', 99);
}
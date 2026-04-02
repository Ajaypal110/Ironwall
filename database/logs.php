<?php
if(!defined('ABSPATH')){
    exit;
}

function irw_create_tables(){
    global $wpdb;
    
    $charset=$wpdb->get_charset_collate();
    require_once(ABSPATH.'wp-admin/includes/upgrade.php');

    // Traffic and Security Events Log
    $table_logs=$wpdb->prefix.'irw_logs';
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
    $table_blocked=$wpdb->prefix.'irw_blocked_ips';
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
    $table_scans=$wpdb->prefix.'irw_scan_results';
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
    $table_traffic=$wpdb->prefix.'irw_live_traffic';
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
register_activation_hook(IRW_PATH.'wp-security-guard.php', 'irw_create_tables');

// Helper functions (Wrappers for the new Logger class)
function irw_insert_log($event, $user='', $details=''){
    return \Ironwall\Database\Logger::log($event, $user, $details);
}

function irw_block_ip($ip, $reason, $duration_seconds = 86400) {
    return \Ironwall\Database\Logger::block_ip($ip, $reason, $duration_seconds);
}

function irw_is_ip_blocked($ip) {
    return \Ironwall\Database\Logger::is_blocked($ip);
}

function irw_log_live_traffic() {
    return \Ironwall\Database\Logger::log_traffic();
}

// Hook early
if(isset($_SERVER['REMOTE_ADDR'])) {
    add_action('plugins_loaded', 'irw_log_live_traffic', 99);
}

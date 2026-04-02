<?php
if(!defined('ABSPATH')){
    exit;
}

class WSG_Scanner {
    
    private $malware_signatures = array(
        'eval\(base64_decode' => 'Obfuscated exact eval(base64_decode())',
        'str_rot13\(base64_decode' => 'Obfuscated str_rot13 + base64',
        'eval\(\$_(POST|GET)' => 'Direct evaluation of request data',
        'WP_CD_CODE' => 'Known WP-VCD malware signature',
        'file_get_contents\([\'"]https?:\/\/[^\'"]+\.txt[\'"]\)' => 'Remote payload execution',
        'gzinflate\(base64_decode' => 'Gzinflate base64 encoded payload'
    );

    public function run_full_scan() {
        global $wpdb;
        $table_scans = $wpdb->prefix . 'wsg_scan_results';
        
        // Clear old results
        $wpdb->query("TRUNCATE TABLE $table_scans");
        
        $this->scan_basic_issues();
        $this->scan_core_integrity();
        $this->scan_directory(WP_PLUGIN_DIR);
        $this->scan_directory(get_theme_root());
        
        wsg_insert_log("Full Security Scan Completed");
        update_option('wsg_last_scan', current_time('mysql'));
    }

    private function scan_basic_issues() {
        global $wpdb;
        $table_scans = $wpdb->prefix . 'wsg_scan_results';
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $this->log_issue('wp-config.php', 'Debug mode enabled in production', 'medium', 'Disable WP_DEBUG to prevent info disclosure');
        }
        
        if (!defined('DISALLOW_FILE_EDIT') || !DISALLOW_FILE_EDIT) {
            $this->log_issue('wp-config.php', 'File editor enabled', 'high', 'Plugin/Theme editor allows arbitrary code execution if admin compromised');
        }
        
        $user = get_user_by('login', 'admin');
        if ($user) {
            $this->log_issue('Database', 'Default admin username found', 'high', 'The "admin" username is highly targeted by brute force attacks');
        }
    }

    private function scan_core_integrity() {
        global $wp_version;
        $locale = get_locale();
        
        $response = wp_remote_get("https://api.wordpress.org/core/checksums/1.0/?version={$wp_version}&locale={$locale}");
        
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return; // Could not fetch checksums, skip
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (empty($data['checksums']) || !is_array($data['checksums'])) {
            return;
        }

        $checksums = $data['checksums'];
        
        // As a performance optimization, we only check a critical subset for this implementation
        $critical_files = array('wp-login.php', 'wp-settings.php', 'wp-load.php', 'wp-cron.php', 'wp-admin/admin.php', 'wp-includes/plugin.php');
        
        foreach ($critical_files as $file) {
            if (isset($checksums[$file])) {
                $local_path = ABSPATH . $file;
                if (!file_exists($local_path)) {
                    $this->log_issue($file, 'Missing Core File', 'critical', 'A critical WordPress core file has been removed.');
                    continue;
                }
                
                $local_md5 = md5_file($local_path);
                if ($local_md5 !== $checksums[$file]) {
                    $this->log_issue($file, 'Core File Modified', 'critical', 'This WordPress core file does not match the official repository checksum. It may be compromised.');
                }
            }
        }
    }

    private function scan_directory($dir) {
        if (!is_dir($dir)) return;

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        
        foreach ($iterator as $file) {
            if ($file->isDir()) continue;
            
            // Prevent scanner from matching its own signatures
            if ($file->getPathname() === __FILE__) continue;
            
            $ext = pathinfo($file->getFilename(), PATHINFO_EXTENSION);
            if ($ext !== 'php') continue;
            
            $this->scan_file($file->getPathname());
        }
    }

    private function scan_file($filepath) {
        $content = @file_get_contents($filepath);
        if ($content === false) return;

        foreach ($this->malware_signatures as $pattern => $description) {
            if (preg_match('/' . current((array)$pattern) . '/i', $content)) { // simplified check for the demo
                $this->log_issue($filepath, 'Malware Signature Detected', 'critical', $description);
            }
        }
        
        // Additional basic check for massive obfuscation
        if (substr_count($content, 'chr(') > 50 || substr_count($content, 'base64_decode(') > 10) {
             $this->log_issue($filepath, 'Highly Obfuscated Code', 'high', 'File contains excessive obfuscation functions');
        }
    }

    private function log_issue($file, $type, $severity, $details) {
        global $wpdb;
        $table_scans = $wpdb->prefix . 'wsg_scan_results';
        
        $wpdb->insert($table_scans, array(
            'file_path' => $file,
            'issue_type' => $type,
            'severity' => $severity,
            'details' => $details
        ));
    }
}

// Ensure the old basic scan function remains for compatibility with existing widgets until updated
function wsg_basic_scan() {
    $issues=array();
    if(defined('WP_DEBUG') && WP_DEBUG){ $issues[]='Debug mode enabled'; }
    if(!defined('DISALLOW_FILE_EDIT')){ $issues[]='File editor enabled'; }
    if(get_user_by('login','admin')){ $issues[]='Default admin username found'; }
    global $wpdb;
    $table = $wpdb->prefix . 'wsg_scan_results';
    $results = $wpdb->get_results("SELECT issue_type, file_path FROM $table WHERE status='unresolved'");
    foreach($results as $res) {
        $issues[] = esc_html($res->issue_type . ' in ' . basename($res->file_path));
    }
    return $issues;
}

// CRON JOB SETUP
function wsg_activate_cron() {
    if (!wp_next_scheduled('wsg_daily_scan_event')) {
        wp_schedule_event(time(), 'daily', 'wsg_daily_scan_event');
    }
}
register_activation_hook(WSG_PATH.'wp-security-guard.php', 'wsg_activate_cron');

function wsg_deactivate_cron() {
    $timestamp = wp_next_scheduled('wsg_daily_scan_event');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'wsg_daily_scan_event');
    }
}
register_deactivation_hook(WSG_PATH.'wp-security-guard.php', 'wsg_deactivate_cron');

function wsg_run_cron_scan() {
    $scanner = new WSG_Scanner();
    $scanner->run_full_scan();
    
    // Optional: Could send an email here if issues.count > 0.
}
add_action('wsg_daily_scan_event', 'wsg_run_cron_scan');
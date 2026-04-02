<?php
namespace Ironwall\Core;

if (!defined('ABSPATH')) exit;

/**
 * Ironwall AJAX Scanner Engine
 * 
 * Performs stateful, batch-based security scans to prevent PHP timeouts 
 * on large WordPress installations.
 */
class Scanner {
    
    // Malware detection patterns
    private $malware_signatures = [
        'eval\(base64_decode' => 'Obfuscated exact eval(base64_decode())',
        'str_rot13\(base64_decode' => 'Obfuscated str_rot13 + base64',
        'eval\(\$_(POST|GET)' => 'Direct evaluation of request data',
        'WP_CD_CODE' => 'Known WP-VCD malware signature',
        'file_get_contents\([\'"]https?:\/\/[^\'"]+\.txt[\'"]\)' => 'Remote payload execution',
        'gzinflate\(base64_decode' => 'Gzinflate base64 encoded payload'
    ];

    public function __construct() {}

    /**
     * Crawl the filesystem to build a list of all PHP files.
     */
    public function get_files_to_scan() {
        $files = [];
        $dirs = [
            ABSPATH . 'wp-admin', 
            ABSPATH . 'wp-includes', 
            WP_PLUGIN_DIR, 
            get_theme_root()
        ];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) continue;
            
            try {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                );

                foreach ($iterator as $file) {
                    if ($file->isFile() && $file->getExtension() === 'php') {
                        $files[] = $file->getPathname();
                    }
                }
            } catch (\Exception $e) {
                error_log('WSG Scanner: Failed to scan directory ' . $dir);
            }
        }
        return $files;
    }

    /**
     * Scan a specific file for known malware signatures.
     */
    public function scan_file($filepath) {
        // Prevent false positives by skipping the plugin's own directory (since it contains the malware signatures)
        if (strpos($filepath, dirname(dirname(__DIR__))) !== false) {
            return [];
        }

        $content = @file_get_contents($filepath);
        if ($content === false) return [];

        $issues = [];
        foreach ($this->malware_signatures as $pattern => $description) {
            if (preg_match('/' . $pattern . '/i', $content)) {
                $issues[] = [
                    'type'     => __('Malware Signature Detected', 'ironwall'), 
                    'severity' => __('critical', 'ironwall'),
                    'details'  => __($description, 'ironwall')
                ];
            }
        }

        // Check for common obfuscation techniques
        if (substr_count($content, 'chr(') > 50 || substr_count($content, 'base64_decode(') > 10) {
             $issues[] = [
                 'type'     => __('Highly Obfuscated Code', 'ironwall'), 
                 'severity' => __('high', 'ironwall'),
                 'details'  => __('File contains excessive obfuscation functions', 'ironwall')
             ];
        }

        return $issues;
    }

    /**
     * Verify the integrity of WordPress core files against official checksums.
     */
    public function verify_core_integrity() {
        global $wp_version;
        $locale = get_locale();
        $response = wp_remote_get("https://api.wordpress.org/core/checksums/1.0/?version={$wp_version}&locale={$locale}");
        
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return [];
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (empty($data['checksums']) || !is_array($data['checksums'])) {
            return [];
        }

        $checksums = $data['checksums'];
        $issues = [];
        
        // Focus on critical core files for performance and liability
        $critical_files = [
            'wp-login.php', 
            'wp-settings.php', 
            'wp-load.php', 
            'wp-cron.php', 
            'wp-admin/admin.php', 
            'wp-includes/plugin.php'
        ];
        
        foreach ($critical_files as $file) {
            $local_path = ABSPATH . $file;
            if (!file_exists($local_path)) {
                $issues[] = [
                    'file'     => $file, 
                    'type'     => 'Missing Core File', 
                    'severity' => 'critical', 
                    'details'  => 'A critical WordPress core file has been removed.'
                ];
                continue;
            }
            
            if (md5_file($local_path) !== $checksums[$file]) {
                $issues[] = [
                    'file'     => $file, 
                    'type'     => 'Core File Modified', 
                    'severity' => 'critical', 
                    'details'  => 'This WordPress core file does not match the official repository checksum.'
                ];
            }
        }
        return $issues;
    }

    /**
     * Scan database and settings for common security misconfigurations.
     */
    public function scan_configuration() {
        global $wpdb;
        $issues = [];
        
        // 1. Check for default 'admin' username
        $has_admin = $wpdb->get_var("SELECT ID FROM {$wpdb->users} WHERE user_login = 'admin' LIMIT 1");
        if ($has_admin) {
            $issues[] = [
                'file'     => 'Database Config',
                'type'     => 'Security Misconfiguration',
                'severity' => 'high',
                'details'  => 'The default username "admin" is present. This is heavily targeted by brute-force bots.'
            ];
        }

        // 2. Check if anyone can register
        if (get_option('users_can_register')) {
            $issues[] = [
                'file'     => 'WP Settings',
                'type'     => 'Security Misconfiguration',
                'severity' => 'medium',
                'details'  => 'Anyone can register on this site. Ensure this is intentional otherwise disable it.'
            ];
        }

        return $issues;
    }
}

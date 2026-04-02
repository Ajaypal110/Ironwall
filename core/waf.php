<?php
if(!defined('ABSPATH')){
    exit;
}

class WSG_WAF {
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance == null) {
            self::$instance = new WSG_WAF();
        }
        return self::$instance;
    }

    private function __construct() {
        // Run as early as possible
        $this->process_request();
    }

    private function process_request() {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '127.0.0.1';

        // 1. Check if IP is permanently or temporarily blocked
        if (function_exists('wsg_is_ip_blocked') && wsg_is_ip_blocked($ip)) {
            $this->block_request("Your IP address has been temporarily blocked for security reasons.");
        }

        // 1.5 Advanced Rate Limiting
        $this->check_rate_limit($ip);

        // 2. Inspect request data
        $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $query_string = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
        
        $this->inspect_payload($_GET, 'GET');
        $this->inspect_payload($_POST, 'POST');
        $this->inspect_payload($_COOKIE, 'COOKIE');
        
        // Specific checks for URI
        $this->check_lfi($request_uri);
        $this->check_sqli($request_uri);
    }

    private function inspect_payload($data, $method) {
        if (empty($data) || !is_array($data)) return;

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $this->inspect_payload($value, $method);
                continue;
            }

            $value = urldecode((string)$value);
            
            $this->check_sqli($value);
            $this->check_xss($value);
            $this->check_lfi($value);
            $this->check_bad_bots();
        }
    }

    private function check_sqli($string) {
        $patterns = array(
            '/union\s+select/i',
            '/select\s+.*\s+from/i',
            '/insert\s+into/i',
            '/update\s+.*\s+set/i',
            '/delete\s+from/i',
            '/drop\s+table/i',
            '/\bconcat\s*\(/i',
            '/WAITFOR\s+DELAY/i'
        );

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $string)) {
                $this->log_and_block('SQL Injection Attempt Detected');
            }
        }
    }

    private function check_xss($string) {
        $patterns = array(
            '/<script[^>]*>.*?<\/script>/is',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload=/i',
            '/onerror=/i',
            '/onmouseover=/i',
            '/eval\s*\(/i',
            '/alert\s*\(/i',
            '/<iframe/i'
        );

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $string)) {
                $this->log_and_block('XSS Attempt Detected');
            }
        }
    }

    private function check_lfi($string) {
        // Local File Inclusion & Directory Traversal
        $patterns = array(
            '/\.\.\//', // ../
            '/\.\.\\\\/', // ..\
            '/etc\/passwd/i',
            '/windows\/system32/i',
            '/boot\.ini/i'
        );

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $string)) {
                $this->log_and_block('Local File Inclusion (LFI) Attempt Detected');
            }
        }
    }
    
    private function check_bad_bots() {
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : '';
        $bad_bots = array('sqlmap', 'nikto', 'dirbuster', 'nmap', 'zmeu', 'blackwidow');
        
        foreach ($bad_bots as $bot) {
            if (strpos($user_agent, $bot) !== false) {
                 $this->log_and_block('Malicious User-Agent Detected');
            }
        }
    }

    private function log_and_block($reason) {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '127.0.0.1';
        
        if (function_exists('wsg_block_ip')) {
            wsg_block_ip($ip, $reason, 3600); // Block for 1 hour
        }
        
        $this->block_request("Access Denied: $reason.");
    }

    private function block_request($message) {
        if (!headers_sent()) {
            http_response_code(403);
        }
        
        echo '<!DOCTYPE html><html><head><title>Access Denied</title><style>body{font-family:sans-serif;text-align:center;padding:50px;background:#f9f9f9;}h1{color:#d32f2f;}</style></head><body>';
        echo '<h1>403 Forbidden</h1>';
        echo '<p>' . esc_html($message) . '</p>';
        echo '<p>Protected by <strong>WP Sentinel WAF</strong>.</p>';
        echo '</body></html>';
        exit;
    }

    private function check_rate_limit($ip) {
        // Skip rate-limiting for admin users if WP is loaded enough (usually WAF runs too early though)
        $transient_key = 'wsg_rl_' . md5($ip);
        
        // Track requests per minute
        $requests = (int) get_transient($transient_key);
        $requests++;
        
        if ($requests === 1) {
            // First request in this window, set expiration for 1 minute
            set_transient($transient_key, 1, 60);
        } else {
            // Update without resetting timeout is tricky with Transients. Let's just blindly update.
            // On high traffic this could race, but it's acceptable for a basic WAF.
            set_transient($transient_key, $requests, 60);
        }

        // Limit: 180 requests per minute
        if ($requests > 180) {
            $this->log_and_block('Rate Limit Exceeded (>180 req/min)');
        }
    }
}

// Initialize WAF
WSG_WAF::get_instance();

<?php
namespace Ironwall\Core;

if (!defined('ABSPATH')) exit;

/**
 * Ironwall Web Application Firewall (WAF)
 * 
 * Inspects all incoming requests for malicious payloads, SQL injection, 
 * XSS attempts, bad bots, and rate-limiting violations.
 */
class WAF {
    private static $instance = null;
    
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->process_request();
    }

    private function process_request() {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '127.0.0.1';

        // 1. Check if IP is blocked
        if (\Ironwall\Database\Logger::is_blocked($ip)) {
            $this->block_request(__('Your IP address has been temporarily blocked for security reasons.', 'ironwall'));
        }

        // 2. Rate Limiting
        $this->check_rate_limit($ip);

        // 3. Check for malicious user agents
        $this->check_bad_bots();

        // 4. Inspect request data
        $request_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
        
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
        $patterns = array(
            '/\.\.\//',
            '/\.\.\\\\/',
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
    
    /**
     * Detect malicious automated scanning tools via User-Agent.
     */
    private function check_bad_bots() {
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower(sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT']))) : '';
        $bad_bots = array('sqlmap', 'nikto', 'dirbuster', 'nmap', 'zmeu', 'blackwidow', 'havij', 'w3af');
        
        foreach ($bad_bots as $bot) {
            if (strpos($user_agent, $bot) !== false) {
                 $this->log_and_block('Malicious User-Agent Detected');
            }
        }
    }

    private function log_and_block($reason) {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '127.0.0.1';
        
        \Ironwall\Database\Logger::block_ip($ip, $reason, 3600);
        
        $this->block_request("Access Denied: $reason.");
    }

    private function block_request($message) {
        if (!headers_sent()) {
            http_response_code(403);
        }
        
        echo '<!DOCTYPE html><html><head><title>' . esc_html__('Access Denied', 'ironwall') . '</title><style>body{font-family:Inter,sans-serif;text-align:center;padding:50px;background:#0f172a;color:#e2e8f0;}h1{color:#f43f5e;}.container{max-width:500px;margin:0 auto;padding:40px;background:rgba(30,41,59,0.8);border-radius:16px;border:1px solid rgba(99,102,241,0.2);}</style></head><body>';
        echo '<div class="container"><h1>403 Forbidden</h1>';
        echo '<p>' . esc_html($message) . '</p>';
        echo '<p style="color:#94a3b8;margin-top:20px;">Protected by <strong style="color:#818cf8;">Ironwall WAF</strong></p>';
        echo '</div></body></html>';
        exit;
    }

    private function check_rate_limit($ip) {
        $transient_key = 'irw_rl_' . md5($ip);
        
        $requests = (int) get_transient($transient_key);
        $requests++;
        
        set_transient($transient_key, $requests, 60);

        if ($requests > 180) {
            $this->log_and_block('Rate Limit Exceeded (>180 req/min)');
        }
    }
}

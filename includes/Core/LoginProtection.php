<?php
namespace Ironwall\Core;

if (!defined('ABSPATH')) exit;

/**
 * Ironwall Login Protection
 * 
 * Handles Brute Force protection via IP banning on repeated failed logins.
 */
class LoginProtection {

    public function __construct() {
        if (get_option('irw_login_protection')) {
            // Brute Force Protection
            add_action('wp_login_failed', array($this, 'handle_failed_login'));
            add_action('login_init', array($this, 'check_brute_force'));
        }
    }

    /**
     * Log a failed login attempt.
     */
    public function handle_failed_login($username) {
        \Ironwall\Database\Logger::log(
            'Login Failed',
            sanitize_user($username),
            __('Failed login attempt', 'ironwall')
        );
    }

    /**
     * Check if the current IP has exceeded the failed login threshold.
     */
    public function check_brute_force() {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '127.0.0.1';
        $attempts = \Ironwall\Database\Logger::get_failed_attempts($ip);

        if ($attempts >= 5) {
            $reason = __('Brute Force Attack Detected', 'ironwall');
            \Ironwall\Database\Logger::block_ip($ip, $reason, 86400);
            
            wp_die(
                esc_html__('Too many failed login attempts. Your IP has been temporarily blocked for 24 hours.', 'ironwall'),
                esc_html__('Access Blocked', 'ironwall'),
                array('response' => 403)
            );
        }
    }
}

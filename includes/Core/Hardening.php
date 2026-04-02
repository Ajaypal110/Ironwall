<?php
namespace Ironwall\Core;

if (!defined('ABSPATH')) exit;

/**
 * Ironwall Hardening
 * 
 * Implements security best practices like disabling XML-RPC,
 * blocking author enumeration, and hiding WordPress version strings.
 */
class Hardening {
    
    public function __construct() {
        $this->init();
    }

    private function init() {
        // Disable XML-RPC if option is set
        if (get_option('irw_xmlrpc_disable')) {
            add_filter('xmlrpc_enabled', '__return_false');
            add_filter('xmlrpc_methods', [$this, 'remove_xmlrpc_methods']);
        }

        // Block Author Enumeration
        add_action('init', [$this, 'block_author_enumeration']);
        
        // Disable Theme/Plugin File Editor
        if (!defined('DISALLOW_FILE_EDIT')) {
            define('DISALLOW_FILE_EDIT', true);
        }
        
        // Hide WordPress version for privacy
        remove_action('wp_head', 'wp_generator');
        add_filter('the_generator', '__return_empty_string');
        
        // Mask login errors
        add_filter('login_errors', [$this, 'mask_login_errors']);
        
        // Security Headers
        if (get_option('irw_security_headers')) {
            add_action('send_headers', [$this, 'send_security_headers']);
        }
    }

    public function mask_login_errors() {
        return __('Login failed. Please check your credentials.', 'ironwall');
    }

    public function send_security_headers() {
        if (!headers_sent()) {
            header('X-Frame-Options: SAMEORIGIN');
            header('X-XSS-Protection: 1; mode=block');
            header('X-Content-Type-Options: nosniff');
            header('Referrer-Policy: strict-origin-when-cross-origin');
        }
    }

    /**
     * Remove specific XML-RPC methods for pingback bypass.
     */
    public function remove_xmlrpc_methods($methods) {
        unset($methods['pingback.ping']);
        unset($methods['pingback.extensions.getPingbacks']);
        return $methods;
    }

    /**
     * Redirect ?author=N requests to homepage.
     */
    public function block_author_enumeration() {
        if (isset($_REQUEST['author']) && !is_admin()) {
            wp_safe_redirect(home_url());
            exit;
        }
    }
}

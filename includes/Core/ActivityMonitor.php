<?php
namespace Ironwall\Core;

if (!defined('ABSPATH')) exit;

/**
 * Ironwall Activity Monitor
 * 
 * Tracks significant events within the WordPress ecosystem,
 * such as successful logins and plugin activations.
 */
class ActivityMonitor {
    
    public function __construct() {
        $this->init();
    }

    private function init() {
        // Successful login
        add_action('wp_login', [$this, 'log_successful_login'], 10, 2);
        
        // Plugin activation
        add_action('activated_plugin', [$this, 'log_plugin_activation']);
    }

    /**
     * Log a successful user login.
     */
    public function log_successful_login($user_login, $user) {
        \Ironwall\Database\Logger::log('Successful Login', $user_login);
    }

    /**
     * Log when a plugin is activated.
     */
    public function log_plugin_activation($plugin) {
        \Ironwall\Database\Logger::log('Plugin Activated', '', "Plugin: $plugin");
    }
}

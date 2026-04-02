<?php
/**
 * Plugin Name: Ironwall
 * Plugin URI: https://github.com/Ajaypal110/Ironwall
 * Description: Professional-grade WordPress security monitoring, WAF, and Malware Scanner.
 * Version: 5.1
 * Author: Ajaypal Singh
 * Author URI: https://github.com/Ajaypal110
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ironwall
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('IRW_PATH', plugin_dir_path(__FILE__));

// Initialize Autoloader
require_once IRW_PATH . 'includes/Autoloader.php';
\Ironwall\Autoloader::register();

// Initialize Plugin
function wps_init() {
    $plugin = \Ironwall\Plugin::instance();
    
    // Register Hooks
    register_activation_hook(__FILE__, array($plugin, 'activate'));
    register_deactivation_hook(__FILE__, array($plugin, 'deactivate'));
}
wps_init();
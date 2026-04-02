<?php
namespace Ironwall;

if (!defined('ABSPATH')) exit;

class Autoloader {
    public static function register() {
        spl_autoload_register(function ($class) {
            $prefix = 'Ironwall\\';
            $base_dir = plugin_dir_path(__FILE__);

            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                return;
            }

            $relative_class = substr($class, $len);
            $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

            if (file_exists($file)) {
                require $file;
            }
        });
    }
}

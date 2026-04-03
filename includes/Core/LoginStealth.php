<?php
namespace Ironwall\Core;

if (!defined('ABSPATH')) exit;

class LoginStealth {
    private static $is_filtering = false;

    public function __construct() {
        if (defined('IRONWALL_DISABLE_STEALTH') && IRONWALL_DISABLE_STEALTH) {
            return; // Safety fallback
        }

        $is_enabled = get_option('irw_stealth_enable', 0);
        $slug = get_option('irw_login_slug');

        // Only enforce stealth login if the toggle is ON and a custom slug is actually set AND it's not the default wp-login.php
        if ($is_enabled && !empty($slug) && $slug !== 'wp-login.php') {
            add_action('login_init', [$this, 'block_default_login'], 1);
            add_action('wp_loaded', [$this, 'custom_login_loader'], 1);
            
            // Rewrite standard URLs to point to our custom slug
            add_filter('login_url', [$this, 'filter_login_url'], 10, 3);
            add_filter('logout_url', [$this, 'filter_logout_url'], 10, 2);
            add_filter('lostpassword_url', [$this, 'filter_login_url'], 10, 2);
            add_filter('register_url', [$this, 'filter_login_url'], 10, 2);
            
            // Redirect after logout
            add_filter('logout_redirect', [$this, 'logout_redirect'], 10, 3);
        }
    }

    private function get_safe_home_url() {
        return untrailingslashit(get_option('home'));
    }

    public function get_slug() {
        static $slug = null;
        if ($slug === null) {
            $is_enabled = get_option('irw_stealth_enable', 0);
            $option = get_option('irw_login_slug');
            $slug = (!$is_enabled || empty($option)) ? 'wp-login.php' : trim($option, '/');
        }
        return $slug;
    }

    public function filter_login_url($url, $redirect = '', $force_reauth = false) {
        if (self::$is_filtering) return $url;
        self::$is_filtering = true;

        $custom_url = $this->get_safe_home_url() . '/' . $this->get_slug();
        if (!empty($redirect)) {
            $custom_url = add_query_arg('redirect_to', urlencode($redirect), $custom_url);
        }

        self::$is_filtering = false;
        return $custom_url;
    }

    public function filter_logout_url($url, $redirect = '') {
        if (self::$is_filtering) return $url;
        self::$is_filtering = true;

        $custom_url = $this->get_safe_home_url() . '/' . $this->get_slug() . '?action=logout';
        if (!empty($redirect)) {
            $custom_url = add_query_arg('redirect_to', urlencode($redirect), $custom_url);
        }
        
        $final_url = wp_nonce_url($custom_url, 'log-out');
        self::$is_filtering = false;
        return $final_url;
    }

    public function block_default_login() {
        if (defined('DOING_AJAX') || defined('DOING_CRON') || defined('REST_REQUEST')) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_POST['log'])) {
            return;
        }

        if (isset($_GET['action'])) {
            $allowed = ['logout', 'lostpassword', 'retrievepassword', 'resetpass', 'rp'];
            if (in_array($_GET['action'], $allowed)) {
                return;
            }
        }

        $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        if (strpos($request_uri, 'wp-login.php') !== false) {
            wp_safe_redirect(home_url('/'));
            exit;
        }
    }

    public function custom_login_loader() {
        if (defined('DOING_AJAX') || defined('DOING_CRON') || defined('REST_REQUEST')) {
            return;
        }

        $slug = $this->get_slug();
        $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        $path = trim(parse_url($request_uri, PHP_URL_PATH), '/');
        $site_path = trim(parse_url(home_url(), PHP_URL_PATH), '/');
        
        if (!empty($site_path)) {
            $path = preg_replace('/^' . preg_quote($site_path, '/') . '\//', '', $path);
        }

        if ($path === $slug) {
            global $pagenow, $user_login, $error, $wp_error, $action, $user, $user_ID, $interim_login, $redirect_to;

            $user_login = '';
            $error = '';
            $pagenow = 'wp-login.php';

            if (!defined('WP_LOGIN_PAGE')) {
                define('WP_LOGIN_PAGE', true);
            }

            @require_once ABSPATH . 'wp-login.php';
            exit;
        }
    }

    public function logout_redirect($redirect, $requested, $user) {
        return site_url('/' . $this->get_slug() . '?loggedout=true');
    }
}

<?php
namespace Ironwall\Core;

if (!defined('ABSPATH')) exit;

class LoginStealth {

    public function __construct() {
        if (get_option('irw_login_slug')) {
            add_action('login_init', [$this, 'block_default_login']);
            add_action('init', [$this, 'custom_login_loader']);
            add_filter('logout_redirect', [$this, 'logout_redirect'], 10, 3);
        }
    }

    public function get_slug() {
        $slug = get_option('irw_login_slug');
        return empty($slug) ? 'secure-login' : trim($slug, '/');
    }

    public function block_default_login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_POST['log'])) {
            return;
        }

        if (isset($_GET['action'])) {
            $allowed = ['logout', 'lostpassword', 'retrievepassword', 'resetpass', 'rp'];
            if (in_array($_GET['action'], $allowed)) {
                return;
            }
        }

        if (strpos($_SERVER['REQUEST_URI'], 'wp-login.php') !== false) {
            wp_safe_redirect(home_url());
            exit;
        }
    }

    public function custom_login_loader() {
        $slug = $this->get_slug();
        $current = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

        if ($current === $slug) {
            global $pagenow, $user_login, $error, $wp_error, $action, $user, $user_ID, $interim_login, $redirect_to;

            $user_login = '';
            $error = '';
            $pagenow = 'wp-login.php';

            if (!defined('WP_LOGIN_PAGE')) {
                define('WP_LOGIN_PAGE', true);
            }

            require_once ABSPATH . 'wp-login.php';
            exit;
        }
    }

    public function logout_redirect($redirect, $requested, $user) {
        return home_url('/' . $this->get_slug());
    }
}

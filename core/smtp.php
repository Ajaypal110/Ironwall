<?php
if(!defined('ABSPATH')){ exit; }

/**
 * WP Sentinel Built-in SMTP Engine
 * Routes wp_mail() through a real SMTP server (Gmail, Outlook, etc.)
 * so that 2FA OTP emails actually get delivered.
 */

add_action('phpmailer_init', 'wsg_configure_smtp');

function wsg_configure_smtp($phpmailer) {
    $smtp_enabled = get_option('wsg_smtp_enabled');
    if (!$smtp_enabled) return;

    $host     = get_option('wsg_smtp_host', '');
    $port     = get_option('wsg_smtp_port', 587);
    $username = get_option('wsg_smtp_username', '');
    $password = get_option('wsg_smtp_password', '');
    $from     = get_option('wsg_smtp_from', '');
    $fromname = get_option('wsg_smtp_from_name', '');

    // Fallback: use username as From if not set
    if (empty($from)) $from = $username;
    if (empty($fromname)) $fromname = get_bloginfo('name');

    if (empty($host) || empty($username) || empty($password)) return;

    $phpmailer->isSMTP();
    $phpmailer->Host       = $host;
    $phpmailer->SMTPAuth   = true;
    $phpmailer->Port       = (int) $port;
    $phpmailer->Username   = $username;
    $phpmailer->Password   = $password;
    $phpmailer->SMTPSecure = ((int) $port === 465) ? 'ssl' : 'tls';
    $phpmailer->From       = $from;
    $phpmailer->FromName   = $fromname;
}

// SMTP Test Email handler
add_action('admin_post_wsg_test_smtp', 'wsg_test_smtp_handler');

function wsg_test_smtp_handler() {
    if (!current_user_can('manage_options')) { wp_die('Unauthorized'); }
    check_admin_referer('wsg_test_smtp_action');

    // Temporarily force SMTP on for this test, even if the toggle is off
    $host     = get_option('wsg_smtp_host', '');
    $port     = get_option('wsg_smtp_port', 587);
    $username = get_option('wsg_smtp_username', '');
    $password = get_option('wsg_smtp_password', '');
    $from     = get_option('wsg_smtp_from', '');
    $fromname = get_option('wsg_smtp_from_name', '');

    if (empty($from)) $from = $username;
    if (empty($fromname)) $fromname = get_bloginfo('name');

    if (empty($host) || empty($username) || empty($password)) {
        set_transient('wsg_smtp_test_result', 'fail:Please fill in SMTP Host, Username, and Password before testing.', 30);
        wp_redirect(admin_url('admin.php?page=wsg-settings'));
        exit;
    }

    // Force-apply SMTP just for this request
    add_action('phpmailer_init', function($phpmailer) use ($host, $port, $username, $password, $from, $fromname) {
        $phpmailer->isSMTP();
        $phpmailer->Host       = $host;
        $phpmailer->SMTPAuth   = true;
        $phpmailer->Port       = (int) $port;
        $phpmailer->Username   = $username;
        $phpmailer->Password   = $password;
        $phpmailer->SMTPSecure = ((int) $port === 465) ? 'ssl' : 'tls';
        $phpmailer->From       = $from;
        $phpmailer->FromName   = $fromname;
    }, 99);

    $current_user = wp_get_current_user();
    $to = $current_user->user_email;

    $subject = 'WP Sentinel SMTP Test';
    $headers = array('Content-Type: text/html; charset=UTF-8', 'From: ' . $fromname . ' <' . $from . '>');
    $message = "
    <div style='font-family:sans-serif; max-width:500px; border:1px solid #e2e8f0; padding:24px; border-radius:12px;'>
        <h2 style='color:#4f46e5; margin-top:0;'>✅ SMTP Configuration Verified</h2>
        <p style='color:#334155;'>This email confirms that your <strong>WP Sentinel</strong> SMTP settings are working correctly.</p>
        <p style='color:#334155;'>Your 2FA OTP emails will now be delivered successfully.</p>
        <hr style='border:none; border-top:1px solid #e2e8f0; margin:20px 0;'>
        <p style='font-size:12px; color:#94a3b8;'>Protected by WP Sentinel Security Suite v4.4</p>
    </div>";

    $result = wp_mail($to, $subject, $message, $headers);

    if ($result) {
        set_transient('wsg_smtp_test_result', 'success', 30);
    } else {
        global $phpmailer;
        $error = 'Unknown error';
        if (isset($phpmailer) && is_object($phpmailer) && !empty($phpmailer->ErrorInfo)) {
            $error = $phpmailer->ErrorInfo;
        }
        set_transient('wsg_smtp_test_result', 'fail:' . $error, 30);
    }

    wp_redirect(admin_url('admin.php?page=wsg-settings'));
    exit;
}

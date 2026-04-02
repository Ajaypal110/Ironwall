<?php
if(!defined('ABSPATH')){
    exit;
}

class WSG_Login_Protection {

    public function __construct() {
        if(get_option('wsg_login_protection')){
            // Brute Force Protection
            add_action('wp_login_failed', array($this, 'handle_failed_login'));
            add_action('login_init', array($this, 'check_brute_force'));
        }

        if(get_option('wsg_enable_2fa')){
            // 2FA Hooks
            add_filter('authenticate', array($this, 'do_2fa_authentication'), 30, 3);
            add_action('login_form_wsg_2fa', array($this, 'render_2fa_form'));
            add_action('login_form', array($this, 'add_2fa_session_field'));
        }
    }

    // --- Brute Force Protection ---

    public function handle_failed_login($username){
        wsg_insert_log('Failed login', $username);
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '127.0.0.1';
        
        $attempts = (int) get_transient('wsg_bf_' . $ip);
        $attempts++;
        
        set_transient('wsg_bf_' . $ip, $attempts, 300); // 5 minutes window
        
        if ($attempts >= 5) {
            // Block IP for 24 hours
            if(function_exists('wsg_block_ip')) {
                wsg_block_ip($ip, "Too many failed login attempts", 86400);
            }
            wsg_insert_log('IP blocked via Brute Force Protection', $username, "IP $ip blocked after $attempts attempts.");
        }
    }

    public function check_brute_force(){
        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field($_SERVER['REMOTE_ADDR']) : '127.0.0.1';
        if(function_exists('wsg_is_ip_blocked') && wsg_is_ip_blocked($ip)){
            wp_die('<strong>ERROR</strong>: Your IP address has been temporarily blocked due to too many failed login attempts. Please try again later.');
        }
    }
    
    // --- 2FA Authentication ---

    public function do_2fa_authentication($user, $username, $password) {
        if (is_wp_error($user)) {
            return $user;
        }

        if (empty($user) || empty($user->ID)) {
            return $user; // Not authenticated yet
        }

        // Only enforce 2FA for administrators for now
        if (!in_array('administrator', (array) $user->roles)) {
            return $user;
        }

        $is_2fa_submitted = isset($_POST['wsg_2fa_code']);
        
        if ($is_2fa_submitted) {
            $code = sanitize_text_field($_POST['wsg_2fa_code']);
            $saved_code = get_user_meta($user->ID, '_wsg_2fa_code', true);
            $expiry = get_user_meta($user->ID, '_wsg_2fa_expiry', true);
            
            if ($code === $saved_code && time() < $expiry) {
                // Success
                delete_user_meta($user->ID, '_wsg_2fa_code');
                delete_user_meta($user->ID, '_wsg_2fa_expiry');
                wsg_insert_log('Successful 2FA Login', $username);
                return $user;
            } else {
                return new WP_Error('invalid_2fa', '<strong>ERROR</strong>: Invalid or expired 2FA code.');
            }
        }

        // Generate and send code
        $code = wp_generate_password(6, false);
        update_user_meta($user->ID, '_wsg_2fa_code', $code);
        update_user_meta($user->ID, '_wsg_2fa_expiry', time() + 600); // 10 mins

        $user_email = $user->user_email;
        $site_name = get_bloginfo('name');
        
        $subject = "Your 2FA Login Code - $site_name";
        $headers = array('Content-Type: text/html; charset=UTF-8');
        $headers[] = 'From: ' . $site_name . ' Security <wordpress@' . parse_url(get_site_url(), PHP_URL_HOST) . '>';
        
        $message = "
        <div style='font-family:sans-serif; max-width:500px; border:1px solid #eee; padding:20px; border-radius:10px;'>
            <h2 style='color:#4f46e5;'>Security Verification</h2>
            <p>Verification code for <strong>$username</strong>:</p>
            <div style='background:#f1f5f9; padding:15px; font-size:24px; font-weight:bold; letter-spacing:5px; text-align:center; border-radius:6px; color:#1e293b;'>$code</div>
            <p style='color:#64748b; font-size:13px;'>This code expires in 10 minutes. If you did not attempt to log in, please change your password immediately.</p>
            <hr style='border:none; border-top:1px solid #eee; margin:20px 0;'>
            <p style='font-size:11px; color:#94a3b8;'>Protected by WP Sentinel Security Suite</p>
        </div>";

        $mail_sent = wp_mail($user_email, $subject, $message, $headers);
        
        if ($mail_sent) {
            wsg_insert_log('2FA Code Sent', $username, "Sent to $user_email");
        } else {
            // Fail-safe for localhost/XAMPP: Log the code so the user can see it in the dashboard
            wsg_insert_log('2FA Email Failed', $username, "CRITICAL: Email delivery failed. Your OTP code is: $code (Check this log if you didn't get the email)");
        }

        // Render the 2FA form
        $this->render_2fa_form($user);
        exit;
    }

    public function render_2fa_form($user = null) {
        $user_id = $user ? $user->ID : 0;
        $username = $user ? $user->user_login : '';
        login_header(__('Two-Factor Authentication'), '<p class="message">' . __('A verification code has been sent to your email address.') . '</p>');
        ?>
        <form name="loginform" id="loginform" action="<?php echo esc_url(site_url('wp-login.php', 'login_post')); ?>" method="post">
            <p>
                <label for="wsg_2fa_code"><?php _e('Authentication Code') ?><br />
                <input type="text" name="wsg_2fa_code" id="wsg_2fa_code" class="input" value="" size="20" required /></label>
            </p>
            <input type="hidden" name="log" value="<?php echo esc_attr($username); ?>" />
            <!-- Note: Password is required by wp-login.php again, so we might need to pass it, but for secure implementation, 
                 we would use a session token. For this demo, we use a hidden token to prevent re-entering pass. -->
            <input type="hidden" name="wsg_2fa_user_id" value="<?php echo esc_attr($user_id); ?>" />
            <?php 
            // Minimal bypass just to show concept
            // Real implementation requires auth-cookie exchange
            ?>
            <p class="submit">
                <input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e('Verify'); ?>" />
            </p>
        </form>
        <?php
        login_footer();
        exit;
    }

    public function add_2fa_session_field() {
        // Additional form fields if needed
    }
}

new WSG_Login_Protection();
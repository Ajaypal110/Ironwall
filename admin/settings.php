<?php
if(!defined('ABSPATH')){
    exit;
}

/* Register settings */
add_action('admin_init','wsg_register_settings');

function wsg_register_settings(){
    register_setting('wsg_settings_group','wsg_login_protection');
    register_setting('wsg_settings_group','wsg_xmlrpc_disable');
    register_setting('wsg_settings_group','wsg_security_headers');
    register_setting('wsg_settings_group','wsg_login_slug');
    register_setting('wsg_settings_group','wsg_enable_2fa');
}

/* Settings page */
function wsg_settings_page(){
    $slug=get_option('wsg_login_slug');
    if(!$slug){
        $slug='secure-login';
    }
    ?>
    <style>
        .wsg-wrap { max-width: 1000px; margin: 20px 20px 20px 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; }
        .wsg-header { margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid #e2e8f0; }
        .wsg-header h1 { font-size: 28px; font-weight: 600; color: #1e293b; margin: 0; line-height: 1.2; }
        .wsg-header p { color: #64748b; font-size: 15px; margin: 8px 0 0; }
        
        .wsg-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .wsg-card-title { font-size: 18px; font-weight: 600; color: #0f172a; margin-top: 0; margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; }
        
        .wsg-setting-row { display: flex; justify-content: space-between; align-items: center; padding: 16px 0; border-bottom: 1px solid #f1f5f9; }
        .wsg-setting-row:last-child { border-bottom: none; padding-bottom: 0; }
        .wsg-setting-info h4 { margin: 0 0 4px; font-size: 15px; color: #334155; }
        .wsg-setting-info p { margin: 0; font-size: 13px; color: #64748b; }
        
        /* Modern Toggle Switch */
        .wsg-toggle { position: relative; display: inline-block; width: 44px; height: 24px; }
        .wsg-toggle input { opacity: 0; width: 0; height: 0; }
        .wsg-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .3s; border-radius: 24px; }
        .wsg-slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .3s; border-radius: 50%; box-shadow: 0 1px 2px rgba(0,0,0,0.2); }
        .wsg-toggle input:checked + .wsg-slider { background-color: #4f46e5; }
        .wsg-toggle input:focus + .wsg-slider { box-shadow: 0 0 1px #4f46e5; }
        .wsg-toggle input:checked + .wsg-slider:before { transform: translateX(20px); }

        /* Inputs */
        .wsg-input-text { padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; width: 100%; max-width: 300px; box-shadow: inset 0 1px 2px rgba(0,0,0,0.02); transition: border-color 0.15s; }
        .wsg-input-text:focus { border-color: #4f46e5; outline: none; box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
        
        .wsg-alert { background: #fef2f2; border-left: 4px solid #ef4444; padding: 12px 16px; margin-top: 16px; border-radius: 0 6px 6px 0; }
        .wsg-alert p { color: #991b1b; margin: 0; font-size: 14px; font-weight: 500; }
        
        .wsg-url-preview { background: #f8fafc; padding: 12px; border-radius: 6px; font-family: monospace; font-size: 13px; color: #475569; border: 1px solid #e2e8f0; margin-top: 10px; }
        .wsg-url-preview strong { color: #4f46e5; }
        
        .button-wsg-primary { background: #4f46e5 !important; border-color: #4338ca !important; color: white !important; box-shadow: 0 1px 2px rgba(0,0,0,0.1) !important; padding: 6px 24px !important; font-size: 15px !important; border-radius: 6px !important; height: auto !important; transition: background 0.2s !important; }
        .button-wsg-primary:hover { background: #4338ca !important; }
    </style>

    <div class="wsg-wrap">
        <div class="wsg-header">
            <h1>Platform Settings</h1>
            <p>Configure active protection modules and lockdown your WordPress instance.</p>
        </div>

        <form method="post" action="options.php">
            <?php settings_fields('wsg_settings_group'); ?>

            <div class="wsg-card">
                <h2 class="wsg-card-title">Core Protection Engines</h2>
                
                <div class="wsg-setting-row">
                    <div class="wsg-setting-info">
                        <h4>Brute Force Protection</h4>
                        <p>Automatically block IPs that repeatedly fail login attempts (5 fails = 24h ban).</p>
                    </div>
                    <label class="wsg-toggle">
                        <input type="checkbox" name="wsg_login_protection" value="1" <?php checked(1,get_option('wsg_login_protection'),true); ?>>
                        <span class="wsg-slider"></span>
                    </label>
                </div>
                
                <div class="wsg-setting-row">
                    <div class="wsg-setting-info">
                        <h4>Two-Factor Authentication (2FA)</h4>
                        <p>Require administrators to enter a 6-digit email OTP to authorize logins.</p>
                    </div>
                    <label class="wsg-toggle">
                        <input type="checkbox" name="wsg_enable_2fa" value="1" <?php checked(1,get_option('wsg_enable_2fa'),true); ?>>
                        <span class="wsg-slider"></span>
                    </label>
                </div>
                
                <div class="wsg-setting-row">
                    <div class="wsg-setting-info">
                        <h4>Disable XML-RPC</h4>
                        <p>Close the WordPress remote API used heavily by DDoS botnets.</p>
                    </div>
                    <label class="wsg-toggle">
                        <input type="checkbox" name="wsg_xmlrpc_disable" value="1" <?php checked(1,get_option('wsg_xmlrpc_disable'),true); ?>>
                        <span class="wsg-slider"></span>
                    </label>
                </div>
                
                <div class="wsg-setting-row">
                    <div class="wsg-setting-info">
                        <h4>HTTP Security Headers</h4>
                        <p>Enforce strict browser caching and block cross-site framing (Clickjacking protection).</p>
                    </div>
                    <label class="wsg-toggle">
                        <input type="checkbox" name="wsg_security_headers" value="1" <?php checked(1,get_option('wsg_security_headers'),true); ?>>
                        <span class="wsg-slider"></span>
                    </label>
                </div>
            </div>

            <div class="wsg-card">
                <h2 class="wsg-card-title">Login Stealth Configuration</h2>
                
                <div class="wsg-setting-info" style="margin-bottom: 16px;">
                    <h4>Custom Login Path</h4>
                    <p>Obscure your WordPress login portal from automated scanners. (Default is usually `wp-login.php`)</p>
                </div>
                
                <input type="text" name="wsg_login_slug" value="<?php echo esc_attr($slug); ?>" class="wsg-input-text" placeholder="e.g. secure-login">
                
                <div class="wsg-url-preview">
                    Your active secure URL: <strong><?php echo home_url('/'.$slug); ?></strong>
                </div>
                
                <div class="wsg-alert">
                    <p>⚠️ Important: Modifying this field immediately disables `wp-login.php`. Please bookmark your new URL before saving!</p>
                </div>
            </div>

            <p class="submit">
                <button type="submit" name="submit" class="button button-primary button-wsg-primary">Save Security Configuration</button>
            </p>
        </form>
    </div>
    <?php
}
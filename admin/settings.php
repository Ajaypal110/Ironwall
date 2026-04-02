<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function irw_settings_page() {
	$slug = get_option( 'irw_login_slug', 'secure-entrance' );
	?>
	<div class="wrap wsg-wrap">
		<div class="wsg-settings-header">
			<div>
				<h1><?php esc_html_e( 'Settings', 'ironwall' ); ?></h1>
				<p><?php esc_html_e( 'Configure your security shield and hardening parameters.', 'ironwall' ); ?></p>
			</div>
			<div>
				<svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="var(--wsg-accent, #818cf8)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="opacity:0.6;"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
			</div>
		</div>

		<?php settings_errors( 'irw_settings_group' ); ?>

		<form method="post" action="options.php" class="wsg-settings-form">
			<?php settings_fields( 'irw_settings_group' ); ?>

			<div class="wsg-dashboard-grid settings-grid">
				<div class="wsg-col-main">
					<div class="wsg-card">
						<h2>
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--wsg-accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
							<?php esc_html_e( 'Core Protection Modules', 'ironwall' ); ?>
						</h2>

						<div class="wsg-setting-row">
							<div class="wsg-setting-info">
								<h4><?php esc_html_e( 'Brute Force Protection', 'ironwall' ); ?></h4>
								<p><?php esc_html_e( 'Automatically detect and block repeated failed login attempts.', 'ironwall' ); ?></p>
							</div>
							<label class="wsg-toggle">
								<input type="checkbox" name="irw_login_protection" value="1" <?php checked( 1, get_option( 'irw_login_protection' ), true ); ?>>
								<span class="wsg-slider"></span>
							</label>
						</div>

						<div class="wsg-setting-row">
							<div class="wsg-setting-info">
								<h4><?php esc_html_e( 'Disable XML-RPC', 'ironwall' ); ?></h4>
								<p><?php esc_html_e( 'Disable the legacy XML-RPC API to prevent pingback and brute-force attacks.', 'ironwall' ); ?></p>
							</div>
							<label class="wsg-toggle">
								<input type="checkbox" name="irw_xmlrpc_disable" value="1" <?php checked( 1, get_option( 'irw_xmlrpc_disable' ), true ); ?>>
								<span class="wsg-slider"></span>
							</label>
						</div>

						<div class="wsg-setting-row">
							<div class="wsg-setting-info">
								<h4><?php esc_html_e( 'Security Headers', 'ironwall' ); ?></h4>
								<p><?php esc_html_e( 'Inject X-Frame-Options, X-XSS-Protection, Content-Type-Options and Referrer-Policy headers.', 'ironwall' ); ?></p>
							</div>
							<label class="wsg-toggle">
								<input type="checkbox" name="irw_security_headers" value="1" <?php checked( 1, get_option( 'irw_security_headers' ), true ); ?>>
								<span class="wsg-slider"></span>
							</label>
						</div>
					</div>

					<div class="wsg-card">
						<h2>
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--wsg-warning)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
							<?php esc_html_e( 'Login Stealth (Cloaking)', 'ironwall' ); ?>
						</h2>
						<div class="wsg-setting-info" style="margin-bottom: 18px;">
							<h4><?php esc_html_e( 'Custom Login Path', 'ironwall' ); ?></h4>
							<p><?php esc_html_e( 'Obscure your WordPress login portal from automated bots and scanners.', 'ironwall' ); ?></p>
						</div>

						<input type="text" name="irw_login_slug" value="<?php echo esc_attr( $slug ); ?>" class="wsg-input-text" placeholder="<?php esc_attr_e( 'e.g. secure-login', 'ironwall' ); ?>">

						<div class="wsg-url-preview">
							<?php esc_html_e( 'Your active secure URL:', 'ironwall' ); ?> <strong><?php echo esc_url( home_url( '/' . $slug ) ); ?></strong>
						</div>

						<div class="wsg-alert">
							<p>⚠️ <?php esc_html_e( 'Important: Modifying this field immediately disables wp-login.php. Bookmark your new URL before saving!', 'ironwall' ); ?></p>
						</div>
					</div>
				</div>

				<div class="wsg-col-side">
					<div class="wsg-card">
						<h2 style="justify-content:center;">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--wsg-success)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
							<?php esc_html_e( 'Save Changes', 'ironwall' ); ?>
						</h2>
						<p style="color: var(--wsg-text-dim); font-size:13px; margin:0 0 20px; text-align:center; line-height:1.6;">
							<?php esc_html_e( 'Review your settings carefully. Changes take effect immediately on save.', 'ironwall' ); ?>
						</p>
						<?php submit_button( __( 'Apply Settings', 'ironwall' ), 'button-primary button-wsg-primary', 'submit', true, array( 'style' => 'width:100%;justify-content:center;' ) ); ?>
					</div>

					<div class="wsg-card">
						<h2 style="justify-content:center;">
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--wsg-info)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
							<?php esc_html_e( 'Quick Info', 'ironwall' ); ?>
						</h2>
						<ul class="wsg-list">
							<li>
								<span style="color:var(--wsg-text-muted);font-size:13px;"><?php esc_html_e( 'Plugin Version', 'ironwall' ); ?></span>
								<span class="wsg-status-badge wsg-status-on"><?php echo esc_html( defined( 'IRW_VERSION' ) ? IRW_VERSION : '5.0' ); ?></span>
							</li>
							<li>
								<span style="color:var(--wsg-text-muted);font-size:13px;"><?php esc_html_e( 'PHP Version', 'ironwall' ); ?></span>
								<span style="color:var(--wsg-text);font-size:13px;font-weight:600;"><?php echo esc_html( phpversion() ); ?></span>
							</li>
							<li>
								<span style="color:var(--wsg-text-muted);font-size:13px;"><?php esc_html_e( 'WordPress', 'ironwall' ); ?></span>
								<span style="color:var(--wsg-text);font-size:13px;font-weight:600;"><?php echo esc_html( get_bloginfo( 'version' ) ); ?></span>
							</li>
						</ul>
					</div>
				</div>
			</div>
		</form>
	</div>
	<?php
}
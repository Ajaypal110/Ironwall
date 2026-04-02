<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function irw_dashboard() {
	global $wpdb;

	$login   = get_option( 'irw_login_protection' );
	$xmlrpc  = get_option( 'irw_xmlrpc_disable' );
	$headers = get_option( 'irw_security_headers' );

	// Calculate Score.
	$score = 35;
	if ( $login ) {
		$score += 20;
	}
	if ( $xmlrpc ) {
		$score += 15;
	}
	if ( $headers ) {
		$score += 15;
	}
	if ( defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT ) {
		$score += 15;
	}

	// Get basic stats.
	$table_logs    = $wpdb->prefix . 'irw_logs';
	$event_count   = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM $table_logs WHERE created > %s", gmdate( 'Y-m-d 00:00:00' ) ) );
	$blocked_count = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $table_logs WHERE severity = 'high'" );

	$table_traffic = $wpdb->prefix . 'irw_live_traffic';
	$active_ips    = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT ip) FROM $table_traffic WHERE created > NOW() - INTERVAL 1 HOUR" );

	$recent_blocks = $wpdb->get_results( "SELECT ip, event_type as reason, created FROM $table_logs WHERE severity = 'high' ORDER BY created DESC LIMIT 5" );
	$last_scan     = get_option( 'irw_last_scan', __( 'Never', 'ironwall' ) );

	$score_pct = min( $score, 100 );
	?>
	<div class="wrap wsg-wrap">
		<div class="wsg-topbar">
			<div>
				<h1><?php esc_html_e( 'Ironwall', 'ironwall' ); ?></h1>
				<p><?php esc_html_e( 'Enterprise Security Suite — Real-time Monitoring & Firewall', 'ironwall' ); ?></p>
			</div>
			<div style="display:flex; gap:10px; align-items:center;">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ironwall-scanner' ) ); ?>" class="wsg-btn">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
					<?php esc_html_e( 'Run Scan', 'ironwall' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=ironwall-settings' ) ); ?>" class="wsg-btn" style="background: rgba(255,255,255,0.08) !important; box-shadow: none !important; border: 1px solid rgba(255,255,255,0.12) !important;">
					<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
					<?php esc_html_e( 'Settings', 'ironwall' ); ?>
				</a>
			</div>
		</div>

		<h3 class="wsg-section-title"><?php esc_html_e( 'Visual Security Analytics', 'ironwall' ); ?></h3>
		<div class="wsg-dashboard-grid analytics-grid">
			<div class="wsg-stat-card">
				<div class="card-header">
					<h3><?php esc_html_e( 'Security Event Trends (7 Days)', 'ironwall' ); ?></h3>
				</div>
				<div class="chart-container">
					<canvas id="wsg-events-chart"></canvas>
				</div>
			</div>
			<div class="wsg-stat-card">
				<div class="card-header">
					<h3><?php esc_html_e( 'Traffic Composition', 'ironwall' ); ?></h3>
				</div>
				<div class="chart-container">
					<canvas id="wsg-traffic-chart"></canvas>
				</div>
			</div>
		</div>

		<div class="wsg-dashboard-grid main-grid">
			<div class="wsg-col-main">
				<div class="wsg-card">
					<h2>
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--wsg-accent)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
						<?php esc_html_e( 'Web Application Firewall', 'ironwall' ); ?>
					</h2>
					<div class="wsg-metric-row">
						<div class="wsg-metric">
							<div class="wsg-metric-val"><?php echo esc_html( number_format( $event_count ) ); ?></div>
							<div class="wsg-metric-label"><?php esc_html_e( 'Events Today', 'ironwall' ); ?></div>
						</div>
						<div class="wsg-metric">
							<div class="wsg-metric-val"><?php echo esc_html( number_format( $blocked_count ) ); ?></div>
							<div class="wsg-metric-label"><?php esc_html_e( 'Threats Blocked', 'ironwall' ); ?></div>
						</div>
					</div>
				</div>

				<div class="wsg-card">
					<h2>
						<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--wsg-danger)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
						<?php esc_html_e( 'Recent Firewall Blocks', 'ironwall' ); ?>
					</h2>
					<?php if ( empty( $recent_blocks ) ) : ?>
						<div class="wsg-empty">
							<p><?php esc_html_e( 'No blocks recorded. All traffic is clean.', 'ironwall' ); ?></p>
						</div>
					<?php else : ?>
						<table class="wsg-table">
							<thead>
								<tr>
									<th><?php esc_html_e( 'IP Address', 'ironwall' ); ?></th>
									<th><?php esc_html_e( 'Reason / Rule', 'ironwall' ); ?></th>
									<th><?php esc_html_e( 'Time', 'ironwall' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $recent_blocks as $block ) : ?>
									<tr>
										<td><span class="ip-address"><?php echo esc_html( $block->ip ); ?></span></td>
										<td><span class="wsg-chip chip-blocked"><?php echo esc_html( $block->reason ); ?></span></td>
										<td style="color: var(--wsg-text-dim); white-space: nowrap;">
											<?php
											echo esc_html(
												human_time_diff( strtotime( $block->created ), current_time( 'timestamp' ) )
												. ' ' . __( 'ago', 'ironwall' )
											);
											?>
										</td>
									</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
					<?php endif; ?>
				</div>
			</div>

			<div class="wsg-col-side">
				<div class="wsg-card score-card">
					<h2 style="justify-content: center;">
						<?php esc_html_e( 'Security Score', 'ironwall' ); ?>
					</h2>
					<div class="wsg-score-wrapper" style="--score-pct: <?php echo (int) $score_pct; ?>%;">
						<div class="wsg-score-val"><?php echo (int) $score; ?></div>
					</div>
					<div class="wsg-score-label" style="text-align:center; margin-bottom:8px;"><?php esc_html_e( 'OUT OF 100', 'ironwall' ); ?></div>
					<p class="last-scan" style="text-align:center;">
						<?php
						printf(
							/* translators: %s: date of last scan */
							esc_html__( 'Last Scan: %s', 'ironwall' ),
							esc_html( $last_scan )
						);
						?>
					</p>
				</div>

				<div class="wsg-card">
					<h2>
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--wsg-success)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
						<?php esc_html_e( 'Feature Status', 'ironwall' ); ?>
					</h2>
					<ul class="wsg-list">
						<li>
							<span><?php esc_html_e( 'Advanced WAF', 'ironwall' ); ?></span>
							<span class="wsg-status-badge wsg-status-on"><?php esc_html_e( 'Active', 'ironwall' ); ?></span>
						</li>
						<li>
							<span><?php esc_html_e( 'Login Protection', 'ironwall' ); ?></span>
							<span class="wsg-status-badge <?php echo $login ? 'wsg-status-on' : 'wsg-status-off'; ?>">
								<?php echo $login ? esc_html__( 'Active', 'ironwall' ) : esc_html__( 'Inactive', 'ironwall' ); ?>
							</span>
						</li>
						<li>
							<span><?php esc_html_e( 'Security Headers', 'ironwall' ); ?></span>
							<span class="wsg-status-badge <?php echo $headers ? 'wsg-status-on' : 'wsg-status-off'; ?>">
								<?php echo $headers ? esc_html__( 'Active', 'ironwall' ) : esc_html__( 'Inactive', 'ironwall' ); ?>
							</span>
						</li>
						<li>
							<span><?php esc_html_e( 'XML-RPC', 'ironwall' ); ?></span>
							<span class="wsg-status-badge <?php echo $xmlrpc ? 'wsg-status-on' : 'wsg-status-off'; ?>">
								<?php echo $xmlrpc ? esc_html__( 'Disabled', 'ironwall' ) : esc_html__( 'Open', 'ironwall' ); ?>
							</span>
						</li>
						<li>
							<span><?php esc_html_e( 'File Editor', 'ironwall' ); ?></span>
							<span class="wsg-status-badge <?php echo ( defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT ) ? 'wsg-status-on' : 'wsg-status-off'; ?>">
								<?php echo ( defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT ) ? esc_html__( 'Secured', 'ironwall' ) : esc_html__( 'Open', 'ironwall' ); ?>
							</span>
						</li>
					</ul>
				</div>
			</div>
		</div>
	</div>
	<?php
}
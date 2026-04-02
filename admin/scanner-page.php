<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function irw_scanner_page() {
	global $wpdb;

	// Handle quarantine action.
	if ( isset( $_GET['irw_quarantine'], $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'irw_quarantine_action' ) ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'ironwall' ) );
		}
		$target_file = sanitize_text_field( urldecode( $_GET['irw_quarantine'] ) );
		if ( file_exists( $target_file ) && 0 === strpos( $target_file, ABSPATH ) ) {
			$quarantine_name = $target_file . '.wsg-quarantined';
			if ( @rename( $target_file, $quarantine_name ) ) {
				$wpdb->delete( $wpdb->prefix . 'irw_scan_results', array( 'file_path' => $target_file ) );
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'File successfully quarantined.', 'ironwall' ) . '</p></div>';
			}
		}
	}

	$results   = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}irw_scan_results ORDER BY severity DESC, created DESC" );
	$last_scan = get_option( 'irw_last_scan', __( 'Never', 'ironwall' ) );
	$res_count = count( $results );

	?>
	<div class="wrap wsg-wrap">
		<div class="wsg-header">
			<div>
				<h1><?php esc_html_e( 'Integrity Scanner', 'ironwall' ); ?></h1>
				<p><?php esc_html_e( 'Deep algorithmic scans on themes, plugins, and WordPress core files.', 'ironwall' ); ?></p>
			</div>
		</div>

		<div class="wsg-scan-hero">
			<div class="wsg-scan-info">
				<h2 id="wsg-scan-status"><?php esc_html_e( 'Scanner Status: Ready', 'ironwall' ); ?></h2>
				<p><?php esc_html_e( 'Last Analysis:', 'ironwall' ); ?> <strong id="wsg-last-scan-display" style="color:var(--wsg-accent);"><?php echo esc_html( $last_scan ); ?></strong></p>

				<!-- Progress Bar -->
				<div id="wsg-scan-progress-container" style="display:none; margin-top:24px; width:100%; max-width:420px;">
					<div style="display:flex; justify-content:space-between; margin-bottom:8px; font-size:12px; font-weight:600; letter-spacing:0.04em;">
						<span id="wsg-scan-step" style="color:var(--wsg-text-muted);"><?php esc_html_e( 'Initializing...', 'ironwall' ); ?></span>
						<span id="wsg-scan-perc" style="color:var(--wsg-accent);">0%</span>
					</div>
					<div style="background:rgba(255,255,255,0.06); border-radius:10px; height:6px; overflow:hidden;">
						<div id="wsg-scan-bar" style="background:var(--wsg-gradient); width:0%; height:100%; transition:width 0.4s cubic-bezier(0.4,0,0.2,1); border-radius:10px;"></div>
					</div>
				</div>
			</div>
			<div>
				<button id="wsg-start-scan-btn" class="btn-pulse">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
					<?php esc_html_e( 'Start Deep Scan', 'ironwall' ); ?>
				</button>
			</div>
		</div>

		<div class="wsg-results-card">
			<div class="wsg-results-header" style="display:flex;justify-content:space-between;align-items:center;">
				<h3><?php esc_html_e( 'Threats & Deviations', 'ironwall' ); ?></h3>
				<?php if ( $res_count > 0 ) : ?>
					<span class="wsg-badge badge-critical" style="font-size:11px;"><?php echo esc_html( $res_count ); ?> <?php esc_html_e( 'found', 'ironwall' ); ?></span>
				<?php endif; ?>
			</div>

			<div id="wsg-scan-results-container">
				<?php if ( empty( $results ) ) : ?>
					<div class="wsg-empty-state">
						<div class="wsg-empty-icon">🛡️</div>
						<p class="wsg-empty-text"><?php esc_html_e( 'Zero Threats Detected', 'ironwall' ); ?></p>
						<p class="wsg-empty-subtext"><?php esc_html_e( 'Your files are pristine. No malware or core modifications found.', 'ironwall' ); ?></p>
					</div>
				<?php else : ?>
					<table class="wsg-modern-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Location / Target', 'ironwall' ); ?></th>
								<th><?php esc_html_e( 'Severity', 'ironwall' ); ?></th>
								<th><?php esc_html_e( 'Details', 'ironwall' ); ?></th>
								<th><?php esc_html_e( 'Resolution', 'ironwall' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $results as $res ) : ?>
								<tr>
									<td><span class="code-path"><?php echo esc_html( $res->file_path ); ?></span></td>
									<td>
										<?php
										$sev_class = 'badge-medium';
										if ( 'critical' === strtolower( $res->severity ) ) {
											$sev_class = 'badge-critical';
										} elseif ( 'high' === strtolower( $res->severity ) ) {
											$sev_class = 'badge-high';
										}
										?>
										<span class="wsg-badge <?php echo esc_attr( $sev_class ); ?>">
											<?php echo esc_html( strtoupper( $res->severity ) ); ?>
										</span>
									</td>
									<td style="max-width:320px;">
										<div style="color:#e2e8f0; font-size:13px; margin-bottom: 4px; font-weight: 500;">
											<?php echo esc_html( $res->issue_type ); ?>
										</div>
										<div style="color:var(--wsg-text-dim); font-size:12px; line-height: 1.4;">
											<?php echo esc_html( $res->details ); ?>
										</div>
									</td>
									<td style="max-width:300px;">
										<?php
										$suggestion = '';
										$sug_color = '#94a3b8';
										if ( strpos( $res->issue_type, 'Missing Core' ) !== false ) {
											$suggestion = __( 'Suggestion: Re-install WordPress core to safely restore this missing file.', 'ironwall' );
											$sug_color  = '#facc15';
										} elseif ( strpos( $res->issue_type, 'Core File Modified' ) !== false ) {
											$suggestion = __( 'Suggestion: Core file altered. Overwrite immediately with official WP repository file.', 'ironwall' );
											$sug_color  = '#f87171';
										} elseif ( strpos( $res->issue_type, 'Malware Signature' ) !== false ) {
											$suggestion = __( 'Suggestion: High risk payload detected. Quarantine the file to secure your site.', 'ironwall' );
											$sug_color  = '#f87171';
										} elseif ( strpos( $res->issue_type, 'Highly Obfuscated' ) !== false ) {
											$suggestion = __( 'Suggestion: Suspicious encoding. Manually inspect code for hidden backdoors.', 'ironwall' );
											$sug_color  = '#fb923c';
										} elseif ( strpos( $res->issue_type, 'Security Misconfiguration' ) !== false ) {
											if ( strpos( $res->details, 'admin' ) !== false ) {
												$suggestion = __( 'Suggestion: Create a new administrator account and delete the "admin" user.', 'ironwall' );
											} else {
												$suggestion = __( 'Suggestion: Update your WordPress settings to secure this vulnerability.', 'ironwall' );
											}
											$sug_color  = '#fb923c';
										} else {
											$suggestion = __( 'Suggestion: Review file context for potential anomalies.', 'ironwall' );
										}
										?>
										<div style="color:<?php echo esc_attr( $sug_color ); ?>; font-size:11.5px; font-weight: 500; display:flex; align-items:flex-start; gap:6px; background: rgba(0,0,0,0.2); padding: 8px 10px; border-radius: 6px; border-left: 2px solid <?php echo esc_attr( $sug_color ); ?>; margin-bottom: 10px;">
											<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0; margin-top: 1px;"><circle cx="12" cy="12" r="10"></circle><path d="M12 16v-4"></path><path d="M12 8h.01"></path></svg>
											<span><?php echo esc_html( $suggestion ); ?></span>
										</div>

										<?php if ( 'critical' === $res->severity || 'high' === $res->severity ) : ?>
											<?php $q_url = wp_nonce_url( '?page=ironwall-scanner&irw_quarantine=' . rawurlencode( $res->file_path ), 'irw_quarantine_action' ); ?>
											<a href="<?php echo esc_url( $q_url ); ?>" class="btn-action-small btn-delete">
												<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px;margin-right:3px;"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
												<?php esc_html_e( 'Quarantine', 'ironwall' ); ?>
											</a>
										<?php else : ?>
											<span style="color:var(--wsg-text-dim); font-size:11px; text-transform:uppercase; letter-spacing:0.06em; font-weight:600;"><?php esc_html_e( 'Info Only', 'ironwall' ); ?></span>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<script>
	jQuery(document).ready(function($) {
		let totalFiles = 0;
		let processedFiles = 0;
		const nonce = '<?php echo esc_js( wp_create_nonce( 'irw_scan_nonce' ) ); ?>';

		$('#wsg-start-scan-btn').on('click', function() {
			const btn = $(this);
			btn.prop('disabled', true).css('opacity', 0.6);
			btn.html('<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="animation:spin 1s linear infinite;"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg> <?php echo esc_js( __( 'Initializing...', 'ironwall' ) ); ?>');
			$('#wsg-scan-status').text('<?php echo esc_js( __( 'Scanner Status: Initializing...', 'ironwall' ) ); ?>');
			$('#wsg-scan-progress-container').fadeIn(300);
			startScan();
		});

		function startScan() {
			$.post(ajaxurl, {
				action: 'irw_start_scan',
				nonce: nonce
			}, function(response) {
				if (response.success) {
					totalFiles = response.data.total;
					$('#wsg-scan-step').text('<?php echo esc_js( __( 'Scanning Files', 'ironwall' ) ); ?> (0 / ' + totalFiles + ')');
					$('#wsg-start-scan-btn').html('<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="animation:spin 1s linear infinite;"><circle cx="12" cy="12" r="10"></circle><path d="M12 6v6l4 2"></path></svg> <?php echo esc_js( __( 'Scanning...', 'ironwall' ) ); ?>');
					batchScan();
				} else {
					alert('<?php echo esc_js( __( 'Scan failed to initialize.', 'ironwall' ) ); ?>');
					resetUI();
				}
			});
		}

		function batchScan() {
			$.post(ajaxurl, {
				action: 'irw_batch_scan',
				nonce: nonce
			}, function(response) {
				if (response.success) {
					if (response.data.done) {
						completeScan();
					} else {
						processedFiles = response.data.progress;
						let perc = Math.round((processedFiles / totalFiles) * 100);
						$('#wsg-scan-bar').css('width', perc + '%');
						$('#wsg-scan-perc').text(perc + '%');
						$('#wsg-scan-step').text('<?php echo esc_js( __( 'Scanning Files', 'ironwall' ) ); ?> (' + processedFiles + ' / ' + totalFiles + ')');
						batchScan();
					}
				} else {
					alert('<?php echo esc_js( __( 'Batch scan error.', 'ironwall' ) ); ?>');
					resetUI();
				}
			});
		}

		function completeScan() {
			$('#wsg-scan-bar').css('width', '100%');
			$('#wsg-scan-perc').text('100%');
			$('#wsg-scan-status').text('<?php echo esc_js( __( 'Scanner Status: Complete ✓', 'ironwall' ) ); ?>');
			$('#wsg-scan-step').text('<?php echo esc_js( __( 'Scan Finished Successfully!', 'ironwall' ) ); ?>');
			$('#wsg-start-scan-btn').html('✓ <?php echo esc_js( __( 'Complete', 'ironwall' ) ); ?>').css('opacity', 1);
			setTimeout(() => { location.reload(); }, 1200);
		}

		function resetUI() {
			$('#wsg-start-scan-btn').prop('disabled', false).css('opacity', 1)
				.html('<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg> <?php echo esc_js( __( 'Start Deep Scan', 'ironwall' ) ); ?>');
			$('#wsg-scan-progress-container').hide();
		}
	});
	</script>

	<style>
		@keyframes spin {
			from { transform: rotate(0deg); }
			to   { transform: rotate(360deg); }
		}
	</style>
	<?php
}

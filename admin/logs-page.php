<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function irw_logs_page() {
	global $wpdb;

	$table = $wpdb->prefix . 'irw_logs';
	$logs  = $wpdb->get_results( "SELECT * FROM $table ORDER BY id DESC LIMIT 100" );
	$count = count( $logs );
	?>
	<div class="wrap wsg-wrap">
		<div class="wsg-header">
			<div>
				<h1><?php esc_html_e( 'Audit Logs', 'ironwall' ); ?></h1>
				<p><?php esc_html_e( 'A raw, unfiltered history of significant security events.', 'ironwall' ); ?></p>
			</div>
			<div style="display:flex; gap:8px; align-items:center;">
				<a href="<?php echo esc_url( admin_url( 'admin-post.php?action=irw_export_logs' ) ); ?>" class="button button-secondary">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;margin-right:4px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
					<?php esc_html_e( 'Export CSV', 'ironwall' ); ?>
				</a>
			</div>
		</div>

		<div class="wsg-card" style="padding:0; overflow:hidden;">
			<div class="wsg-card-header" style="padding:18px 28px; display:flex; justify-content:space-between; align-items:center;">
				<h3><?php esc_html_e( 'Latest Events', 'ironwall' ); ?></h3>
				<span style="color:var(--wsg-text-dim); font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.06em;">
					<?php
					printf(
						/* translators: %d: number of events shown */
						esc_html__( '%d events', 'ironwall' ),
						$count
					);
					?>
				</span>
			</div>

			<?php if ( empty( $logs ) ) : ?>
				<div class="wsg-empty-state">
					<div class="wsg-empty-icon">📋</div>
					<p class="wsg-empty-text"><?php esc_html_e( 'No Audit Logs Yet', 'ironwall' ); ?></p>
					<p class="wsg-empty-subtext"><?php esc_html_e( 'Security events will appear here as they are detected.', 'ironwall' ); ?></p>
				</div>
			<?php else : ?>
				<table class="wsg-modern-table">
					<thead>
						<tr>
							<th style="width:50px;"><?php esc_html_e( 'ID', 'ironwall' ); ?></th>
							<th><?php esc_html_e( 'Timestamp', 'ironwall' ); ?></th>
							<th><?php esc_html_e( 'Event', 'ironwall' ); ?></th>
							<th><?php esc_html_e( 'IP Address', 'ironwall' ); ?></th>
							<th><?php esc_html_e( 'Details', 'ironwall' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ( $logs as $log ) :
							$raw_time   = $log->created;
							$chip_class = 'chip-default';
							$ev         = strtolower( $log->event );
							if ( false !== strpos( $ev, 'block' ) || false !== strpos( $ev, 'fail' ) ) {
								$chip_class = 'chip-blocked';
							} elseif ( false !== strpos( $ev, 'login' ) || false !== strpos( $ev, 'auth' ) ) {
								$chip_class = 'chip-login';
							}
							?>
							<tr>
								<td style="color:var(--wsg-text-dim); font-size:12px;">#<?php echo esc_html( $log->id ); ?></td>
								<td>
									<span class="time-date irw-localize-date" data-timestamp="<?php echo esc_attr( get_gmt_from_date( $raw_time ) . 'Z' ); ?>"><?php echo esc_html( mysql2date( 'M j, Y', $raw_time ) ); ?></span>
									<span class="time-hour irw-localize-time" data-timestamp="<?php echo esc_attr( get_gmt_from_date( $raw_time ) . 'Z' ); ?>"><?php echo esc_html( mysql2date( 'H:i:s', $raw_time ) ); ?></span>
								</td>
								<td><span class="wsg-chip <?php echo esc_attr( $chip_class ); ?>"><?php echo esc_html( $log->event ); ?></span></td>
								<td><span class="ip-address"><?php echo esc_html( $log->ip ); ?></span></td>
								<td style="color:var(--wsg-text-dim); font-size:13px; max-width:300px;">
									<?php
									if ( ! empty( $log->username ) ) {
										echo '<strong style="color:var(--wsg-text);">' . esc_html( $log->username ) . '</strong> &mdash; ';
									}
									echo esc_html( $log->details );
									?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
	</div>
	<?php
}
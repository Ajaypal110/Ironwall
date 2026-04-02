<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function irw_live_traffic_page() {
	global $wpdb;

	$table_traffic = $wpdb->prefix . 'irw_live_traffic';

	// Clear logs if requested.
	if ( isset( $_POST['irw_clear_traffic'] ) && check_admin_referer( 'irw_clear_traffic_action', 'irw_traffic_nonce' ) ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'ironwall' ) );
		}
		$wpdb->query( "TRUNCATE TABLE $table_traffic" );
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Live traffic log successfully cleared.', 'ironwall' ) . '</p></div>';
	}

	// Pagination.
	$per_page    = 50;
	$page        = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
	$offset      = ( $page - 1 ) * $per_page;
	$total_items = (int) $wpdb->get_var( "SELECT COUNT(id) FROM $table_traffic" );
	$total_pages = max( 1, (int) ceil( $total_items / $per_page ) );
	$results     = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_traffic ORDER BY created DESC LIMIT %d OFFSET %d", $per_page, $offset ) );
	?>
	<div class="wrap wsg-wrap">
		<div class="wsg-top">
			<div>
				<h1><?php esc_html_e( 'Live Traffic', 'ironwall' ); ?></h1>
				<p style="color: var(--wsg-text-muted); margin: 6px 0 0;">
					<?php
					printf(
						/* translators: 1: total items, 2: current page, 3: total pages */
						esc_html__( '%1$s requests logged — page %2$s of %3$s', 'ironwall' ),
						'<strong style="color:var(--wsg-text);">' . number_format( $total_items ) . '</strong>',
						'<strong style="color:var(--wsg-text);">' . $page . '</strong>',
						'<strong style="color:var(--wsg-text);">' . $total_pages . '</strong>'
					);
					?>
				</p>
			</div>
			<div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
				<form method="post" action="" style="display:inline-block; margin:0;">
					<?php wp_nonce_field( 'irw_clear_traffic_action', 'irw_traffic_nonce' ); ?>
					<button type="submit" name="irw_clear_traffic" class="button button-secondary" onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to clear all traffic logs?', 'ironwall' ); ?>');">
						<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;margin-right:4px;"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
						<?php esc_html_e( 'Clear', 'ironwall' ); ?>
					</button>
				</form>
				<a href="<?php echo esc_url( admin_url( 'admin-post.php?action=irw_export_traffic' ) ); ?>" class="button button-secondary">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-2px;margin-right:4px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
					<?php esc_html_e( 'Export CSV', 'ironwall' ); ?>
				</a>
				<a href="<?php echo esc_url( add_query_arg( 'paged', $page ) ); ?>" class="wsg-btn" style="padding: 9px 20px !important; font-size:13px !important;">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="23 4 23 10 17 10"></polyline><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"></path></svg>
					<?php esc_html_e( 'Refresh', 'ironwall' ); ?>
				</a>
			</div>
		</div>

		<div class="wsg-card" style="padding:0; overflow:hidden;">
			<table class="wsg-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Time', 'ironwall' ); ?></th>
						<th><?php esc_html_e( 'Type', 'ironwall' ); ?></th>
						<th><?php esc_html_e( 'IP Address', 'ironwall' ); ?></th>
						<th><?php esc_html_e( 'Method', 'ironwall' ); ?></th>
						<th><?php esc_html_e( 'URL', 'ironwall' ); ?></th>
						<th><?php esc_html_e( 'User Agent', 'ironwall' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $results ) ) : ?>
						<tr>
							<td colspan="6" style="text-align:center; padding:48px 24px; color:var(--wsg-text-dim);">
								<?php esc_html_e( 'No traffic logged yet. Requests will appear here in real-time.', 'ironwall' ); ?>
							</td>
						</tr>
					<?php else : ?>
						<?php foreach ( $results as $res ) : ?>
							<tr>
								<td style="white-space:nowrap;">
									<span class="time-date"><?php echo esc_html( mysql2date( 'H:i:s', $res->created ) ); ?></span>
									<span class="time-hour"><?php echo esc_html( mysql2date( 'M j', $res->created ) ); ?></span>
								</td>
								<td>
									<?php if ( $res->is_bot ) : ?>
										<span class="wsg-bot"><?php esc_html_e( 'Bot', 'ironwall' ); ?></span>
									<?php else : ?>
										<span class="wsg-human"><?php esc_html_e( 'Human', 'ironwall' ); ?></span>
									<?php endif; ?>
								</td>
								<td><span class="ip-address"><?php echo esc_html( $res->ip ); ?></span></td>
								<td>
									<span class="wsg-method <?php echo 'post' === strtolower( $res->method ) ? 'wsg-method-post' : 'wsg-method-get'; ?>">
										<?php echo esc_html( strtoupper( $res->method ) ); ?>
									</span>
								</td>
								<td style="word-break:break-all; max-width:280px;">
									<code style="color:var(--wsg-text-muted);font-size:12px;background:rgba(15,23,42,0.5);padding:2px 6px;border-radius:4px;"><?php echo esc_html( $res->requested_url ); ?></code>
								</td>
								<td><span class="wsg-ua" title="<?php echo esc_attr( $res->ua ); ?>"><?php echo esc_html( $res->ua ); ?></span></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>
		</div>

		<?php if ( $total_pages > 1 ) : ?>
			<div class="wsg-pagination">
				<?php if ( $page > 1 ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'paged', $page - 1 ) ); ?>" class="button button-secondary">&laquo; <?php esc_html_e( 'Previous', 'ironwall' ); ?></a>
				<?php endif; ?>
				<?php if ( $page < $total_pages ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'paged', $page + 1 ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Next', 'ironwall' ); ?> &raquo;</a>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
	<?php
}

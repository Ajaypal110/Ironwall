<?php
if(!defined('ABSPATH')){
    exit;
}

function wsg_live_traffic_page(){
    global $wpdb;
    
    $table_traffic = $wpdb->prefix . 'wsg_live_traffic';
    
    // Clear logs if requested
    if (isset($_POST['wsg_clear_traffic']) && check_admin_referer('wsg_clear_traffic_action', 'wsg_traffic_nonce')) {
        $wpdb->query("TRUNCATE TABLE $table_traffic");
        echo '<div class="notice notice-success is-dismissible"><p>Live traffic log successfully cleared.</p></div>';
    }

    // Pagination
    $per_page = 50;
    $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $offset = ($page - 1) * $per_page;
    
    $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_traffic");
    $total_pages = ceil($total_items / $per_page);
    
    $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_traffic ORDER BY created DESC LIMIT %d OFFSET %d", $per_page, $offset));

    ?>
    <style>
        .wsg-traffic-wrap {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: #f8fafc;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            border: 1px solid #e2e8f0;
        }
        .wsg-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .wsg-top h1 { margin: 0; font-size: 24px; color: #0f172a; }
        .wsg-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .wsg-table th { background: #f1f5f9; text-align: left; padding: 12px 16px; font-weight: 600; color: #475569; border-bottom: 2px solid #e2e8f0; }
        .wsg-table td { padding: 12px 16px; border-bottom: 1px solid #e2e8f0; color: #1e293b; font-size: 13px; }
        .wsg-table tr:hover td { background: #f8fafc; }
        .wsg-method { padding: 3px 6px; border-radius: 4px; font-weight: 600; font-size: 11px; }
        .wsg-method-get { background: #dbeafe; color: #1d4ed8; }
        .wsg-method-post { background: #dcfce7; color: #15803d; }
        .wsg-bot { color: #f59e0b; font-weight: bold; font-size: 11px; text-transform: uppercase; border: 1px solid #f59e0b; padding: 2px 6px; border-radius: 4px; }
        .wsg-human { color: #10b981; font-weight: bold; font-size: 11px; text-transform: uppercase; border: 1px solid #10b981; padding: 2px 6px; border-radius: 4px; }
        .wsg-ua { font-size: 11px; color: #64748b; max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; }
        .wsg-pagination { margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px; }
    </style>

    <div class="wrap">
        <div class="wsg-traffic-wrap">
            <div class="wsg-top">
                <div>
                    <h1>📡 Live Traffic Logging</h1>
                    <p style="color: #64748b; margin: 5px 0 0;">Monitor incoming requests in real-time. Showing page <?php echo $page; ?> of <?php echo max(1, $total_pages); ?>.</p>
                </div>
                <div>
                    <form method="post" action="" style="display:inline-block;">
                        <?php wp_nonce_field('wsg_clear_traffic_action', 'wsg_traffic_nonce'); ?>
                        <button type="submit" name="wsg_clear_traffic" class="button button-secondary" onclick="return confirm('Are you sure you want to clear all traffic logs?');">Clear Logs</button>
                    </form>
                    <a href="<?php echo admin_url('admin-post.php?action=wsg_export_traffic'); ?>" class="button button-secondary">Export CSV</a>
                    <a href="?page=wsg-live-traffic" class="button button-primary">Refresh</a>
                </div>
            </div>

            <table class="wsg-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Type</th>
                        <th>IP Address</th>
                        <th>Method</th>
                        <th>URL</th>
                        <th>User Agent</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($results)): ?>
                        <tr><td colspan="6" style="text-align: center; padding: 30px; color: #64748b;">No traffic logged yet.</td></tr>
                    <?php else: ?>
                        <?php foreach($results as $res): ?>
                            <tr>
                                <td style="white-space: nowrap;"><?php echo esc_html(mysql2date('H:i:s M j', $res->created)); ?></td>
                                <td>
                                    <?php if($res->is_bot): ?>
                                        <span class="wsg-bot">Bot / Crawler</span>
                                    <?php else: ?>
                                        <span class="wsg-human">Human</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo esc_html($res->ip); ?></strong></td>
                                <td>
                                    <span class="wsg-method <?php echo strtolower($res->method) == 'post' ? 'wsg-method-post' : 'wsg-method-get'; ?>">
                                        <?php echo esc_html($res->method); ?>
                                    </span>
                                </td>
                                <td style="word-break: break-all;"><code><?php echo esc_html($res->url); ?></code></td>
                                <td><span class="wsg-ua" title="<?php echo esc_attr($res->user_agent); ?>"><?php echo esc_html($res->user_agent); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if($total_pages > 1): ?>
                <div class="wsg-pagination">
                    <?php if($page > 1): ?>
                        <a href="?page=wsg-live-traffic&paged=<?php echo $page - 1; ?>" class="button">&laquo; Previous</a>
                    <?php endif; ?>
                    <?php if($page < $total_pages): ?>
                        <a href="?page=wsg-live-traffic&paged=<?php echo $page + 1; ?>" class="button">Next &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

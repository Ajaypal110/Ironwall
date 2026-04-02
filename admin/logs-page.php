<?php
if(!defined('ABSPATH')){
    exit;
}

function wsg_logs_page(){
    global $wpdb;
    $table=$wpdb->prefix.'wsg_logs';
    $logs=$wpdb->get_results("SELECT * FROM $table ORDER BY id DESC LIMIT 100");
    ?>
    <style>
        .wsg-wrap { max-width: 1100px; margin: 20px 20px 20px 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; }
        .wsg-header { margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: flex-end; }
        .wsg-header h1 { font-size: 28px; font-weight: 600; color: #1e293b; margin: 0 0 8px; line-height: 1.2; }
        .wsg-header p { color: #64748b; font-size: 15px; margin: 0; }
        
        .wsg-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .wsg-card-header { padding: 20px 24px; border-bottom: 1px solid #f1f5f9; background: #fafafa; display: flex; justify-content: space-between; align-items: center; }
        .wsg-card-header h3 { margin: 0; font-size: 16px; font-weight: 600; color: #334155; }
        
        /* Modern Table */
        table.wsg-modern-table { width: 100%; border-collapse: collapse; text-align: left; }
        table.wsg-modern-table th { background: #fff; color: #64748b; font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; padding: 16px 24px; border-bottom: 1px solid #e2e8f0; }
        table.wsg-modern-table td { padding: 16px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; font-size: 14px; vertical-align: middle; }
        table.wsg-modern-table tr:last-child td { border-bottom: none; }
        table.wsg-modern-table tr:hover { background: #f8fafc; }
        
        /* Event Badges */
        .wsg-chip { display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; text-transform: capitalize; border: 1px solid transparent; }
        .chip-blocked { background: #fef2f2; color: #dc2626; border-color: #fecaca; }
        .chip-login { background: #eff6ff; color: #2563eb; border-color: #bfdbfe; }
        .chip-default { background: #f1f5f9; color: #475569; border-color: #cbd5e1; }
        
        .time-date { font-weight: 500; color: #0f172a; display: block; }
        .time-hour { color: #94a3b8; font-size: 12px; }
        .ip-address { font-family: ui-monospace, monospace; color: #4f46e5; font-weight: 500; background: #e0e7ff; padding: 2px 6px; border-radius: 4px; }
    </style>

    <div class="wsg-wrap">
        <div class="wsg-header">
            <div>
                <h1>Security Audit Logs</h1>
                <p>A raw, unfiltered history of significant security events within WordPress.</p>
            </div>
            <div>
                <a href="<?php echo admin_url('admin-post.php?action=wsg_export_logs'); ?>" class="button" style="color:#64748b; border-color:#cbd5e1;">Export CSV</a>
            </div>
        </div>

        <div class="wsg-card">
            <div class="wsg-card-header">
                <h3>Latest 100 Events</h3>
            </div>
            
            <?php if(empty($logs)): ?>
                <div style="padding: 40px; text-align: center; color: #64748b;">
                    <p>No audit logs recorded yet.</p>
                </div>
            <?php else: ?>
                <table class="wsg-modern-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Timestamp</th>
                            <th>Event Category</th>
                            <th>IP Address</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($logs as $log): 
                            // Time is already stored as local time via current_time('mysql')
                            // Display it directly — NO timezone conversion needed
                            $raw_time = $log->created;
                            
                            // Determine chip style
                            $chip_class = 'chip-default';
                            $ev = strtolower($log->event);
                            if (strpos($ev, 'block') !== false || strpos($ev, 'fail') !== false) {
                                $chip_class = 'chip-blocked';
                            } elseif (strpos($ev, 'login') !== false || strpos($ev, 'auth') !== false) {
                                $chip_class = 'chip-login';
                            }
                        ?>
                            <tr>
                                <td style="color:#94a3b8; font-size:13px;">#<?php echo esc_html($log->id); ?></td>
                                <td>
                                    <span class="time-date"><?php echo esc_html(date('M j, Y', strtotime($raw_time))); ?></span>
                                    <span class="time-hour"><?php echo esc_html(date('H:i:s', strtotime($raw_time))); ?></span>
                                </td>
                                <td><span class="wsg-chip <?php echo $chip_class; ?>"><?php echo esc_html($log->event); ?></span></td>
                                <td><span class="ip-address"><?php echo esc_html($log->ip); ?></span></td>
                                <td style="color:#64748b; font-size:13px;">
                                    <?php 
                                        if(!empty($log->username)) { echo "<strong>User:</strong> " . esc_html($log->username) . " | "; }
                                        echo esc_html($log->details); 
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
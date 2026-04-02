<?php
if(!defined('ABSPATH')){
    exit;
}

function wsg_dashboard(){
    global $wpdb;

    $login = get_option('wsg_login_protection');
    $xmlrpc = get_option('wsg_xmlrpc_disable');
    $headers = get_option('wsg_security_headers');
    $twofa = get_option('wsg_enable_2fa');
    
    // Calculate Score
    $score = 25; // Base
    if($login) $score += 15;
    if($twofa) $score += 20;
    if($xmlrpc) $score += 10;
    if($headers) $score += 10;
    if(defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT) $score += 20;

    // Get basic stats
    $table_blocked = $wpdb->prefix . 'wsg_blocked_ips';
    $table_logs = $wpdb->prefix . 'wsg_logs';
    
    $blocked_count = $wpdb->get_var("SELECT COUNT(id) FROM $table_blocked");
    $event_count = $wpdb->get_var("SELECT COUNT(id) FROM $table_logs WHERE created > (NOW() - INTERVAL 24 HOUR)");
    $recent_blocks = $wpdb->get_results("SELECT ip, reason, created FROM $table_blocked ORDER BY created DESC LIMIT 5");
    $last_scan = get_option('wsg_last_scan', 'Never');

    ?>
    <style>
        :root {
            --wsg-primary: #6366f1;
            --wsg-primary-hover: #4f46e5;
            --wsg-bg: #f8fafc;
            --wsg-card: #ffffff;
            --wsg-text-main: #0f172a;
            --wsg-text-muted: #64748b;
            --wsg-border: #e2e8f0;
            --wsg-success: #10b981;
            --wsg-warning: #f59e0b;
            --wsg-danger: #ef4444;
        }
        
        .wrap.wsg-wrap {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            background: var(--wsg-bg);
            margin: 20px 20px 0 0;
            padding-bottom: 40px;
        }

        .wsg-topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            padding: 30px;
            border-radius: 16px;
            color: white;
            margin-bottom: 24px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        }

        .wsg-topbar h1 {
            color: white;
            margin: 0 0 8px;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -0.025em;
        }
        
        .wsg-topbar p {
            margin: 0;
            color: #cbd5e1;
            font-size: 15px;
        }

        .wsg-btn {
            background: var(--wsg-primary);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
            box-shadow: 0 4px 6px -1px rgba(99, 102, 241, 0.2);
            display: inline-block;
        }

        .wsg-btn:hover {
            background: var(--wsg-primary-hover);
            transform: translateY(-1px);
            color: white;
        }

        .wsg-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 24px;
        }

        .wsg-card {
            background: var(--wsg-card);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.025);
            border: 1px solid rgba(226, 232, 240, 0.8);
            margin-bottom: 24px;
        }

        .wsg-card h2 {
            margin: 0 0 20px;
            font-size: 18px;
            color: var(--wsg-text-main);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .wsg-metric-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .wsg-metric {
            flex: 1;
            background: #f1f5f9;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid var(--wsg-border);
        }

        .wsg-metric-val {
            font-size: 36px;
            font-weight: 700;
            color: var(--wsg-primary);
            line-height: 1;
            margin-bottom: 8px;
        }

        .wsg-metric-label {
            color: var(--wsg-text-muted);
            font-size: 13px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .wsg-score-wrapper {
            position: relative;
            width: 180px;
            height: 180px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: conic-gradient(
                <?php echo $score > 80 ? 'var(--wsg-success)' : ($score > 50 ? 'var(--wsg-warning)' : 'var(--wsg-danger)'); ?> <?php echo $score; ?>%, 
                #f1f5f9 auto
            );
        }

        .wsg-score-inner {
            width: 150px;
            height: 150px;
            background: var(--wsg-card);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.06);
        }

        .wsg-score-val {
            font-size: 42px;
            font-weight: 800;
            color: var(--wsg-text-main);
            line-height: 1;
        }

        .wsg-score-label {
            font-size: 12px;
            color: var(--wsg-text-muted);
            margin-top: 4px;
        }

        .wsg-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .wsg-list li {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid var(--wsg-border);
        }

        .wsg-list li:last-child {
            border-bottom: none;
        }

        .wsg-status-badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .wsg-status-on { background: #dcfce7; color: #166534; }
        .wsg-status-off { background: #fee2e2; color: #991b1b; }

        .wsg-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        
        .wsg-table th {
            text-align: left;
            padding: 12px 16px;
            color: var(--wsg-text-muted);
            font-weight: 500;
            border-bottom: 2px solid var(--wsg-border);
        }

        .wsg-table td {
            padding: 16px;
            border-bottom: 1px solid var(--wsg-border);
            color: var(--wsg-text-main);
        }
        
        .wsg-table tr:hover td {
            background: #f8fafc;
        }

        .wsg-empty {
            text-align: center;
            padding: 40px 20px;
            color: var(--wsg-text-muted);
            background: #f8fafc;
            border-radius: 12px;
            border: 1px dashed var(--wsg-border);
        }

    </style>

    <div class="wrap wsg-wrap">
        <div class="wsg-topbar">
            <div>
                <h1>WP Sentinel</h1>
                <p>Advanced Security & Firewall Protection</p>
            </div>
            <div>
                <a href="?page=wsg-scanner" class="wsg-btn">Run Malware Scan</a>
            </div>
        </div>

        <div class="wsg-grid">
            <div class="wsg-col-main">
                <div class="wsg-card">
                    <h2> Web Application Firewall</h2>
                    <div class="wsg-metric-row">
                        <div class="wsg-metric">
                            <div class="wsg-metric-val"><?php echo number_format($event_count); ?></div>
                            <div class="wsg-metric-label">Events (Last 24h)</div>
                        </div>
                        <div class="wsg-metric">
                            <div class="wsg-metric-val"><?php echo number_format($blocked_count); ?></div>
                            <div class="wsg-metric-label">Total Threats Blocked</div>
                        </div>
                    </div>
                </div>

                <div class="wsg-card">
                    <h2>🛡️ Recent Firewall Blocks</h2>
                    <?php if(empty($recent_blocks)): ?>
                        <div class="wsg-empty">
                            <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="margin-bottom:10px; opacity:0.5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            <p>No recent blocks. Traffic is currently clean.</p>
                        </div>
                    <?php else: ?>
                        <table class="wsg-table">
                            <thead>
                                <tr>
                                    <th>IP Address</th>
                                    <th>Reason / Rule</th>
                                    <th>Time Generated</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recent_blocks as $block): ?>
                                    <tr>
                                        <td><strong><?php echo esc_html($block->ip); ?></strong></td>
                                        <td><?php echo esc_html($block->reason); ?></td>
                                        <td style="color:var(--wsg-text-muted);"><?php echo esc_html(date('M j, Y H:i', strtotime($block->created))); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <div class="wsg-col-side">
                <div class="wsg-card" style="text-align: center;">
                    <h2>Site Security Score</h2>
                    <div class="wsg-score-wrapper">
                        <div class="wsg-score-inner">
                            <div class="wsg-score-val"><?php echo $score; ?></div>
                            <div class="wsg-score-label">OUT OF 100</div>
                        </div>
                    </div>
                    <p style="color: var(--wsg-text-muted); font-size: 14px; margin:0;">
                        Last Scan: <?php echo esc_html($last_scan); ?>
                    </p>
                </div>

                <div class="wsg-card">
                    <h2>Feature Status</h2>
                    <ul class="wsg-list">
                        <li>
                            <span>Advanced WAF</span>
                            <span class="wsg-status-badge wsg-status-on">Active</span>
                        </li>
                        <li>
                            <span>Brute Force Protection</span>
                            <span class="wsg-status-badge <?php echo $login ? 'wsg-status-on' : 'wsg-status-off'; ?>"><?php echo $login ? 'Active' : 'Disabled'; ?></span>
                        </li>
                        <li>
                            <span>Two-Factor Auth (2FA)</span>
                            <span class="wsg-status-badge <?php echo $twofa ? 'wsg-status-on' : 'wsg-status-off'; ?>"><?php echo $twofa ? 'Active' : 'Disabled'; ?></span>
                        </li>
                        <li>
                            <span>Security Headers</span>
                            <span class="wsg-status-badge <?php echo $headers ? 'wsg-status-on' : 'wsg-status-off'; ?>"><?php echo $headers ? 'Active' : 'Disabled'; ?></span>
                        </li>
                        <li>
                            <span>File Editor Checks</span>
                            <span class="wsg-status-badge <?php echo (defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT) ? 'wsg-status-on' : 'wsg-status-off'; ?>"><?php echo (defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT) ? 'Secured' : 'Warning'; ?></span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <?php
}
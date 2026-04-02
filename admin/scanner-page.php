<?php
if(!defined('ABSPATH')){
    exit;
}

function wsg_scanner_page(){
    global $wpdb;

    // Handle scan trigger
    if (isset($_POST['wsg_run_scan']) && check_admin_referer('wsg_run_scan_action', 'wsg_scan_nonce')) {
        if (class_exists('WSG_Scanner')) {
            $scanner = new WSG_Scanner();
            $scanner->run_full_scan();
            echo '<div class="notice notice-success is-dismissible" style="border-left-color: #4f46e5;"><p><strong>Scan Complete:</strong> System analysis finished successfully!</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p>Scanner module not loaded.</p></div>';
        }
    }
    
    // Handle quarantine action
    if (isset($_GET['wsg_quarantine']) && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'wsg_quarantine_action')) {
        $target_file = sanitize_text_field(urldecode($_GET['wsg_quarantine']));
        
        // Ensure the file exists and is actually a literal file path in WP structure (preventing arbitrary string quarantines)
        if (file_exists($target_file) && strpos($target_file, ABSPATH) === 0) {
            $quarantine_name = $target_file . '.wsg-quarantined';
            if (@rename($target_file, $quarantine_name)) {
                $wpdb->delete($wpdb->prefix . 'wsg_scan_results', array('file_path' => $target_file));
                echo '<div class="notice notice-success is-dismissible"><p>File successfully quarantined and rendered inert.</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Failed to quarantine file. Check permissions.</p></div>';
            }
        } else {
            // Dismiss false positive or DB abstract string alert
            $wpdb->delete($wpdb->prefix . 'wsg_scan_results', array('file_path' => $target_file));
            echo '<div class="notice notice-info is-dismissible"><p>Threat record dismissed from scanner.</p></div>';
        }
    }

    $table_scans = $wpdb->prefix . 'wsg_scan_results';
    $results = $wpdb->get_results("SELECT * FROM $table_scans ORDER BY severity DESC, created DESC");
    $last_scan = get_option('wsg_last_scan', 'Never');

    ?>
    <style>
        .wsg-wrap { max-width: 1100px; margin: 20px 20px 20px 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; }
        .wsg-header { margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid #e2e8f0; }
        .wsg-header h1 { font-size: 28px; font-weight: 600; color: #1e293b; margin: 0 0 8px; line-height: 1.2; }
        .wsg-header p { color: #64748b; font-size: 15px; margin: 0; }
        
        /* Hero Card */
        .wsg-scan-hero { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border-radius: 12px; padding: 32px; display: flex; justify-content: space-between; align-items: center; color: white; margin-bottom: 30px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06); }
        .wsg-scan-info h2 { color: white; margin: 0 0 10px; font-size: 22px; }
        .wsg-scan-info p { color: #cbd5e1; margin: 0 0 5px; font-size: 15px; }
        .wsg-scan-info strong { color: #38bdf8; }
        
        .btn-pulse { background: #4f46e5; color: white; padding: 14px 32px; font-size: 16px; font-weight: 600; border: none; border-radius: 8px; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 0 0 0 rgba(79, 70, 229, 0.7); animation: pulse-blue 2s infinite; display: inline-flex; align-items: center; gap: 8px; }
        .btn-pulse:hover { background: #4338ca; transform: translateY(-1px); }
        .btn-pulse svg { width: 20px; height: 20px; }
        
        @keyframes pulse-blue {
            0% { transform: scale(0.98); box-shadow: 0 0 0 0 rgba(79, 70, 229, 0.7); }
            70% { transform: scale(1); box-shadow: 0 0 0 10px rgba(79, 70, 229, 0); }
            100% { transform: scale(0.98); box-shadow: 0 0 0 0 rgba(79, 70, 229, 0); }
        }

        /* Results Card & Table */
        .wsg-results-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .wsg-results-header { padding: 20px 24px; border-bottom: 1px solid #f1f5f9; background: #fafafa; }
        .wsg-results-header h3 { margin: 0; font-size: 16px; font-weight: 600; color: #334155; }
        
        table.wsg-modern-table { width: 100%; border-collapse: collapse; text-align: left; }
        table.wsg-modern-table th { background: #fff; color: #64748b; font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; padding: 16px 24px; border-bottom: 1px solid #e2e8f0; }
        table.wsg-modern-table td { padding: 16px 24px; border-bottom: 1px solid #f1f5f9; color: #334155; font-size: 14px; vertical-align: middle; }
        table.wsg-modern-table tr:last-child td { border-bottom: none; }
        
        .code-path { background: #f1f5f9; padding: 4px 8px; border-radius: 4px; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 13px; color: #475569; word-break: break-all; }
        
        /* Badges */
        .wsg-badge { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 9999px; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .badge-critical { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
        .badge-high { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; }
        .badge-medium { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        
        /* Empty State */
        .wsg-empty-state { padding: 60px 20px; text-align: center; }
        .wsg-empty-icon { font-size: 48px; margin-bottom: 16px; opacity: 0.8; }
        .wsg-empty-text { font-size: 18px; color: #334155; font-weight: 500; margin: 0 0 8px; }
        .wsg-empty-subtext { color: #64748b; font-size: 14px; margin: 0; }
        
        .btn-action-small { padding: 6px 12px; font-size: 13px; border-radius: 6px; text-decoration: none; display: inline-block; font-weight: 500; cursor: pointer; border: 1px solid transparent; }
        .btn-delete { background: #fef2f2; color: #dc2626; border-color: #fecaca; }
        .btn-delete:hover { background: #fee2e2; color: #b91c1c; }
    </style>

    <div class="wsg-wrap">
        <div class="wsg-header">
            <h1>Integrity Scanner</h1>
            <p>Perform deep algorithmic scans on your themes, plugins, and core files.</p>
        </div>

        <div class="wsg-scan-hero">
            <div class="wsg-scan-info">
                <h2>Scanner Status: Ready</h2>
                <p>Last Analysis: <strong><?php echo esc_html($last_scan); ?></strong></p>
                <p style="opacity:0.8; font-size:13px; margin-top:10px;">Connects securely to API.WordPress.org to verify core file checksums.</p>
            </div>
            <div>
                <form method="post" action="">
                    <?php wp_nonce_field('wsg_run_scan_action', 'wsg_scan_nonce'); ?>
                    <button type="submit" name="wsg_run_scan" class="btn-pulse" onclick="this.innerHTML='<svg viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'currentColor\' stroke-width=\'2\' stroke-linecap=\'round\' stroke-linejoin=\'round\'><path d=\'M21 12a9 9 0 11-6.219-8.56\'></path></svg> Scanning...'; this.style.animation='none'; this.style.opacity='0.8';">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        Start Deep Scan
                    </button>
                </form>
            </div>
        </div>

        <div class="wsg-results-card">
            <div class="wsg-results-header">
                <h3>Threats & Deviations</h3>
            </div>
            
            <?php if(empty($results)): ?>
                <div class="wsg-empty-state">
                    <div class="wsg-empty-icon">🛡️</div>
                    <p class="wsg-empty-text">Zero Threats Detected</p>
                    <p class="wsg-empty-subtext">Your files are pristine. No malware or core modifications found.</p>
                </div>
            <?php else: ?>
                <table class="wsg-modern-table">
                    <thead>
                        <tr>
                            <th>Location / Target</th>
                            <th>Detection Engine</th>
                            <th>Severity</th>
                            <th>Details</th>
                            <th>Resolution</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($results as $res): ?>
                            <tr>
                                <td><span class="code-path"><?php echo esc_html($res->file_path); ?></span></td>
                                <td><strong><?php echo esc_html($res->issue_type); ?></strong></td>
                                <td>
                                    <?php 
                                    $sev_class = 'badge-medium';
                                    if(strtolower($res->severity) == 'critical') $sev_class = 'badge-critical';
                                    if(strtolower($res->severity) == 'high') $sev_class = 'badge-high';
                                    ?>
                                    <span class="wsg-badge <?php echo $sev_class; ?>">
                                        <?php echo esc_html(strtoupper($res->severity)); ?>
                                    </span>
                                </td>
                                <td style="color:#64748b; font-size:13px;"><?php echo esc_html($res->details); ?></td>
                                <td>
                                    <?php if($res->severity === 'critical' || $res->severity === 'high'): 
                                        $q_url = wp_nonce_url('?page=wsg-scanner&wsg_quarantine=' . urlencode($res->file_path), 'wsg_quarantine_action');
                                    ?>
                                        <a href="<?php echo htmlspecialchars($q_url); ?>" class="btn-action-small btn-delete">Quarantine File</a>
                                    <?php else: ?>
                                        <span style="color:#94a3b8; font-size:12px;">Info Only</span>
                                    <?php endif; ?>
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

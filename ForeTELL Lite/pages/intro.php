<?php
$module = $GLOBALS['module'];

$window_days     = $module->getWindowDays();
$table_limit     = $module->getTableLimit(200);
[$off_start, $off_end] = $module->getOffHoursBounds();

$min_sev_dashboard = $module->getMinSeverity();
$min_sev_alerts    = $module->getSystemSetting('alert_min_severity') ?: 'high';

$thr_logins      = $module->getThreshold('threshold_suspicious_logins', 6);
$thr_ip_events   = $module->getThreshold('threshold_ip_events', 10);
$thr_ip_users    = $module->getThreshold('threshold_ip_users', 3);
$thr_user_events = $module->getThreshold('threshold_user_events', 20);
$thr_user_ips    = $module->getThreshold('threshold_user_ips', 5);

$alert_enabled   = (bool)$module->getSystemSetting('alert_email_enabled');
$alert_email     = trim((string)$module->getSystemSetting('alert_email'));
$alert_cooldown  = (int)$module->getSystemSetting('alert_cooldown_minutes') ?: 60;
?>

<div class="ftl-section-header" style="margin-top:0;">
    <i class="fas fa-shield-alt" style="margin-right:6px; color:#336699;"></i> About ForeTELL Lite
</div>
<p style="font-size:13px; color:#444; max-width:900px; line-height:1.7;">
    ForeTELL Lite is a lightweight security monitoring dashboard built as a REDCap External Module at Women's College Hospital. It analyzes authentication patterns, IP-level threats, user activity anomalies, and off-hours access using a single cached log window for fast, real-time visibility.
</p>

<div class="ftl-section-header" style="margin-top:28px;">
    <i class="fas fa-sliders-h" style="margin-right:6px; color:#336699;"></i> Current Configuration
    <span style="font-weight:400; font-size:11px; color:#aaa; margin-left:8px;">edit in Control Center → External Modules → ForeTELL Lite → Configure</span>
</div>

<div style="display:flex; flex-wrap:wrap; gap:16px; margin-bottom:8px;">
    <div class="card" style="border-left:4px solid #336699; flex:1; min-width:200px;">
        <div class="card-body" style="padding:14px 18px;">
            <div class="ftl-config-label">Analysis Window</div>
            <div class="ftl-config-value"><?= (int)$window_days ?> days</div>
        </div>
    </div>
    <div class="card" style="border-left:4px solid #336699; flex:1; min-width:200px;">
        <div class="card-body" style="padding:14px 18px;">
            <div class="ftl-config-label">Max Rows Per Table</div>
            <div class="ftl-config-value"><?= (int)$table_limit ?></div>
        </div>
    </div>
    <div class="card" style="border-left:4px solid #6c3483; flex:1; min-width:200px;">
        <div class="card-body" style="padding:14px 18px;">
            <div class="ftl-config-label">Off-Hours Window</div>
            <div class="ftl-config-value"><?= htmlspecialchars($off_start, ENT_QUOTES, 'UTF-8') ?> – <?= htmlspecialchars($off_end, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    </div>
    <div class="card" style="border-left:4px solid #2980b9; flex:1; min-width:200px;">
        <div class="card-body" style="padding:14px 18px;">
            <div class="ftl-config-label">Dashboard Severity Filter</div>
            <div class="ftl-config-value"><?= htmlspecialchars(strtoupper($min_sev_dashboard), ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    </div>
    <div class="card" style="border-left:4px solid <?= $alert_enabled ? '#27ae60' : '#aaa' ?>; flex:1; min-width:200px;">
        <div class="card-body" style="padding:14px 18px;">
            <div class="ftl-config-label">Alert Emails</div>
            <div class="ftl-config-value" style="color:<?= $alert_enabled ? '#27ae60' : '#aaa' ?>;"><?= $alert_enabled ? 'Enabled' : 'Disabled' ?></div>
            <?php if ($alert_enabled && $alert_email !== ''): ?>
            <div class="ftl-config-sub">
                → <?= htmlspecialchars($alert_email, ENT_QUOTES, 'UTF-8') ?><br>
                Cooldown: <?= (int)$alert_cooldown ?> min<br>
                Min Severity: <?= htmlspecialchars(strtoupper($min_sev_alerts), ENT_QUOTES, 'UTF-8') ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
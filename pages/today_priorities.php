<?php
/**
 * ForeTELL Lite — today_priorities.php
 * Today's high-priority incidents. Logic aligned with ForeTELL R Shiny
 * tab_alerts query: honey-pot probing, brute-force, privilege events, mass exports.
 */

$module = $GLOBALS['module'];
$alerts = $module->getTodayHighPriorities();
$today  = date('Y-m-d');
?>

<div class="ftl-section-header" style="color:#c0392b;">
    <i class="fas fa-radiation" style="margin-right:6px; color:#c0392b;"></i>
    Today's High-Priority Incidents
    <span style="font-weight:400; font-size:11px; color:#aaa; margin-left:8px;">
        Since midnight &mdash; <?= htmlspecialchars($today, ENT_QUOTES, 'UTF-8') ?>
    </span>
</div>

<p style="font-size:12px; color:#888; margin-bottom:16px;">
    Aggregates urgent security anomalies from today's log activity:
    privilege page probing, brute-force authentication bursts, high-severity system events,
    and high-volume data exports. Aligned with ForeTELL (R Shiny) alert logic.
</p>

<?php if (empty($alerts)): ?>
    <div class="alert alert-success" style="font-size:13px;">
        <i class="fas fa-check-circle" style="margin-right:6px;"></i>
        No high-priority incidents detected today. System appears clean.
    </div>
<?php else: ?>

<div style="overflow-x:auto;">
<table class="table table-bordered" style="width:100%; font-size:13px;">
    <thead>
        <tr style="background:#c0392b; color:#fff;">
            <th style="white-space:nowrap;">Timestamp</th>
            <th>Severity</th>
            <th>Incident Type</th>
            <th>User</th>
            <th>Source IP</th>
            <th>Diagnostics</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($alerts as $a):
            if ($a['severity'] === 'CRITICAL') {
                $rowStyle = 'background:#fcd2d2;';
                $badge = '<span style="background:#7b0000;color:#fff;font-size:11px;font-weight:700;padding:2px 7px;border-radius:3px;">CRITICAL</span>';
            } elseif ($a['severity'] === 'HIGH') {
                $rowStyle = 'background:#ffe7cc;';
                $badge = '<span style="background:#c0392b;color:#fff;font-size:11px;font-weight:700;padding:2px 7px;border-radius:3px;">HIGH</span>';
            } else {
                $rowStyle = '';
                $badge = '<span style="background:#e67e22;color:#fff;font-size:11px;font-weight:700;padding:2px 7px;border-radius:3px;">MEDIUM</span>';
            }
        ?>
        <tr style="<?= $rowStyle ?>">
            <td style="white-space:nowrap; font-size:12px;"><?= htmlspecialchars($a['ts'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= $badge ?></td>
            <td style="font-weight:600;"><?= htmlspecialchars($a['incident'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><code><?= htmlspecialchars($a['user'], ENT_QUOTES, 'UTF-8') ?></code></td>
            <td><code><?= htmlspecialchars($a['ip'], ENT_QUOTES, 'UTF-8') ?></code></td>
            <td style="font-size:12px; color:#555;"><?= htmlspecialchars($a['diagnostics'], ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>

<p style="font-size:11px; color:#aaa; margin-top:8px;">
    <?= count($alerts) ?> incident<?= count($alerts) !== 1 ? 's' : '' ?> flagged today.
    Export this page via the Generate Report button for handover to the security team.
</p>

<?php endif; ?>

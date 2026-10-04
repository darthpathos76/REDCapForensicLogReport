<?php
/**
 * ForeTELL Lite — user_anomalies.php
 * Displays anomalous actions crossed against individual account baseline thresholds.
 */

$module = $GLOBALS['module'];
$rows = $module->getUserActivityAnomalies();
$days = $module->getWindowDays();
?>

<div class="ftl-section">
    <div class="ftl-section-header" style="font-weight:700; font-size:16px; margin-bottom:15px; color:#2980b9;">
        <i class="fas fa-user-clock" style="margin-right:6px;"></i> User Activity Anomalies
    </div>
    <p class="text-muted" style="font-size:13px; margin-bottom:15px;">
        Analysis window: <strong><?= (int)$days; ?> days</strong>. Showing users targeting infrastructure beyond standard access limits.
    </p>

    <div style="overflow-x:auto;">
    <table class="table table-bordered table-striped ftl-table">
        <thead>
            <tr>
                <th>User</th>
                <th>Total Events</th>
                <th>Distinct IPs</th>
                <th>First Seen</th>
                <th>Last Seen</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr>
                    <td colspan="5" class="text-center text-muted" style="padding: 15px; font-size: 13px;">
                        No user activity anomalies detected in this window meeting current threshold configurations.
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($r['user'], ENT_QUOTES, 'UTF-8'); ?></code></td>
                        <td><?= number_format($r['total_events']); ?></td>
                        <td><span class="badge badge-secondary" style="font-size:12px; background:#4a5568;"><?= (int)$r['distinct_ips']; ?></span></td>
                        <td style="font-size:12px; white-space:nowrap;"><?= htmlspecialchars($r['first_seen'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td style="font-size:12px; white-space:nowrap;"><?= htmlspecialchars($r['last_seen'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>
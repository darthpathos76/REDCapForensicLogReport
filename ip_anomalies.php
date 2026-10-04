<?php
$module = $GLOBALS['module'];
$rows = $module->getIPAnomalies();
$thr_events = $module->getThreshold('threshold_ip_events', 10);
$thr_users  = $module->getThreshold('threshold_ip_users', 3);
?>

<div class="ftl-section-header">
    <i class="fas fa-network-wired" style="margin-right:6px; color:#e67e22;"></i> IP Anomalies
</div>

<?php if (empty($rows)): ?>
    <p class="text-muted" style="font-size:13px;">No IP anomalies detected in this window.</p>
<?php else: ?>
<div style="overflow-x:auto;">
<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>IP Address</th>
            <th>Total Failures</th>
            <th>Distinct Users</th>
            <th>Signal</th>
            <th>First Seen</th>
            <th>Last Seen</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $row): 
            if ($row['distinct_users'] >= $thr_users) {
                $badge = '<span class="ftl-badge ftl-badge-high" style="background:#c0392b;color:#fff;padding:2px 6px;border-radius:3px;">High</span>';
            } elseif ($row['total_events'] >= $thr_events) {
                $badge = '<span class="ftl-badge ftl-badge-medium" style="background:#e67e22;color:#fff;padding:2px 6px;border-radius:3px;">Medium</span>';
            } else {
                $badge = '<span class="ftl-badge ftl-badge-info" style="background:#2980b9;color:#fff;padding:2px 6px;border-radius:3px;">Info</span>';
            }
        ?>
        <tr>
            <td><code><?= htmlspecialchars($row['ip'], ENT_QUOTES, 'UTF-8') ?></code></td>
            <td><?= number_format((int)$row['total_events']) ?></td>
            <td><?= (int)$row['distinct_users'] ?></td>
            <td><?= $badge ?></td>
            <td style="font-size:12px; white-space:nowrap;"><?= htmlspecialchars($row['first_seen'], ENT_QUOTES, 'UTF-8') ?></td>
            <td style="font-size:12px; white-space:nowrap;"><?= htmlspecialchars($row['last_seen'], ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>
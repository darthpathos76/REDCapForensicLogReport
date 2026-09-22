<?php
$module = $GLOBALS['module'];
$rows = $module->getSuspiciousLogins();
$thr = $module->getThreshold('threshold_suspicious_logins', 6);
$days = $module->getWindowDays();
?>

<div class="ftl-section-header">
    <i class="fas fa-user-secret" style="margin-right:6px; color:#c0392b;"></i> Suspicious Logins
    <span style="font-weight:400; font-size:11px; color:#aaa; margin-left:8px;"><?= htmlspecialchars($days === 1 ? 'last 24 hours' : "last {$days} days", ENT_QUOTES, 'UTF-8') ?></span>
</div>

<?php if (empty($rows)): ?>
    <p class="text-muted" style="font-size:13px;">No suspicious login activity detected.</p>
<?php else: ?>
<div style="overflow-x:auto;">
<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>User</th>
            <th>IP Address</th>
            <th>Date</th>
            <th>Failures</th>
            <th>Signal</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $row): 
            if ($row['cnt'] >= ($thr * 3)) {
                $badge = '<span class="ftl-badge" style="background:#c0392b;color:#fff;padding:2px 6px;border-radius:3px;">High</span>';
            } elseif ($row['cnt'] >= ($thr * 2)) {
                $badge = '<span class="ftl-badge" style="background:#e67e22;color:#fff;padding:2px 6px;border-radius:3px;">Medium</span>';
            } else {
                $badge = '<span class="ftl-badge" style="background:#2980b9;color:#fff;padding:2px 6px;border-radius:3px;">Info</span>';
            }
        ?>
        <tr>
            <td><code><?= htmlspecialchars($row['user'], ENT_QUOTES, 'UTF-8') ?></code></td>
            <td><code><?= htmlspecialchars($row['ip'], ENT_QUOTES, 'UTF-8') ?></code></td>
            <td style="white-space:nowrap;"><?= htmlspecialchars($row['ts_date'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><?= (int)$row['cnt'] ?></td>
            <td><?= $badge ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>
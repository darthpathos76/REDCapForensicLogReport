<?php
$module = $GLOBALS['module'];
$rows = $module->getSystemEvents();

$high_events   = ['USER_RIGHTS', 'API_TOKEN_CREATE', 'USER_CREATE'];
$medium_events = ['DATA_EXPORT', 'DOC_UPLOAD', 'DOC_DELETE'];

usort($rows, function($a, $b) use ($high_events, $medium_events) {
    $priority = function($row) use ($high_events, $medium_events) {
        if (in_array($row['event'], $high_events, true))   return 0;
        if (in_array($row['event'], $medium_events, true)) return 1;
        return 2;
    };
    $pa = $priority($a);
    $pb = $priority($b);
    if ($pa !== $pb) return $pa - $pb;
    return strcmp($b['ts'], $a['ts']);
});
?>

<div class="ftl-section-header">
    <i class="fas fa-server" style="margin-right:6px; color:#27ae60;"></i> System Events
</div>

<?php if (empty($rows)): ?>
    <p class="text-muted" style="font-size:13px;">No system events recorded.</p>
<?php else: ?>
<div style="overflow-x:auto;">
<table class="table table-bordered table-striped" style="width:100%;">
    <thead>
        <tr>
            <th>Timestamp</th>
            <th>User</th>
            <th>IP Address</th>
            <th>Event</th>
            <th>Signal</th>
            <th>Project ID</th>
            <th>Page</th>
            <th>Miscellaneous</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rows as $row): 
            if (in_array($row['event'], $high_events, true)) {
                $badge = '<span class="ftl-badge" style="background:#c0392b;color:#fff;padding:2px 6px;border-radius:3px;">High</span>';
            } elseif (in_array($row['event'], $medium_events, true)) {
                $badge = '<span class="ftl-badge" style="background:#e67e22;color:#fff;padding:2px 6px;border-radius:3px;">Medium</span>';
            } else {
                $badge = '<span class="ftl-badge" style="background:#2980b9;color:#fff;padding:2px 6px;border-radius:3px;">Info</span>';
            }
        ?>
        <tr>
            <td style="white-space:nowrap; font-size:12px;"><?= htmlspecialchars($row['ts'], ENT_QUOTES, 'UTF-8') ?></td>
            <td><code><?= htmlspecialchars($row['user'], ENT_QUOTES, 'UTF-8') ?></code></td>
            <td><code><?= htmlspecialchars($row['ip'], ENT_QUOTES, 'UTF-8') ?></code></td>
            <td><code><?= htmlspecialchars($row['event'], ENT_QUOTES, 'UTF-8') ?></code></td>
            <td><?= $badge ?></td>
            <td><?= htmlspecialchars($row['project_id'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
            <td style="font-size:11px; color:#777; word-break:break-all;"><?= htmlspecialchars(basename((string)($row['page'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
            <td style="font-size:12px;"><?= htmlspecialchars($row['miscellaneous'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>
<?php
$module = $GLOBALS['module'];
$rows = $module->getIPAnomalies();
?>

<div class="ftl-section-header">
    <i class="fas fa-network-wired"
       style="margin-right:6px; color:#e67e22;"></i>
    IP Anomalies
</div>

<?php if (empty($rows)): ?>

    <p class="text-muted" style="font-size:13px;">
        No IP anomalies detected in this window.
    </p>

<?php else: ?>

<div style="overflow-x:auto;">
<table class="table table-bordered table-striped">

    <thead>
        <tr>
            <th>IP Address</th>
            <th>Total Failed Logins</th>
            <th>Distinct Known Users</th>
            <th>Invalid / Unknown Username Attempts</th>
            <th>Signal</th>
            <th>First Seen</th>
            <th>Last Seen</th>
        </tr>
    </thead>

    <tbody>

        <?php foreach ($rows as $row): ?>

            <?php
            $distinctKnownUsers = (int)(
                $row['distinct_known_users'] ?? 0
            );

            $invalidAttempts = (int)(
                $row['invalid_username_attempts'] ?? 0
            );

            $totalEvents = (int)(
                $row['total_events'] ?? 0
            );

   $severity = strtolower(
    (string)($row['severity'] ?? 'low')
);

switch ($severity) {
    case 'high':
        $badge =
            '<span class="ftl-badge ftl-badge-high" '
            . 'style="background:#c0392b;color:#fff;'
            . 'padding:2px 6px;border-radius:3px;">'
            . 'High</span>';
        break;

    case 'medium':
        $badge =
            '<span class="ftl-badge ftl-badge-medium" '
            . 'style="background:#e67e22;color:#fff;'
            . 'padding:2px 6px;border-radius:3px;">'
            . 'Medium</span>';
        break;

    default:
        $badge =
            '<span class="ftl-badge ftl-badge-info" '
            . 'style="background:#2980b9;color:#fff;'
            . 'padding:2px 6px;border-radius:3px;">'
            . 'Low</span>';
        break;
}
            ?>

            <tr>

                <td>
                    <code>
                        <?= htmlspecialchars(
                            (string)($row['ip'] ?? ''),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </code>
                </td>

                <td>
                    <?= number_format($totalEvents) ?>
                </td>

                <td>
                    <?= $distinctKnownUsers ?>
                </td>

                <td>
                    <?= number_format($invalidAttempts) ?>
                </td>

                <td>
                    <?= $badge ?>
                </td>

                <td style="font-size:12px; white-space:nowrap;">
                    <?= htmlspecialchars(
                        (string)($row['first_seen'] ?? ''),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </td>

                <td style="font-size:12px; white-space:nowrap;">
                    <?= htmlspecialchars(
                        (string)($row['last_seen'] ?? ''),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </td>

            </tr>

        <?php endforeach; ?>

    </tbody>

</table>
</div>

<?php endif; ?>
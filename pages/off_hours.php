<?php
/**
 * ForeTELL Lite — off_hours.php
 * Off-hours analytics configuration and bar-chart rendering engine.
 */

$module = $GLOBALS['module'];

$days          = $module->getWindowDays();
$limit         = $module->getTableLimit(500);

$allRows = $module->getOffHoursEvents();

$by_user         = [];
$invalid_by_ip   = [];
$by_hour         = array_fill(0, 24, 0);

foreach ($allRows as $r) {
    $timestamp = (string)($r['ts'] ?? '');
    $user      = (string)($r['user'] ?? '');
    $ip        = (string)($r['ip'] ?? '');
    $event     = (string)($r['event'] ?? '');

    if ($timestamp === '') {
        continue;
    }

    // Track all off-hours security events by hour.
    $hour = (int)substr($timestamp, 11, 2);

    if ($hour >= 0 && $hour <= 23) {
        $by_hour[$hour]++;
    }

    /*
     * REDCap uses [not_valid_username] for failed authentication
     * attempts involving an invalid or unknown username. Preserve
     * these events, but group them by source IP instead of treating
     * the sentinel value as an authenticated REDCap account.
     */
    if ($user === '[not_valid_username]' && $event === 'LOGIN_FAIL') {
        $ipKey = $ip !== '' ? $ip : '[unknown_ip]';

        if (!isset($invalid_by_ip[$ipKey])) {
            $invalid_by_ip[$ipKey] = [
                'ip'            => $ipKey,
                'attempt_count' => 0,
                'days'          => [],
                'first_event'   => $timestamp,
                'last_event'    => $timestamp,
            ];
        }

        $invalid_by_ip[$ipKey]['attempt_count']++;
        $invalid_by_ip[$ipKey]['days'][substr($timestamp, 0, 10)] = true;

        if ($timestamp < $invalid_by_ip[$ipKey]['first_event']) {
            $invalid_by_ip[$ipKey]['first_event'] = $timestamp;
        }

        if ($timestamp > $invalid_by_ip[$ipKey]['last_event']) {
            $invalid_by_ip[$ipKey]['last_event'] = $timestamp;
        }

        continue;
    }

    // Only authenticated/non-empty usernames belong in the user table.
    if ($user === '') {
        continue;
    }

    if (!isset($by_user[$user])) {
        $by_user[$user] = [
            'user'        => $user,
            'event_count' => 0,
            'days'        => [],
            'first_event' => $timestamp,
            'last_event'  => $timestamp,
        ];
    }

    $by_user[$user]['event_count']++;
    $by_user[$user]['days'][substr($timestamp, 0, 10)] = true;

    if ($timestamp < $by_user[$user]['first_event']) {
        $by_user[$user]['first_event'] = $timestamp;
    }

    if ($timestamp > $by_user[$user]['last_event']) {
        $by_user[$user]['last_event'] = $timestamp;
    }
}

$by_user = array_values(array_map(function(array $row): array {
    $row['active_days'] = count($row['days']);
    unset($row['days']);
    return $row;
}, $by_user));

$invalid_by_ip = array_values(array_map(function(array $row): array {
    $row['active_days'] = count($row['days']);
    unset($row['days']);
    return $row;
}, $invalid_by_ip));

usort($by_user, function(array $a, array $b): int {
    return (int)$b['event_count'] <=> (int)$a['event_count'];
});

usort($invalid_by_ip, function(array $a, array $b): int {
    return (int)$b['attempt_count'] <=> (int)$a['attempt_count'];
});

// Apply display limits after aggregation, not to raw evidence.
$by_user       = array_slice($by_user, 0, $limit);
$invalid_by_ip = array_slice($invalid_by_ip, 0, $limit);?>

<div class="ftl-section-header" style="font-weight:700; font-size:16px; margin-bottom:15px; color:#8e44ad;">
    <i class="fas fa-moon" style="margin-right:6px;"></i>
    Authenticated Off-Hours Activity by User
    <span style="font-weight:400; font-size:11px; color:#aaa; margin-left:8px;">
        <?= htmlspecialchars(
            $days === 1 ? 'last 24 hours' : "last {$days} days",
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </span>
</div>

<?php if (array_sum($by_hour) > 0): ?>
<div class="card style-distribution" style="margin-bottom:30px; border:1px solid #dee2e6;">
    <div class="card-header" style="background:#f8f9fa; font-weight:600; font-size:13px;">
        <i class="fas fa-chart-bar" style="margin-right:5px; color:#8e44ad;"></i>
        Off-Hours Event Distribution by Hour of Day
    </div>

    <div class="card-body" style="padding:15px;">
        <?php
        $max_hour_count = max($by_hour);

        for ($h = 0; $h < 24; $h++):
            if ($by_hour[$h] === 0) continue;

            $percentage = round(($by_hour[$h] / $max_hour_count) * 100);
            $display_hour = sprintf("%02d:00", $h);
        ?>
        <div style="display:flex; align-items:center; margin-bottom:6px;">
            <div style="width:55px; font-family:monospace; font-size:12px; color:#555;">
                <?= $display_hour ?>
            </div>

            <div style="flex-grow:1; background:#f1f1f1; height:18px; border-radius:3px; margin:0 10px; overflow:hidden;">
                <div style="background:#8e44ad; width:<?= $percentage ?>%; height:100%; border-radius:3px;"></div>
            </div>

            <div style="width:50px; font-size:12px; font-weight:bold; text-align:right; color:#333;">
                <?= number_format($by_hour[$h]) ?>
            </div>
        </div>
        <?php endfor; ?>
    </div>
</div>
<?php endif; ?>


<?php if (empty($by_user)): ?>

    <p class="text-muted" style="font-size:13px;">
        No authenticated off-hours activity recorded.
    </p>

<?php else: ?>

<div style="overflow-x:auto;">
<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>Username</th>
            <th>Off-Hours Events</th>
            <th>Distinct Days Active</th>
            <th>First Event</th>
            <th>Last Event</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($by_user as $row): ?>
        <tr>
            <td>
                <code><?= htmlspecialchars(
                    $row['user'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?></code>
            </td>
            <td><?= number_format($row['event_count']) ?></td>
            <td><?= (int)$row['active_days'] ?></td>
            <td style="font-size:12px; white-space:nowrap;">
                <?= htmlspecialchars(
                    $row['first_event'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </td>
            <td style="font-size:12px; white-space:nowrap;">
                <?= htmlspecialchars(
                    $row['last_event'],
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


<div class="ftl-section-header"
     style="font-weight:700; font-size:16px; margin-top:30px; margin-bottom:15px; color:#c0392b;">

    <i class="fas fa-user-slash" style="margin-right:6px;"></i>
    Invalid or Unknown Username Login Attempts by Source IP

    <span style="font-weight:400; font-size:11px; color:#aaa; margin-left:8px;">
        <?= htmlspecialchars(
            $days === 1 ? 'last 24 hours' : "last {$days} days",
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </span>
</div>

<?php if (empty($invalid_by_ip)): ?>

    <p class="text-muted" style="font-size:13px;">
        No off-hours invalid or unknown username login attempts recorded.
    </p>

<?php else: ?>

<div style="overflow-x:auto;">
<table class="table table-bordered table-striped">
    <thead>
        <tr>
            <th>Source IP</th>
            <th>Failed Login Attempts</th>
            <th>Distinct Days Active</th>
            <th>First Attempt</th>
            <th>Last Attempt</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($invalid_by_ip as $row): ?>
        <tr>
            <td>
                <code><?= htmlspecialchars(
                    $row['ip'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?></code>
            </td>

            <td><?= number_format($row['attempt_count']) ?></td>
            <td><?= (int)$row['active_days'] ?></td>

            <td style="font-size:12px; white-space:nowrap;">
                <?= htmlspecialchars(
                    $row['first_event'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </td>

            <td style="font-size:12px; white-space:nowrap;">
                <?= htmlspecialchars(
                    $row['last_event'],
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
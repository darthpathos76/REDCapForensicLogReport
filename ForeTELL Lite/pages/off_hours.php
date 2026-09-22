<?php
/**
 * ForeTELL Lite — off_hours.php
 * Off-hours analytics configuration and bar-chart rendering engine.
 */

$module = $GLOBALS['module'];

$days          = $module->getWindowDays();
[$start, $end] = $module->getOffHoursBounds();
$limit         = $module->getTableLimit(500);

$start_hhmm = substr($start, 0, 5);
$end_hhmm   = substr($end,   0, 5);
$allRows = $module->getWindowedLogRows();

$by_user = [];
$by_hour = array_fill(0, 24, 0);

foreach ($allRows as $r) {
    if (empty($r['user'])) continue;

    $time = substr($r['ts'], 11, 5);
    if ($module->isWithinCoreHours($time, $start_hhmm, $end_hhmm)) continue;

    // Track hourly metrics
    $h = (int)substr($r['ts'], 11, 2);
    $by_hour[$h]++;

    // Track user profiles
    $u = $r['user'];
    if (!isset($by_user[$u])) {
        $by_user[$u] = [
            'user'        => $u,
            'event_count' => 0,
            'days'        => [],
            'first_event' => $r['ts'],
            'last_event'  => $r['ts'],
        ];
    }

    $by_user[$u]['event_count']++;
    $day = substr($r['ts'], 0, 10);
    $by_user[$u]['days'][$day] = true;

    if ($r['ts'] < $by_user[$u]['first_event']) $by_user[$u]['first_event'] = $r['ts'];
    if ($r['ts'] > $by_user[$u]['last_event'])  $by_user[$u]['last_event']  = $r['ts'];
}

$by_user = array_values(array_map(function($u) {
    $u['active_days'] = count($u['days']);
    unset($u['days']);
    return $u;
}, $by_user));

usort($by_user, fn($a, $b) => $b['event_count'] <=> $a['event_count']);
$by_user = array_slice($by_user, 0, $limit);
?>

<div class="ftl-section-header" style="font-weight:700; font-size:16px; margin-bottom:15px; color:#8e44ad;">
    <i class="fas fa-moon" style="margin-right:6px;"></i> Off-Hours Activity by User
    <span style="font-weight:400; font-size:11px; color:#aaa; margin-left:8px;">
        <?= htmlspecialchars($days === 1 ? 'last 24 hours' : "last {$days} days", ENT_QUOTES, 'UTF-8') ?>
    </span>
</div>

<?php if (array_sum($by_hour) > 0): ?>
<div class="card style-distribution" style="margin-bottom: 30px; border: 1px solid #dee2e6;">
    <div class="card-header" style="background: #f8f9fa; font-weight: 600; font-size: 13px;">
        <i class="fas fa-chart-bar" style="margin-right: 5px; color: #8e44ad;"></i> Event Threat Distribution by Hour of Day
    </div>
    <div class="card-body" style="padding: 15px;">
        <?php 
        $max_hour_count = max($by_hour);
        for ($h = 0; $h < 24; $h++): 
            if ($by_hour[$h] === 0) continue;
            $percentage = round(($by_hour[$h] / $max_hour_count) * 100);
            $display_hour = sprintf("%02d:00", $h);
        ?>
        <div style="display: flex; align-items: center; margin-bottom: 6px;">
            <div style="width: 55px; font-family: monospace; font-size: 12px; color: #555;"><?= $display_hour ?></div>
            <div style="flex-grow: 1; background: #f1f1f1; height: 18px; border-radius: 3px; margin: 0 10px; overflow: hidden;">
                <div style="background: #8e44ad; width: <?= $percentage ?>%; height: 100%; border-radius: 3px;"></div>
            </div>
            <div style="width: 50px; font-size: 12px; font-weight: bold; text-align: right; color: #333;"><?= number_format($by_hour[$h]) ?></div>
        </div>
        <?php endfor; ?>
    </div>
</div>
<?php endif; ?>

<?php if (empty($by_user)): ?>
    <p class="text-muted" style="font-size:13px;">No off-hours activity recorded.</p>
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
            <td><code><?= htmlspecialchars($row['user'], ENT_QUOTES, 'UTF-8') ?></code></td>
            <td><?= number_format($row['event_count']) ?></td>
            <td><?= (int)$row['active_days'] ?></td>
            <td style="font-size:12px; white-space:nowrap;"><?= htmlspecialchars($row['first_event'], ENT_QUOTES, 'UTF-8') ?></td>
            <td style="font-size:12px; white-space:nowrap;"><?= htmlspecialchars($row['last_event'], ENT_QUOTES, 'UTF-8') ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>
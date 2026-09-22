<?php
/** ForeTELL Lite — index.php
 * Control Center dashboard (framework-version 14 / Bootstrap 4)
 */

$module = $GLOBALS['module'];
$base   = $module->getUrl('pages/index.php');

require_once APP_PATH_DOCROOT . 'ControlCenter/header.php';

$enabled = [
    'intro'             => true,
    'suspicious_logins' => $module->getSystemSetting('enable_suspicious_logins') !== '0',
    'ip_anomalies'      => $module->getSystemSetting('enable_ip_anomalies')      !== '0',
    'user_anomalies'    => $module->getSystemSetting('enable_user_anomalies')    !== '0',
    'system_events'     => $module->getSystemSetting('enable_system_events')     !== '0',
    'off_hours'         => $module->getSystemSetting('enable_off_hours')         !== '0',
    'today_priorities'  => $module->getSystemSetting('enable_today_priorities')  !== '0',
];

$valid_views = array_keys(array_filter($enabled));
$view = $_GET['view'] ?? 'intro';
if (!in_array($view, $valid_views, true)) $view = 'intro';

$all_tabs = [
    'intro'             => ['label' => 'Overview',           'icon' => 'fas fa-info-circle',   'color' => '#336699'],
    'suspicious_logins' => ['label' => 'Suspicious Logins',  'icon' => 'fas fa-user-secret',   'color' => '#c0392b'],
    'ip_anomalies'      => ['label' => 'IP Anomalies',       'icon' => 'fas fa-network-wired', 'color' => '#e67e22'],
    'user_anomalies'    => ['label' => 'User Activity',      'icon' => 'fas fa-user-clock',    'color' => '#2980b9'],
    'system_events'     => ['label' => 'System Events',      'icon' => 'fas fa-server',        'color' => '#27ae60'],
    'off_hours'         => ['label' => 'Off-Hours Activity', 'icon' => 'fas fa-moon',          'color' => '#8e44ad'],
    'today_priorities'  => ['label' => "Today's Priorities",  'icon' => 'fas fa-radiation',     'color' => '#c0392b'],
];

$tabs = array_intersect_key($all_tabs, array_filter($enabled));
$counts = ['intro' => 0];

if ($enabled['suspicious_logins']) $counts['suspicious_logins'] = $module->getSuspiciousLoginsCount();
if ($enabled['ip_anomalies'])      $counts['ip_anomalies']      = $module->getIPAnomaliesCount();
if ($enabled['user_anomalies'])    $counts['user_anomalies']    = $module->getUserActivityAnomaliesCount();
if ($enabled['system_events'])     $counts['system_events']     = count($module->getSystemEvents());
if ($enabled['off_hours'])         $counts['off_hours']         = count($module->getOffHoursEvents());
if ($enabled['today_priorities'])  $counts['today_priorities']  = count($module->getTodayHighPriorities());

$trend_html = "<span style='color:#999;'>—</span>";
if ($enabled['suspicious_logins']) {
    $trend = $module->getLoginFailTrend();
    if ($trend['today'] > $trend['yesterday']) {
        $trend_html = "<span style='color:#c0392b;' title='Up from yesterday'>▲</span>";
    } elseif ($trend['today'] < $trend['yesterday']) {
        $trend_html = "<span style='color:#27ae60;' title='Down from yesterday'>▼</span>";
    }
}

$spark_series = $module->getAllSparklineData();
?>

<style>
    .ftl-grid-row   { display:flex; flex-wrap:wrap; margin-right:-10px; margin-left:-10px; }
    .ftl-grid-col   { flex:0 0 20%; max-width:20%; padding:10px; box-sizing:border-box; }
    .ftl-content-card { background:#fff; border:1px solid #dee2e6; border-top:none; padding:20px; }
    .ftl-timestamp { font-size:11px; color:#aaa; margin-top:15px; text-align:right; }
    @media (max-width:1200px) { .ftl-grid-col { flex:0 0 33.333%; max-width:33.333%; } }
    @media (max-width:768px)  { .ftl-grid-col { flex:0 0 50%;     max-width:50%;     } }
    @media (max-width:480px)  { .ftl-grid-col { flex:0 0 100%;    max-width:100%;    } }
</style>

<?php
if (!function_exists('ftl_svg_sparkline')) {
    function ftl_svg_sparkline(array $values, string $color): string
    {
        $count = count($values);
        if ($count < 2) return '<div style="height:36px;"></div>';

        $max = max($values);
        if ($max == 0) return '<div style="height:36px;"></div>';

        $w = 180; $h = 36; $pad = 2;
        $step = ($w - $pad * 2) / ($count - 1);

        $points = [];
        foreach ($values as $i => $v) {
            $x = $pad + $i * $step;
            $y = $h - $pad - (($v / $max) * ($h - $pad * 2));
            $points[] = round($x, 1) . ',' . round($y, 1);
        }

        return "<svg viewBox='0 0 {$w} {$h}' preserveAspectRatio='none' style='width:100%;height:36px;display:block;' xmlns='http://www.w3.org/2000/svg'><polyline points='" . implode(' ', $points) . "' fill='none' stroke='" . htmlspecialchars($color, ENT_QUOTES, 'UTF-8') . "' stroke-width='1.5' stroke-linejoin='round' stroke-linecap='round'/></svg>";
    }
}
?>

<div class="projhdr">
    <i class="fas fa-lightbulb" style="margin-right:8px; color:#336699;"></i>
    ForeTELL Lite — Security Dashboard
</div>

<div class="container-fluid" style="padding-top:10px; max-width:1400px;">
    <div class="ftl-grid-row" style="margin-bottom:20px;">
        <?php foreach ($tabs as $key => $meta): ?>
        <div class="ftl-grid-col">
            <a href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>&view=<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"
               class="ftl-stat-card card <?= $view === $key ? 'active-card' : '' ?>"
               style="border-left:4px solid <?= htmlspecialchars($meta['color'], ENT_QUOTES, 'UTF-8') ?>; text-decoration:none; color:inherit;">
                <div class="card-body" style="padding:14px 18px;">
                    <div class="ftl-stat-label" style="font-size:12px; font-weight:600; color:#555;"><?= htmlspecialchars($meta['label'], ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="ftl-stat-count" style="font-size:22px; font-weight:700; margin:4px 0;">
                        <?php if ($key === 'intro'): ?>
                            <span style="font-size:13px; font-weight:400; color:#aaa;">System Metrics</span>
                        <?php else: ?>
                            <?= number_format((int)$counts[$key]) ?>
                            <?php if ($key === 'suspicious_logins'): ?>
                                <?= $trend_html ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <?php if ($key !== 'intro' && isset($spark_series[$key])): ?>
                        <?= ftl_svg_sparkline($spark_series[$key], $meta['color']) ?>
                    <?php else: ?>
                        <div style="height:36px;"></div>
                    <?php endif; ?>
                </div>
            </a>
        </div>
        <?php endforeach; ?>
    </div>

    <ul class="nav nav-tabs ftl-tabs" style="border-bottom:2px solid #dee2e6; margin-bottom:0;">
        <?php foreach ($tabs as $key => $meta): ?>
        <li class="nav-item">
            <a class="nav-link <?= $view === $key ? 'active' : '' ?>"
               href="<?= htmlspecialchars($base, ENT_QUOTES, 'UTF-8') ?>&view=<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>">
                <i class="<?= htmlspecialchars($meta['icon'], ENT_QUOTES, 'UTF-8') ?>" style="margin-right:5px; font-size:12px;"></i>
                <?= htmlspecialchars($meta['label'], ENT_QUOTES, 'UTF-8') ?>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>

    <div style="display:flex; justify-content:flex-end; align-items:center; margin:8px 0 4px 0;">
        <?php
        $todayCount = $enabled['today_priorities'] ? count($module->getTodayHighPriorities()) : 0;
        if ($todayCount > 0): ?>
        <span style="background:#c0392b; color:#fff; font-size:12px; font-weight:700; border-radius:12px; padding:2px 10px; margin-right:10px;">
            <i class="fas fa-exclamation-circle" style="margin-right:4px;"></i>
            <?= $todayCount ?> incident<?= $todayCount !== 1 ? 's' : '' ?> today
        </span>
        <?php endif; ?>
        <a href="<?= htmlspecialchars($module->getUrl('pages/report.php'), ENT_QUOTES, 'UTF-8') ?>"
           target="_blank"
           style="display:inline-flex; align-items:center; background:#336699; color:#fff; font-size:12px; font-weight:600; padding:5px 14px; border-radius:4px; text-decoration:none;">
            <i class="fas fa-file-word" style="margin-right:6px;"></i> Generate Report
        </a>
    </div>

    <div class="ftl-content-card">
        <?php
        $pageFile = __DIR__ . '/' . $view . '.php';
        if (file_exists($pageFile) && in_array($view, $valid_views, true)) {
            include $pageFile;
        } else {
            echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle' style='margin-right:6px;'></i>Page not found.</div>";
        }
        ?>
    </div>
    <div class="ftl-timestamp">Last updated: <?= date('Y-m-d H:i:s') ?></div>
</div>

<?php require_once APP_PATH_DOCROOT . 'ControlCenter/footer.php'; ?>
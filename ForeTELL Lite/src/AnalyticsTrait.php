<?php
namespace ForetellLite;

trait AnalyticsTrait
{
    /**
     * Return counts of unique suspicious logins.
     * @return int
     */
    public function getSuspiciousLoginsCount(): int
    {
        if (!method_exists($this, 'getSuspiciousLogins')) return 0;
        return count($this->getSuspiciousLogins());
    }

    /**
     * Calculates absolute IP anomaly vectors metrics.
     * @return int
     */
    public function getIPAnomaliesCount(): int
    {
        $rows = $this->getWindowedLogRows();
        $eventsThresh = $this->getThreshold('threshold_ip_events', 10);
        $usersThresh  = $this->getThreshold('threshold_ip_users', 3);

        $byIp = [];
        foreach ($rows as $r) {
            if ($r['event'] !== 'LOGIN_FAIL') continue;

            $ip = (string)$r['ip'];
            if ($ip === '') continue;

            if (!isset($byIp[$ip])) {
                $byIp[$ip] = ['events' => 0, 'users' => []];
            }

            $byIp[$ip]['events']++;
            $byIp[$ip]['users'][(string)$r['user']] = true;
        }

        $count = 0;
        foreach ($byIp as $ip => $info) {
            if ($info['events'] >= $eventsThresh || count($info['users']) >= $usersThresh) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Return counts of active unique anomaly records.
     * @return int
     */
    public function getUserActivityAnomaliesCount(): int
    {
        return count($this->getUserActivityAnomalies());
    }

    /**
     * Compute and extract individual user activity exception logs.
     * @return array<int, array<string, mixed>>
     */
    public function getUserActivityAnomalies(): array
    {
        $rows = $this->getWindowedLogRows();
        $eventsThresh = $this->getThreshold('threshold_user_events', 20);
        $ipsThresh    = $this->getThreshold('threshold_user_ips', 5);
        $grouped = [];

        foreach ($rows as $r) {
            $u = (string)$r['user'];
            if ($u === '') continue;

            if (!isset($grouped[$u])) {
                $grouped[$u] = [
                    'user'          => $u,
                    'total_events'  => 0,
                    'ips'           => [],
                    'first_seen'    => $r['ts'],
                    'last_seen'     => $r['ts'],
                ];
            }

            $grouped[$u]['total_events']++;
            $grouped[$u]['ips'][(string)$r['ip']] = true;

            if ($r['ts'] < $grouped[$u]['first_seen']) $grouped[$u]['first_seen'] = $r['ts'];
            if ($r['ts'] > $grouped[$u]['last_seen'])  $grouped[$u]['last_seen']  = $r['ts'];
        }

        $out = [];
        foreach ($grouped as $u => $g) {
            $distinctIps = count($g['ips']);
            
            if ($g['total_events'] >= $eventsThresh || $distinctIps >= $ipsThresh) {
                $out[] = [
                    'user'         => $u,
                    'total_events' => $g['total_events'],
                    'distinct_ips' => $distinctIps,
                    'first_seen'   => $g['first_seen'],
                    'last_seen'    => $g['last_seen'],
                ];
            }
        }

        usort($out, function(array $a, array $b): int {
            if ($a['distinct_ips'] !== $b['distinct_ips']) {
                return (int)$b['distinct_ips'] <=> (int)$a['distinct_ips'];
            }
            return (int)$b['total_events'] <=> (int)$a['total_events'];
        });

        return $out;
    }

    /**
     * Pull limited slice of core filtered system records.
     * @return array<int, array<string, mixed>>
     */
    public function getSystemEvents(): array
    {
        return array_slice($this->getWindowedLogRows(), 0, $this->getTableLimit(200));
    }

    /**
     * Pull counts mapping chronological failure changes.
     * @return array<string, int>
     */
    public function getLoginFailTrend(): array
    {
        $rows = $this->getWindowedLogRows();
        $today     = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        $counts = [$today => 0, $yesterday => 0];

        foreach ($rows as $r) {
            if ($r['event'] !== 'LOGIN_FAIL') continue;
            $d = substr((string)$r['ts'], 0, 10);
            if (isset($counts[$d])) {
                $counts[$d]++;
            }
        }

        return [
            'today'     => $counts[$today],
            'yesterday' => $counts[$yesterday],
        ];
    }

    /**
     * Today's high-priority incidents, aligned with ForeTELL R Shiny alert logic.
     * Covers: honey-pot probing, brute-force bursts, privilege events, mass exports.
     * @return array<int, array<string, string>>
     */
    public function getTodayHighPriorities(): array
    {
        $rows  = $this->getWindowedLogRows();
        $today = date('Y-m-d');
        $thr   = $this->getThreshold('threshold_suspicious_logins', 6);

        $alerts     = [];
        $loginByIp  = [];
        $exportByUser = [];

        $privilegeEvents = ['USER_RIGHTS', 'API_TOKEN_CREATE', 'USER_CREATE'];
        $exportEvents    = ['DATA_EXPORT', 'DOC_UPLOAD', 'DOC_DELETE'];

        foreach ($rows as $r) {
            if (substr((string)$r['ts'], 0, 10) !== $today) {
                continue;
            }

            $user  = (string)$r['user'];
            $ip    = (string)$r['ip'];
            $event = (string)$r['event'];
            $page  = strtolower((string)($r['page'] ?? ''));

            // --- Honey-pot / privilege page probing by non-admin ---
            if (
                (strpos($page, 'controlcenter') !== false
                 || strpos($page, 'userrights') !== false
                 || strpos($page, 'viewauth') !== false
                 || strpos($page, 'add_users') !== false)
                && stripos($user, 'admin') === false
                && stripos($user, 'root') === false
                && $user !== ''
            ) {
                $alerts[] = [
                    'severity'     => 'CRITICAL',
                    'incident'     => 'Privilege Page Probing',
                    'user'         => $user,
                    'ip'           => $ip,
                    'ts'           => (string)$r['ts'],
                    'diagnostics'  => 'Attempted access to: ' . htmlspecialchars(basename((string)($r['page'] ?? '')), ENT_QUOTES, 'UTF-8'),
                ];
            }

            // --- Brute-force burst aggregation ---
            if ($event === 'LOGIN_FAIL') {
                if (!isset($loginByIp[$ip])) {
                    $loginByIp[$ip] = ['cnt' => 0, 'last_ts' => ''];
                }
                $loginByIp[$ip]['cnt']++;
                $loginByIp[$ip]['last_ts'] = (string)$r['ts'];
            }

            // --- High-severity privilege events (direct, not aggregated) ---
            if (in_array($event, $privilegeEvents, true)) {
                $alerts[] = [
                    'severity'    => 'HIGH',
                    'incident'    => 'Privilege System Event: ' . $event,
                    'user'        => $user,
                    'ip'          => $ip,
                    'ts'          => (string)$r['ts'],
                    'diagnostics' => 'Event: ' . $event . ' on project ' . htmlspecialchars((string)($r['project_id'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'),
                ];
            }

            // --- Mass export aggregation ---
            if (in_array($event, $exportEvents, true) && $user !== '') {
                if (!isset($exportByUser[$user])) {
                    $exportByUser[$user] = ['cnt' => 0, 'last_ts' => '', 'last_misc' => ''];
                }
                $exportByUser[$user]['cnt']++;
                $exportByUser[$user]['last_ts']   = (string)$r['ts'];
                $exportByUser[$user]['last_misc']  = (string)($r['miscellaneous'] ?? '');
            }
        }

        // Emit brute-force alerts for IPs over threshold
        foreach ($loginByIp as $ip => $info) {
            if ($info['cnt'] >= $thr) {
                $alerts[] = [
                    'severity'    => 'HIGH',
                    'incident'    => 'Brute-Force Authentication Burst',
                    'user'        => 'MULTIPLE',
                    'ip'          => $ip,
                    'ts'          => $info['last_ts'],
                    'diagnostics' => $info['cnt'] . ' login failures from this IP today',
                ];
            }
        }

        // Emit mass-export alerts (threshold: 20 export events in one day)
        $exportThr = $this->getThreshold('threshold_export_alert', 20);
        foreach ($exportByUser as $user => $info) {
            if ($info['cnt'] >= $exportThr) {
                $alerts[] = [
                    'severity'    => 'HIGH',
                    'incident'    => 'High-Volume Data Export',
                    'user'        => $user,
                    'ip'          => 'VARIES',
                    'ts'          => $info['last_ts'],
                    'diagnostics' => $info['cnt'] . ' export events today. Last: ' . htmlspecialchars($info['last_misc'], ENT_QUOTES, 'UTF-8'),
                ];
            }
        }

        // Sort: CRITICAL first, then by timestamp descending
        usort($alerts, function(array $a, array $b): int {
            $sevOrder = ['CRITICAL' => 0, 'HIGH' => 1, 'MEDIUM' => 2];
            $sa = $sevOrder[$a['severity']] ?? 9;
            $sb = $sevOrder[$b['severity']] ?? 9;
            if ($sa !== $sb) return $sa - $sb;
            return strcmp($b['ts'], $a['ts']);
        });

        return $alerts;
    }

    /**
     * Gather system threat objects to pipe directly to our email framework triggers.
     * @param string $min
     * @return array<int, array<string, string>>
     */
    public function collectHighPriorityAlerts(string $min): array
    {
        $alerts = [];

        if (method_exists($this, 'getSuspiciousLogins')) {
            foreach ($this->getSuspiciousLogins() as $l) {
                // Key includes the count: if this same user/IP/date group grows
                // (more failures land), it gets a new key and re-alerts. If the
                // count is unchanged from a prior run, it's the same stale group
                // still sitting inside the rolling window and is suppressed.
                $key = "login:{$l['user']}:{$l['ip']}:{$l['ts_date']}:{$l['cnt']}";
                $alerts[] = [
                    'severity' => 'high',
                    'key'      => $key,
                    'message'  => "Suspicious login failures for user {$l['user']} from IP {$l['ip']} ({$l['cnt']} failures)"
                ];
            }
        }

        if ($min !== 'high' && method_exists($this, 'getIPAnomalies')) {
            foreach ($this->getIPAnomalies() as $ip) {
                $key = "ipanom:{$ip['ip']}:{$ip['total_events']}:{$ip['distinct_users']}";
                $alerts[] = [
                    'severity' => 'medium',
                    'key'      => $key,
                    'message'  => "IP anomaly: {$ip['ip']} ({$ip['total_events']} failures, {$ip['distinct_users']} users)"
                ];
            }
        }

        foreach ($this->getSystemEvents() as $s) {
            if (in_array($s['event'], ['USER_RIGHTS', 'API_TOKEN_CREATE', 'USER_CREATE'], true)) {
                // event_id is a permanent unique ID from redcap_log_view, so each
                // log entry can only ever produce one alert, no matter how many
                // cron runs see it while it's inside the window.
                $key = "sysevt:{$s['event_id']}";
                $alerts[] = [
                    'severity' => 'high',
                    'key'      => $key,
                    'message'  => "System event: {$s['event']} by {$s['user']} (PID {$s['project_id']})"
                ];
            }
        }

        return $alerts;
    }
}
<?php
namespace ForetellLite;

trait TablesTrait
{
    /**
     * Gather structural suspicious login event lines.
     * @return array<int, array<string, mixed>>
     */
    public function getSuspiciousLogins(): array
    {
        $rows      = $this->getWindowedLogRows();
        $threshold = $this->getThreshold('threshold_suspicious_logins', 6);
        $limit     = $this->getTableLimit(200);

        $groups = [];

        foreach ($rows as $r) {
            if ($r['event'] !== 'LOGIN_FAIL') continue;

            $date = substr((string)$r['ts'], 0, 10);
            $key  = (string)$r['user'] . '|' . (string)$r['ip'] . '|' . $date;

            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'user'    => $r['user'],
                    'ip'      => $r['ip'],
                    'ts_date' => $date,
                    'cnt'     => 0,
                ];
            }

            $groups[$key]['cnt']++;
        }

        $filtered = array_values(array_filter($groups, function (array $g) use ($threshold): bool {
            return (int)$g['cnt'] >= $threshold;
        }));

        usort($filtered, function (array $a, array $b): int {
            return (int)$b['cnt'] <=> (int)$a['cnt'];
        });

        return array_slice($filtered, 0, $limit);
    }

    /**
     * Gathers structural IP address anomaly trends.
     * @return array<int, array<string, mixed>>
     */
    public function getIPAnomalies(): array
    {
        $rows         = $this->getWindowedLogRows();
        $eventsThresh = $this->getThreshold('threshold_ip_events', 10);
        $usersThresh  = $this->getThreshold('threshold_ip_users', 3);
        $limit        = $this->getTableLimit(200);

        $byIp = [];

        foreach ($rows as $r) {
            if ($r['event'] !== 'LOGIN_FAIL') continue;

            $ip = (string)$r['ip'];
            if ($ip === '') continue;

            if (!isset($byIp[$ip])) {
                $byIp[$ip] = [
                    'ip'           => $ip,
                    'total_events' => 0,
                    'users'        => [],
                    'first_seen'   => $r['ts'],
                    'last_seen'    => $r['ts'],
                ];
            }

            $byIp[$ip]['total_events']++;
            $byIp[$ip]['users'][(string)$r['user']] = true;

            if ($r['ts'] < $byIp[$ip]['first_seen']) $byIp[$ip]['first_seen'] = $r['ts'];
            if ($r['ts'] > $byIp[$ip]['last_seen'])  $byIp[$ip]['last_seen']  = $r['ts'];
        }

        $filtered = [];
        foreach ($byIp as $ip => $info) {
            $distinctUsers = count($info['users']);
            if ($info['total_events'] >= $eventsThresh || $distinctUsers >= $usersThresh) {
                $info['distinct_users'] = $distinctUsers;
                unset($info['users']);
                $filtered[] = $info;
            }
        }

        usort($filtered, function (array $a, array $b): int {
            return (int)$b['total_events'] <=> (int)$a['total_events'];
        });

        return array_slice($filtered, 0, $limit);
    }

    /**
     * Ledger collection detailing off-hours raw matrix events.
     * @return array<int, array<string, mixed>>
     */
    public function getOffHoursEvents(): array
    {
        $rows          = $this->getWindowedLogRows();
        [$start, $end] = $this->getOffHoursBounds();
        $limit         = $this->getTableLimit(500);

        $startTime = substr($start, 0, 5);
        $endTime   = substr($end, 0, 5);

        $out = [];
        foreach ($rows as $r) {
            $time = substr((string)$r['ts'], 11, 5);
            if ($this->isWithinCoreHours($time, $startTime, $endTime)) {
                continue;
            }
            $out[] = $r;
        }

        return array_slice($out, 0, $limit);
    }
}
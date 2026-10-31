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
 * Gather IP-level failed-login anomaly trends.
 *
 * Keeps invalid/unknown username attempts as security evidence
 * without counting the REDCap sentinel value as a real user.
 *
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
        if (($r['event'] ?? '') !== 'LOGIN_FAIL') {
            continue;
        }

        $ip = (string)($r['ip'] ?? '');

        if ($ip === '') {
            continue;
        }

        $user = (string)($r['user'] ?? '');
        $ts   = (string)($r['ts'] ?? '');

        if (!isset($byIp[$ip])) {
            $byIp[$ip] = [
                'ip'                       => $ip,
                'total_events'             => 0,
                'known_users'              => [],
                'invalid_username_attempts'=> 0,
                'first_seen'               => $ts,
                'last_seen'                => $ts,
            ];
        }

        $byIp[$ip]['total_events']++;

        if ($user === '[not_valid_username]' || $user === '') {
            $byIp[$ip]['invalid_username_attempts']++;
        } else {
            $byIp[$ip]['known_users'][$user] = true;
        }

        if (
            $ts !== ''
            && (
                $byIp[$ip]['first_seen'] === ''
                || $ts < $byIp[$ip]['first_seen']
            )
        ) {
            $byIp[$ip]['first_seen'] = $ts;
        }

        if (
            $ts !== ''
            && (
                $byIp[$ip]['last_seen'] === ''
                || $ts > $byIp[$ip]['last_seen']
            )
        ) {
            $byIp[$ip]['last_seen'] = $ts;
        }
    }

    $filtered = [];

    foreach ($byIp as $info) {
        $distinctKnownUsers = count($info['known_users']);

        if (
            $info['total_events'] >= $eventsThresh
            || $distinctKnownUsers >= $usersThresh
        ) {
            $info['distinct_known_users'] = $distinctKnownUsers;
            unset($info['known_users']);

            $filtered[] = $info;
        }
    }

    usort(
        $filtered,
        function(array $a, array $b): int {
            return (int)$b['total_events']
                <=> (int)$a['total_events'];
        }
    );

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

        
        $startTime = substr($start, 0, 5);
        $endTime   = substr($end, 0, 5);

        $out = [];

        foreach ($rows as $r) {
            $timestamp = (string)($r['ts'] ?? '');

            if ($timestamp === '') {
                continue;
            }

            $time = substr($timestamp, 11, 5);

            // ISO-8601 weekday number: 1 = Monday, 7 = Sunday.
            $dayOfWeek = (int)date('N', strtotime($timestamp));
            $isWeekend = ($dayOfWeek >= 6);

            // Weekend activity is always considered off-hours.
            // On weekdays, use the configured core-hours window.
            if (!$isWeekend && $this->isWithinCoreHours($time, $startTime, $endTime)) {
                continue;
            }

           $out[] = $r;
           }
            return $out;
    }
}
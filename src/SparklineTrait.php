<?php
namespace ForetellLite;

trait SparklineTrait
{
    /**
     * Compute array vectors for high-level UI dash visualizations.
     * @return array<string, array<int, int>>
     */
    public function getAllSparklineData(): array
    {
        $rows          = $this->getWindowedLogRows();
        $days          = $this->getWindowDays();
        [$start, $end] = $this->getOffHoursBounds();

        $startTime = substr($start, 0, 5);
        $endTime   = substr($end, 0, 5);

        /** @var array<string, array<string, mixed>> $byDate */
        $byDate = [];

        foreach ($rows as $r) {
            $d = substr((string)$r['ts'], 0, 10);

            if (!isset($byDate[$d])) {
                $byDate[$d] = [
                    'login_fail'    => 0,
                    'ip_set'        => [],
                    'user_set'      => [],
                    'system_events' => 0,
                    'off_hours'     => 0,
                ];
            }

            $event = (string)$r['event'];

            if ($event === 'LOGIN_FAIL') {
                $byDate[$d]['login_fail']++;
                $byDate[$d]['ip_set'][(string)$r['ip']]     = true;
                $byDate[$d]['user_set'][(string)$r['user']] = true;
            }

            $byDate[$d]['system_events']++;

            $time = substr((string)$r['ts'], 11, 5);
            if (!$this->isWithinCoreHours($time, $startTime, $endTime)) {
                $byDate[$d]['off_hours']++;
            }
        }

        $series = [
            'suspicious_logins' => [],
            'ip_anomalies'      => [],
            'user_anomalies'    => [],
            'system_events'     => [],
            'off_hours'         => [],
        ];

        for ($i = $days - 1; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-{$i} days"));
            $r = $byDate[$d] ?? null;

            $series['suspicious_logins'][] = $r ? (int)$r['login_fail']        : 0;
            $series['ip_anomalies'][]      = $r ? count($r['ip_set'])           : 0;
            $series['user_anomalies'][]    = $r ? count($r['user_set'])         : 0;
            $series['system_events'][]     = $r ? (int)$r['system_events'] : 0;
            $series['off_hours'][]         = $r ? (int)$r['off_hours'] : 0;
        }

        return $series;
    }
}
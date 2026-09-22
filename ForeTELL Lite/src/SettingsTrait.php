<?php
namespace ForetellLite;

trait SettingsTrait
{
    /**
     * Get analysis window in days.
     * @return int
     */
    public function getWindowDays(): int
    {
        $days = (int)$this->getSystemSetting('window_days');
        return $days > 0 ? $days : 7;
    }

    /**
     * Get table view layout limits.
     * @param int $default
     * @return int
     */
    public function getTableLimit(int $default = 200): int
    {
        $limit = (int)$this->getSystemSetting('table_limit');
        return $limit > 0 ? $limit : $default;
    }

    /**
     * Safely resolve configurable signal and count thresholds.
     * @param string $key
     * @param int $default
     * @return int
     */
    public function getThreshold(string $key, int $default): int
    {
        $val = (int)$this->getSystemSetting($key);
        return $val > 0 ? $val : $default;
    }

    /**
     * Extract off-hours operational schedule bounds.
     * @return array<int, string>
     */
    public function getOffHoursBounds(): array
    {
        $start = trim((string)$this->getSystemSetting('off_hours_start'));
        $end   = trim((string)$this->getSystemSetting('off_hours_end'));

        if ($start === '') $start = '19:00';
        if ($end   === '') $end   = '07:00';

        if (strlen($start) === 5) $start .= ':00';
        if (strlen($end) === 5) $end .= ':00';

        return [$start, $end];
    }

    /**
     * Parse safe users exclusion list.
     * @return array<int, string>
     */
    public function getSafeUsersArray(): array
    {
        $raw = (string)$this->getSystemSetting('safe_users');
        if (trim($raw) === '') return [];
        return array_filter(array_map('trim', explode(',', $raw)));
    }

    /**
     * Parse safe projects exclusion list.
     * @return array<int, string>
     */
    public function getSafeProjectsArray(): array
    {
        $raw = (string)$this->getSystemSetting('safe_projects');
        if (trim($raw) === '') return [];
        return array_filter(array_map('trim', explode(',', $raw)));
    }

    /**
     * Parse safe IP range patterns.
     * @return array<int, string>
     */
    public function getSafeIpRangesArray(): array
    {
        $raw = (string)$this->getSystemSetting('safe_ip_ranges');
        if (trim($raw) === '') return [];
        return array_filter(array_map('trim', explode(',', $raw)));
    }

    /**
     * Extract minimum severity display signal.
     * @return string
     */
    public function getMinSeverity(): string
    {
        $min = (string)$this->getSystemSetting('min-signal-severity');
        if (!in_array($min, ['low', 'medium', 'high'], true)) {
            return 'low';
        }
        return $min;
    }

    /**
     * Evaluates whether a given timestamp time falls inside core operational shifts.
     * @param string $time
     * @param string $start
     * @param string $end
     * @return bool
     */
    public function isWithinCoreHours(string $time, string $start, string $end): bool
    {
        $t = substr($time, 0, 5);
        $s = substr($start, 0, 5);
        $e = substr($end, 0, 5);

        if ($s < $e) {
            return ($t >= $s && $t < $e);
        }
        // Overnight window (e.g. 19:00–07:00): core hours are between end and start
        return !($t >= $s || $t < $e);
    }
}
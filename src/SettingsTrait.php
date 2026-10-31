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
     * Organization name for report branding (empty string if not configured).
     * @return string
     */
    public function getOrgName(): string
    {
        return trim((string)$this->getSystemSetting('org_name'));
    }

    /**
     * "Prepared By" team label for reports.
     * @return string
     */
    public function getReportPreparedBy(): string
    {
        $v = trim((string)$this->getSystemSetting('report_prepared_by'));
        return $v !== '' ? $v : 'REDCap System Administration';
    }

    /**
     * Confidentiality / retention note shown in the report footer.
     * @return string
     */
    public function getReportFooterNote(): string
    {
        $v = trim((string)$this->getSystemSetting('report_footer_note'));
        return $v !== '' ? $v : 'CONFIDENTIAL — For internal security team use only.';
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
         * Convert a severity label to a comparable numeric rank.
         *
         * @param string $severity
         * @return int
         */
    public function getSeverityRank(string $severity): int
    {
        switch (strtolower(trim($severity))) {
            case 'critical':
                return 4;

            case 'high':
                return 3;

            case 'medium':
                return 2;

            case 'low':
                return 1;

            default:
                return 0;
        }
    }

    /**
     * Determine whether a classified finding meets the configured
     * minimum dashboard severity.
     *
     * This is presentation-only. It must not be used to remove
     * events from the underlying security-analysis dataset.
     *
     * @param string $severity
     * @return bool
     */
    public function meetsDashboardSeverity(string $severity): bool
    {
        $minimum = $this->getMinSeverity();

        return $this->getSeverityRank($severity)
            >= $this->getSeverityRank($minimum);
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
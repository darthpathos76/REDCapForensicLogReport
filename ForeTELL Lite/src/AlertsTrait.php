<?php
namespace ForetellLite;

trait AlertsTrait
{
    /**
     * Main runtime alert digest engine. Safe for standard CRON execution blocks.
     * @return void
     */
    public function sendAlertDigest(): void
    {
        try {
            if (!$this->getSystemSetting('alert_email_enabled')) {
                return;
            }

            $recipient = trim((string)$this->getSystemSetting('alert_email'));
            if ($recipient === '') {
                return;
            }

            // Enforce cooling periods
            $cooldown = (int)$this->getSystemSetting('alert_cooldown_minutes');
            if ($cooldown <= 0) {
                $cooldown = 60;
            }

            $lastSent = (int)$this->getSystemSetting('last_alert_sent_ts');
            if (time() - $lastSent < ($cooldown * 60)) {
                return;
            }

            $alertSev = (string)$this->getSystemSetting('alert_min_severity');
            if (!in_array($alertSev, ['low', 'medium', 'high'], true)) {
                $alertSev = 'high';
            }
            $alerts = $this->collectHighPriorityAlerts($alertSev);

            if (empty($alerts)) {
                return;
            }

            // Filter out anything we've already alerted on. Each alert carries a
            // 'key' fingerprint (set in collectHighPriorityAlerts) that uniquely
            // identifies the underlying event/group at its current count. If the
            // same fingerprint was sent before, it's a stale repeat from the
            // rolling analysis window, not a new issue, so it's dropped here.
            $seen = $this->getSeenAlertKeys();
            $newAlerts = [];
            $newKeys = [];

            foreach ($alerts as $a) {
                $key = (string)($a['key'] ?? '');
                if ($key === '' || isset($seen[$key])) {
                    continue;
                }
                $newAlerts[] = $a;
                $newKeys[$key] = time();
            }

            if (empty($newAlerts)) {
                // Nothing new since the last alert. Don't send, and don't touch
                // last_alert_sent_ts so a genuinely new event isn't held up
                // waiting for a fresh cooldown window.
                return;
            }

            $subject = "[ForeTELL Lite] Security Alert Digest";
            $body = "The following high-priority security events were detected:\n\n";

            foreach ($newAlerts as $a) {
                $body .= strtoupper((string)$a['severity']) . ": " . (string)$a['message'] . "\n";
            }

            $body .= "\nTimestamp: " . date('Y-m-d H:i:s');

            if (class_exists('\\REDCap')) {
                \REDCap::email($recipient, 'no-reply@redcap.local', $subject, nl2br($body));
            }

            $this->setSystemSetting('last_alert_sent_ts', time());
            $this->saveSeenAlertKeys(array_merge($seen, $newKeys));

        } catch (\Throwable $e) {
            error_log('[ForeTELL Lite] sendAlertDigest error: ' . $e->getMessage());
        }
    }

    /**
     * Load the registry of alert fingerprints already emailed, pruning anything
     * older than the analysis window so fingerprints can naturally re-fire once
     * they've rolled out of the lookback period.
     * @return array<string, int> map of fingerprint => unix timestamp first sent
     */
    private function getSeenAlertKeys(): array
    {
        $raw = (string)$this->getSystemSetting('alert_seen_keys');
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        $days = method_exists($this, 'getWindowDays') ? $this->getWindowDays() : 7;
        $cutoff = time() - (($days + 1) * 86400);

        $out = [];
        foreach ($decoded as $key => $ts) {
            if ((int)$ts >= $cutoff) {
                $out[(string)$key] = (int)$ts;
            }
        }

        return $out;
    }

    /**
     * Persist the seen-alert registry, capped to a sane size as a safety net
     * against unbounded growth.
     * @param array<string, int> $keys
     * @return void
     */
    private function saveSeenAlertKeys(array $keys): void
    {
        if (count($keys) > 500) {
            // Keep the most recent 500 by timestamp
            arsort($keys);
            $keys = array_slice($keys, 0, 500, true);
        }

        $this->setSystemSetting('alert_seen_keys', json_encode($keys));
    }
}
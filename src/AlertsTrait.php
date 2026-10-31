<?php
namespace ForetellLite;

trait AlertsTrait
{
    /**
     * Main runtime alert digest engine.
     *
     * Applies alert enablement, cooldown, severity, deduplication,
     * and qualifying-finding requirements before attempting delivery.
     */
    public function sendAlertDigest(): void
    {
        try {
            if (!$this->getSystemSetting('alert_email_enabled')) {
                return;
            }

            $recipient = trim(
                (string)$this->getSystemSetting('alert_email')
            );

            if ($recipient === '') {
                return;
            }

            // Enforce the configured cooldown for production alerts.
            $cooldown = (int)$this->getSystemSetting(
                'alert_cooldown_minutes'
            );

            if ($cooldown <= 0) {
                $cooldown = 60;
            }

            $lastSent = (int)$this->getSystemSetting(
                'last_alert_sent_ts'
            );

            if (
                $lastSent > 0
                && time() - $lastSent < ($cooldown * 60)
            ) {
                return;
            }

            $alertSeverity = (string)$this->getSystemSetting(
                'alert_min_severity'
            );

            if (
                !in_array(
                    $alertSeverity,
                    ['low', 'medium', 'high'],
                    true
                )
            ) {
                $alertSeverity = 'high';
            }

            $alerts = $this->collectHighPriorityAlerts(
                $alertSeverity
            );

            if (empty($alerts)) {
                return;
            }

            /*
             * Remove alerts that were already delivered. Each alert
             * carries a fingerprint that identifies the underlying
             * event or group at its current count.
             */
            $seen      = $this->getSeenAlertKeys();
            $newAlerts = [];
            $newKeys   = [];
            $sentAt    = time();

            foreach ($alerts as $alert) {
                $key = (string)($alert['key'] ?? '');

                if ($key === '' || isset($seen[$key])) {
                    continue;
                }

                $newAlerts[]   = $alert;
                $newKeys[$key] = $sentAt;
            }

            if (empty($newAlerts)) {
                return;
            }

            $subject = '[ForeTELL Lite] Security Alert Digest';

            $body = "The following security events were detected:\n\n";

            foreach ($newAlerts as $alert) {
                $severity = strtoupper(
                    (string)($alert['severity'] ?? 'UNKNOWN')
                );

                $message = (string)(
                    $alert['message'] ?? 'No description available'
                );

                $body .= $severity . ': ' . $message . "\n";
            }

            $body .= "\nGenerated: "
                . date('Y-m-d H:i:s');

            $sent = $this->sendForetellEmail(
                $recipient,
                $subject,
                $body
            );

            /*
             * Only update cooldown and deduplication state after
             * successful delivery. Failed delivery remains eligible
             * for a future retry.
             */
            if (!$sent) {
                error_log(
                    '[ForeTELL Lite] Alert digest email was not sent.'
                );
                return;
            }

            $this->setSystemSetting(
                'last_alert_sent_ts',
                $sentAt
            );

            $this->saveSeenAlertKeys(
                array_merge($seen, $newKeys)
            );

        } catch (\Throwable $e) {
            error_log(
                '[ForeTELL Lite] sendAlertDigest error: '
                . $e->getMessage()
            );
        }
    }

    /**
     * Send a delivery test without requiring alerts, cooldown expiry,
     * enabled production alerts, or an unused alert fingerprint.
     *
     * @return bool true when REDCap reports successful delivery
     */
    public function sendTestAlert(): bool
    {
        try {
            $recipient = trim(
                (string)$this->getSystemSetting('alert_email')
            );

            if ($recipient === '') {
                error_log(
                    '[ForeTELL Lite] Test alert not sent: '
                    . 'recipient email is not configured.'
                );
                return false;
            }

            $subject = '[ForeTELL Lite] Test Alert';

            $body = implode("\n", [
                'This is a ForeTELL Lite test alert.',
                '',
                'Email delivery is configured and operational.',
                '',
                'Generated: ' . date('Y-m-d H:i:s'),
            ]);

            $sent = $this->sendForetellEmail(
                $recipient,
                $subject,
                $body
            );

            if (!$sent) {
                error_log(
                    '[ForeTELL Lite] Test alert email was not sent.'
                );
            }

            return $sent;

        } catch (\Throwable $e) {
            error_log(
                '[ForeTELL Lite] sendTestAlert error: '
                . $e->getMessage()
            );

            return false;
        }
    }

    /**
     * Send a ForeTELL email through REDCap.
     *
     * Keeping delivery in one method ensures production and test
     * messages use the same recipient-independent transport logic.
     */
    private function sendForetellEmail(
        string $recipient,
        string $subject,
        string $body
    ): bool {
        if (!class_exists('\\REDCap')) {
            error_log(
                '[ForeTELL Lite] Email not sent: '
                . 'REDCap email service is unavailable.'
            );
            return false;
        }

        $from = trim(
            (string)($GLOBALS['project_contact_email'] ?? '')
        );

        if ($from === '') {
            $from = 'no-reply@redcap.local';
        }

        $result = \REDCap::email(
            $recipient,
            $from,
            $subject,
            nl2br(
                htmlspecialchars(
                    $body,
                    ENT_QUOTES,
                    'UTF-8'
                )
            )
        );

        return $result === true;
    }

    /**
     * Load alert fingerprints already delivered and remove entries
     * older than the current analysis window.
     *
     * @return array<string, int>
     */
    private function getSeenAlertKeys(): array
    {
        $raw = (string)$this->getSystemSetting(
            'alert_seen_keys'
        );

        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        if (!is_array($decoded)) {
            return [];
        }

        $days = method_exists($this, 'getWindowDays')
            ? $this->getWindowDays()
            : 7;

        $cutoff = time() - (($days + 1) * 86400);

        $out = [];

        foreach ($decoded as $key => $timestamp) {
            if ((int)$timestamp >= $cutoff) {
                $out[(string)$key] = (int)$timestamp;
            }
        }

        return $out;
    }

    /**
     * Persist the delivered-alert fingerprint registry.
     *
     * @param array<string, int> $keys
     */
    private function saveSeenAlertKeys(array $keys): void
    {
        if (count($keys) > 500) {
            arsort($keys);
            $keys = array_slice(
                $keys,
                0,
                500,
                true
            );
        }

        $encoded = json_encode($keys);

        if ($encoded === false) {
            error_log(
                '[ForeTELL Lite] Alert fingerprints '
                . 'could not be encoded.'
            );
            return;
        }

        $this->setSystemSetting(
            'alert_seen_keys',
            $encoded
        );
    }
}
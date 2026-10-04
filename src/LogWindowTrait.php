<?php
namespace ForetellLite;

trait LogWindowTrait
{
    /** * Local memory cache for log records to prevent multiple redundant SQL hits.
     * @var array<int, array<string, mixed>>|null 
     */
    private ?array $cachedLogRows = null;

    /**
     * Pulls rows from the system log table restricted exactly to our evaluation window.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getWindowedLogRows(): array
    {
        if ($this->cachedLogRows !== null) {
            return $this->cachedLogRows;
        }

        $days = $this->getWindowDays();
        $windowStart = date('Y-m-d H:i:s', time() - ($days * 86400));

        // All events that can ever be security signals — nothing else is fetched.
        // PAGE_VIEW and other navigation noise are excluded here at the DB layer
        // so they never consume memory or appear in any tab regardless of severity setting.
        $signalEvents = [
            'USER_RIGHTS', 'API_TOKEN_CREATE', 'USER_CREATE',  // high
            'DATA_EXPORT', 'DOC_UPLOAD', 'DOC_DELETE',         // medium
            'LOGIN_FAIL',                                        // medium (auth)
        ];
        $placeholders = implode(',', array_fill(0, count($signalEvents), '?'));

        /** @var string $sql */
        $sql = "
            SELECT ts, user, ip, event, page, project_id, event_id, miscellaneous
            FROM redcap_log_view
            WHERE ts >= ?
              AND event IN ($placeholders)
            ORDER BY ts DESC
            LIMIT 20000
        ";

        $params = array_merge([$windowStart], $signalEvents);

        // Leverage the framework parameterization layer securely
        $q = $this->query($sql, $params);
        
        $rows = [];
        while ($row = $q->fetch_assoc()) {
            $rows[] = $row;
        }

        /* ============================================================
         * FIRST PASS: SAFE INCLUSIONS / EXCLUSIONS
         * ============================================================ */
        $params = [];
        $safeUsers = $this->getSafeUsersArray();
        $safeProjects = $this->getSafeProjectsArray();
        $safeIpPatterns = $this->getSafeIpRangesArray();

        $rows = array_filter($rows, function(array $r) use ($safeUsers, $safeProjects, $safeIpPatterns): bool {
            // User filter
            if (!empty($safeUsers) && in_array($r['user'], $safeUsers, true)) {
                return false;
            }
            // Project filter
            if (!empty($safeProjects) && in_array((string)$r['project_id'], $safeProjects, true)) {
                return false;
            }
            // IP range filtering matching sql LIKE patterns
            if (!empty($safeIpPatterns)) {
                $ip = (string)$r['ip'];
                foreach ($safeIpPatterns as $pattern) {
                    $regex = '/^' . str_replace('%', '.*', preg_quote($pattern, '/')) . '$/';
                    if (preg_match($regex, $ip)) {
                        return false;
                    }
                }
            }
            return true;
        });

        /* ============================================================
         * SECOND PASS: DATA_EXPORT PAGE FILTER
         * redcap_log_view records DATA_EXPORT for both genuine file
         * downloads and routine report-builder interactions (viewing,
         * editing, previewing reports). We only want real exports.
         * Real export pages contain 'DataExport' or 'ExportManager';
         * report-builder pages contain 'report' or 'DataEntry'.
         * ============================================================ */
        $rows = array_filter($rows, function(array $r): bool {
            if ($r['event'] !== 'DATA_EXPORT') {
                return true;
            }
            $page = strtolower((string)($r['page'] ?? ''));
            return (strpos($page, 'dataexport') !== false
                 || strpos($page, 'exportmanager') !== false
                 || strpos($page, 'data_export') !== false);
        });

        /* ============================================================
         * THIRD PASS: SEVERITY FILTER
         * ============================================================ */
        $min = $this->getMinSeverity();
        $high   = ['USER_RIGHTS', 'API_TOKEN_CREATE', 'USER_CREATE'];
        $medium = ['DATA_EXPORT', 'DOC_UPLOAD', 'DOC_DELETE', 'LOGIN_FAIL'];

        $rows = array_filter($rows, function(array $r) use ($min, $high, $medium): bool {
            $sev = in_array($r['event'], $high, true) ? 'high'
                 : (in_array($r['event'], $medium, true) ? 'medium' : 'low');

            if ($min === 'medium' && $sev === 'low') return false;
            if ($min === 'high'   && $sev !== 'high') return false;

            return true;
        });

        $this->cachedLogRows = array_values($rows);
        return $this->cachedLogRows;
    }
}
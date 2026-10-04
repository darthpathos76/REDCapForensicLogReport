# ForeTELL Lite

**A lightweight REDCap security monitoring dashboard built as an External Module.**

ForeTELL Lite surfaces authentication anomalies, IP-level threats, user activity patterns, and off-hours access by querying `redcap_log_view` directly. All data stays within the REDCap database and is visible only to system administrators. No external tools, data exports, or infrastructure beyond REDCap itself are required.

---

## Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Dashboard Tabs](#dashboard-tabs)
- [Signal Levels](#signal-levels)
- [Automated Alert Digest](#automated-alert-digest)
- [Database Notes](#database-notes)
- [File Structure](#file-structure)
- [Known Limitations](#known-limitations)
- [Version History](#version-history)
- [Author](#author)

---

## Requirements

| Requirement | Detail |
|---|---|
| REDCap version | 17.x (tested on 17.0.3 and 17.0.8) |
| Framework version | 14 |
| PHP | 8.x |
| Database | MariaDB with `redcap_log_view` accessible |
| Permissions | System Administrator access only |
| External dependencies | None — uses REDCap-bundled Bootstrap 4 and Font Awesome |

---

## Installation

1. Download or clone the module folder and name it `foretell_lite_v1.0.0`.
2. Place it in your REDCap modules directory: `<webroot>/modules/foretell_lite_v1.0.0/`
3. Navigate to **Control Center → External Modules → Module Manager**.
4. Find **ForeTELL Lite** and click **Enable**.
5. The dashboard link appears in the Control Center left sidebar under **External Modules**.

---

## Configuration

Navigate to **Control Center → External Modules → ForeTELL Lite → Configure** to set the following options. All settings have sensible defaults and the dashboard works out of the box without any configuration.

### Analysis Settings

| Setting | Default | Description |
|---|---|---|
| Analysis window (days) | 7 | Lookback period for all queries |
| Maximum rows per table | 200 | Row limit applied to all data tables |
| Off-hours start time (HH:MM) | 19:00 | Start of off-hours window |
| Off-hours end time (HH:MM) | 07:00 | End of off-hours window (next day) |

### Detection Thresholds

| Setting | Default | Description |
|---|---|---|
| Suspicious login threshold | 6 | Minimum failed attempts per user+IP+day to flag |
| IP anomaly event threshold | 10 | Minimum total events per IP to flag |
| IP anomaly distinct user threshold | 3 | Minimum distinct users per IP to flag |
| User anomaly event threshold | 20 | Minimum total events per user to flag |
| User anomaly distinct IP threshold | 5 | Minimum distinct IPs per user to flag |

### Exclusions

| Setting | Description | Example |
|---|---|---|
| Trusted IP ranges | Comma-separated LIKE patterns excluded from all detection | `172.%, 3.98.92.%, 127.0.0.1` |
| Trusted users | Comma-separated usernames excluded from user anomaly detection | `site_admin, svc_account` |
| Excluded projects | Comma-separated project IDs excluded from project-scoped detection | `14, 27, 103` |

### Tab Toggles

Each of the five security tabs can be individually enabled or disabled. Tabs default to enabled when the setting has never been explicitly saved. Uncheck and save to hide a tab.

- Enable Suspicious Logins tab
- Enable IP Anomalies tab
- Enable User Activity tab
- Enable System Events tab
- Enable Off-Hours Activity tab

### Alert Settings

| Setting | Default | Description |
|---|---|---|
| Enable high-priority alert emails | Off | Master toggle for the alert digest |
| Alert recipient email address | — | Address to receive alert digest emails |
| Minimum severity to trigger email | High | Low / Medium / High |
| Minimum minutes between alerts | 60 | Cooldown period to prevent repeated alerts |
| Send a test alert on next cron run | Off | Check and save to trigger a test email |

---

## Dashboard Tabs

### Overview

The landing tab. Displays current configuration values, per-tab query definitions, signal level explanations, exclusion list, performance notes, alert digest conditions, and version information. All values are read live from settings so the tab always reflects the current configuration.

### Suspicious Logins

Surfaces failed login attempts grouped by user, IP address, and date. Uses `event = 'LOGIN_FAIL'` from `redcap_log_view`. Rows flagged High at 20+ attempts per user+IP+day, Medium at 10+, Low below that.

**Intended to detect:** Brute force attacks, credential spray campaigns, repeated failed access from automated tools.

### IP Anomalies

Shows IP addresses generating high login event volumes or appearing across multiple user accounts. Excludes trusted IP ranges configured in settings.

**Intended to detect:** Scanning tools, shared attack infrastructure, IPs used across many accounts simultaneously.

### User Activity

Shows user accounts with unusually high event volumes or appearing from many distinct IP addresses. Covers LOGIN_SUCCESS, LOGIN_FAIL, and PAGE_VIEW events.

**Intended to detect:** Credential sharing (one account used from many IPs), automated scraping, unusually active accounts warranting review.

### System Events

The 200 most recent events from `redcap_log_view`, sorted by signal level then timestamp descending so high-priority events always appear at the top regardless of when they occurred.

**Signal classification:**

| Signal | Events |
|---|---|
| High | USER_RIGHTS, API_TOKEN_CREATE, USER_CREATE |
| Medium | DATA_EXPORT, DOC_UPLOAD, DOC_DELETE |
| Low | All other events |

> **Note:** These event types will only appear once `redcap_log_view` is updated to expose the full `redcap_log_event` column set. Currently the view exposes only LOGIN_SUCCESS, LOGIN_FAIL, LOGOUT, and PAGE_VIEW. The badge classification is in place and will activate automatically once the view is updated.

### Off-Hours Activity

All events occurring before the configured start time, after the configured end time, or on Saturday and Sunday. Includes a user summary table, an hourly distribution bar chart, and a raw event ledger of the 500 most recent off-hours events.

**Intended to detect:** Unusual after-hours access, automated jobs running outside normal windows, accounts active at atypical times.

---

## Signal Levels

| Badge | Meaning |
|---|---|
| **HIGH** | Meets the upper detection threshold. Warrants prompt review. Triggers alert emails if configured. |
| **MEDIUM** | Meets the lower detection threshold. Worth monitoring for pattern development. |
| **LOW** | Below threshold but included for visibility. No immediate action required. |

---

## Automated Alert Digest

When alert emails are enabled, a cron job runs hourly and sends an HTML digest if any of the following high-priority conditions are detected. A configurable cooldown period prevents repeated emails within a short window.

| Check | Condition |
|---|---|
| Brute Force / Credential Spray | Failed attempts per user+IP+day ≥ 3× the suspicious login threshold |
| Credential Sharing Risk | Distinct IPs per user ≥ user IP threshold + 3 |
| High Off-Hours Volume | Off-hours events per user ≥ 50 in the analysis window |

The digest email includes the alert type, detailed context (user, IP, counts), and the REDCap instance URL. A test alert can be triggered by checking **Send a test alert on next cron run** in settings and saving — the flag clears automatically after sending.

> REDCap crons are triggered by user traffic, not a system scheduler. On a quiet server the hourly cron may not fire exactly on schedule.

---

## Database Notes

### redcap_log_view

ForeTELL Lite queries `redcap_log_view`, a database view that unions across all `redcap_log_event` partition tables into a single queryable source. This avoids the need to query individual partition tables directly.

**Current column set exposed by the view:**

| Column | Type | Notes |
|---|---|---|
| log_view_id | bigint | Primary key |
| ts | timestamp | Indexed |
| user | varchar(255) | Indexed |
| event | enum | LOGIN_SUCCESS, LOGIN_FAIL, LOGOUT, PAGE_VIEW |
| ip | varchar(100) | Indexed |
| browser_name | varchar(255) | |
| browser_version | varchar(255) | |
| full_url | text | |
| page | varchar(255) | Indexed |
| project_id | int | Indexed |
| event_id | int | |
| record | varchar(255) | |
| form_name | varchar(100) | |
| miscellaneous | text | Free-text context field |
| session_id | varchar(32) | Indexed |

### Indexes

The following indexes are confirmed present on `redcap_log_view` and cover the primary query patterns used by this dashboard:

```
page_ts_project_id  (page, ts, project_id)   — BTREE
ts_user_event       (ts, user, event)         — BTREE
```

No additional indexes are required. All queries are written to take advantage of the `ts` leading column in `ts_user_event`.

### Pending Enhancement

The current view definition exposes only four event types. A request has been submitted to update the view to expose the full `redcap_log_event` column set including `description`, `data_values`, `change_reason`, and richer event types such as USER_RIGHTS, DATA_EXPORT, and API_TOKEN_CREATE. The System Events badge classification and the alert digest checks for privilege escalation are already in place and will activate automatically once the view is updated.

---

## File Structure

```
foretell_lite_v1.0.0/
├── config.json                 Module manifest, settings, cron definition
├── foretelllite.php            Module class — all query methods and alert engine
├── README.md                   This file
└── pages/
    ├── index.php               Dashboard shell — cards, tabs, Chart.js sparklines
    ├── intro.php               Overview tab — live config, query definitions
    ├── suspicious_logins.php   Suspicious Logins tab
    ├── ip_anomalies.php        IP Anomalies tab
    ├── user_anomalies.php      User Activity tab
    ├── system_events.php       System Events tab
    └── off_hours.php           Off-Hours Activity tab
```

---

## Known Limitations

**Event type coverage** — `redcap_log_view` currently exposes only LOGIN_SUCCESS, LOGIN_FAIL, LOGOUT, and PAGE_VIEW. High-signal audit events (USER_RIGHTS, DATA_EXPORT, API_TOKEN_CREATE, record modifications) are not yet surfaced. This is a view definition limitation, not a module limitation.

**No caching** — All queries run at page load time against a live database view. On large instances with years of log history, load time increases with the analysis window. Keeping the window at 7 days and the row limit at 200 is recommended for daily use.

**Cron timing** — REDCap crons are triggered by user traffic, not a system clock. Alert emails may not fire exactly on the configured hourly schedule on quiet servers.

**IP exclusion pattern matching** — Trusted IP ranges use SQL LIKE patterns (e.g. `172.%`), not CIDR notation. Ensure patterns match your actual network ranges.

**No multi-instance support** — ForeTELL Lite is designed for single-instance deployments querying a local `redcap_log_view`. It does not aggregate across multiple REDCap instances.

---

## Version History

| Version | Date | Notes |
|---|---|---|
| 1.0.0 | 2026-06 | Initial release — five-tab dashboard, alert digest, configurable thresholds and exclusions, feature toggles, Chart.js sparklines |

---

## Author

**Chris Battiston**
REDCap System Administrator and Research Data Analyst
Women's College Hospital, Toronto, Ontario, Canada
chris.battiston@wchospital.ca

Built on REDCap External Module Framework v14.
Queries are read-only. No data is written to or modified in the REDCap database by this module, with the exception of module settings stored via the standard External Module settings API.

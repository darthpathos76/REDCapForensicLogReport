# ForeTELL & ForeTELL Lite — Forensic Log Analysis for REDCap
ForeTELL and ForeTELL Lite are companion tools designed to help REDCap administrators detect suspicious activity, understand user behavior, and strengthen operational security. Both tools analyze REDCap’s native logs, but each serves a different purpose and audience.

Together, they provide a complete, scalable, institution‑friendly security analytics ecosystem.

## Why ForeTELL Exists
REDCap’s native logging system is incredibly detailed — every action, every login, every project change, every data interaction is recorded. But the logs were never designed for analysis. They’re raw, dense, and difficult to interpret without significant effort.

Most institutions face the same challenges:

<ul><li>Security teams want visibility, but REDCap logs are not easily searchable</li>
<li>REDCap admins want early warning signs, but there’s no built‑in anomaly detection</li>
<li>Compliance teams want reporting, but REDCap doesn’t provide trend analysis</li>
<li>IT teams want to understand behavior, but the logs don’t tell a story</li></ul>

ForeTELL was created to bridge this gap.  It transforms REDCap’s raw logs into meaningful insights — highlighting unusual behavior, surfacing anomalies, and helping administrators understand what’s happening inside their systems.  ForeTELL Lite extends this mission by bringing a streamlined version of those capabilities directly into REDCap, making security awareness accessible even in environments without analytics infrastructure.  Together, they help institutions move from reactive to proactive security.

## Project Overview
### ForeTELL (Full Application — R/Shiny)
A full‑featured, interactive, high‑power analytics environment built in R/Shiny.
ForeTELL is designed for institutions that want:

<ul><li>Deep forensic analysis</li>
<li>Multi‑dimensional filtering</li>
<li>Interactive dashboards</li>
<li>Statistical anomaly detection</li>
<li>Institutional‑level reporting</li>
<li>Long‑term trend analysis</li>

It is the powerhouse of the ForeTELL ecosystem — ideal for central IT, security teams, and environments with small REDCap Teams.

### ForeTELL Lite (REDCap External Module)
A lightweight, fast, easy‑to‑deploy module that runs inside REDCap.

ForeTELL Lite is designed for:

<ul><li>Quick installation</li>
<li>Zero external dependencies</li>
<li>Built‑in dashboards</li>
<li>Daily or on‑demand alert digests</li>
<li>Real‑time anomaly detection</li>
<li>Minimal maintenance</li></ul>
  
It provides immediate value to REDCap administrators without requiring R/Shiny infrastructure.

## Shared Security Principles
Both ForeTELL and ForeTELL Lite follow the same core philosophy:

<ul><li>No external APIs</li>
<li>No outbound network calls</li>
<li>No cloud dependencies</li>
<li>All processing stays inside your institution</li>
<li>Built entirely on REDCap’s native logs</li>
<li>Your data never leaves your environment.</li></ul>


Architecture Overview
ForeTELL (R/Shiny)
Modular R components

Reactive dashboards

Statistical anomaly detection

Multi‑project analytics

Institutional‑level reporting

ForeTELL Lite (REDCap EM)
Trait‑based PHP architecture

REDCap‑native settings

Digest email engine

Sparkline visualizations

Efficient log windowing

Zero external dependencies

Screenshots (Coming Soon)
This section will include:

ForeTELL Lite dashboard overview

Sparkline visualizations

Trend analysis panels

High‑priority event summaries

ForeTELL (R/Shiny) interactive dashboards

Multi‑dimensional filtering views

Time‑series anomaly detection plots

Screenshots will be added once the UI is finalized and approved for public sharing.

How ForeTELL and ForeTELL Lite Integrate
ForeTELL and ForeTELL Lite are designed to work independently — but they shine brightest when used together.

ForeTELL Lite → ForeTELL
ForeTELL Lite acts as the “front line” inside REDCap:

Detects anomalies in real time

Sends digest alerts

Provides quick dashboards for admins

Highlights events worth deeper investigation

ForeTELL (the full R/Shiny app) then becomes the investigation environment:

Drill deeper into suspicious patterns

Compare across users, projects, or time windows

Explore multi‑dimensional relationships

Build institutional‑level reports

ForeTELL → ForeTELL Lite
ForeTELL can also inform ForeTELL Lite:

Identify new anomaly categories

Tune thresholds

Validate safe lists

Improve off‑hours boundaries

Suggest new alert rules

Together
They form a complete security analytics pipeline:

ForeTELL Lite catches the signal

ForeTELL explains the signal

Administrators respond with clarity and confidence

This combined workflow gives institutions both breadth (Lite) and depth (Full ForeTELL).

Installation
ForeTELL (R/Shiny)
Clone the repository

Install required R packages

Configure database connection

Deploy to Shiny Server or RStudio Connect

ForeTELL Lite (External Module)
Download the latest release

Extract into:

Code
<redcap-root>/modules/foretell_lite_vX.Y.Z/
Enable in Control Center

Configure system settings

Open the dashboard page

Configuration
ForeTELL
Database connection

Log retention windows

Statistical model parameters

UI customization

ForeTELL Lite
Safe IP ranges

Safe users

Safe projects

Off‑hours boundaries

Digest frequency

Alert thresholds

Future Enhancements & Ideas
The ForeTELL ecosystem is actively evolving. Planned and potential enhancements include:

ForeTELL Lite
Project‑level dashboards

User‑level behavioral profiles

Configurable anomaly scoring

Integration with REDCap’s Alerts & Notifications

Optional project‑specific safe lists

More granular digest scheduling

Exportable CSV/JSON summaries

ForeTELL (R/Shiny)
Machine‑learning‑based anomaly detection

Cross‑instance analytics (multi‑REDCap environments)

Customizable dashboards per institution

Role‑based access control

Automated weekly/monthly security reports

Integration with SIEM systems (Splunk, Sentinel, etc.)

Long‑term trend forecasting

Shared Enhancements
Unified configuration schema

Shared safe lists between Lite and Full

Common anomaly taxonomy

Optional API bridge for deeper integration

Institutional “security posture” scoring

Development Notes
ForeTELL Lite is fully PHPStan‑validated (Level 6)

No deprecated REDCap APIs

No dynamic includes

REDCap 17+ compatible

ForeTELL uses modular R/Shiny best practices

Version History
ForeTELL: See CHANGELOG.md

ForeTELL Lite: See config.json

License
Both ForeTELL and ForeTELL Lite are released under the MIT License.

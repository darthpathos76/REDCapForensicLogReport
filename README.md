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
A full‑featured, interactive, high‑power analytics environment built in R/Shiny.  ForeTELL is designed for institutions that want:

<ul><li>Deep forensic analysis</li>
<li>Multi‑dimensional filtering</li>
<li>Interactive dashboards</li>
<li>Statistical anomaly detection</li>
<li>Institutional‑level reporting</li>
<li>Long‑term trend analysis</li>

It is the powerhouse of the ForeTELL ecosystem — ideal for central IT, security teams, and environments with small REDCap Teams.

### ForeTELL Lite (REDCap External Module)
A lightweight, fast, easy‑to‑deploy module that runs inside REDCap.  ForeTELL Lite is designed for:

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

### Architecture Overview
#### ForeTELL (R/Shiny)
##### Modular R components
<ul><li>Reactive dashboards</li>
<li>Statistical anomaly detection</li>
<li>Multi‑project analytics</li>
<li>Institutional‑level reporting</li></ul>

##### ForeTELL Lite (REDCap EM)
<ul><li>Trait‑based PHP architecture</li>
<li>REDCap‑native settings</li>
<li>Digest email engine</li>
<li>Sparkline visualizations</li>
<li>Efficient log windowing</li>
<li>Zero external dependencies</li></ul>

### Screenshots
ForeTELL Lite dashboard overview
<img width="940" height="952" alt="image" src="https://github.com/user-attachments/assets/6460140e-7ff7-4abb-a29c-50c8fc44a82f" />

Trend analysis panels
<img width="990" height="656" alt="image" src="https://github.com/user-attachments/assets/e38f1236-5838-4510-8611-36edb2ec3ed0" />

High‑priority event summaries
<img width="710" height="156" alt="image" src="https://github.com/user-attachments/assets/c1d295f0-1b48-4878-ad52-19b34477d3b1" />


ForeTELL (R/Shiny) interactive dashboards
Multi‑dimensional filtering views
Time‑series anomaly detection plots
Screenshots will be added once the UI is finalized and approved for public sharing.

### How ForeTELL and ForeTELL Lite Integrate
ForeTELL and ForeTELL Lite are designed to work independently — but they work the best when used together.

#### ForeTELL Lite → ForeTELL
ForeTELL Lite acts as the “front line” inside REDCap:
<ul><li>Detects anomalies in real time</li>
<li>Sends digest alerts</li>
<li>Provides quick dashboards for admins</li>
<li>Highlights events worth deeper investigation</li></ul>

#### ForeTELL (the full R/Shiny app) then becomes the investigation environment:
<ul><li>Drill deeper into suspicious patterns</li>
<li>Compare across users, projects, or time windows</li>
<li>Explore multi‑dimensional relationships</li>
<li>Build institutional‑level reports</li></ul>

#### ForeTELL → ForeTELL Lite
ForeTELL can also inform ForeTELL Lite:
<ul><li>Identify new anomaly categories</li>
<li>Tune thresholds</li>
<li>Validate safe lists</li>
<li>Improve off‑hours boundaries</li>
<li>Suggest new alert rules</li>

#### Together...
They form a complete security analytics pipeline:
<ul><li>ForeTELL Lite catches the signal</li>
<li>ForeTELL explains the signal</li>
<li>Administrators respond with clarity and confidence</li>
This combined workflow gives institutions both breadth (Lite) and depth (Full ForeTELL).

### Installation
#### ForeTELL (R/Shiny)
<ol><li>Clone the repository</li>
<li>Install required R packages</li>
<li>Configure database connection</li>
<li>Deploy to Shiny Server or RStudio Connect</li>

#### ForeTELL Lite (External Module)
<ol><li>Download the latest release</li>
<li>Extract into <code><redcap-root>/modules/foretell_lite_vX.Y.Z/</code></li>
<li>Enable in Control Center</li>
<li>Configure system settings</li>
<li>Open the dashboard page</li>

### Configuration
#### ForeTELL
<ul><li>Database connection</li>
<li>Log retention windows</li>
<li>Statistical model parameters</li>
<li>UI customization</li></ul>

#### ForeTELL Lite
<ul><li>Safe IP ranges</li>
<li>Safe users</li>
<li>Safe projects</li>
<li>Off‑hours boundaries</li>
<li>Digest frequency</li>
<li>Alert thresholds</li>

### Future Enhancements & Ideas
The ForeTELL ecosystem is actively evolving. Planned and potential enhancements include:

#### ForeTELL Lite
<ul><li>Project‑level dashboards</li>
<li>User‑level behavioral profiles</li>
<li>Configurable anomaly scoring</li>
<li>Integration with REDCap’s Alerts & Notifications</li>
<li>Optional project‑specific safe lists</li>
<li>More granular digest scheduling</li>
<li>Exportable CSV/JSON summaries</li></ul>

#### ForeTELL (R/Shiny)
<ul><li>Cross‑instance analytics (multi‑REDCap environments)</li>
<li>Customizable dashboards per institution</li>
<li>Role‑based access control</li>
<li>Automated weekly/monthly security reports</li>
<li>Integration with other dashboarding / monitoring systems (Grafana, etc.)</li>
<li>Long‑term trend forecasting</li></ul>

Shared Enhancements
<ul><li>Unified configuration schema</li>
<li>Shared safe lists between Lite and Full</li>
<li>Common anomaly taxonomy</li>
<li>Optional API bridge for deeper integration</li>
<li>Institutional “security posture” scoring</li>

### Development Notes
ForeTELL Lite is fully PHPStan‑validated (Level 6)
No deprecated REDCap APIs
No dynamic includes
REDCap 17+ compatible
ForeTELL uses modular R/Shiny best practices

### Version History
ForeTELL: See CHANGELOG.md
ForeTELL Lite: See config.json

License
Both ForeTELL and ForeTELL Lite are released under the MIT License.

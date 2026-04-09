# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.2.0-alpha] — 2026-04-09

### Changed
- Reorganised repository-only lesson examples under `examples/`
- Added `.gitattributes` rules so release archives exclude examples, tests, and contribution-only files
- Rewrote plugin-facing documentation to describe the repository as a formal Moodle activity module package
- Tightened declared Moodle support metadata to the tested Moodle 5.0 branch

## [0.1.4-alpha] — 2026-04-08

### Added
- Added Moodle backup and restore support for activity settings, uploaded lesson packages, learner events, and submission snapshots
- Added a privacy provider covering stored interaction and submission data

### Changed
- Restored English labels in the `lang/en` pack for fullscreen controls
- Moved launch-path validation messages into language strings for plugin-localisation compliance
- Declared groups, groupings, and Moodle backup support explicitly via `micp_supports()`
- Extended privacy export/delete coverage to include teachers recorded as manual reviewers
- Removed redundant manual loading of plugin `styles.css` and relied on Moodle's standard plugin stylesheet pipeline

## [0.1.3-alpha] — 2026-04-08

### Changed
- Replaced host-container fullscreen controls with direct iframe fullscreen entry and exit handling
- Switched normal inline rendering back to page-level scrolling instead of nested host scroll areas
- Added same-origin iframe height measurement, root overflow normalization, and an injected exit control inside fullscreen iframe content

## [0.1.2-alpha] — 2026-04-08

### Changed
- Reworked the activity host frame so embedded lessons open in a taller, less cramped viewport by default
- Added learner-facing expand/collapse and fullscreen controls on the activity page
- Updated the host-side AMD controller to keep iframe height aligned with viewport state changes

## [0.1.1-alpha] — 2026-03-30

### Added
- `mod_micp` activity module, compatible with **Moodle 5.x**
- Server-side scoring engine reading `micp-scoring.json` rules
- Two scoring strategies: `all_or_nothing` and `proportional`
- Gradebook integration via `grade_update()` official API
- AJAX event submission via Moodle `core/ajax` services
- `MICP.sendEvent()` with `navigator.sendBeacon` fallback
- `MICP.submit()` with JSON POST and session-aware scoring
- `file.php` pluginfile handler for ZIP package extraction
- `report.php` participant grade report (teacher view)
- Built-in demo lesson: `examples/demo-package/index.html`

### Security
- All write ops require `require_login()` + `require_sesskey()`
- `userid` never trusted from client payload
- `score` always computed server-side

## [0.1.0-alpha] — 2026-03-24

### Added
- Initial plugin skeleton
- `version.php`, `lib.php`, `view.php`, `mod_form.php`
- `micp.js` client SDK
- Database schema (`micp_events`, `micp_submissions`)
- Language strings
- AMD JavaScript modules

# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
- Built-in demo lesson: `sample_content/demo.html`
- Production-ready sample packages in `generated/`:
  - `audio-digitization-micp/` (English, 33 nodes)
  - `audio-digitization-micp-zh/` (Chinese)
  - `photosynthesis-micp/` (English, progressive disclosure)

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

# mod_micp

[**中文版**](./README_zh.md)

`mod_micp` is a Moodle activity module that delivers uploaded HTML lesson packages inside Moodle, records learner interaction events, applies server-side scoring rules, and publishes grades to the gradebook.

The repository root is the plugin root. A release package must unpack directly to `mod/micp` in a Moodle site.

## Key Capabilities

- Upload a lesson package as a ZIP file or a single HTML file
- Launch the uploaded content inside the activity page
- Record learner events through Moodle AJAX services
- Score submissions on the server with `micp-scoring.json`
- Support mixed auto-graded and manual-review workflows
- Publish grades through Moodle's gradebook API
- Export, delete, and enumerate personal data through the privacy API
- Back up and restore activity configuration, uploaded packages, and learner records

## Requirements

- Moodle 5.0
- PHP 8.1 or later
- No Composer or npm step is required for runtime
- No external API key is required for learners

## Installation

Clone the repository directly into Moodle's `mod` directory:

```bash
git clone git@github.com:YOUR_USERNAME/moodle-mod_micp.git /path/to/your/moodle/mod/micp
```

Or install from a release archive so that Moodle finds:

```text
/path/to/your/moodle/mod/micp/version.php
```

Then visit `Site administration -> Notifications`.

Detailed installation notes are in [INSTALL.md](./INSTALL.md).

## Lesson Package Format

The uploaded lesson package should contain:

- `index.html`
- `micp-scoring.json`
- any related `assets/`

`window.MICP` is the client runtime bridge. It reports learner events and submits attempts, but scoring is always performed on the server.

Without `micp-scoring.json`, the plugin falls back to a minimal completion rule: any recorded interaction produces a full score, and no interaction produces zero.

## Repository Layout

The repository keeps Moodle plugin code at the root and stores non-runtime examples separately.

```text
.
├── amd/
├── backup/
├── classes/
├── db/
├── examples/
├── lang/
├── pix/
├── templates/
├── tests/
├── version.php
├── lib.php
├── mod_form.php
└── view.php
```

- `examples/` contains repository-only sample lesson packages and source files
- `.gitattributes` marks repository-only material so release archives stay focused on the plugin itself
- `tests/` contains PHPUnit coverage for scoring and submission services

## Teacher Workflow

1. Create a `mod_micp` activity in Moodle.
2. Upload a ZIP package or single HTML file.
3. Students open the activity and interact with the embedded lesson.
4. The lesson runtime sends events and submissions to Moodle.
5. The plugin scores objective items immediately and queues manual items for review when required.
6. Moodle stores the result and updates the gradebook.

## Privacy

The plugin stores only data required to deliver and grade the activity:

- learner interaction events
- the latest submission snapshot per learner
- manual-review metadata when a teacher finalises a review

The runtime does not require sending learner data to an external service.

## Development Notes

- [CONTRIBUTING.md](./CONTRIBUTING.md)
- [INSTALL.md](./INSTALL.md)
- [CHANGES.md](./CHANGES.md)
- [SECURITY.md](./SECURITY.md)

## License

GPL v3 or later

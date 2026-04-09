# MICP for Moodle (`mod_micp`)

[**中文版**](./README_zh.md)

MICP is a Moodle activity plugin for interactive lessons.

In plain language: it lets a teacher put an interactive HTML lesson into Moodle as a normal course activity, let students use it online, and send the result back to Moodle gradebook.

This is useful when a normal Moodle page or quiz is not enough, for example:

- click-through practice
- drag/drop or step-by-step activities
- simulations or visual explorations
- short written reflections mixed with automatically scored questions

## What A Teacher Gets

With MICP, a teacher can:

- upload one lesson package into Moodle
- let students open it like a normal activity
- automatically score the objective parts
- keep subjective parts for teacher review
- see results inside Moodle instead of using a separate website

The main benefit is simple: richer learning activities can run inside Moodle without building a custom plugin for every lesson.

## How It Works

1. Install the MICP plugin in Moodle.
2. Prepare or obtain a lesson package.
3. Create a MICP activity in a course.
4. Upload the lesson package.
5. Students open the activity and complete it.
6. Moodle stores the result and updates the gradebook.

## What Is A Lesson Package

A lesson package is usually a ZIP file containing:

- `index.html`
- `micp-scoring.json`
- optional `assets/`

You can create that package in different ways:

- write it manually
- have a developer build it
- use an AI authoring workflow

MICP does not require AI in order to run. AI is optional. The plugin's job is to deliver, record, score, and report the activity inside Moodle.

## What Changes In Teaching Practice

Without a tool like this, teachers often face a bad choice:

- stay inside Moodle, but use only rigid activity types
- build richer web activities, but lose Moodle integration

MICP is meant to remove that tradeoff.

It helps when a teacher wants students to:

- explore a diagram or process
- complete a guided multi-step activity
- interact with a custom visual explanation
- submit both objective answers and open responses in one activity

## Quick Start

### 1. Install the plugin

Place this repository in Moodle as:

```text
/path/to/your/moodle/mod/micp
```

Then visit:

```text
Site administration -> Notifications
```

Detailed installation notes are in [INSTALL.md](./INSTALL.md).

### 2. Create a MICP activity

Inside a Moodle course:

1. Turn editing on.
2. Add an activity.
3. Choose `MICP`.
4. Upload a lesson ZIP or single HTML file.
5. Save the activity.

### 3. Run it with students

Students open the activity in Moodle, interact with the lesson, and submit it.

MICP can:

- record interaction events
- score the attempt on the server
- send the grade to Moodle gradebook
- keep manual-review items for teacher follow-up

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

## Technical Notes

The repository root is the plugin root. A release package must unpack directly to `mod/micp` in a Moodle site.

The uploaded lesson package should contain:

- `index.html`
- `micp-scoring.json`
- any related `assets/`

`window.MICP` is the client runtime bridge. It reports learner events and submits attempts, but scoring is always performed on the server.

Without `micp-scoring.json`, the plugin falls back to a minimal completion rule: any recorded interaction produces a full score, and no interaction produces zero.

## Repository Layout

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

## Development Notes

- [CONTRIBUTING.md](./CONTRIBUTING.md)
- [INSTALL.md](./INSTALL.md)
- [CHANGES.md](./CHANGES.md)
- [SECURITY.md](./SECURITY.md)

## License

GPL v3 or later

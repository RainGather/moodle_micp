# mod_micp — Moodle Interactive Content Protocol

**mod_micp** is a Moodle activity module that delivers AI-generated interactive HTML lessons and records student scores directly into the Moodle gradebook.

---

## What It Does

1. Teachers create a **mod_micp** activity and upload a ZIP package (or pick a built-in demo)
2. Students open the activity, interact with the embedded HTML content
3. Every interaction is sent to the server via `MICP.sendEvent()`
4. On completion, `MICP.submit()` triggers server-side scoring
5. The final score is written to the Moodle gradebook via the official `grade_update()` API

The plugin is a **universal interaction framework** — it is not tied to any specific question type. Any HTML-based interactive experience can be plugged in.

---

## Requirements

- **Moodle 5.x** (verified against Moodle 5.0.dev)
- PHP 8.1+

---

## Quick Start

### 1. Install the plugin

Copy the `mod/micp/` directory into your Moodle's `mod/` directory:

```bash
cp -r mod/micp /path/to/your/moodle/mod/
```

Then visit **Site Administration → Notifications** to trigger the database installation.

### 2. Create an activity

1. Enable editing on any course page
2. Click **Add an activity** → select **MICP** (Interactive Content Protocol)
3. Give it a name and (optionally) upload a ZIP package

### 3. Bundle your own interactive lesson

A MICP lesson package is a ZIP containing:

```
my-lesson.zip
├── index.html          # Main entry — the interactive experience
├── micp-scoring.json   # Scoring rules (optional; defaults to 100 if absent)
└── assets/             # Images, audio, fonts, etc.
```

**`micp-scoring.json`** example:

```json
{
  "rules": [
    {
      "id": "step1",
      "label": "Read the introduction",
      "type": "interaction",
      "check": { "event": "interaction", "data.step": 1 },
      "weight": 1.0
    },
    {
      "id": "step2",
      "label": "Complete the exercise",
      "type": "interaction",
      "check": { "event": "interaction", "data.action": "exercise_done" },
      "weight": 1.0
    }
  ],
  "scoring": {
    "strategy": "all_or_nothing",
    "passing_score": 50
  }
}
```

Without `micp-scoring.json`, the server returns **100 if any interaction was recorded, 0 otherwise**.

---

## Frontend SDK

Inside every lesson page, the global `window.MICP` object is available:

```javascript
// Initialize (called automatically on page load)
MICP.init();

// Send an interaction event
MICP.sendEvent('interaction', { step: 1, action: 'click' });

// Submit the lesson — triggers server-side scoring + gradebook write
MICP.submit({ raw: { actions: [...] } });

// Read the current user/cmid context
const ctx = MICP.getContext();
// ctx.cmid, ctx.userid, ctx.courseid, ctx.sesskey
```

All requests include the Moodle `sesskey` automatically.

---

## Architecture

```
mod/micp/
├── lib.php              # Core: scoring engine, gradebook wrapper, file resolver
├── view.php             # Activity page (iframe host)
├── file.php             # Pluginfile handler for ZIP/HTML assets
├── report.php           # Participant grade report
├── mod_form.php         # Activity settings form
├── micp.js              # Client-side SDK
├── db/
│   ├── install.xml      # Table schema (micp_events, micp_submissions)
│   ├── services.php     # Moodle AJAX services
│   └── access.php       # Capabilities
├── classes/local/
│   └── scoring_service.php  # Server-side scoring logic
├── sample_content/      # Built-in demo lesson
└── lang/en/micp.php     # Language strings
```

### Scoring Flow

```
Student interacts
  → MICP.sendEvent() → /mod/micp/api/event.php (AJAX)
  → stored in {micp_events}

Student clicks "Done"
  → MICP.submit() → /mod/micp/api/submit.php (AJAX)
  → scoring_service::evaluate() reads micp-scoring.json
  → grade_update() writes to gradebook
  → returns { score, rawgrade, details }
```

---

## Built-in Sample Lessons

Two ready-to-use lesson packages are included in `generated/`:

| Package | Description |
|---|---|
| `audio-digitization-micp/` | English — Audio digitization fundamentals, 33 interaction nodes |
| `audio-digitization-micp-zh/` | 中文 — Same content, Chinese language |
| `photosynthesis-micp/` | English — Photosynthesis, progressive disclosure design |

ZIP files are pre-built and ready for upload:
- `generated/audio-digitization-micp.zip`
- `generated/audio-digitization-micp-zh.zip`
- `generated/photosynthesis-micp.zip`

---

## Security Model

- All write operations require `require_login()` + `require_sesskey()`
- `userid` is always read from the server session, never trusted from the client
- `score` is computed server-side; the client cannot forge a grade
- Repeated submissions overwrite the previous score (idempotent)

---

## Capabilities

| Capability | Default | Description |
|---|---|---|
| `mod/micp:addinstance` | teacher | Create/edit MICP activities |
| `mod/micp:view` | student | View the activity and interact |
| `mod/micp:submit` | student | Submit and receive a score |
| `mod/micp:viewreports` | teacher | View the participant report |

---

## Extensibility

The scoring engine is pluggable:

```php
// In classes/local/scoring_service.php
// Swap the evaluator by replacing $this->evaluator
// Built-in: AllOrNothingEvaluator, ProportionalEvaluator
// Future: AI evaluator, Python script evaluator
```

---

## License

GPLv3 — same as Moodle itself.

---

## Changelog

See [CHANGELOG.md](./CHANGELOG.md) for version history.

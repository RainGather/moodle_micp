# mod_micp — Moodle Interactive Content Protocol

[**中文版**](./README_zh.md)

**AI-first interactive HTML lessons for Moodle — authored by AI, graded by the server.**

---

## Create a Lesson in 30 Seconds

Install the plugin, then tell the AI what you want:

```
"Create a MICP lesson about [any topic]"
```

The bundled **micp-html-authoring** Skill (`.skills/micp-html-authoring/SKILL.md`) tells any AI agent how to generate a complete mod_micp package: interactive `index.html` + server-side `micp-scoring.json`. Upload the ZIP to Moodle and students get a self-grading interactive lesson — no manual authoring required.

---

## Install

```bash
cp -r mod/micp /path/to/your/moodle/mod/
```
Visit **Site Administration → Notifications** to install. Requires **Moodle 5.x**, PHP 8.1+.

---

## Use a Built-in Sample (Zero Authoring)

Three ready-to-upload packages are included:

| File | Topic |
|---|---|
| `generated/photosynthesis-micp.zip` | Photosynthesis — progressive disclosure design |
| `generated/audio-digitization-micp.zip` | Audio digitization — 33 interaction nodes (EN) |
| `generated/audio-digitization-micp-zh.zip` | Same, Chinese language |

Upload any `.zip` when creating a MICP activity in your course. Students interact → server grades → score writes to gradebook automatically.

---

## What mod_micp Does

1. Teacher uploads a ZIP lesson package (or picks a built-in demo)
2. Student opens the activity, interacts with the HTML
3. Every interaction is recorded server-side via `MICP.sendEvent()`
4. Student clicks "Done" → `MICP.submit()` triggers scoring
5. Score writes to Moodle gradebook via official `grade_update()` API

Not a quiz type — a **universal interaction framework**. Any HTML-based experience works.

---

## License

GPLv3

---

## Documentation

<details>
<summary>Click to expand — full technical documentation</summary>

### Architecture

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
└── lang/en/micp.php     # Language strings
```

### Scoring Flow

```
Student interacts
  → MICP.sendEvent() → stored in {micp_events}

Student clicks "Done"
  → MICP.submit() → scoring_service::evaluate() reads micp-scoring.json
  → grade_update() writes to gradebook
  → returns { score, rawgrade, details }
```

### Client SDK (`window.MICP`)

```javascript
// Initialize (called automatically on page load)
MICP.init();

// Send an interaction event
MICP.sendEvent('interaction', {
  interactionid: 'q1_choice',
  response: 'a',
  outcome: 'selected',
  sequence: 1,
});

// Submit — triggers server-side scoring + gradebook write
MICP.submit({ raw: { actions: actions } });

// Read current user/cmid context
const ctx = MICP.getContext();
// ctx.cmid, ctx.userid, ctx.courseid, ctx.sesskey
```

All requests include the Moodle `sesskey` automatically. **The client never computes a score.**

### Lesson Package Structure

A MICP lesson is a ZIP:

```
my-lesson.zip
├── index.html          # Interactive experience
├── micp-scoring.json   # Server-side grading rules
└── assets/
    └── micp.js         # Required; copy from .skills/micp-html-authoring/references/assets/
```

### `micp-scoring.json` Example

```json
{
  "rules": [
    {
      "id": "q1_choice",
      "label": "Question 1",
      "check": { "event": "interaction", "scoring": { "correct": "a" } }
    },
    {
      "id": "q2_reflect",
      "label": "Reflection",
      "check": { "event": "interaction", "scoring": { "requireNonEmpty": true } }
    }
  ],
  "scoring": {
    "strategy": "all_or_nothing",
    "passing_score": 50
  }
}
```

Scoring strategies: `all_or_nothing` (all rules complete = 100, else 0) or `proportional` (partial credit by weight).

Without `micp-scoring.json`, the server returns **100 if any interaction was recorded, 0 otherwise**.

### Security

- All write ops require `require_login()` + `require_sesskey()`
- `userid` always read from server session — never trusted from client
- `score` always computed server-side — client cannot forge a grade
- Repeated submissions overwrite the previous score (idempotent)

### Capabilities

| Capability | Default | Description |
|---|---|---|
| `mod/micp:addinstance` | teacher | Create/edit MICP activities |
| `mod/micp:view` | student | View and interact |
| `mod/micp:submit` | student | Submit and receive a score |
| `mod/micp:viewreports` | teacher | View participant report |

### Extensibility

The scoring engine is pluggable. Replace `$this->evaluator` in `classes/local/scoring_service.php`:

- `AllOrNothingEvaluator` — default
- `ProportionalEvaluator` — partial credit
- Future: AI evaluator, Python script evaluator

</details>

---

## Changelog

See [CHANGELOG.md](./CHANGELOG.md).

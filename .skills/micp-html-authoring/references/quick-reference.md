# MICP HTML Authoring — Quick Reference

## File Structure

```
output-dir/
├── index.html          # Main lesson (required)
├── micp-scoring.json   # Scoring config (required for graded lessons)
└── assets/
    └── micp.js         # JS runtime (copy from skill assets)
```

Nested packaging is also valid:

```text
my-lesson/
├── index.html
├── micp-scoring.json
└── assets/
    └── micp.js
```

If you nest the lesson, keep **all referenced assets relative to that nested `index.html`**.

## Scoring Types

| Type | scoring key | response example |
|---|---|---|
| Exact match | `"correct": "B"` | `response: "B"` |
| Non-empty | `"requireNonEmpty": true` | `response: "any text"` |
| Completed | `"completed": true` | `response: "revealed"` |
| Presence only | `{}` (empty) | any non-null response |

## interactionid Rules

- Use `snake_case`: `q1_choice`, `reflection_step3`
- Prefix with context: `wave_sampling`, `binary_encode`
- Every graded interactionid must appear in BOTH `index.html` AND `micp-scoring.json`

## MICP SDK Calls

```js
// Initialize (once, at end of script)
window.MICP.init({ source: 'my-lesson' });

// Record an interaction
window.MICP.sendEvent('interaction', {
  interactionid: 'my_id',
  response: 'user_response_value',
  outcome: 'correct|incorrect|selected|adjusted|completed',
  sequence: actions.length + 1,
  // ... any extra fields
});

// Submit attempt (once, when submit button clicked)
window.MICP.submit({
  raw: { actions: actions }
});
```

## Uploaded ZIP Path Rule

Moodle serves uploaded launch packages by the **full relative path** of the launch file.

- Root launch file: `index.html`
- Nested launch file: `my-lesson/index.html`

So when you author HTML:

- use `assets/micp.js`, not `/assets/micp.js`
- use relative asset URLs everywhere
- do not rely on basename-only lookup for `index.html` or other files

## Progressive Disclosure Setup

In JS setup section:
```js
var completedSteps = { 1: false, 2: false, 3: false };
var gradedInteractions = { 'q1_choice': true, 'q2_reflect': true };
var gradeStepMap = { 'q1_choice': 1, 'q2_reflect': 2 };
```

Each step's HTML has `id="step-N"` and `data-step="N"`.

## Interface Guardrails

- Make one action visually primary per step.
- Use semantic tokens and the existing template classes before adding one-off styling.
- Prefer SVG icons and concept visuals; avoid emoji as core UI.
- Keep motion subtle and helpful. If you add animation, support reduced-motion.
- Cover empty, loading, saved, and error states when the lesson needs them.
- Keep the lesson browser-friendly and responsive; do not mimic a native app shell.
- Never let styling interfere with MICP hooks, interaction IDs, or submission behavior.

## Template Placeholders (in index.html template)

| Placeholder | Purpose |
|---|---|
| `{{TITLE}}` | Page `<title>` |
| `{{LESSON_TITLE}}` | Main heading `<h1>` |
| `{{COURSE_LABEL}}` | Hero eyebrow text |
| `{{STEPS_CONTENT}}` | Replace with step HTML |
| `{{PROGRESS_ITEMS}}` | Replace with `<li>` elements |
| `{{TOTAL_STEPS}}` | Number for unlockUntil loop |
| `{{PACKAGE_SOURCE}}` | Identifier for MICP.init |
| `{{INTERACTION_ID}}` | Your stable interaction ID |

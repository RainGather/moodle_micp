---
name: micp-html-authoring
description: Generate complete interactive HTML lesson packages for Moodle mod_micp. Use whenever the user wants to create a MICP-compatible activity, interactive HTML lesson, progressive disclosure lesson, quiz-like interaction, or any HTML content for Moodle mod_micp. This skill generates both index.html and micp-scoring.json together, with proper MICP SDK integration, progressive step structure, and server-side grading. Triggers for: "create a lesson about X", "make an interactive HTML for MICP", "write a mod_micp activity", "generate an interactive quiz", "build a progressive disclosure lesson", "create MICP package", "制作互动HTML", "创建MICP课程包".
---

# MICP HTML Authoring

This skill creates **complete MICP-compatible lesson packages** — `index.html` + `micp-scoring.json` — for the `mod_micp` Moodle 5 activity module. Every generated package uses the MICP postMessage bridge to communicate with Moodle.

## What You Produce

Always produce these files together (never just one):

```
output-dir/
├── index.html          # Interactive lesson (has MICP SDK calls)
├── micp-scoring.json   # Server-side grading rules
└── assets/
    └── micp.js         # Copy from skill references/assets/
```

If the user only asks for one, remind them both are needed and produce both anyway.

## The Core Rule

**The HTML never computes its own grade.** It only records structured evidence. The server grades via `micp-scoring.json`. Never put `score`, `rawgrade`, or any grade claim in `submit()` payloads.

## ZIP Packaging Contract

Uploaded MICP packages are served by Moodle using the **full relative launch path**, not by guessing from the filename alone.

That means these ZIP layouts are both valid:

```text
# Flat ZIP
index.html
assets/
  micp.js
  ...

# Nested ZIP
my-lesson/
  index.html
  assets/
    micp.js
    ...
```

But the lesson must obey two rules:

1. The launch file path is a real relative path such as `index.html` or `my-lesson/index.html`.
2. All lesson assets must be referenced **relative to that launch file**, for example `assets/micp.js`, `assets/diagram.svg`, `./assets/audio.mp3`.

Do **not** assume the server will find `index.html` or `micp.js` by basename only. If the lesson lives in a nested directory, keep the whole package structure coherent inside that directory.

## SDK Contract

Every lesson must call these in `index.html`:

```js
// 1. At the bottom of your script — ONE time
window.MICP.init({ source: 'your-lesson-name' });

// 2. Every interaction
window.MICP.sendEvent('interaction', {
  interactionid: 'your_stable_id',   // must match micp-scoring.json
  response: 'user_response_value',    // what the learner did/chose/typed
  outcome: 'correct|incorrect|selected|adjusted|completed|saved',
  sequence: actions.length + 1,
});

// 3. On submit button click — ONE time
window.MICP.submit({ raw: { actions: actions } });
```

## Interactionid Naming

Good: `q1_choice`, `wave_sampling_rate`, `reflection_step3`, `binary_encode_101`
Bad: `button1`, `item`, `test`, `answer`

Every `interactionid` in `micp-scoring.json` **must** appear in `index.html` payloads exactly.

## Scoring Patterns (in micp-scoring.json)

```json
{ "scoring": { "correct": "B" } }           // exact match
{ "scoring": { "requireNonEmpty": true } }  // any non-blank text
{ "scoring": { "completed": true } }        // presence counts
{ "scoring": {} }                            // pure presence (no rules)
```

## Output Structure Per Lesson Type

### 1. Progressive Disclosure Lesson (multi-step, recommended)

Sequential steps that unlock one-by-one. Steps unlock when the graded interaction in the previous step is recorded.

**HTML structure:**
```html
<section class="step unlocked" id="step-1" data-step="1">
  <div class="step-head">
    <div>
      <div class="step-index">Step 1</div>
      <h2>Topic introduction</h2>
    </div>
    <div class="status-pill" id="status-step-1">Ready</div>
  </div>
  <div class="step-grid">
    <div class="viz-card">
      <!-- Canvas or SVG visualization -->
    </div>
    <div class="info-card">
      <h3>Checkpoint</h3>
      <!-- Interaction: choice buttons, slider, text, etc. -->
      <div class="option-grid" id="q1-options">
        <button type="button" class="option-button"
          data-group="q1" data-id="q1_choice" data-response="a">
          <strong>Option A</strong>
          Explanation.
        </button>
        <!-- more options... -->
      </div>
      <div class="feedback" id="q1-feedback"></div>
    </div>
  </div>
</section>
```

**JS setup (at top of script):**
```js
var actions = [];
var completedSteps = { 1: false, 2: false, 3: false, 4: false };
var gradedInteractions = {
  'q1_choice': true,
  'q2_quant_bits': true,
  'q3_binary': true,
  'q4_filter': true
};
var gradeStepMap = {
  'q1_choice': 1,
  'q2_quant_bits': 2,
  'q3_binary': 3,
  'q4_filter': 4
};
var latestByInteraction = {};
```

### 2. Single-Screen Quiz

All questions visible at once, submit at end. No progressive unlocking.

### 3. Exploration Lab

Non-graded sliders/canvases for free exploration + one final graded reflection text.

### 4. Visualization + Choice

Show a canvas animation, then ask a single-choice question about it.

## Workflow

1. **Understand the topic and learning goal** — ask if unclear
2. **Plan interactions** — list each graded interaction with its `interactionid`, type, and scoring rule
3. **Assign weights** — weights sum to your `maxscore` (usually 100); distribute by importance
4. **Generate** `index.html` and `micp-scoring.json` together
5. **Cross-check**: every `interactionid` in scoring config appears in HTML event payloads
6. **Verify**: `window.MICP.init()` is called, `sendEvent` fires for each interaction, `submit` fires on button click
7. **Verify path integrity**: if `index.html` is nested, all asset URLs still resolve relative to it
8. **Package**: ZIP the directory for upload to mod_micp

## Bundle Resources

Use these bundled references when generating:

| File | Purpose |
|---|---|
| `references/templates/index.html` | Full visual template with CSS framework |
| `references/templates/micp-scoring.json` | Scoring config template |
| `references/assets/micp.js` | MICP JavaScript runtime (copy to `assets/`) |
| `references/patterns/progressive-step.html` | Step HTML structure |
| `references/patterns/multiple-choice.html` | Choice button pattern |
| `references/patterns/canvas-waveform.html` | Animated waveform canvas |
| `references/patterns/slider.html` | Parameter slider |
| `references/patterns/text-reflection.html` | Open-ended reflection |
| `references/patterns/data-explorer.html` | Data→representation explorer |
| `references/quick-reference.md` | SDK cheat sheet |

## Quality Checklist

Before finishing, verify ALL of these:

- [ ] `window.MICP.init({ source: '...' })` called once at bottom of script
- [ ] Every graded interaction fires `window.MICP.sendEvent('interaction', {...})`
- [ ] Every `interactionid` in `micp-scoring.json` appears in HTML exactly as spelled
- [ ] `window.MICP.submit({ raw: { actions: actions } })` fires on submit button click
- [ ] Submit button has `id="submit-attempt"`
- [ ] Progress list `<ol id="progress-list">` exists with steps
- [ ] `actionLog` pre element with `id="action-log"` exists
- [ ] No `score`, `rawgrade`, or grade fields in submit payload
- [ ] `assets/micp.js` copied to output directory
- [ ] CSS uses the CSS variable system from the template (or no custom CSS conflicts)
- [ ] All paths are relative (ZIP-safe — no absolute URLs)
- [ ] If the package is nested, `index.html` and `assets/` still line up via relative paths
- [ ] Language attribute set correctly (`lang="en"` or `lang="zh"`)

## Styling System

The template uses a dark space-inspired design system. Key CSS variables:

```css
--color-ink: #ebf5ff;           /* primary text */
--color-muted: #9cb7cf;         /* secondary text */
--color-panel: rgba(9,17,40,0.84); /* card backgrounds */
--color-accent: #8feaff;         /* primary accent */
--color-accent-strong: #4fc3ff;  /* hover/active accent */
--color-secondary: #ffbf69;       /* step index, secondary accent */
--color-success: #7fffd4;         /* correct state */
--color-danger: #ff8f8f;         /* wrong state */
--bg-page: /* dark gradient background */
```

Use these classes: `.panel`, `.step`, `.viz-card`, `.info-card`, `.control-card`, `.option-button`, `.primary`, `.secondary`, `.feedback`, `.status-box`, `.sticky-panel`.

## Language

If the user says "in Chinese", "中文版", or "生成中文版本", set `lang="zh"` and translate all UI text. The micp-scoring.json interaction labels can stay in English or translate — scoring uses `id` fields, not labels.

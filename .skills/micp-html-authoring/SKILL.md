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

## Interface Quality Rule

Visual polish is important, but it is always secondary to the MICP contract. Modern styling must **never** interfere with MICP SDK integration, postMessage flows, step progression, accessibility, response capture, or server-side grading.

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
2. **Plan the interface system** — define a lightweight visual direction before writing HTML: hierarchy, palette, typography scale, spacing rhythm, icon style, motion limits, and anti-patterns
3. **Plan interactions** — list each graded interaction with its `interactionid`, type, and scoring rule
4. **Assign weights** — weights sum to your `maxscore` (usually 100); distribute by importance
5. **Generate** `index.html` and `micp-scoring.json` together
6. **Cross-check**: every `interactionid` in scoring config appears in HTML event payloads
7. **Verify**: `window.MICP.init()` is called, `sendEvent` fires for each interaction, `submit` fires on button click
8. **Verify path integrity**: if `index.html` is nested, all asset URLs still resolve relative to it
9. **Package**: ZIP the directory for upload to mod_micp

## UI/UX Authoring Guardrails

Apply these in order of priority:

1. **Accessibility and clarity first** — readable hierarchy, labeled controls, visible focus states, adequate contrast, and calm feedback matter more than decorative flair.
2. **Interaction clarity before styling** — each step should make the learner's next action obvious.
3. **Responsive layout before ornament** — lessons must remain readable and usable on narrow browser widths without horizontal scrolling.
4. **Style with restraint** — use polish to support comprehension, not to turn the lesson into a landing page or app shell.

When generating lesson UI:

- Prefer **one primary action per step or screen**. Secondary actions should be visually quieter.
- Use **progressive disclosure** so each step reveals only the amount of information needed for the current learning task.
- Use **semantic tokens** for color, spacing, radius, shadow, and motion. Avoid one-off hard-coded styling unless it clearly matches the template system.
- Use **SVG icons or topic visuals**, not emoji as structural UI.
- Keep motion subtle: short transitions, hover/focus feedback, and state changes that help orientation. Respect `prefers-reduced-motion` when adding custom animation.
- Include clear **empty, loading, saved, success, and error states** where the interaction needs them. Feedback should be calm, specific, and actionable.
- Keep visuals purposeful. Charts, diagrams, and hero visuals should explain the concept, not just decorate the page.
- Maintain a consistent visual rhythm: strong heading hierarchy, concise copy blocks, generous spacing, and repeatable card patterns.

## Anti-Patterns to Avoid

Do **not** generate any of these unless the user explicitly asks for them and they still fit the MICP lesson format:

- Landing-page hero sections with marketing copy or conversion language
- Multiple competing primary CTAs in one step
- Native-app chrome such as bottom tab bars, swipe-first navigation, or app-shell layouts
- Decorative animation, parallax, autoplay motion, or flashy transitions that distract from the task
- Emoji-driven UI in place of proper labels, icons, or diagrams
- Dense dashboards that bury the learning interaction under metrics or status widgets
- Styling changes that obscure interaction IDs, feedback states, or submit behavior

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
- [ ] One primary action is visually dominant in each step or screen
- [ ] Visual hierarchy is clear through spacing, heading scale, contrast, and grouping
- [ ] Layout works on narrow screens without horizontal scrolling or cramped controls
- [ ] Icons/illustrations are SVG-based and concept-relevant, not emoji placeholders
- [ ] Motion is subtle, purposeful, and reduced-motion-safe
- [ ] Empty/loading/saved/error states are covered where the interaction needs them
- [ ] Visual polish supports the lesson instead of turning it into a landing page or app shell
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

Use the template as a **lightweight design system**, not just a CSS dump:

- Reuse the token system for spacing, radius, shadow, and motion before inventing new values.
- Keep typography disciplined: one strong display style for major headings, one readable body style for instructions and feedback.
- Maintain a consistent elevation scale. A few soft shadows are enough; avoid stacking multiple glow effects.
- Prefer concise copy blocks and scannable cards over long paragraphs.
- Browser layouts should work comfortably across small mobile widths, tablet widths, and large desktop widths.
- If you customize the visual theme, preserve the same level of contrast, focus visibility, and state clarity.

## Language

If the user says "in Chinese", "中文版", or "生成中文版本", set `lang="zh"` and translate all UI text. The micp-scoring.json interaction labels can stay in English or translate — scoring uses `id` fields, not labels.

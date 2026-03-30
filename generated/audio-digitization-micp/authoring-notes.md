# Audio Digitization MICP package notes

This package is a zip-safe MICP lesson rooted at `index.html` with local script asset `assets/micp.js`.

## Gradable interaction ids and scoring

1. `analog_reveal`
   - Trigger: learner reveals the analog waveform story in section 1.
   - Scoring: `completed: true`
   - Weight: 10

2. `sampling_rate_choice`
   - Trigger: learner chooses which sampling density best preserves the waveform.
   - Scoring: `correct: "high"`
   - Weight: 15

3. `quantization_bits_choice`
   - Trigger: learner answers which bit depth gives 32 quantization levels.
   - Scoring: `correct: "5"`
   - Weight: 15

4. `binary_code_choice`
   - Trigger: learner identifies the correct 3-bit code for decimal level 5.
   - Scoring: `correct: "101"`
   - Weight: 15

5. `processing_filter_choice`
   - Trigger: learner identifies which digital process removes rapid zig-zag variation.
   - Scoring: `correct: "smoothing"`
   - Weight: 15

6. `final_synthesis_reflection`
   - Trigger: learner saves the final synthesis explanation.
   - Scoring: `requireNonEmpty: true`
   - Weight: 30

## Non-gradable exploratory events

The HTML also emits exploratory `interaction` events for sliders, sample freezes, and visualization focus changes, but those ids are not listed in `micp-scoring.json`. They exist only as evidence of learner exploration.

## Submission contract

- Calls `window.MICP.init()` on load.
- Emits `window.MICP.sendEvent('interaction', payload)` with stable ids.
- Submits only `raw.actions` via `window.MICP.submit({ raw: { actions: [...] } })`.
- Does **not** compute or submit client-authoritative grade fields.

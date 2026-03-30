---
name: ai-dynamic-course
description: Generate an "AI dynamic course" lesson package integrating Moodle (entry + Cloze post-quiz), MagicSchool (lesson-content markdown + proof codes used only inside MagicSchool), and optional H5P (key interactive course presentation only). Use when user asks to create/produce a dynamic AI-assisted lesson or course like "生成一个动态课程".
---

# AI Dynamic Course

Create one lesson package with a fixed structure:
- Moodle is the official entry + progress record.
- MagicSchool agent is the main learning experience (dialogue).
- H5P is optional and used only for key interactive displays (not post-tests).
- Post-lesson assessment is Moodle Cloze (not H5P).

## Workflow (per lesson)

1) Decide the lesson number/title and target folder.
2) Generate three proof codes (BRONZE/SILVER/GOLD), 32 chars, A-Z0-9.
3) Create lesson directory with standard files (strict naming):
   - `README.md` (merge: teacher setup + index)
   - Student-facing Moodle entry page (HTML-in-.txt)
   - MagicSchool lesson-content markdown (includes proof codes)
   - Moodle Cloze: post-quiz (multiple questions)
   - Optional: one CoursePresentation `.h5p` (no H5P quiz)
   - Optional: `src/` starter code
4) Verify constraints:
   - Proof codes appear only in MagicSchool content, never in Moodle student-facing pages or H5P.
   - H5P is not used for post-quiz.

## Output spec (must follow)

Use `references/course_spec.md` as the canonical spec, including strict folder/file naming rules and the single-README requirement.

## Tools / resources

- Spec: `references/course_spec.md`
- Proof code generator: `scripts/generate_proof_codes.py`
- Minimal H5P builder: `scripts/make_h5p_course_presentation_min.py`

## When writing Cloze

Use Moodle Cloze syntax (avoid free-text unless the answer is unambiguous).
Do NOT create any Moodle activity that asks students to submit proof codes. Proof codes are used inside MagicSchool only.

## Safety checks

Before finalizing a lesson package:
- Search student-facing files for proof codes (they must not be present).
- If generating `.h5p`, unzip and check `content/content.json` for proof codes.

---
name: h5p-patterns
description: Create interactive H5P content for Moodle and web platforms. Use when building interactive exercises, quizzes, or multimedia learning content.
allowed-tools: Read, Write, Grep, Glob, Bash
---

# H5P Integration Skill

Create and package H5P content for Moodle 4+.

## What this skill must do

When the user asks for “H5P content”, this skill should produce real `.h5p` files (not just a plan) whenever possible.

Default deliverables per lesson:
- 1x **Course Presentation** `.h5p` (slide-based teaching + embedded interactions)
- 1x **Quiz-style** `.h5p` (use `H5P.SingleChoiceSet` as a reliable “question set” when `H5P.QuestionSet` is not available)

## Local sample package (reference export)

This repository may include a decompressed, complete H5P export under `sample/`.
It is/was a *real* Moodle-exported `.h5p` unzip result, and is useful as an authoritative reference for:
- which libraries/versions are expected
- how `content/content.json` is shaped for common content types
- how `semantics.json` defines the authoring schema

`sample/` structure (typical):

```
sample/
  h5p.json
  content/
    content.json
    images/            # optional; assets referenced by content.json (may be empty)
  H5P.*-<ver>/         # runnable content types and dependencies
    library.json       # library metadata + dependency list
    semantics.json     # content schema (what keys/fields are valid)
    dist/              # bundled JS/CSS/assets (if library is built)
    scripts/ styles/   # source assets for some libraries
    language/*.json    # i18n strings
    icon.svg           # optional
    presave.js         # optional: validation/migration hooks
    upgrades.js        # optional: content upgrades between versions
  H5PEditor.*-<ver>/   # editor-side widgets used by H5P authoring UI
    library.json
    (scripts/styles/language/...)
  FontAwesome-<ver>/, jQuery.ui-<ver>/, Tether-<ver>/, Shepherd-<ver>/
                         # third-party deps some H5P libs rely on
```

Important: copying the full library folders into a new package can fail on some filesystems/permissions and often produces unnecessarily huge `.h5p` files.
Preferred approach: generate **minimal packages** containing only:
- `h5p.json`
- `content/content.json`

These minimal `.h5p` files import successfully on Moodle sites that already have the required H5P content types installed.

## Trigger

- H5P content requests
- Interactive element creation
- H5P embedding in Kirby or Moodle

## H5P in Moodle

H5P activities are native in Moodle 4+. This skill targets Moodle 4.3+.

### Minimal `.h5p` package format (practical)

A `.h5p` file is a ZIP archive with this structure:

- `h5p.json`
- `content/`
  - `content.json`

You can create it by zipping a folder with those files.

### Template versions (from `sample/`)

Use these versions unless the user provides different versions:
- Course Presentation: `H5P.CoursePresentation 1.25`
- Text block: `H5P.AdvancedText 1.1`
- Multiple choice interaction: `H5P.MultiChoice 1.16`
- Quiz set: `H5P.SingleChoiceSet 1.11`

If a site lacks `H5P.SingleChoiceSet`, fall back to embedding `H5P.MultiChoice` interactions inside a Course Presentation only.

### How to generate packages (workflow)

1) Choose output directory (usually per-lesson folder, e.g. `rewrite_book/<lesson>/`).
2) Create a working directory for each H5P package:
   - `<out>/h5p/<name>_pkg/h5p.json`
   - `<out>/h5p/<name>_pkg/content/content.json`
3) Write `h5p.json`:
   - `mainLibrary` must match the content type (`H5P.CoursePresentation` or `H5P.SingleChoiceSet`).
   - Include `preloadedDependencies` for the main library and its core deps.
4) Write `content/content.json`:
   - For Course Presentation: `{"presentation":{"slides":[...]},"override":...,"l10n":...}`
   - For SingleChoiceSet: fields must match `H5P.SingleChoiceSet` semantics for your installed version (this repo used to keep `sample/H5P.SingleChoiceSet-1.11/semantics.json` as a local reference).
5) Zip the package folder into `<out>/<title>.h5p`.
6) Sanity check: unzip/list entries and confirm both `h5p.json` and `content/content.json` exist.

### Content schema notes

**Course Presentation** (`sample/content/content.json` is/was a working example):
- Root keys: `presentation`, `override`, `l10n`
- Slides: `presentation.slides[]`
- Each slide contains `elements[]`.
- Typical element action libraries:
  - `H5P.AdvancedText 1.1` with params `{ "text": "<h2>...</h2>" }`
  - `H5P.MultiChoice 1.16` with params keys like `question`, `answers`, `behaviour`, `overallFeedback`

**SingleChoiceSet**: use semantics as the source of truth:
- `choices[]` where each choice has:
  - `question` (HTML string)
  - `answers` (list of 2–4 HTML strings) — **first answer is correct**
- `overallFeedback.overallFeedback[]` uses percent ranges (`from`/`to` 0–100)
- `behaviour` contains keys like `autoContinue`, `enableRetry`, `enableSolutionsButton`, `passPercentage`

### Recommended authoring style

- Keep text in Chinese (zh-cn) by default.
- Avoid images unless the user supplies assets.
- Prefer embedded checks (MultiChoice) over free-text to reduce grading ambiguity.

### Common content types

- Interactive Video
- Course Presentation
- Question Sets
- Branching Scenarios

### Embedding in Moodle Page

```html
<div class="cloodle-h5p-wrapper">
    <iframe src="/mod/h5pactivity/embed.php?id=123"
            class="h5p-iframe"
            allowfullscreen>
    </iframe>
</div>
```

## H5P in Kirby

Use iframe embedding with public H5P URLs:

```php
<?php snippet('h5p-embed', ['id' => $block->h5pId()]) ?>
```

### Snippet Template

```php
<div class="uk-card uk-card-default uk-card-body cloodle-h5p">
    <iframe
        src="<?= $moodleUrl ?>/mod/h5pactivity/embed.php?id=<?= $id ?>"
        class="uk-width-1-1"
        style="border: none; min-height: 400px;">
    </iframe>
</div>
```

## Styling H5P

```scss
.cloodle-h5p-wrapper {
    border-radius: $cloodle-border-radius;
    overflow: hidden;
    box-shadow: $card-box-shadow;

    iframe {
        width: 100%;
        min-height: 500px;
        border: none;
    }
}
```

## Content Types for Education

| Type                | Use Case              |
| ------------------- | --------------------- |
| Interactive Video   | Lecture with quizzes  |
| Course Presentation | Slide-based learning  |
| Question Set        | Assessment            |
| Dialog Cards        | Vocabulary/flashcards |
| Timeline            | Historical content    |


## Sample

当前目录下的 `sample/` 文件夹（如果存在）就是 Moodle 平台的 H5P 导出后解压出来的，以供参考。

### Quick check commands (optional)

- List `.h5p` contents: `python3 -c "import zipfile; z=zipfile.ZipFile('file.h5p'); print('\n'.join(z.namelist()[:20]))"`
- Verify required files exist: ensure `h5p.json` and `content/content.json` are inside the zip.


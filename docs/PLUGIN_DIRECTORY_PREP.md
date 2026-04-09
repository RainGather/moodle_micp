# Moodle Plugins Directory Prep

This note tracks the remaining work between the current repository state and a realistic Moodle Plugins Directory submission.

## What Is Already Covered

- Backup and restore API has been added for the activity module.
- Privacy API provider has been added.
- English language strings were normalised and hard-coded UI labels were reduced.
- The plugin now explicitly declares backup, groups, and groupings support.

## Remaining Manual Items

### 1. Publish from a dedicated public plugin repository

The current working repository is a multi-purpose project workspace, while the public Moodle plugin repository should be a plugin-focused repository.

Recommended target shape:

```text
moodle-mod_micp/
├── version.php
├── lib.php
├── view.php
├── ...
```

Recommended public repository name:

```text
moodle-mod_micp
```

In practice, that means publishing `mod/micp/` as the repository root instead of publishing the whole current workspace as-is.

### 2. Configure public release metadata outside the codebase

These are not solved by code changes alone:

- public issue tracker URL
- public documentation URL
- maintainer / security contact used for releases
- plugin directory entry text and categorisation

### 3. Produce submission assets

Prepare the assets expected for a real plugin listing:

- screenshots of the activity page
- screenshots of the teacher report / manual review flow
- a short and sober plugin description for the listing

### 4. Run real validation in a Moodle environment

This repository has not yet been validated in a runnable Moodle + PHP environment from this workspace.

Before submission, run at least:

- install and upgrade on Moodle 5.x
- backup and restore of a course containing `mod_micp`
- privacy export and delete requests for both learners and reviewers
- student submission flow
- teacher manual review flow
- report page rendering

### 5. Run cross-environment checks

Before submission, verify the plugin on the environments you intend to support:

- target PHP version(s)
- target database(s)
- developer debugging enabled

### 6. Keep non-plugin workspace files out of the published plugin package

The current workspace contains project-only material such as:

- `skill/`
- `sample/`
- `docs/`
- `.sisyphus/`

Those should not ship inside the final plugin ZIP uploaded to Moodle Plugins Directory.

## Source References

- Moodle plugin contribution checklist:
  `https://moodledev.io/general/community/plugincontribution/checklist`
- Moodle activity module API overview:
  `https://moodledev.io/docs/5.0/apis/plugintypes/mod`
- Moodle privacy API overview:
  `https://moodledev.io/docs/5.0/apis/subsystems/privacy`

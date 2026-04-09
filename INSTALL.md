# Installation

`mod_micp` is a standard Moodle activity module plugin. This repository is structured so that the repository root is the plugin root.

## Requirements

- Moodle 5.0
- PHP 8.1+

## Option 1. Install from Git

Clone this repository directly into Moodle's `mod` directory as `micp`:

```bash
git clone git@github.com:YOUR_USERNAME/moodle-mod_micp.git /path/to/your/moodle/mod/micp
```

After the files are in place, visit:

```text
Site administration -> Notifications
```

Moodle will detect the plugin and run the installation or upgrade.

## Option 2. Install from a release ZIP

Download a release package and extract it so that Moodle ends up with:

```text
/path/to/your/moodle/mod/micp/version.php
/path/to/your/moodle/mod/micp/lib.php
/path/to/your/moodle/mod/micp/db/install.xml
```

When preparing a release archive from Git, prefer `git archive` or an equivalent process that respects `.gitattributes`, so repository-only material such as examples and tests is not bundled unnecessarily.

Then visit:

```text
Site administration -> Notifications
```

## Upgrade

Replace the plugin files with a newer version while preserving the same target directory:

```text
/path/to/your/moodle/mod/micp
```

Then complete the upgrade from:

```text
Site administration -> Notifications
```

## Package Structure

The public plugin package should unpack directly into Moodle's `mod/micp` directory.

The repository root intentionally contains plugin files such as:

```text
version.php
lib.php
db/
classes/
lang/
templates/
pix/
```

Repository-only assets are kept under `examples/` and excluded from release archives by `.gitattributes`.

## Lesson Content

The plugin runs uploaded lesson packages made of:

- `index.html`
- `micp-scoring.json`
- optional `assets/`

The plugin runtime does not require Composer, npm, or any external API key to run uploaded lesson packages for learners.

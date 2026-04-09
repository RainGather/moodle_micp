# Contributing to mod_micp

Thank you for your interest in contributing!

## How to Contribute

1. **Fork** the repository on GitHub
2. **Clone** your fork locally:
   ```bash
   git clone git@github.com:YOUR_USERNAME/moodle-mod_micp.git
   ```
3. If you are testing inside a full Moodle checkout, place this repository at:
   ```bash
   /path/to/your/moodle/mod/micp
   ```
4. **Create a feature branch**:
   ```bash
   git checkout -b feature/your-feature-name
   ```
5. **Make your changes** — follow the Moodle coding style
6. **Run tests** (if applicable — see Testing section below)
7. **Commit** with a clear message:
   ```bash
   git commit -m "Add: your feature description"
   ```
8. **Push** to your fork:
   ```bash
   git push origin feature/your-feature-name
   ```
9. Open a **Pull Request** against `master` on the main repository

## Coding Standards

- PHP: Follow the [Moodle Coding Style](https://docs.moodle.org/dev/Coding_style)
  - Use `phpcs` with the Moodle standard: `vendor/bin/phpcs --standard=moodle`
- JavaScript: [Moodle JavaScript Coding Guidelines](https://docs.moodle.org/dev/JavaScript_coding_style)
- Mustache templates: [Moodle Mustache template guidelines](https://docs.moodle.org/dev/Templates)

## Testing

Unit tests are in `tests/`. Run with:

```bash
# Inside your Moodle root
php admin/tool/phpunit/cli/init.php
vendor/bin/phpunit --filter mod_micp
```

## Reporting Bugs

Please open a GitHub Issue with:
- Moodle version
- PHP version
- Steps to reproduce
- Expected vs actual behavior

When publishing the plugin publicly, prefer Moodle's repository naming convention:
- `moodle-mod_micp`

## Security

If you discover a security vulnerability, **do not open a public issue**.
Use the repository's private security disclosure channel, or follow [SECURITY.md](./SECURITY.md).

## License

By contributing, you agree that your contributions will be licensed under GPLv3.

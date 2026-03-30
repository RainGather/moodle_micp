# Contributing to mod_micp

Thank you for your interest in contributing!

## How to Contribute

1. **Fork** the repository on GitHub
2. **Clone** your fork locally:
   ```bash
   git clone git@github.com:YOUR_USERNAME/moodle_micp.git
   ```
3. **Create a feature branch**:
   ```bash
   git checkout -b feature/your-feature-name
   ```
4. **Make your changes** — follow the Moodle coding style
5. **Run tests** (if applicable — see Testing section below)
6. **Commit** with a clear message:
   ```bash
   git commit -m "Add: your feature description"
   ```
7. **Push** to your fork:
   ```bash
   git push origin feature/your-feature-name
   ```
8. Open a **Pull Request** against `master` on the main repository

## Coding Standards

- PHP: Follow the [Moodle Coding Style](https://docs.moodle.org/dev/Coding_style)
  - Use `phpcs` with the Moodle standard: `vendor/bin/phpcs --standard=moodle`
- JavaScript: [Moodle JavaScript Coding Guidelines](https://docs.moodle.org/dev/JavaScript_coding_style)
- Mustache templates: [Moodle Mustache template guidelines](https://docs.moodle.org/dev/Templates)

## Testing

Unit tests are in `mod/micp/tests/`. Run with:

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

## Security

If you discover a security vulnerability, **do not open a public issue**.
Email the maintainers directly instead.

## License

By contributing, you agree that your contributions will be licensed under GPLv3.

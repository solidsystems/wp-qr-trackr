# Contributing to QR Trackr Plugin Template

Thank you for your interest in contributing! This project is both a production-ready WordPress plugin (QR Trackr) and a modern template for building your own plugins. We welcome improvements, bug fixes, and new features.

## Getting Started

- Please review the [How to Use with GitHub Actions (CI/CD)](./README.md#how-to-use-with-github-actions-cicd) section in the README before running or modifying any CI/CD workflows. This section lists all required environment variables, secrets, and infrastructure expectations for automated testing, linting, and deployment.

- Make sure your environment and secrets are set up as described to avoid CI failures.

## How to Contribute
- Fork the repository and create a feature branch for your changes.
- Ensure your code follows project standards (see `.cursorrules`).
- Update or add documentation and tests as needed.
- Submit a pull request (PR) with a clear description of your changes.
- For larger changes, open an issue or discussion first.

## Task Tracker & Potential Improvements
This section serves as a living list of potential improvements and TODOs. If you start working on an item, add your name next to it. Feel free to suggest new ideas or improvements!

### Potential Improvements / TODOs
- [ ] **Automated Windows/Linux setup scripts** — Add cross-platform setup scripts for non-macOS environments. _(unassigned)_
- [ ] **Docker Compose for local dev** — Provide a Docker Compose file for easy local development and testing. _(unassigned)_
- [ ] **GitHub Actions CI** — Add automated tests and linting to run on every PR. _(unassigned)_
- [ ] **More example hooks/filters** — Demonstrate extensibility with more real-world examples. _(unassigned)_
- [ ] **Admin UI theme options** — Add light/dark mode and more customizable admin UI. _(unassigned)_
- [ ] **Accessibility audit** — Review and improve accessibility of all admin screens. _(unassigned)_
- [ ] **Internationalization (i18n)** — Add translation support and example language files. _(unassigned)_
- [ ] **Performance profiling** — Add tools or docs for profiling plugin performance. _(unassigned)_
- [ ] **Better error/debug UI** — Surface debug logs and errors in the admin panel. _(unassigned)_
- [ ] **User onboarding wizard** — Guide new users through initial setup in the plugin. _(unassigned)_
- [ ] **Automated changelog generation** — Script or GitHub Action to generate changelogs from PRs. _(unassigned)_
- [ ] **Pro plugin integration guide** — Expand docs for integrating with premium/pro plugins. _(unassigned)_

_Add your name in parentheses if you start working on an item!_

## Code Style & Standards
- Follow WordPress and project-specific best practices.
- Use Yarn for JS dependencies.
- All new features must include documentation and tests.
- See `.cursorrules` for more details.

## Coding Standards

- All PHP code must comply with the project's `.phpcs.xml` ruleset.
- The `vendor/` directory is excluded from all linting and auto-fixing.
- Never manually edit files in `vendor/`.
- To auto-fix most issues, use:

```sh
./vendor/bin/phpcbf --standard=.phpcs.xml --extensions=php .
```

- Pre-commit hooks and CI will enforce these rules automatically.

## Practical Plugin Examples

Here are some practical plugin ideas you can build using this modular, standards-compliant framework:

- **Tic Tac Toe Game**: A playable game in the WordPress admin, demonstrating UI, AJAX, and state management.
- **Simple Polls/Voting**: Let users create and vote in polls, with results displayed in real time.
- **Contact Form with Logging**: A secure, extensible contact form with admin-side message logging and spam protection.
- **Custom Redirect Manager**: Manage and track custom URL redirects, with analytics and error logging.
- **Admin Notes/Sticky Notes**: Allow admins to leave notes for themselves or other users, with privacy and permissions.
- **Maintenance Mode Switch**: Toggle site-wide maintenance mode with customizable messaging and scheduling.
- **User Feedback Widget**: Collect feedback from users, with moderation and export features.
- **Simple Event Calendar**: Add and display events, with RSVP and notification options.
- **Download Manager**: Track and control file downloads, with access restrictions and reporting.
- **Admin Dashboard Widgets**: Add custom widgets to the WordPress dashboard for stats, tips, or quick actions.

These examples are great starting points for learning, contributing, or building your own production plugins. If you have an idea, open an issue or PR!

## Questions or Suggestions?
Open an issue or start a discussion! We're happy to help and open to new ideas. 
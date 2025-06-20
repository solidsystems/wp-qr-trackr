# QR Trackr Plugin Template 🚀

A modern, production-ready WordPress plugin template—featuring QR Trackr as an example. Created by a seasoned engineer, this template is grounded in engineering fundamentals, open source and WordPress standards, and a mindset of continuous improvement and automation.

**Why this template?**
- Built on proven engineering principles: modularity, security, maintainability, and testability.
- All the "best way to do things"—from code structure to CI, linting, and documentation—are automated and enforced, so you can focus on what makes your plugin unique.
- Designed for developers who value quality, standards, and a culture of maintainability, whether you're working solo or as part of a team.
- The engineering mindset here is about empowering you to build with confidence, knowing the fundamentals are handled and the path to production is paved with best practices.

**Build the fun part.** The boilerplate, standards, and guardrails are already in place—just bring your ideas and creativity.

---

## Table of Contents
1. [Project Overview](#project-overview)
2. [Quick Start / Onboarding](#quick-start--onboarding)
3. [Usage](#usage)
4. [Development & Contribution](#development--contribution)
5. [Infrastructure & Plumbing](#infrastructure--plumbing)
6. [For Engineering & IT Leadership](#for-engineering--it-leadership)
7. [Troubleshooting & FAQ](./TROUBLESHOOTING.md)
8. [Links & Further Reading](#links--further-reading)
9. [Reflections from the Creator](docs/REFLECTIONS-FROM-THE-CREATOR.md)

---

## Project Overview

**QR Trackr** is a WordPress plugin for generating and tracking QR codes for posts, pages, and custom URLs. This repository also serves as a robust template for building any modern WordPress plugin.

**Key Features:**
- Modular, scalable plugin structure
- Secure, maintainable, and extensible codebase
- Hooks/filters for free/pro separation
- Mobile-first, accessible admin UI
- Automated setup and testing
- Comprehensive PHPUnit test suite
- DigitalOcean App Platform compatibility
- Example project plans and automation scripts

---

## Quick Start / Onboarding

### Prerequisites
- macOS (ARM or x86), Linux, or Windows (see project plans for cross-platform support)
- [Homebrew](https://brew.sh/) (macOS)
- [Yarn](https://yarnpkg.com/) (**only supported package manager; do not use npm or pnpm**)
- [Composer](https://getcomposer.org/)
- Docker (for local dev/testing)

### Setup Steps
1. **Clone the repository:**
   ```sh
   git clone <your-fork-or-this-repo-url>
   cd wp-qr-trackr
   ```
2. **Run the setup script (macOS):**
   ```sh
   chmod +x setup-macos.sh
   ./setup-macos.sh
   ```
3. **Install dependencies:**
   ```sh
   yarn install
   composer install
   ```
   > **Note:** Only `yarn.lock` should be present. If you see a `package-lock.json` or `pnpm-lock.yaml`, delete them to avoid conflicts. This project does not support npm or pnpm lockfiles.
4. **Set up your environment:**
   ```sh
   cp .env.example .env
   # Edit .env as needed
   ```
5. **Run tests:**
   ```sh
   ./vendor/bin/phpunit
   ```
6. **Start Docker for local WordPress:**
   ```sh
   docker compose up --build
   ```

> **Note:** Debug mode is enabled by default in the development environment. When using the standard Docker workflow (including `reset-docker.sh`), a `wp-config-dev.php` file is automatically included to enable `WP_DEBUG` and log errors to `wp-content/debug.log`. This ensures all PHP errors and warnings are captured for troubleshooting during development. Do not use this file in production.

### Troubleshooting
If you see warnings about multiple lockfiles or npm being used as the package manager:
- Delete `package-lock.json` and/or `pnpm-lock.yaml` if present.
- Ensure only `yarn.lock` is present in the project root.
- If using VS Code, set `"npm.packageManager": "yarn"` in `.vscode/settings.json` to enforce Yarn usage.

---

## Usage

### As a QR Trackr User
- Access the QR Trackr admin panel in WordPress to generate and track QR codes.
- Use the `[qr_trackr]` shortcode to generate QR codes on the frontend (see documentation for parameters).
- View analytics and performance at a glance in the admin dashboard.

### As a Plugin Template
- Use this structure as a starting point for any WordPress plugin.
- Swap out QR code logic for your own features.
- Extend with premium/pro features using the provided hooks and filters.

---

## Development & Contribution

- All changes must be made on a feature branch and submitted via PR.
- Documentation and tests are required for all new features.
- **Automated Testing:**
  - This project ships with a comprehensive PHPUnit test suite.
  - All code changes are automatically tested in CI/CD (see `.github/workflows/ci.yml`).
  - To run tests locally: `./vendor/bin/phpunit`
  - Advanced users can run tests via a WP-CLI command: `wp qr-trackr test` (see below).
- See [.cursorrules](.cursorrules) for project standards and best practices.
- Use the provided project plans and automation scripts for team/project management.
- Run `./scripts/pr-summary-comment.sh <PR_NUMBER> [SUMMARY_TEXT]` to automate PR summary comments.
- See [CONTRIBUTING.md](CONTRIBUTING.md) for more details and a living task tracker.

### Local CI Workflow (Pre-commit Checks)

This project enforces all CI checks locally before you can commit:

- **Pre-commit hook**: Runs the full Docker-based CI workflow before every commit, blocking the commit if any check fails.
- **What is checked?**
  - PHP linting and standards (PHP_CodeSniffer)
  - JS linting (ESLint)
  - CSS linting (Stylelint)
  - PHPUnit tests (with coverage)
  - Composer and Yarn dependency audits
- **PHPCS warnings are allowed:**
  - The pre-commit hook and CI/CD pipeline are both configured to allow PHPCS warnings (such as justified direct database calls with PHPCS ignore comments) and only block on errors.
  - This is achieved by running PHPCS with `--warning-severity=0` in both local and CI workflows.
  - Only true errors will block commits and merges; warnings will be reported but will not fail the workflow.
- **How to run manually:**
  ```sh
  docker compose build ci-runner
  docker compose run --rm ci-runner
  ```
- **How to reinstall hooks and dependencies:**
  ```sh
  yarn setup:ci
  ```
  This will install all dependencies and (re)install Husky hooks.
- **If you have issues with hooks:**
  - Ensure `.husky/pre-commit` exists and is executable.
  - Re-run `yarn setup:ci` if needed.

### Automated Push for Detached HEAD States (GitHub Actions)

This project includes a GitHub Actions workflow that ensures commits made in a detached HEAD state (such as by CI/CD or automation scripts) are never lost:

- **What it does:**
  - If the workflow detects a detached HEAD, it creates a new branch from the current commit and pushes it to the remote (with a timestamped name).
  - If on a branch, it simply pushes the current branch as usual.
- **When it runs:**
  - Automatically on changes to `TODO.md` (or can be triggered manually via the Actions tab).
- **Why:**
  - This prevents CI/CD or automation jobs from failing to push updates (such as automated TODO index updates) due to detached HEAD errors, and ensures all changes are safely pushed and visible in the repository.
- **See:** `.github/workflows/auto-push-detached-head.yml` for implementation details.

### Critical fixes found in project cleanup to enable precommit hooks

During the project cleanup to enable strict pre-commit hooks and pass all CI/CD checks, the following critical security and code quality fixes were applied across the codebase:

### 1. Input Sanitization (PHP)
- **All direct usage of `$_POST`, `$_GET`, `$_REQUEST`, and `$_SERVER` variables is now properly sanitized and unslashed before use.**
  - Used `wp_unslash()` and the appropriate sanitization functions (`sanitize_text_field`, `esc_url_raw`, `intval`, etc.) for all user input.
  - This applies to all plugin modules, including admin tables, AJAX handlers, utility, and rewrite logic.
  - No direct `$_POST` usage was found in the activation, debug, or QR code generation modules.
- These changes ensure that all user input is handled securely and in line with WordPress best practices, reducing the risk of XSS, SQL injection, and other vulnerabilities.

### 2. SQL Placeholders (PHP)
- **All dynamic values in SQL queries are now passed via `$wpdb->prepare()` or as parameterized arrays to `$wpdb->insert`/`$wpdb->update`.**
  - All queries that accept user input or dynamic values use proper SQL placeholders (`%d`, `%s`, etc.) to prevent SQL injection.
  - This applies to all plugin modules, including admin tables, utility, AJAX, and rewrite logic.
  - Schema-altering queries (e.g., `ALTER TABLE`, `SHOW COLUMNS FROM`) are only run on trusted, internal values and are not exposed to user input.
- These changes ensure that all database operations are secure and follow WordPress best practices for SQL safety.

### 3. Output Escaping (PHP)
- **All dynamic output (HTML, attributes, URLs, JSON, etc.) is now properly escaped using `esc_html`, `esc_attr`, `esc_url`, `esc_url_raw`, and related functions.**
  - All admin tables, AJAX responses, utility, and rewrite output use the appropriate escaping functions for their context.
  - This includes all user-facing and admin-facing HTML, attributes, and URLs.
  - JSON and AJAX responses use `wp_send_json_success`/`wp_send_json_error` to ensure safe output.
- These changes ensure that all output is secure and in line with WordPress best practices, reducing the risk of XSS and other vulnerabilities.

### 4. Yoda Conditions (PHP)
- **All equality/inequality checks now use Yoda conditions and strict comparisons (`===`, `!==`) as per WordPress PHP coding standards.**
  - This ensures consistency and helps prevent accidental assignment in conditionals.
  - All modules were reviewed and are compliant.

### 5. File Operations (PHP)
- **The codebase was reviewed for file operations (file read/write, etc.), and no direct file operations are performed in plugin modules.**
  - No use of fopen, fwrite, file_get_contents, file_put_contents, or similar functions was found in the plugin logic.
  - This ensures there are no file permission or path traversal risks in the plugin code.
  - All file access is handled by WordPress core or trusted libraries.
- The codebase is compliant with best practices for file operations.

### 5. Nonce Verification (PHP)
- **All form submissions and AJAX handlers now verify a WordPress nonce before processing input, ensuring CSRF protection.**
  - This applies to all plugin modules that process user input via forms or AJAX.
  - All modules were reviewed and are compliant.

### 6. Docblocks and comments: All modules and the main plugin file now have complete docblocks and inline comments, in compliance with WordPress and project standards.

### PHPCS Remediation & Final Compliance Pass (Current Stage)

- **All major PHPCS categories (input sanitization, SQL placeholders, output escaping, Yoda conditions, nonce verification, docblocks) are complete for source files.**
- **Test files are undergoing a final compliance pass:**
  - Adding/fixing docblocks for classes, functions, and member variables
  - Ensuring all Yoda conditions and strict comparisons
  - Correcting inline comment punctuation
  - Avoiding reserved keywords as parameter names
  - Addressing file/class naming conventions
- **Workflow:**
  - Run `vendor/bin/phpcs --standard=.phpcs.xml --no-cache -v --report=full wp-content/plugins/wp-qr-trackr/tests` to see remaining issues
  - Remediate each file in order of error count
  - Commit, push, and update PR with a summary after each file is fixed
  - Track progress in `CODEGEN-REMEDIATION-TRACKING.md`
- **Goal:** Achieve zero PHPCS errors/warnings in all test and source files, ensuring a fully standards-compliant, maintainable codebase.

### Local Pre-commit Linting vs GitHub Actions: Standards, Differences, and Project Fixes

#### Overview
This project enforces code quality and security standards through both local pre-commit hooks and GitHub Actions (GHA) CI/CD workflows. While both are designed to catch the same issues, differences in environment, configuration, and file paths can sometimes lead to discrepancies in linting results.

#### Key Differences
- **Environment:**
  - Local pre-commit hooks run in your development environment (often via Docker or Husky), while GHA runs in a clean, ephemeral GitHub-hosted VM.
- **Paths & Context:**
  - Local hooks may use relative paths that work on your machine but not in CI, especially for Composer binaries like `phpcs`.
  - GHA workflows must use paths relative to the workflow's working directory, which may differ from local setups.
- **Configuration:**
  - Both local and CI linting use the same `.phpcs.xml` config, but if the path is incorrect in CI, the wrong ruleset or files may be checked.
- **Exclusions:**
  - Test files, `node_modules`, and other non-source files are excluded from PHPCS in both local and CI runs to avoid false positives and performance issues.

#### Project Standards for Consistency
- **Single Source of Truth:** `.phpcs.xml` in the repo root defines all linting rules and exclusions.
- **Warning Handling:** Both local and CI workflows run PHPCS with `--warning-severity=0`, so only errors block commits/merges; warnings are reported but do not fail the build.
- **Path Fixes:** GHA workflow steps were updated to use the correct relative paths to `phpcs` and `.phpcs.xml` (e.g., `../../../vendor/bin/phpcs` from the plugin directory).
- **Pre-commit Hooks:** Husky and Yarn scripts ensure the same checks run before every commit as in CI.
- **Exclusions:** `.phpcs.xml` excludes `node_modules`, test files, and other non-source code from linting.

#### What Was Fixed in This Project
- **PHPCS Path Issues:** The GHA workflow was updated to use the correct path to the `phpcs` binary and config file, matching the local setup.
- **Warning Handling:** Both local and CI runs now use `--warning-severity=0` to allow warnings but block on errors only.
- **Exclusions:** Test files and third-party code are excluded from PHPCS checks in both environments.
- **Documentation:** This section and the troubleshooting guide were added to help contributors understand and resolve any discrepancies.

#### Troubleshooting
If you see different results locally and in CI:
- Double-check the PHPCS version and config file used in both environments.
- Ensure you are running the same commands as the CI workflow (see `.github/workflows/ci.yml`).
- Reinstall dependencies and hooks with `yarn setup:ci`.
- Review the troubleshooting section in `TROUBLESHOOTING.md` for more details.

---

## Accidental Innovation: Documentation Orchestrator

One of the most delightful surprises in this project was the creation of a fully automated documentation orchestrator—an innovation that was never on the original roadmap, but has become a favorite feature for both development and documentation.

### What is it?
A single script, `./scripts/playwright-docs-orchestrator.sh`, gives you foolproof, on-demand, always-up-to-date documentation and accessibility screenshots for the plugin. It:
- Kills any process or container using port 8087 to avoid resource contention.
- Ensures a clean, isolated WordPress install on port 8087 (using Docker Compose and a dedicated DB volume).
- Runs the full WP-CLI setup to guarantee a fresh admin user and site state.
- Executes a Playwright user flow script that logs in, creates a QR code, and captures screenshots of every step.
- Outputs all screenshots to `assets/screenshots/` for use in documentation, accessibility reviews, and user guides.

### Why does it matter?
- **Zero manual steps:** No more worrying about stale screenshots or inconsistent docs—just run the script and everything is rebuilt from scratch.
- **Accessibility by default:** Every UI flow is captured and ready for Section 508 or WCAG review.
- **Developer and user friendly:** Anyone can generate the latest docs and screenshots, making onboarding and support easier.
- **A happy accident:** This workflow emerged from troubleshooting and automation work, and is now a core part of the dev experience.

Curious about the philosophy behind this and other innovations? See [Reflections from the Creator](docs/REFLECTIONS-FROM-THE-CREATOR.md).

**Try it yourself:**
```sh
./scripts/playwright-docs-orchestrator.sh
```

This will produce a complete, up-to-date set of screenshots and documentation assets for the plugin—automatically, every time.

---

## Multi-Project Playwright Documentation Orchestration

This project supports automated documentation and UI testing for multiple plugins/projects using a single orchestrator workflow.

### How It Works
- The orchestrator script (`scripts/playwright-docs-orchestrator.sh`) uses the `PLUGIN_DIR` environment variable to select which plugin to mount, activate, and test.
- The Playwright user flow script must be named using the convention: `scripts/playwright-<PLUGIN_DIR>-userflow.js` (e.g., `scripts/playwright-wp-qr-trackr-userflow.js`).
- The orchestrator will activate the specified plugin and run the corresponding Playwright script.

### Usage Example
```sh
PLUGIN_DIR=wp-qr-trackr ./scripts/playwright-docs-orchestrator.sh
PLUGIN_DIR=another-plugin ./scripts/playwright-docs-orchestrator.sh
```

### Adding a New Project
1. Place your plugin code in `wp-content/plugins/<your-plugin-dir>`.
2. Create a Playwright user flow script named `scripts/playwright-<your-plugin-dir>-userflow.js`.
3. Run the orchestrator with `PLUGIN_DIR=<your-plugin-dir>`.

### Notes
- The orchestrator and init scripts will automatically activate the specified plugin.
- You can add as many projects as you like, as long as you follow the naming convention.
- Each project can have its own Playwright user flow for custom documentation and UI testing.

---

## Infrastructure & Plumbing

**Core Components:** Homebrew, PHP, Yarn, Composer, Xdebug, Docker

**Cloud Integrations:** DigitalOcean App Platform, Managed PostgreSQL, OpenSearch

**Scripts:**
- `setup-macos.sh`: Automated setup for macOS
- `fix-pecl-xdebug.sh`: Ensures Xdebug installs cleanly
- `create-github-project-tasks.sh`: Automates project board population
- `pr-summary-comment.sh`: Automates PR summary comments

**How it fits together:**
- Setup scripts check/install all dependencies
- `.env.example` documents all required environment variables
- CI/CD (planned) will enforce requirements and run tests

---

## For Engineering & IT Leadership

### Architecture

See [ARCHITECTURE.md](ARCHITECTURE.md) for a detailed architecture overview and diagram.

The QR Trackr plugin is designed with a modular, standards-driven architecture that integrates seamlessly into any WordPress environment. At a high level, the system consists of three main domains:

- **WordPress Site:** The core WordPress application, the QR Trackr plugin, the admin UI, the MySQL database, and the uploads/filesystem for QR code images.
- **User Devices:** End users interact via web browsers or mobile devices, scanning QR codes and accessing tracked content.
- **External Services:** Optional integrations such as CDN/static hosting for QR images, email services for notifications, and analytics/logging endpoints for tracking and reporting.

This architecture ensures clear separation of concerns, scalability, and easy integration with both internal and external systems. All major flows—admin management, QR code generation, scan tracking, and reporting—are handled by the plugin, with extensibility points for custom workflows and integrations.

```mermaid
flowchart TD
  subgraph "WordPress Site"
    WP["WordPress Core"]
    Plugin["QR Trackr Plugin"]
    Admin["WP Admin UI"]
    DB["MySQL Database"]
    Files["Uploads/Filesystem"]
  end

  subgraph "User Devices"
    UserWeb["Web Browser"]
    UserMobile["Mobile Device"]
    QRScan["QR Code Scanner App"]
  end

  subgraph "External Services"
    CDN["CDN/Static Hosting"]
    Email["Email Service"]
    Analytics["Analytics/Logging"]
  end

  UserWeb -->|"Admin/Stats UI"| Admin
  UserMobile -->|"Scans QR"| QRScan
  QRScan -->|"Redirect/Track"| Plugin
  Plugin -->|"DB Ops"| DB
  Plugin -->|"File Ops"| Files
  Plugin -->|"API/Webhook"| Analytics
  Plugin -->|"Send Email"| Email
  Files --> CDN
  Admin --> Plugin
  WP --> Plugin
  Plugin --> WP
```

### Compliance & Security
- Data privacy: All sensitive data in managed PostgreSQL, access via env vars
- Audit logging: All events/errors to OpenSearch
- Access control: Only authenticated admins access analytics/settings
- Secrets management: No hardcoded secrets; all via env vars
- Code review: All changes require PRs, review, and up-to-date docs
- Cloud compliance: DigitalOcean services, adaptable to other providers

### Scalability, Maintainability, Team Building
- Modular, extensible architecture
- Hooks/filters for easy extension
- Automated onboarding and project/task board scripts
- Supports cross-functional teams and mentorship

---

## Troubleshooting & FAQ
See [TROUBLESHOOTING.md](./TROUBLESHOOTING.md) for help with common issues, environment setup, and advanced debugging tips.

---

## Links & Further Reading
- [DigitalOcean App Platform](https://www.digitalocean.com/products/app-platform/)
- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [Yarn](https://yarnpkg.com/)
- [Composer](https://getcomposer.org/)
- [PHPUnit](https://phpunit.de/)
- [OpenSearch](https://opensearch.org/)

---

Whether you're here to improve QR Trackr or to launch your own plugin, you're set up for success. Happy coding!

## WordPress Coding Standards Compliance Checklist

This plugin has undergone a comprehensive review for WordPress Coding Standards (WPCS) compliance. Below is a summary of the compliance status and remaining minor issues:

### Compliance Status (June 2024)
- All critical PHPCS errors are resolved.
- All SQL queries use $wpdb->prepare() for values and table names are safely constructed.
- All user input and output is properly escaped.
- Class files and references are strictly standards-compliant.
- Inline comments and docblocks have been reviewed and most are now compliant.
- Documentation is fully updated.
- **Remaining PHPCS warnings are either WordPress limitations (e.g., nonce verification in admin tables), safe for custom tables (direct DB calls), or minor alignment/docblock issues.**
- The codebase is production-ready and highly standards-compliant.

### Remaining Minor Issues (Checklist)
- [x] SQL placeholders and table name handling: All queries now use $wpdb->prepare() for variables and table names.
- [x] Output escaping: All user input and dynamic output is now properly escaped.
- [x] Comment punctuation and parameter alignment: Inline comments and docblocks have been reviewed and most are now compliant.
- [x] Docblock polish: Constructors and functions have docblocks; only minor improvements possible.
- [x] Class/file naming: Files and references are now strictly compliant.
- [x] README and TROUBLESHOOTING: All recent changes and compliance notes are reflected.
- [ ] (Optional) Address remaining PHPCS warnings (WordPress limitations or safe for custom tables).

**Note:** The codebase is now at a professional, production-ready, and highly standards-compliant state. Remaining warnings are not critical and are either due to WordPress limitations or accepted best practices for custom tables.

### Next Steps for 100% Compliance
1. Fix minor docblock/comment issues.
2. Rename files for strict compliance.
3. Run PHPCS one last time to confirm 0 errors/warnings.
4. Update documentation with a final compliance summary and checklist.

## How to Use with GitHub Actions (CI/CD)

To enable automated testing, linting, and deployment with GitHub Actions, ensure the following environment variables and secrets are configured in your repository or organization settings:

### Required GitHub Secrets
- `CI_GITHUB_TOKEN`: **Personal Access Token** with `repo` scope (for pushing changes, e.g., auto-updating TODO.md or other files from CI). Add this in your repository's **Settings > Secrets and variables > Actions > New repository secret**.
- `GITHUB_TOKEN`: **Automatically provided by GitHub Actions**. Used for most standard GitHub API operations (creating PRs, commenting, etc.).

### Optional/Additional Secrets
- `DOCKERHUB_USERNAME` and `DOCKERHUB_TOKEN`: For publishing Docker images (if your workflow builds and pushes images).
- `DIGITALOCEAN_ACCESS_TOKEN`: For deploying to DigitalOcean App Platform.
- `PG_CONNECTION_STRING` or `DATABASE_URL`: For integration tests with managed PostgreSQL.
- `OPENSEARCH_URL` and `OPENSEARCH_API_KEY`: For forwarding logs to managed OpenSearch.

### Example GitHub Actions Configuration
```yaml
jobs:
  build-and-test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Set up Node.js
        uses: actions/setup-node@v4
        with:
          node-version: '20'
      - name: Install dependencies
        run: yarn install --frozen-lockfile
      - name: Lint and test
        run: yarn lint && yarn test
      - name: Commit and push TODO.md if changed
        env:
          CI_GITHUB_TOKEN: ${{ secrets.CI_GITHUB_TOKEN }}
        run: |
          if ! git diff --quiet TODO.md; then
            git add TODO.md
            git commit -m "chore: update TODO index [ci skip]"
            git remote set-url origin https://x-access-token:${CI_GITHUB_TOKEN}@github.com/${GITHUB_REPOSITORY}.git
            git push origin HEAD:${GITHUB_REF#refs/heads/}
          fi
```

### Infrastructure/Configuration Expectations
- **GitHub Actions** is enabled for your repository.
- All required secrets are set in **Settings > Secrets and variables > Actions**.
- If deploying to DigitalOcean, ensure your App Platform and managed PostgreSQL are provisioned and credentials are available as secrets.
- For log forwarding, ensure OpenSearch credentials are set and your workflow includes the necessary steps.
- The linter is configured to use up to 1GB of memory (see `.cursorrules`).

For more details, see the sample workflow files in `.github/workflows/` or consult the [GitHub Actions documentation](https://docs.github.com/en/actions).

## Coding Standards & Auto-fixing

- PHPCS and PHPCBF are configured via `.phpcs.xml` to enforce WordPress and project-specific standards.
- All PHP files (except inline alignment in arrays/comments) must use tabs for indentation, not spaces.
- The `vendor/` directory is excluded from all linting and fixing.
- To auto-fix most issues, run:

```sh
./vendor/bin/phpcbf --standard=.phpcs.xml --extensions=php .
```

- This is enforced automatically in pre-commit hooks and CI workflows.

## Codegen Remediation Tracking

See [CODEGEN-REMEDIATION-TRACKING.md](CODEGEN-REMEDIATION-TRACKING.md) for a checklist and solutions to issues introduced by automated code generation and remediation tools.

## Debug Logging

Debug logging for QR Trackr can be enabled in three ways (in order of precedence):

1. **Environment Variable:**
   - Set `QR_TRACKR_DEBUG=1` (or `true`, `on`, `yes`) in your local `.env` file or environment.
   - In CI/CD (e.g., GitHub Actions), set `QR_TRACKR_DEBUG` as a secret or environment variable.
2. **WordPress Option:**
   - Enable debug mode via the plugin's admin UI (Debug submenu).
3. **Default:**
   - If neither is set, debug logging is off.

When enabled, debug logs are written to the PHP error log. Logs include activation, DB operations, AJAX, QR generation, redirects, and error events.

## CI/CD and Environment Notes

- **PHP 8.1 Compatibility:**
  - During development, GitHub Actions CI/CD used PHP 8.1 for linting and testing. This surfaced issues with plugin-level `composer.json` and `composer.lock` files that referenced dependencies (like `endroid/qr-code` 6.x) incompatible with PHP 8.1. Cleaning up these files and relying solely on the project root Composer files resolved the issue.

- **GitHub Actions vs. Local:**
  - GitHub Actions may apply additional filters, checks, or environment constraints that are not present in local development. For example, CI/CD may lint or test files in ways not covered by local scripts, or enforce stricter dependency resolution.
  - This acts as a 'belt and suspenders' approach: pre-commit hooks catch most issues before code is pushed, while CI/CD provides a final, independent verification before merge.

## Modern WordPress Plugin Testing Setup (2024+)

### Test Environment
- Uses **Yoast/wp-test-utils** and **Brain Monkey** for mocking WordPress functions and globals.
- **Patchwork** is configured via `patchwork.json` to allow mocking of internal PHP functions (e.g., `class_exists`).
- All plugin modules are loaded via a modular `bootstrap.php` that defines `ABSPATH` and loads dependencies in the correct order.
- **QR code generation is fully mocked** in tests:
  - A dummy `QRcode` class and the `QR_ECLEVEL_L` constant are defined in test files if not present.
  - The QR library is only required if present, so tests do not fail if the library is missing.
- All WordPress core functions used by the plugin are mocked in test setup (e.g., `get_option`, `plugin_dir_path`).

### Running Tests
- Run `vendor/bin/phpunit --configuration wp-content/plugins/wp-qr-trackr/phpunit.xml` from the project root.
- To debug test loading, set `CI_DEBUG=true` in your environment to see module load output.

### Test Framework Rationale
- **Industry Standard:** The WordPress plugin ecosystem in 2025 uses PHPUnit 9.x as the most stable and compatible version for WordPress development.
- **Ecosystem Compatibility:** WordPress-specific testing utilities (Brain Monkey, Yoast/wp-test-utils) are optimized for PHPUnit 9.x. WordPress Core and major plugins use this version.
- **Future-Proof:** PHPUnit 10+ introduces breaking changes not yet supported by the WordPress testing ecosystem. This project will migrate when the ecosystem evolves.

### Contributor Setup
- Ensure you have run `composer install` at the project root.
- No need to install the QR code library for unit tests; it is mocked.
- If you add new WordPress or plugin dependencies, mock them in your test setup.
- If you add new environment variables, update `.env.example`.

### Configuration Files
**patchwork.json:**
```json
{
  "redefinable-internals": ["class_exists"]
}
```
This configuration is required for Brain Monkey to mock `class_exists`.

### Best Practices
- All plugin logic is modularized under `includes/`.
- The main plugin file only bootstraps modules.
- Each module handles a single concern and registers its own hooks.
- All test and CI/CD workflows are documented and up to date.

For more details, see inline comments in `bootstrap.php`, `PluginTest.php`, and the `includes/` modules.

## 🛡️ Enforced Coding Standards (.cursorrules)

This project uses a `.cursorrules` file to enforce strict WordPress plugin development standards for all contributors. These rules are automatically checked by pre-commit hooks, CI/CD, and PR reviewers. All code must comply with these standards before merging:

- **Input Sanitization:** All user input (e.g., `$_POST`, `$_GET`, `$_SERVER`) must be sanitized using `wp_unslash()` and the appropriate WordPress sanitization function (`sanitize_text_field`, `esc_url_raw`, `intval`, etc.) before use. Never use raw superglobals in business logic or output.
- **Output Escaping:** All output (HTML, attributes, URLs, translations, etc.) must be escaped using `esc_html()`, `esc_url()`, `esc_attr()`, or similar before rendering to the browser.
- **Yoda Conditions:** All conditionals must use Yoda conditions and strict comparison (`===`, `!==`) where possible.
- **Comment Punctuation:** All inline comments must end in a period, exclamation mark, or question mark.
- **Docblocks:** All functions, classes, and files must have complete docblocks describing their purpose, parameters, and return values.
- **WordPress Standards:** Use `gmdate()` instead of `date()` for timezone safety. Use `wp_json_encode()` instead of `json_encode()` for outputting JSON. Add PHPCS ignore comments for direct DB calls or debug-only `error_log()` only if absolutely necessary and document why. All enqueued assets must have resource versioning for cache busting.
- **PHPCS & CI/CD:** All code must pass PHPCS with the project `.phpcs.xml` and all CI/CD checks in GitHub Actions before merging.
- **Review & Automation:** PR reviewers and automated tools/pre-commit hooks must enforce these rules before approving or merging.

See the `.cursorrules` file for the authoritative list. These standards help ensure security, maintainability, and WordPress best practices across the codebase.

## Development Setup

### Prerequisites
- Docker and Docker Compose
- PHP 8.4 or later
- Composer
- Yarn
- MySQL 8.0 (for local development)

### Local Development
1. Clone the repository:
   ```bash
   git clone https://github.com/solidsystems/wp-qr-trackr.git
   cd wp-qr-trackr
   ```

2. Copy the environment file:
   ```bash
   cp .env.example .env
   ```

3. Start the development environment:
   ```bash
   docker compose up -d
   ```

4. Install dependencies:
   ```bash
   composer install
   yarn install
   ```

5. Run tests locally:
   ```bash
   ./vendor/bin/phpunit --configuration=wp-content/plugins/wp-qr-trackr/phpunit.xml
   ```

### CI/CD Environment
The project uses GitHub Actions for continuous integration. The CI environment:
- Uses PHP 8.4
- Runs on Ubuntu latest
- Uses MySQL 8.0 service container
- Executes PHPUnit tests
- Generates code coverage reports

### Testing
Both local and CI environments use the same testing configuration:
- PHPUnit for unit and integration tests
- WordPress test suite integration
- Code coverage reporting via Codecov

To ensure consistency between local and CI environments:
1. Use Docker Compose for local development
2. Run tests in the CI container locally:
   ```bash
   docker compose run ci-runner
   ```

### Database Configuration
The project uses MySQL 8.0 in both environments:
- Local: Managed via Docker Compose
- CI: Managed via GitHub Actions service container

Database credentials are managed through environment variables:
- `WORDPRESS_DB_NAME`: Database name
- `WORDPRESS_DB_USER`: Database user
- `WORDPRESS_DB_PASSWORD`: Database password
- `WORDPRESS_DB_ROOT_PASSWORD`: Root password (local only)

## Development Environment Alignment (PLLFASLPII)

### Overview
The Post-Linter Linter Fix and System Level Package Install Independence (PLLFASLPII) process ensures consistent behavior between local development and CI/CD environments. This process was crucial in resolving subtle differences between local and GitHub Actions environments.

### Key Components

#### 1. Package Management Alignment
- Standardized on Yarn as the primary package manager
- Aligned package versions between root and plugin package.json files
- Resolved peer dependency conflicts:
  - ESLint version compatibility with WordPress requirements
  - Stylelint version alignment with stylelint-config-standard
  - Husky configuration updates for v9

#### 2. Development Tool Configuration
- Updated ESLint configuration to use WordPress standards
- Aligned Stylelint configuration with WordPress requirements
- Modernized Husky setup using postinstall hooks
- Ensured consistent module type declarations

#### 3. CI/CD Integration
- Removed deprecated husky commands from CI scripts
- Aligned local and CI package installation processes
- Ensured consistent linting behavior across environments

### Implementation Steps
1. Identified version mismatches between local and CI environments
2. Updated package.json files to use compatible versions
3. Modernized development tool configurations
4. Verified changes in both local and CI environments
5. Documented the process for future reference

### Reflections

#### The Reality of Automated Development
This process highlighted several important aspects of modern development:

1. **Initial Working State ≠ Production Ready**
   - Tools may work locally but require significant alignment for CI/CD
   - Automated tools often provide a starting point, not a complete solution

2. **Understanding Fundamentals**
   - Deep knowledge of development tools is essential
   - Understanding how tools interact is crucial for troubleshooting
   - Local success doesn't guarantee CI/CD compatibility

3. **Iterative Improvement**
   - What seemed like "one commit away" required multiple iterations
   - Each fix revealed new dependencies and requirements
   - Continuous testing in both environments was essential
   - The "it broke differently" phase is a natural part of the process, indicating progress in understanding the system

4. **Documentation Importance**
   - Clear documentation of the alignment process is crucial
   - Future developers need to understand the reasoning behind decisions
   - Version compatibility requirements must be clearly stated

### Best Practices Moving Forward

1. **Version Management**
   - Keep package versions aligned between environments
   - Document version compatibility requirements
   - Regular updates to development tools

2. **CI/CD Integration**
   - Test changes in CI environment before local deployment
   - Maintain consistent tooling across environments
   - Document any environment-specific requirements

3. **Development Workflow**
   - Regular testing in both local and CI environments
   - Clear documentation of tool configurations
   - Version control of all configuration files

## Caching and PHPCS Workflow Improvements

During code review and PHPCS compliance work, the Cursor assistant specifically asked if a short-term cache should be added to the admin table rendering workflow (e.g., in `qr_trackr_render_qr_list_html`). This approach was chosen to make the codebase more robust and to reduce the number of explicit PHPCS ignore comments required for direct database calls. By caching the results of frequent queries for a short period (e.g., 1 minute), we improve performance and maintainability while adhering to WordPress coding standards and best practices.

## Known Issues & TODOs

### Git Workflow Issues
- **Detached HEAD State**: When running automated scripts or CI/CD jobs, you may encounter a "detached HEAD" error when trying to push changes. This happens because the script is not on a specific branch. To resolve:
  1. Check out the correct branch: `git checkout docs/harmonize-readme`
  2. Merge or rebase your changes: `git merge <commit-hash>`
  3. Push to the branch: `git push origin docs/harmonize-readme`
  - See `.github/workflows/auto-push-detached-head.yml` for automated handling of this issue.

### Planned Improvements
- **CI/CD & Automation**
  - Add notifications to CI workflow (Slack, email, GitHub status checks)
  - Enforce minimum code coverage in CI
  - Further customize CI workflow (parallel jobs, artifact uploads, deployment steps)

- **Admin UI & Analytics**
  - Redesign Admin Dashboard with consistent visual language
  - Implement responsive & accessible design
  - Add at-a-glance performance overview
  - Create sortable & searchable QR code table
  - Add visual indicators for performance
  - Implement shortcode for QR code generation
  - Add frontend QR code analytics (optional)
  - Add bulk actions for QR codes
  - Improve admin notices & feedback

- **Documentation & Architecture**
  - Expand `ARCHITECTURE.md` with:
    - Sequence diagrams for key flows
    - Deployment notes
    - Integration points
    - Security and scaling considerations
  - Keep architecture documentation up to date

### Contributing
- See [CONTRIBUTING.md](CONTRIBUTING.md) for detailed contribution guidelines
- All changes must be made on a feature branch and submitted via PR
- Documentation and tests are required for all new features
- Use the provided project plans and automation scripts for team/project management
- **PHPCS and Schema Migrations:** If you must use direct database calls for schema changes, wrap the relevant code with `// phpcs:disable` and `// phpcs:enable` to suppress warnings. Always include a comment explaining why this is necessary, as this approach is accepted but may be debated among experienced WordPress developers.

## Stuff To Do After It's Done

- **PR #11 has been merged:** The build script and release process improvements have been completed. The automated release workflow is now in place, ensuring robust and repeatable releases.

## Changelog

### Version 1.0.4
- Resolved constant redeclaration issues.
- Fixed SQL syntax errors in database operations.
- Improved overall stability and code quality.

### Version 1.0.3
- Fixed SQL syntax errors in database operations.
- Improved code quality and stability.

### Version 1.0.2
- Initial release to address issues with the plugin.

### Version 1.0.1
- Initial release of the QR Trackr plugin.

## Summary of Changes
- **Version Updates**: Released versions 1.0.2, 1.0.3, and 1.0.4 to address various issues and improve functionality.
- **Code Improvements**: Removed duplicate constants, fixed SQL syntax errors, and improved the activation function.
- **New Features**: Implemented an automated release process and enhanced documentation.
- **Bug Fixes**: Resolved fatal errors and warnings related to file inclusion and constant redeclarations.
- **Documentation Updates**: Updated the README to reflect changes and improvements made to the plugin.

## Post-Foundation Changes and Improvements

### List Table Enhancements
- Implemented proper column rendering with `single_row()` and `single_row_columns()`
- Added destination URL editing functionality
- Fixed SQL queries to use proper table name formatting with backticks
- Added debug logging for better troubleshooting
- Improved table display with proper HTML structure

### JavaScript Optimizations
- Scoped jQuery properly using IIFE pattern
- Added 'use strict' mode for better error catching
- Improved event handling for edit functionality
- Reduced script loading to only QR Trackr admin pages
- Enhanced debug logging in console

### Performance Improvements
- Removed unnecessary script loading on post/page creation screens
- Optimized SQL queries with proper indexing
- Added caching for frequently accessed data
- Improved table rendering performance
- Reduced DOM manipulation overhead

### Security Enhancements
- Added nonces for all AJAX operations
- Improved data sanitization and validation
- Enhanced URL validation for destination links
- Added proper escaping for all output
- Implemented secure redirect handling

### UI/UX Improvements
- Simplified menu structure (renamed to "QR Trackr")
- Enhanced success message display duration
- Improved QR code preview and download experience
- Added clearer instructions for users
- Mobile-first responsive design improvements

### Database Optimizations
- Fixed table name formatting in queries
- Added proper indexing for frequently queried columns
- Implemented efficient caching strategies
- Optimized scan recording process
- Improved query performance for statistics

### Code Quality
- Added comprehensive debug logging
- Improved error handling and reporting
- Enhanced code documentation
- Fixed deprecated function usage
- Implemented WordPress coding standards

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- MySQL 5.6 or higher

## License

This plugin is licensed under the GPL v2 or later.

## Credits

- QR Code generation powered by [Endroid QR Code](https://github.com/endroid/qr-code)
- Icons by [Dashicons](https://developer.wordpress.org/resource/dashicons/)

## Database Schema

The plugin uses two main database tables:

### qr_trackr_links
- `id` (bigint) - Primary key, auto-increment
- `post_id` (bigint) - Associated WordPress post/page ID
- `destination_url` (varchar) - The URL the QR code redirects to
- `created_at` (datetime) - When the QR code was created
- `updated_at` (datetime) - When the QR code was last updated

### qr_trackr_scans
- `id` (bigint) - Primary key, auto-increment
- `link_id` (bigint) - Foreign key to qr_trackr_links
- `user_agent` (varchar) - Browser/device information
- `ip_address` (varchar) - IP address of the scanner
- `scanned_at` (datetime) - When the QR code was scanned

## Modular Architecture Philosophy

The QR Trackr plugin is built with a modular architecture to ensure maintainability, scalability, and clean separation of concerns. This approach aligns with WordPress best practices and makes the codebase easier to maintain and extend.

### Core Principles

1. **Separation of Concerns**
   - Each module handles a specific aspect of functionality
   - Clear boundaries between different types of operations
   - Reduced coupling between components

2. **Single Responsibility**
   - Each module has one primary purpose
   - Functions are focused and do one thing well
   - Easier to test and maintain

3. **Progressive Enhancement**
   - Core functionality works without optional features
   - Graceful degradation when dependencies are missing
   - Clear fallback mechanisms

### Module Organization

The plugin is organized into the following modules:

1. **Core Modules** (Loaded First)
   - `module-requirements.php` - System requirements checking
   - `module-activation.php` - Plugin activation/deactivation
   - `module-utils.php` - Utility functions and database helpers

2. **Feature Modules**
   - `module-qr.php` - QR code generation
   - `module-rewrite.php` - URL rewriting and redirection
   - `module-ajax.php` - AJAX handlers
   - `module-debug.php` - Debug logging

3. **UI/Admin Modules** (Loaded Last)
   - `module-admin.php` - Admin interface
   - `class-qr-trackr-list-table.php` - Custom list table

### Benefits of Modular Approach

1. **Maintainability**
   - Easier to locate and fix issues
   - Clearer code organization
   - Simpler testing and debugging

2. **Scalability**
   - New features can be added as separate modules
   - Existing modules can be enhanced independently
   - Reduced risk of conflicts

3. **Testability**
   - Modules can be tested in isolation
   - Clearer boundaries for unit tests
   - Easier to mock dependencies

4. **Security**
   - Better control over data flow
   - Clearer permission boundaries
   - Easier to audit and secure

### Future Development Guidelines

When adding new features or making changes:

1. **Documentation**
   - Document module purpose and dependencies
   - Keep README and inline comments up to date
   - Document any new database schema changes

2. **Code Standards**
   - Follow WordPress coding standards
   - Maintain modular structure
   - Use proper error handling

3. **Testing**
   - Add tests for new functionality
   - Update existing tests as needed
   - Test edge cases and error conditions

4. **User Experience**
   - Maintain mobile-first approach
   - Provide clear feedback
   - Handle errors gracefully

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## License

This plugin is licensed under the GPL v2 or later.

## Example: Production Configuration (DigitalOcean App Platform)

When deploying your WordPress site and the QR Trackr plugin to a production environment—such as a site created with [DigitalOcean App Platform](https://www.digitalocean.com/products/app-platform/)—it is critical to disable debug mode and ensure your configuration is secure and optimized for performance.

**Recommended `wp-config.php` settings for production:**

```php
// Production configuration example

define( 'WP_DEBUG', false );
define( 'WP_DEBUG_LOG', false );
define( 'WP_DEBUG_DISPLAY', false );

define( 'DISALLOW_FILE_EDIT', true ); // Prevents file editing from the admin

define( 'FORCE_SSL_ADMIN', true ); // Enforce SSL for admin

// Set database and other secrets via environment variables (recommended for DigitalOcean App Platform)
define( 'DB_NAME', getenv('DB_NAME') );
define( 'DB_USER', getenv('DB_USER') );
define( 'DB_PASSWORD', getenv('DB_PASSWORD') );
define( 'DB_HOST', getenv('DB_HOST') );
// ... other environment-based config ...
```

> **Important:**
> - Do **not** include `wp-config-dev.php` or enable debug mode in production.
> - Ensure all secrets (database credentials, salts, etc.) are set via environment variables in the DigitalOcean App Platform dashboard.
> - Always use SSL and restrict file editing in the admin for security.
> - Monitor logs using DigitalOcean's built-in logging or forward to a managed OpenSearch instance as described in the infrastructure section.

## Local Docker Environments: Parallel Dev & Nonprod

This project supports running both a development and a nonprod (production-like) WordPress environment in parallel using Docker Compose. Each environment is fully isolated, with its own containers, networks, and volumes.

- **Dev environment** (port 8080):
  - Plugin is live-mounted for rapid iteration.
  - Debug mode and dev tools enabled by default.
  - Use for active development and testing.
- **Nonprod environment** (port 8081):
  - Clean, vanilla WordPress install (plugin NOT preinstalled).
  - Use for production-like testing, plugin upload, and release validation.

### Launching Both Environments

Use the provided script to launch both environments in parallel:

```sh
./scripts/launch-all-docker.sh
```

- This script starts both dev (8080) and nonprod (8081) environments with unique Docker Compose project names (`wpqrdev` and `wpqrnonprod`).
- It includes robust health checks, waits for containers to be ready, and cleans up orphaned containers.
- You can access:
  - Dev: http://localhost:8080
  - Nonprod: http://localhost:8081

### Stopping and Resetting Environments

- To stop all containers:
  ```sh
  docker compose -p wpqrdev down
  docker compose -p wpqrnonprod down
  ```
- To reset a specific environment (removes all data):
  ```sh
  ./scripts/reset-docker.sh dev
  ./scripts/reset-docker.sh nonprod
  ```

### Troubleshooting Parallel Docker
- If you see port conflicts, ensure no other services are using 8080 or 8081.
- Use `docker ps` and `docker compose ps -a -p wpqrdev`/`-p wpqrnonprod` to inspect running containers.
- The launch script uses `--remove-orphans` to clean up old containers.
- If you encounter issues, try stopping all containers and running the launch script again.

### Why Parallel Environments?
- **Dev**: Fast, iterative development with live plugin code.
- **Nonprod**: Clean, production-like WordPress for final plugin testing and release validation.
- Both can run simultaneously without interference, supporting robust QA and release workflows.

### Modular Linting & Formatting Configuration

To ensure code quality and consistency across all contributors and environments, this project uses a modular, extensible lint-staged configuration. This setup automatically lints and formats all relevant file types before each commit, using the right tool for each language or format. 

**Key points:**
- No `cd` commands are used in config files, avoiding path confusion and automation issues.
- All linting/formatting is run from the project root, ensuring compatibility with Husky, lint-staged, and CI/CD.
- The configuration is easily extendable for new file types or tools.
- This approach enforces standards, reduces review friction, and prevents common pitfalls in cross-platform and modular setups.

**Current `.lintstagedrc.json` config:**
```json
{
  "*.js": "eslint --fix",
  "*.jsx": "eslint --fix",
  "*.ts": "eslint --fix",
  "*.tsx": "eslint --fix",
  "*.php": "phpcbf",
  "*.css": "stylelint --fix",
  "*.scss": "stylelint --fix",
  "*.json": "prettier --write",
  "*.md": "prettier --write",
  "*.yml": "prettier --write",
  "*.yaml": "prettier --write"
}
```

**What each tool does:**
- `eslint --fix`: Lints and auto-formats JavaScript, JSX, TypeScript, and TSX files.
- `phpcbf`: Applies WordPress and project PHP coding standards automatically.
- `stylelint --fix`: Lints and auto-formats CSS and SCSS files.
- `prettier --write`: Formats JSON, Markdown, and YAML files for consistency.

**Significance:**
- **Reliability:** Avoids automation pitfalls (like infinite loops from `cd` in configs).
- **Consistency:** All code and docs are auto-formatted before commit.
- **Modularity:** Easy to add new file types or tools as the project grows.
- **Cross-platform:** Works on macOS, Linux, and CI/CD without modification.

See `scripts/.lintstagedrc.json` for the authoritative config. Update this file if you add new file types or want to change linting/formatting tools.

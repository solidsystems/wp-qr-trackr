# WP QR Trackr 🚀

What started off as a solution to a QR code request has become an opportunity to showcase modern WordPress development excellence, cross-platform compatibility, and professional engineering practices—while creating a **comprehensive foundation for other developers** to build their own WordPress plugins.

Beyond being a production-ready QR code plugin, **WP QR Trackr serves as a complete development template and environment** that others can clone, customize, and use as the foundation for their own WordPress plugin projects. Every aspect—from the automated Docker setup and cross-platform scripts to the modular code architecture and comprehensive testing framework—has been designed to provide developers with a professional starting point that eliminates the complexity of environment setup and demonstrates WordPress best practices.

**Use this project as your plugin development foundation:** Whether you're building a completely different plugin or extending QR functionality, this repository provides the infrastructure, workflows, and standards you need to start developing immediately on any platform.

**Current Status: Stable v1.2.20** ✅
- **Critical error fixes applied** - Resolved rewrite rule timing issues and fatal activation errors
- **Enhanced admin interface** - Modal-based QR code management with clickable images
- **Advanced QR code management** - Common names, referral codes, search, and filtering
- **Production-ready** - Successfully tested and deployed with comprehensive debugging tools
- **Debug mode integration** - Built-in system diagnostics and troubleshooting capabilities
- **Security compliant** - Follows WordPress security standards with proper nonce verification
- **Performance optimized** - Strategic caching and efficient database operations

**Why choose WP QR Trackr as your development foundation?**
- **Complete development environment** - Automated Docker setup, cross-platform scripts, and zero-configuration WordPress installation
- **Professional template structure** - Modular architecture, proper file organization, and WordPress standards compliance
- **Production-ready examples** - Real-world implementation of security, performance, and best practices
- **Cross-platform compatibility** - Works identically on macOS (Intel & ARM), Windows, and Linux with automated setup scripts
- **Comprehensive testing framework** - PHPUnit integration, CI/CD workflows, and debugging tools included
- **Developer-first documentation** - Extensive guides, troubleshooting resources, and architectural documentation
- **Proven foundation** - Battle-tested codebase with production validation and extensive feature implementation

**Ready to clone and customize.** Fork this repository, rename the plugin, and start building your WordPress plugin with a professional foundation that handles all the complex setup and infrastructure concerns.

---

## Table of Contents
1. [Use as Development Foundation](#-use-as-development-foundation)
2. [Project Overview](#project-overview)
3. [Current Status & Recent Updates](#recent-updates--stability)
4. [Admin Interface Features](#admin-interface-features)
5. [Project Management & Automation](#project-management--automation)
6. [Quick Start / Onboarding](#quick-start--onboarding)
7. [Usage](#usage)
8. [Development & Contribution](#development--contribution)
9. [Infrastructure & Plumbing](#infrastructure--plumbing)
10. [For Engineering & IT Leadership](#for-engineering--it-leadership)
11. [Cursor Plugin Development Guide](./docs/CURSOR_PLUGIN_DEVELOPMENT_GUIDE.md)
12. [Features Documentation](./docs/FEATURES.md)
13. [Troubleshooting & FAQ](./docs/TROUBLESHOOTING.md)
14. [Links & Further Reading](#links--further-reading)
15. [Current Status Dashboard](./STATUS.md)

**Quick Links:**
- 📊 [**Current Status Dashboard**](./STATUS.md) - Production readiness and health overview
- 🚀 [**Quick Start Guide**](./ONBOARDING.md) - Get started in minutes
- 🎯 [**Cursor Plugin Development Guide**](./docs/CURSOR_PLUGIN_DEVELOPMENT_GUIDE.md) - Complete guide for building plugins with Cursor AI
- ⚡ [**Cursor Quick Reference**](./docs/CURSOR_QUICK_REFERENCE.md) - Essential prompts cheat sheet
- ✨ [**Features Documentation**](./docs/FEATURES.md) - Comprehensive feature guide including modal system, search, and debug mode
- 🛠️ [**Troubleshooting Guide**](./docs/TROUBLESHOOTING.md) - Solutions for common issues

---

## 🏗️ Use as Development Foundation

**Ready to build your own WordPress plugin?** This repository provides everything you need:

### For New Plugin Development
1. **Fork or clone this repository**
2. **Rename the plugin** - Update file names, class names, and text domains to match your plugin
3. **Customize functionality** - Replace QR code logic with your plugin's core features
4. **Leverage the infrastructure** - Keep the Docker setup, testing framework, and documentation structure

### What You Get Out of the Box
- ✅ **Automated development environment** - Docker containers with WordPress, MySQL, and all dependencies
- ✅ **Cross-platform setup scripts** - Works on macOS, Windows, and Linux with zero manual configuration
- ✅ **Professional code structure** - Modular architecture following WordPress best practices
- ✅ **Complete testing framework** - PHPUnit integration with CI/CD workflows
- ✅ **Advanced project management** - Cursor todo integration with GitHub Projects sync and automation
- ✅ **Security implementations** - Proper nonce verification, input sanitization, and output escaping examples
- ✅ **Database best practices** - Migration system, caching strategies, and PHPCS-compliant queries
- ✅ **Admin interface examples** - Modal systems, AJAX handling, and responsive design patterns
- ✅ **Documentation templates** - Comprehensive guides and troubleshooting resources

### Customization Roadmap
1. **Update plugin identity** - Change plugin name, description, and author information
2. **Replace core functionality** - Substitute QR code features with your plugin's logic
3. **Modify database schema** - Update table structures and migration scripts for your data
4. **Customize admin interface** - Adapt the admin pages and functionality to your requirements
5. **Update documentation** - Modify README, feature docs, and troubleshooting guides

**Start developing immediately** - No need to set up Docker, configure WordPress, or establish coding standards. Focus on your plugin's unique features while standing on a proven foundation.

---

## Project Overview

**WP QR Trackr** is a comprehensive WordPress plugin for generating, managing, and tracking QR codes. Perfect for marketing campaigns, event management, and analytics tracking.

**Core Features:**
- **QR Code Generation** - Create QR codes for posts, pages, and custom URLs
- **Modal-Based Management** - Click QR code images to open detailed management modals
- **Advanced QR Fields** - Common names and referral codes for enhanced organization
- **Search & Filter System** - Powerful search across all QR code fields with referral code filtering
- **Debug Mode Integration** - Comprehensive system diagnostics and troubleshooting tools
- **Analytics & Tracking** - Monitor QR code scans with detailed analytics and recent scan statistics
- **Admin Dashboard** - Intuitive WordPress admin interface with modern, mobile-first design
- **Security First** - Proper nonce verification, input sanitization, and output escaping
- **Performance Optimized** - Database caching and efficient query strategies with automatic migrations

**Technical Excellence:**
- **Production Tested** - Successfully deployed on live WordPress sites
- **Standards Compliant** - Follows WordPress coding standards and best practices
- **Modular Architecture** - Clean, maintainable code structure
- **Comprehensive Testing** - PHPUnit test suite with CI/CD integration
- **Documentation** - Extensive documentation and troubleshooting guides

## Recent Updates & Stability

### v1.2.20 - Enhanced Management Interface ✅
**Major Features Added:**
- ✅ **Modal-based QR management** - Click QR images to open detailed editing modals with real-time updates
- ✅ **Common name & referral code fields** - Enhanced QR code organization and tracking capabilities
- ✅ **Advanced search & filtering** - Search across all fields with referral code filter dropdown
- ✅ **Debug mode integration** - Comprehensive system diagnostics accessible in WordPress admin
- ✅ **UI improvements** - Cleaned up duplicate QR images, modern responsive interface
- ✅ **Critical error fixes** - Resolved rewrite rule timing issues preventing activation
- ✅ **Automatic database migrations** - Seamless upgrades with field additions and indexing

**Current Status:**
- 🟢 **Production Stable** - Successfully tested on live WordPress installations with comprehensive features
- 🟢 **Activation Safe** - All fatal errors resolved, proper hook timing implemented
- 🟢 **Feature Complete** - Full QR code lifecycle management with advanced organization tools
- 🟢 **Debug Ready** - Built-in system diagnostics for troubleshooting and maintenance
- 🟢 **Security Compliant** - Enhanced nonce verification and input validation throughout
- 🟢 **Performance Optimized** - Efficient caching with search-aware cache keys and database indexing

## Admin Interface Features

### Modal-Based QR Code Management
- **Clickable QR Images** - Click any QR code image in the admin list to open detailed management modal
- **Real-Time Editing** - Edit common names and referral codes with instant validation and feedback
- **Live Statistics** - View recent scan counts and analytics directly in the modal interface
- **Mobile-Responsive Design** - Touch-friendly modal interface optimized for all devices
- **Error Handling** - Comprehensive validation with user-friendly error messages

### Search & Filter System
- **Global Search** - Search across common names, referral codes, QR codes, and destination URLs
- **Referral Code Filter** - Dropdown filter to quickly find QR codes by referral code
- **Real-Time Results** - Instant search results with efficient caching
- **Pagination** - 15 items per page with smooth navigation

### Debug Mode Integration
- **System Diagnostics** - Comprehensive health checks for database, rewrite rules, and file system
- **QR Generation Testing** - Live QR code generation testing with visual preview
- **Rewrite Rule Validation** - Check and fix rewrite rule registration issues
- **Force Flush Capability** - Manual rewrite rule flushing for troubleshooting

## QR Code Generation Features

- **Error Correction Levels** - Multiple levels (L, M, Q, H) for different use cases
- **Visual Customization** - Custom colors, sizes (100-1000px), and dot styles
- **Advanced Styling** - Custom eye shapes, gradients, and margin control
- **Logo Integration** - Optional logo embedding with automatic positioning
- **Format Support** - PNG and SVG output formats
- **Fallback Service** - Automatic fallback if primary generation fails
- **URL Tracking** - Built-in analytics and scan tracking with immediate count updates
- **Mobile Optimized** - Responsive design for all devices

---

## Project Management & Automation

### Dual TODO System
**WP QR Trackr** includes a sophisticated dual project management system that combines modern structured todos with comprehensive documentation:

**🎯 Cursor Structured Todos:**
- **Real-time task management** with dependency tracking
- **Status management** (pending, in_progress, completed, cancelled)
- **Development workflow integration** with Cursor IDE
- **Automatic updates** as you work on tasks

**📋 Traditional Documentation:**
- **TODO.md** - Historical tracking and comprehensive task lists
- **STATUS.md** - High-level project health and version tracking
- **PROJECT_PLAN_MCP_ENHANCEMENTS.md** - Detailed project phases and roadmap

### GitHub Projects Integration
**Seamless integration** with GitHub Projects for live task tracking:

- **Automated project creation** with custom fields (Priority, Phase, Effort)
- **Bidirectional sync** between Cursor todos and GitHub Projects
- **Task mapping** with proper status and priority management
- **Automated hooks** for continuous synchronization

### Automation Scripts

**`scripts/update-todo-index.sh`** - TODO Automation
```bash
# Updates TODO.md with current project status
# Syncs between Cursor todos and markdown files
# Automatic backup and validation
./scripts/update-todo-index.sh
```

**`scripts/setup-github-projects.sh`** - GitHub Projects Setup
```bash
# Creates GitHub Project with proper field mapping
# Imports all tasks from Cursor todos
# Sets up automation hooks and documentation
./scripts/setup-github-projects.sh
```

### Project Summary Dashboard
**Real-time project metrics:**
- **Total Tasks:** 31
- **Completed:** 13
- **Active:** 18
- **Completion Rate:** 41%

**Benefits for Your Plugin Development:**
- **Professional project tracking** from day one
- **Automated synchronization** between tools
- **Historical documentation** of project progress
- **Integration with popular development workflows**

**Usage in Your Plugin:**
1. **Clone the automation scripts** to your project
2. **Customize task definitions** for your plugin's requirements
3. **Set up GitHub Projects** for your repository
4. **Use Cursor todos** for active development tracking

📖 **Detailed Documentation:** [GitHub Projects Sync Guide](docs/GITHUB_PROJECTS_SYNC.md)

### 🎯 **Cursor AI Development Guide**
**New to plugin development with Cursor?** We've created a comprehensive guide specifically for Cursor users:

**[📖 Cursor Plugin Development Guide](docs/CURSOR_PLUGIN_DEVELOPMENT_GUIDE.md)**

This guide includes:
- **Step-by-step instructions** for using wp-qr-trackr as your plugin foundation
- **Specific Cursor prompts** for planning, architecture, and implementation
- **Design consideration workflows** with AI collaboration best practices
- **Common plugin development patterns** and examples
- **Quality assurance checklists** and testing strategies

**Perfect for developers who want to:**
- Build WordPress plugins using AI assistance
- Leverage the wp-qr-trackr foundation effectively
- Follow best practices for AI-assisted development
- Create production-ready plugins quickly and efficiently

---

## Quick Start / Onboarding

### Cross-Platform Support ✅
**Fully tested and supported on:**
- **macOS** - Intel (x86_64) and Apple Silicon (ARM64)
- **Windows** - Windows 10/11 with WSL2, Git Bash, or PowerShell  
- **Linux** - Ubuntu, Debian, CentOS, RHEL, Fedora, Arch (Intel & ARM)

📖 **Platform-specific instructions:** [Cross-Platform Setup Guide](docs/CROSS_PLATFORM_SETUP.md)

### Prerequisites
- **Docker Desktop** (Windows/macOS) or **Docker Engine** (Linux)
- **Docker Compose** v2.x+
- **Git**
- [Yarn](https://yarnpkg.com/) (**only supported package manager; do not use npm or pnpm**)
- [Composer](https://getcomposer.org/) (for PHP dependencies)
- [Homebrew](https://brew.sh/) (macOS - optional)

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
6. **Start WordPress development environment:**
   ```bash
   # Start both dev (8080) and nonprod (8081) environments
   docker-compose -f docker-compose.dev.yml -p wpqrdev up -d
   docker-compose -f docker-compose.yml -p wpqrnonprod up -d
   
   # Auto-setup WordPress with trackr:trackr credentials
   # macOS/Linux/WSL2:
   ./scripts/setup-wordpress.sh dev
   ./scripts/setup-wordpress.sh nonprod
   
   # Windows PowerShell:
   .\scripts\setup-wordpress.ps1 dev
   .\scripts\setup-wordpress.ps1 nonprod
   ```

7. **Access your environments:**
   - **Dev Environment:** http://localhost:8080 (live plugin mounting)
   - **Nonprod Environment:** http://localhost:8081 (clean testing)
   - **Admin Login:** Username `trackr` / Password `trackr`

> **Note:** Debug mode is enabled by default in the development environment. When using the standard Docker workflow (including `reset-docker.sh`), a `wp-config-dev.php` file is automatically included to enable `WP_DEBUG` and log errors to `wp-content/debug.log`. This also enables the QR Trackr Debug page in the WordPress admin (QR Trackr → Debug) with comprehensive system diagnostics, QR generation testing, and troubleshooting tools. Do not use debug mode in production.

### Troubleshooting
If you see warnings about multiple lockfiles or npm being used as the package manager:
- Delete `package-lock.json` and/or `pnpm-lock.yaml` if present.
- Ensure only `yarn.lock` is present in the project root.
- If using VS Code, set `"npm.packageManager": "yarn"` in `.vscode/settings.json` to enforce Yarn usage.

---

## Usage

### As a QR Trackr User

#### Basic QR Code Management
- Access the QR Trackr admin panel in WordPress to generate and track QR codes
- Use the "Add New" page to create QR codes for posts, pages, or custom URLs
- Add common names and referral codes for better organization and tracking
- View all QR codes in the admin list with search and filter capabilities

#### Advanced Management Features
- **Click any QR code image** in the admin list to open the management modal
- **Edit details** like common names and referral codes with real-time validation
- **View scan statistics** including recent 30-day scan counts
- **Search and filter** QR codes using the search box and referral code dropdown
- **Debug and troubleshoot** using the Debug page (when debug mode is enabled)

#### Debug Mode Access
- Enable debug mode by setting `WP_DEBUG = true` in wp-config.php
- Or use the "Force Debug Mode" setting in the plugin settings
- Access comprehensive system diagnostics via QR Trackr → Debug in the admin menu

#### Frontend Integration
- Use the `[qr_trackr]` shortcode to generate QR codes on the frontend
- QR codes automatically redirect through tracking URLs for analytics
- Scan tracking updates immediately without wp-cron dependencies

### As a Plugin Template
- Use this structure as a starting point for any WordPress plugin
- Modular architecture with separate modules for admin, AJAX, rewrite rules, and utilities
- Comprehensive error handling and debug logging throughout
- Modern modal-based admin interfaces with AJAX functionality
- Automatic database migrations and version management
- Extend with premium/pro features using the provided hooks and filters

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

### CI/CD Pipeline: A Pre-Built Container Approach

```mermaid
graph TD
    subgraph "Developer"
        A[Push Code]
    end

    subgraph "GitHub Actions"
        A --> B{Dependency Files Changed?}
        B -- Yes --> C[Build & Publish CI Docker Image]
        B -- No --> D[Pull Pre-built CI Image]
        C --> E[Push to GHCR]
        E --> F[CI Workflow: Run Tests]
        D --> F
        F[Run Linting & Tests in Container]
    end
```

#### How It Works

The system is composed of two distinct GitHub Actions workflows:

1.  **`publish-ci-image.yml` (The Builder)**
    - **Purpose:** To build and publish the CI Docker image.
    - **Trigger:** This workflow runs *only* when files defining the CI environment are changed on the `main` branch (e.g., `Dockerfile.ci`, `composer.json`, `package.json`).
    - **Action:** It builds the `ci-runner` image and pushes it to the GitHub Container Registry, where it becomes the new standard for testing.

2.  **`ci.yml` (The Tester)**
    - **Purpose:** To run all linting and testing checks on the code.
    - **Trigger:** Runs on every push and pull request.
    - **Action:**
        - It logs into the GitHub Container Registry.
        - It uses `docker-compose.ci.yml` to pull the pre-built `ci-runner` image.
        - It executes the `ci.sh` script inside the container to run all checks.

This separation of concerns ensures that the time-intensive build process only happens when absolutely necessary, making the day-to-day development and testing cycle fast and efficient.

### Containerized Testing Environment

To ensure maximum portability and consistency, this project has moved away from local dependency requirements for testing and now uses a fully containerized testing environment powered by Docker. This approach eliminates the "it works on my machine" problem and guarantees that tests run in a clean, isolated, and identical environment, whether on a developer's local machine or in a CI/CD pipeline.

**Overview:**
The entire end-to-end (E2E) testing process is managed by a single script that programmatically generates a `docker-compose.ci.yml` file, brings up a WordPress instance and a database, installs all necessary dependencies (including Node.js, Yarn, and Playwright browsers), runs the tests, and tears down the environment.

**Advantages:**
- **Zero Local Dependencies:** Apart from Docker, you do not need to install PHP, a web server, a database, Node.js, or even WordPress locally to run the full test suite.
- **Portability & Consistency:** The testing environment is defined in code and is identical for every developer and every CI run, preventing environment-specific bugs.
- **Isolation:** Tests run in a pristine, ephemeral environment. There is no risk of interference from local configurations, leftover data, or other projects.
- **CI/CD Ready:** The script-based approach is designed for automation and can be dropped directly into any CI/CD platform (like GitHub Actions) to run the same checks that are performed locally.
- **Reliability:** By automating the installation of WordPress and the plugin via `wp-cli`, the tests are more reliable and less prone to failures from manual setup or race conditions.

**Complexities & Challenges Encountered:**
While powerful, implementing this containerized solution presented several technical challenges:
- **Service Orchestration:** Ensuring the database container was fully healthy before the WordPress container started was critical to prevent connection errors. This was solved using Docker Compose's `healthcheck` feature.
- **Automated WordPress Installation:** The initial WordPress setup (running the famous "five-minute install") had to be fully automated with `wp-cli` to create a usable site for the Playwright tests.
- **In-Container Debugging:** When tests failed due to fatal PHP errors within the plugin, diagnosing the issue was complex. It required exec-ing into the running container to inspect the `debug.log` to find the root cause.
- **Environment Variable Propagation:** Correctly passing the `SITE_URL` from the host shell into the Docker container and making it available to the Playwright test runner was a crucial step that required careful configuration.

**Disadvantages:**
- **Performance Overhead:** Docker introduces a minor performance cost compared to running tests natively.
- **Resource Consumption:** The Docker-based environment naturally consumes more disk space and memory than a traditional local setup.
- **Complexity:** The setup script and Docker configuration are more complex than a simple `phpunit` command, which may present a learning curve for developers new to containerization.

### Future Containerization Opportunities

To make this a truly portable, dependency-free solution, the following areas can also be containerized:
1.  **Full Development Environment:** A `docker-compose.dev.yml` could be created to provide a complete, live-reloading development environment. Developers would only need Docker and a code editor, with no requirement to install PHP, Apache/Nginx, or MySQL locally. The plugin source code would be volume-mounted into the container for instant feedback during development.
2.  **Linting and Static Analysis:** Running tools like `phpcs` and `eslint` inside a container would ensure that the exact same tool versions and configurations are used by every developer and by the CI server, removing `phpcs` and Node from the list of local dependencies.
3.  **Asset Building & Packaging:** For plugins that require a build step (e.g., compiling SASS/JS), this process could be encapsulated in a multi-stage Docker build. This would create a production-ready, zipped artifact of the plugin without requiring Node.js or other build tools on the host machine.

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

## Containerization & CI/CD: Lessons Learned

This project uses a pre-built Docker image for CI/CD, pulled from GHCR, to ensure fast, reliable, and consistent test environments. All Composer and PHPCS operations in CI/CD are run with a 2G memory limit to prevent out-of-memory errors. Local development does not enforce these limits by default, but contributors can set them if needed.

### Key Challenges & Solutions
- **Composer memory limits:** Enforced 2G memory limit in CI/CD to prevent OOM errors.
- **Single Composer context:** Removed plugin-level Composer artifacts; all dependencies are managed at the project root.
- **PHPCS sniffs:** Removed references to unavailable sniffs (NormalizedArrays, Universal, Modernize) after they were removed from PHPCSStandards.
- **VCS/Composer issues:** Ensured only available sniffs are referenced and correct repository URLs are used.
- **Docker resource consumption:** CI/CD runners must be provisioned with at least 2G of memory for reliable operation.

See CONTRIBUTING.md and docs/TROUBLESHOOTING.md for more details.

## Lessons Learned

### Platform-Specific Learnings (PHP/WordPress)
- **Restrict PHPCS to PHP files:** To prevent out-of-memory errors and unnecessary linting, always use the `--extensions=php` flag with PHPCS in CI/CD. This ensures only PHP files are scanned, which is especially important in WordPress plugins that may include non-PHP files (e.g., Markdown, XML, shell scripts).
- **Using Cursor as an Assistant:** Leveraging Cursor's AI assistant accelerated troubleshooting, configuration, and documentation, especially for complex CI/CD and linting issues.
- **Modular Configurations:** Modularizing configuration files (Docker, Composer, PHPCS, etc.) made it easier to maintain, debug, and scale the project.
- **Defining and Implementing Best Practices:** Establishing and enforcing best practices for development environments (e.g., memory limits, dependency management, code standards) led to more reliable and maintainable workflows.

## Security Best Practices

All forms and data-changing actions in QR Trackr are protected by WordPress nonces and server-side verification:

- **Admin forms** (e.g., create, edit, delete QR codes) include a nonce field and verify it server-side before processing.
- **AJAX endpoints** require a valid nonce and verify it using `check_ajax_referer()` or `wp_verify_nonce()`.
- **Bulk and destructive actions** (such as deleting QR codes) are protected by nonces in both the action link and the handler.
- **Settings and debug forms** use `wp_nonce_field()` and verify with `check_admin_referer()`.

This ensures robust protection against CSRF and other attacks, fully complying with WordPress security standards and project rules. All new features must include nonce protection for any form or data-changing action.
# QR Trackr Plugin Template (Development)

A modern, production-ready WordPress plugin template—featuring QR Trackr as an example. This version of the documentation is focused on local development, debugging, and contributing.

## Table of Contents
1. Project Overview
2. Quick Start / Onboarding (Development)
3. Usage
4. Development & Contribution
5. Infrastructure & Plumbing
6. Troubleshooting & FAQ (Development)
7. Links & Further Reading

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

## Quick Start / Onboarding (Development)

### Prerequisites
- macOS (ARM or x86), Linux, or Windows (see project plans for cross-platform support)
- [Homebrew](https://brew.sh/) (macOS)
- [Yarn](https://yarnpkg.com/)
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

### Quick Start

1. **Start the environment:**
   ```sh
   ./scripts/launch-nonprod-docker.sh
   ```
   (This script will launch the Docker environment and print access details.)

2. **Access WordPress:**
   - Open [http://localhost:8081](http://localhost:8081) in your browser.
   - Complete the WordPress install wizard (choose any admin credentials).

3. **Upload the plugin:**
   - In the WordPress admin, go to **Plugins → Add New → Upload Plugin**.
   - Select your plugin ZIP file and install/activate it.

4. **Test the plugin:**
   - Use the admin UI to verify plugin features in a clean environment.

### Database Credentials
- DB Name: `wpdb`
- DB User: `wpuser`
- DB Password: `wppass`
- MySQL Root Password: `rootpass`

### Notes
- The plugin code is **not live-mounted**; changes require re-uploading the ZIP.
- Database data persists between runs via Docker volume `db_data`.
- No other plugins or themes are pre-installed.
- **Debug logging is enabled by default in this environment.**
- **Nonprod runs on port 8081, so you can run both dev (8080) and nonprod (8081) environments at the same time.**

## Local Non-Production Docker Testing

This environment provides a clean, vanilla WordPress install for plugin testing. It is designed to:
- Simulate a real-world, production-like WordPress site with no development dependencies, no live-mounts, and no pre-installed plugins or themes.
- Allow you to upload and test your plugin ZIP file as an end user would, ensuring compatibility and catching issues that might not appear in a dev environment.
- Use a separate port (8081) so you can run both dev (8080) and nonprod (8081) environments simultaneously.

**Why?**
- This setup helps catch issues related to plugin packaging, missing dependencies, or environment differences before release.
- It ensures your plugin works on a fresh WordPress install, just like your users will experience.

### PHP Upload Limits for Plugin Testing
- The nonprod environment sets PHP upload limits using `WORDPRESS_CONFIG_EXTRA` in `docker-compose.yml`:
  ```yaml
  WORDPRESS_CONFIG_EXTRA: |
    @ini_set('upload_max_filesize', '64M');
    @ini_set('post_max_size', '64M');
  ```
- This is required because the official WordPress Docker image ignores `PHP_UPLOAD_MAX_FILESIZE` and `PHP_POST_MAX_SIZE` environment variables.
- You can now upload large plugin ZIP files for testing without hitting the "link you followed has expired" error.

## Managing the Non-Production Docker Environment

To make plugin testing easy and reliable, two scripts are provided:

### 1. Reset the Non-Production Environment
Use this to fully reset the nonprod environment, including removing all containers and the database volume for a fresh start.

```sh
./scripts/reset-nonprod-docker.sh
```
- Stops and removes all nonprod (8081) containers
- Removes the `db_data` volume (erases all nonprod database data)
- Rebuilds Docker images for a clean environment
- Use this if you want a completely fresh WordPress install and database

### 2. Launch the Non-Production Environment
Use this to start the nonprod environment after a reset, or to restart it at any time.

```sh
./scripts/launch-nonprod-docker.sh
```
- Tears down any running nonprod containers and volumes (safe to run repeatedly)
- Starts the nonprod WordPress and MySQL containers on port 8081
- Prints access instructions and tails the logs for live debugging

**Typical workflow:**
1. Reset the environment for a clean slate:
   ```sh
   ./scripts/reset-nonprod-docker.sh
   ```
2. Launch the environment and begin testing:
   ```sh
   ./scripts/launch-nonprod-docker.sh
   ```

---

## Usage

(Shared usage instructions...)

---

## Development & Contribution

(Shared and dev-specific contribution instructions...)

---

## Infrastructure & Plumbing

(Shared infrastructure details, with dev notes...)

---

## Troubleshooting & FAQ (Development)

See [docs/TROUBLESHOOTING.dev.md](docs/TROUBLESHOOTING.dev.md) for help with common development issues, environment setup, and advanced debugging tips.

---

## Links & Further Reading

(Shared links...)

---

## Common Module Loading and Activation Issues

### Module Loading Order
- Modules must be loaded in the correct order to ensure all dependencies are available when needed.
- For example, if `module-admin.php` calls a function from `module-debug.php`, the debug module must be loaded first.
- Always update the main plugin file to load modules in dependency order.

### Activation Hook Pitfalls
- Activation hooks can fail if required modules or functions are not loaded before the hook runs.
- Undefined function errors (e.g., `Call to undefined function qr_trackr_is_debug_enabled()`) are usually caused by loading order issues.
- Use robust error handling and debug logging in activation hooks to catch and diagnose these problems.

### Debugging Tips
- Check `wp-content/debug.log` for fatal errors and stack traces.
- Add debug logging at the start and end of each module and activation hook.
- If you see a fatal error about an undefined function, check the module load order in your main plugin file.
- Always test plugin activation and deactivation in a clean environment to catch these issues early.

---

## Release Packaging: rsync + .distignore Exclude List

To guarantee that only the required files and production dependencies are included in the plugin release ZIP (and nothing else), the release process uses `rsync` with an exclude list from `.distignore`.

### Why rsync + .distignore?
- **Speed:** Only the needed files are copied, never copied and then deleted.
- **Reliability:** No risk of accidentally shipping dev files, secrets, or large unnecessary files.
- **Single source of truth:** `.distignore` is the only place you need to update to add/remove files from the release.
- **Industry standard:** This is the approach used by many professional open source projects.

### How to maintain the exclude list
- Edit `.distignore` in the project root. It works like `.gitignore` (one pattern per line).
- Example entries:
  ```
  .git/
  node_modules/
  tests/
  .DS_Store
  *.md
  *.sh
  docker-compose.yml
  php.ini
  scripts/
  wp-content/plugins/wp-qr-trackr/.env
  wp-content/plugins/wp-qr-trackr/.env.example
  ```
- To add or remove files from the release, just update `.distignore` and re-run the release script.

### How to build a release
Run:
```sh
./scripts/build-release.sh [major|minor|patch|prerelease [type] N]
```
This will:
- Bump the version and update the changelog
- Copy only the required files (using rsync and .distignore)
- Install production dependencies
- Create a minimal, production-ready ZIP in the project root

---

## Technical Reference: Work Instructions

### Maintaining the Release Packaging Process
- **To add or remove files from the plugin release:**
  - Edit `.distignore` in the project root. This file controls what is excluded from the release ZIP.
  - Patterns work like `.gitignore`.
- **To build a release:**
  - Run `./scripts/build-release.sh` with the appropriate version bump argument.
- **To verify the release:**
  - Unzip the generated ZIP and confirm only the expected files and the `vendor/` directory (with production dependencies) are present.
- **If you add new dev tools, scripts, or config files:**
  - Add them to `.distignore` if they should not be shipped to users.

---

# wp-qr-trackr Development Guide

> **Note:** Sections marked with [COMMON] are shared with nonprod and production documentation.

## [COMMON] Project Overview
wp-qr-trackr is a modular, robust WordPress plugin for QR code generation and tracking. It is open source and built entirely via prompt engineering and Cursor's Agent Mode.

## Local Development Environment
- Uses Docker Compose for local WordPress + MySQL stack.
- No live-mounts in nonprod; live-mounts may be used in dev for rapid iteration.
- See `docker-compose.yml` (dev version) for service definitions.

## [COMMON] Plugin Structure
- Modular includes: admin, AJAX, rewrite, debug, utility, etc.
- Main plugin file only bootstraps modules.
- All business logic is in `includes/` modules.

## [COMMON] Coding Standards
- WordPress Coding Standards enforced via PHPCS.
- PHPCS requires at least 1GB RAM, 4GB recommended for large codebases (see `.cursorrules`).
- All code must pass CI/CD before merging.

## Development Workflow
- Use feature branches and PRs for all changes.
- Run `./scripts/build-release.sh patch` to build and verify release ZIPs.
- Use `reset-nonprod-docker.sh` to reset the nonprod environment for clean testing.

## [COMMON] Security Practices
- Separate nonces for all admin AJAX actions.
- Strict capability checks for all sensitive actions.

## [COMMON] Release Process
- Automated build script ensures only required files are included.
- Release ZIP is verified for required/forbidden files.
- Releases are published to GitHub with full changelogs.

## [COMMON] Contributor Notes
- See `.cursorrules` for project rules and environment requirements.
- All major documentation files have parallel dev, nonprod, and prod versions.

## See also
- `README.nonprod.md` for nonprod Docker/QA environment.
- `README.prod.md` for production deployment and usage.

## Local All-in-One Environment

You can now start all environments (dev, nonprod, and a local GitHub MCP server) with:

```sh
./scripts/launch-all-docker.sh
```

- **Dev**: WordPress on port 8080 (live-mounts, rapid iteration)
- **Nonprod**: WordPress on port 8081 (clean, no live-mounts)
- **MCP**: Local GitHub MCP server on port 7000 (for merge/conflict attention and repo automation)

See script comments for details and requirements.

---

## [COMMON] MCP Philosophy and Local GitHub MCP

**Model Context Protocol (MCP)** is a standard for giving LLMs (and dev tools) secure, controlled access to tools and data sources. In this project, a local GitHub MCP server is used to help:

- Detect and resolve merge conflicts, detached HEADs, and PR attention issues.
- Automate repo hygiene and reduce human error in common Git workflows.
- Provide a local, auditable API for GitHub operations, which can be used by agents or scripts.

**Example:**
- If you hit a detached HEAD or merge conflict, the MCP server can surface this to your agent or automation, and even suggest or execute safe resolutions.
- Frequent issues like "PR needs attention" or "merge conflict on main" can be detected and handled programmatically, reducing friction for contributors.

MCP is part of the project's commitment to robust, agent-friendly, and future-proof open source workflows. 
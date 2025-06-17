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
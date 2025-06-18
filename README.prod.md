# QR Trackr Plugin Template (Production)

A modern, production-ready WordPress plugin template—featuring QR Trackr as an example. This version of the documentation is focused on secure, stable, and performant production deployment.

## Table of Contents
1. Project Overview
2. Quick Start / Onboarding (Production)
3. Usage
4. Production Deployment & Configuration
5. Infrastructure & Plumbing
6. Troubleshooting & FAQ (Production)
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

## Quick Start / Onboarding (Production)

### Prerequisites
- DigitalOcean App Platform account
- WordPress-compatible hosting
- [Yarn](https://yarnpkg.com/)
- [Composer](https://getcomposer.org/)

### Production Setup Steps
1. **Clone the repository and build the plugin as per release instructions.**
2. **Deploy WordPress to DigitalOcean App Platform or your production host.**
3. **Upload the built plugin zip via the WordPress admin or deploy as part of your CI/CD pipeline.**
4. **Configure environment variables and secrets in the DigitalOcean App Platform dashboard.**
5. **Ensure debug mode is disabled in production.**

---

## Production Deployment & Configuration

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

---

## Usage

(Shared usage instructions...)

---

## Infrastructure & Plumbing

(Shared infrastructure details, with production notes...)

---

## Troubleshooting & FAQ (Production)

See [docs/TROUBLESHOOTING.prod.md](docs/TROUBLESHOOTING.prod.md) for help with common production issues, deployment, and advanced troubleshooting tips.

---

## Links & Further Reading

(Shared links...)

# wp-qr-trackr Production Deployment & Usage Guide

> **Note:** Sections marked with [COMMON] are shared with dev and nonprod documentation.

## [COMMON] Project Overview
wp-qr-trackr is a modular, robust WordPress plugin for QR code generation and tracking. It is open source and built entirely via prompt engineering and Cursor's Agent Mode.

## Production Deployment
- Download the latest verified release ZIP from GitHub Releases.
- Upload the ZIP via the WordPress admin plugin installer.
- No development or test files are included in the release ZIP.
- All dependencies are bundled; no Composer install required on production.

## [COMMON] Plugin Structure
- Modular includes: admin, AJAX, rewrite, debug, utility, etc.
- Main plugin file only bootstraps modules.
- All business logic is in `includes/` modules.

## [COMMON] Coding Standards
- WordPress Coding Standards enforced via PHPCS.
- PHPCS requires at least 1GB RAM, 4GB recommended for large codebases (see `.cursorrules`).
- All code must pass CI/CD before merging.

## Production Usage
- Activate the plugin in the WordPress admin.
- Use the QR Trackr admin page to generate, edit, regenerate, and delete QR codes.
- All admin actions are protected by separate nonces and strict capability checks.

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
- `README.dev.md` for local development.
- `README.nonprod.md` for nonprod Docker/QA environment.

## MCP and All-in-One Launch (for Reference)

While not required in production, the project supports a local GitHub MCP server for agent-based repo automation and merge conflict prevention. See the dev README for philosophy and usage details.

To launch all environments (dev, nonprod, MCP) locally:

```sh
./scripts/launch-all-docker.sh
```

This is primarily for development and advanced QA. 
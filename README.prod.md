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
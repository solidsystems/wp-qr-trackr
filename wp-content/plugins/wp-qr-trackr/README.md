# QR Trackr

Generate and track QR codes for WordPress pages and posts. Adds QR code generation to listings and edit screens, and tracks scans with stats overview.

## Features
- Generate QR codes for any page or post
- Download QR codes for print use
- Track the number of times each QR code is scanned
- View scan stats in the admin menu and on post/page listings
- Mobile-first UI for all admin features

## Installation
1. Copy the `wp-qr-trackr` folder to your `wp-content/plugins/` directory.
2. Activate the plugin through the WordPress admin panel.
3. Use the new QR Trackr menu in the admin sidebar for stats and management.

## Usage
- On the Pages or Posts list, use the QR Trackr quicklink to generate and download QR codes.
- On the edit screen for any page or post, generate a QR code and view scan stats.
- Use the QR Trackr admin menu for an overview and individual scan statistics.

## Database
- Creates custom tables `{prefix}_qr_trackr_scans` (scan events) and `{prefix}_qr_trackr_links` (tracking links).

## Project Architecture

QR Trackr is built using a **modular architecture** for maintainability, scalability, and clarity. All major logic is separated into modules under the `includes/` directory:

- **module-activation.php**: Handles plugin activation, database table creation, and activation-time checks.
- **module-admin.php**: All admin UI logic, including menus, admin pages, admin columns, row actions, notices, and admin script enqueuing.
- **module-ajax.php**: AJAX handlers (e.g., QR code creation via AJAX).
- **module-rewrite.php**: Custom rewrite rules, query vars, and endpoint handlers for QR code tracking and redirection.
- **module-debug.php**: Debug logging utilities, debug mode admin UI, and admin footer debug output.
- **module-utility.php**: Shared utility functions (e.g., rendering QR lists, getting tracking links, DB migrations, save_post handlers).

The main plugin file (`qr-trackr.php`) only bootstraps the plugin and loads these modules.

### Why Modularize?
- **Separation of concerns**: Each module handles a specific aspect of the plugin.
- **Maintainability**: Easier to update, debug, and extend.
- **Scalability**: Add new features as new modules without cluttering the main file.
- **WordPress Standards**: Follows best practices for file organization and hook registration.

### Extending QR Trackr
To add a new feature:
1. Create a new module file in `includes/` (e.g., `module-rest.php`).
2. Register any hooks or functions in that file.
3. Require the new file in `qr-trackr.php`.

## Security & Best Practices
- Follows WordPress coding standards and security best practices.
- All admin features are accessible only to users with `manage_options` capability.
- All user input is sanitized and validated.

## Roadmap
- Export scan stats
- Custom QR code styles
- REST API integration

---

For support or feature requests, open an issue or PR. 
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

### Engineering Philosophy: Systems and Optimization

QR Trackr is built on the principle that a well-designed system should amplify the creator's capabilities while abstracting away implementation details. This philosophy can be illustrated through the metaphor of a flagpole:

#### The Evolution of a System

1. **Unanchored Flagpole (Original Plugin)**
   - Like a flagpole balanced on the ground, the original plugin required constant attention to maintain stability
   - Every change risked toppling the entire system
   - No foundation meant no ability to optimize for performance or reliability

2. **Shallow Foundation (Local Development)**
   - Adding Docker and automated installation created a basic foundation
   - The system is more stable but still requires manual intervention
   - Like a flagpole in loose soil, it's better but not optimal

3. **Concrete Foundation (CI/CD Integration)**
   - With proper CI/CD, the system becomes rock-solid
   - Automated testing and deployment ensure consistency
   - The foundation is now permanent and reliable
   - Developers can focus on the flag (features) rather than the pole (infrastructure)

#### The Value of a System

A well-designed system should:
- **Amplify Creativity**: Let developers focus on what matters
- **Abstract Complexity**: Handle "best practices" automatically
- **Enable Optimization**: Allow for micro-optimizations without worrying about fundamentals
- **Provide Stability**: Create a reliable foundation for future development

Just as a speedrunner can focus on perfecting their route when they don't have to worry about basic controls, developers can focus on creating better features when the system handles the fundamentals. The goal is to create a system where the "flag" (the actual features and user experience) is the only thing that needs attention, while the "pole" (the infrastructure and best practices) is solid and reliable.

### Extending QR Trackr
To add a new feature:
1. Create a new module file in `includes/` (e.g., `module-rest.php`).
2. Register any hooks or functions in that file.
3. Require the new file in `qr-trackr.php`.

## Post-Foundation Plugin Changes (2024-2025)

After the initial refactor and foundation, the following major changes and improvements were made to QR Trackr:

### Modularization & Architecture
- All business logic was separated into modules under `includes/` (admin, AJAX, rewrite, debug, utility, etc.).
- The main plugin file now only bootstraps and loads modules, with no business logic.
- Each module registers its own hooks and handles a single concern, improving maintainability and scalability.

### Database & Tracking
- Custom tables `{prefix}_qr_trackr_links` and `{prefix}_qr_trackr_scans` are now used for all tracking and stats.
- All queries and schema updates use safe, standards-compliant SQL with proper escaping and parameterization.
- Table creation and migrations are handled on activation with robust error handling and logging.

### QR Code Generation
- Switched to local QR code generation using the `endroid/qr-code` library (no external services).
- All QR code images are generated and served from the local uploads directory.
- Added support for custom QR code styles and error correction levels.

### Admin UI & UX
- Redesigned admin pages for mobile-first, responsive experience.
- Added stats overview and individual scan statistics pages.
- Improved table/list rendering, filtering, and bulk actions.
- Added destination URL editing directly from the stats table.
- Enhanced success messages and download instructions for QR code creation.

### JavaScript & Performance
- Refactored admin JS to use proper scoping, event delegation, and debug logging.
- Scripts/styles are only loaded on relevant admin pages for performance.
- Improved AJAX error handling and user feedback.

### Security & Coding Standards
- All user input is sanitized and validated using WordPress functions.
- All output is properly escaped.
- Nonces are used for all AJAX and form operations.
- Follows strict WordPress coding standards (see .cursorrules).
- Comprehensive debug logging added for troubleshooting.

### CI/CD & Testing
- Modernized test environment with Yoast/wp-test-utils and Brain Monkey.
- Fixed PHPCS and ESLint config for full standards compliance.
- Improved pre-commit and CI workflows for reliability and portability.

### Documentation
- README and inline docs updated to reflect all changes and best practices.
- Added detailed instructions for extending, testing, and contributing to the plugin.

## Security & Best Practices
- Follows WordPress coding standards and security best practices.
- All admin features are accessible only to users with `manage_options` capability.
- All user input is sanitized and validated.

## Roadmap
- Export scan stats
- Custom QR code styles
- REST API integration

## Testing & Development

### Dependencies
- **Install dependencies:**
  ```sh
  composer install
  ```
- **Run PHPCS (WordPress Coding Standards):**
  ```sh
  composer phpcs
  ```
  This uses the local config and works on Mac, Linux, and CI. No global PHPCS install is needed.
- **Run JS linting:**
  ```sh
  yarn lint
  ```
- **Run CSS linting:**
  ```sh
  yarn stylelint
  ```

### Best Practices Philosophy
- **Always use local Composer dependencies for tools like PHPCS.**
  - Add PHPCS as a dev dependency in the root composer.json.
  - All contributors (Mac, Linux, CI) should run `composer install` and use `vendor/bin/phpcs`.
  - Never require or rely on global PHPCS installs—this ensures version consistency and zero path issues.
  - Add Composer scripts for common tasks (e.g., `composer phpcs`).
  - This approach is portable, standards-compliant, and works everywhere.

### Testing Framework
For comprehensive testing setup and framework details, see the "Modern WordPress Plugin Testing Setup (2024+)" section in the main project README.

## PR Summary (June 2025)

**WordPress Plugin Test Environment Modernization Complete:**
- Implemented modern testing stack with Yoast/wp-test-utils and Brain Monkey for 2025 industry standards
- Fixed ESLint config to use flat config ignores and updated lint scripts for standards compliance
- Updated pre-commit hook to only lint staged JS source files, excluding config files
- Moved Composer install/audit steps in CI to the project root, preventing failures
- Updated workflow for TODO index to use CI_GITHUB_TOKEN for authenticated pushes
- All changes documented and workflow is now robust, standards-compliant, and future-proof

---

For support or feature requests, open an issue or PR. 
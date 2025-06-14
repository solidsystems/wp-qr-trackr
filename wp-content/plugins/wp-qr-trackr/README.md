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

## Linting & Code Quality

- ESLint is configured to ignore `vendor/`, `node_modules/`, and `coverage/` via a `.eslintignore` file in this directory.
- The `lint` script in `package.json` explicitly uses this ignore file to ensure consistent results locally and in CI.
- To run linting:

```sh
yarn lint
```

## Final Few Items

- Pre-commit hooks now only lint staged JS files in the plugin directory, excluding config files like `eslint.config.js` for robust, standards-compliant workflow.
- The root `lint` script delegates to the plugin's lint script, ensuring Husky and CI both use the correct config and ignore patterns.
- All linting, formatting, and standards checks run automatically before commit, with no need for skip flags.

## PR Summary (June 2025)

- Fixed ESLint config to use flat config ignores and updated lint scripts for standards compliance.
- Updated pre-commit hook to only lint staged JS source files, excluding config files, for robust and standards-compliant workflow.
- Moved Composer install/audit steps in CI to the project root, preventing failures due to missing composer.json in the plugin directory.
- Updated workflow for TODO index to use CI_GITHUB_TOKEN for authenticated pushes, resolving push errors in CI.
- All changes are documented in the README and tracking files, and the workflow is now robust, standards-compliant, and future-proof.

## Best Way to Do Things Philosophy

- **Always use local Composer dependencies for tools like PHPCS.**
  - Add PHPCS as a dev dependency in the root composer.json.
  - All contributors (Mac, Linux, CI) should run `composer install` and use `vendor/bin/phpcs`.
  - Never require or rely on global PHPCS installs—this ensures version consistency and zero path issues.
  - Add Composer scripts for common tasks (e.g., `composer phpcs`).
  - This approach is portable, standards-compliant, and works everywhere.

## Testing & Linting

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

See the 'Best Way to Do Things Philosophy' section for more on why this approach is used.

## Brain Monkey Compatibility & Alternatives

- **Brain Monkey** is a popular library for mocking WordPress functions in unit tests.
- As of June 2025, the latest release is v2.6.2 (Oct 2024). However, Brain Monkey is only officially compatible with PHPUnit up to 9.x. It is not compatible with PHPUnit 10+ or 12+.
- If you encounter errors related to test suites or undefined PHPUnit methods, check your Brain Monkey version and its compatibility with your PHPUnit version.
- **Alternatives:**
  - [Yoast/wp-test-utils](https://github.com/Yoast/wp-test-utils): Provides a compatibility layer for PHPUnit 5.7–9.x and integrates with Brain Monkey, Mockery, and native PHPUnit mocks. It is the most robust option for modern WordPress plugin testing.
  - [WP_Mock](https://github.com/10up/wp_mock): Another popular WordPress mocking library.
  - **Native PHPUnit mocks**: For simple cases, use PHPUnit's built-in mocking.
- If you need to support PHPUnit 10+ or 12+, consider migrating to Yoast/wp-test-utils or using native PHPUnit mocks.
- If you re-enable Brain Monkey, ensure you are using a compatible PHPUnit version (9.x or below) or follow the Yoast/wp-test-utils migration guide.

## Why We Use PHPUnit 9.x and Yoast/wp-test-utils (Not the Latest Version)

- **Industry Standard:** The WordPress plugin ecosystem in 2025 overwhelmingly uses PHPUnit 9.x, as it is the most stable and compatible version for WordPress development and testing.
- **Ecosystem Compatibility:** Most WordPress-specific testing utilities (including Brain Monkey and Yoast/wp-test-utils) are only compatible with PHPUnit 9.x and below. The WordPress Core test suite and most major plugins/themes are also on PHPUnit 9.x.
- **Latest PHPUnit (10+/12+) Not Yet Supported:** As of June 2025, PHPUnit 10+ and 12+ introduce major breaking changes and are not yet supported by Brain Monkey or Yoast/wp-test-utils. The WordPress ecosystem is expected to migrate once these tools are updated.
- **Best Practice:** Using Yoast/wp-test-utils with PHPUnit 9.x ensures maximum compatibility, robust mocking, and a future-proof path as the ecosystem evolves.
- **If/when the ecosystem moves to PHPUnit 10+ or 12+,** this project will revisit and update its testing stack accordingly.

---

For support or feature requests, open an issue or PR. 
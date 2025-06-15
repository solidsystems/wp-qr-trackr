# Codegen Remediation Tracking

This document tracks issues introduced or left unresolved by automated code generation and remediation tools (e.g., PHPCBF, AI refactoring, bulk fixes). It provides a holistic, type-by-type solution plan and a checklist for progress tracking.

---

## Issue Types & Solutions

### 1. Missing Doc Comments (Functions, Classes, Member Variables)
- **Solution:**
  - Add PHPDoc blocks above all functions, classes, and member variables.
  - Use clear, concise descriptions and proper `@param`/`@return` tags.
- **Tracking:**
  - [x] QRTrackrListTableTest.php: All functions and member variables documented, inline comment punctuation fixed.
  - [x] QrCodeTest.php: All functions and member variables documented, inline comment punctuation fixed.
  - [x] QrCodeCoverageTest.php: All functions and member variables documented, Yoda conditions and inline comment punctuation fixed.
  - [x] PluginTest.php: All functions and member variables documented, Yoda conditions and inline comment punctuation fixed.
  - [x] QRTrackrAdminTest.php: All functions and member variables documented, Yoda conditions and inline comment punctuation fixed, useless method overrides removed.
  - [x] QRTrackrCPTTest.php: All functions and member variables documented, Yoda conditions and inline comment punctuation fixed, useless method overrides removed.
  - [x] QRTrackrFunctionsTest.php: All functions and member variables documented, Yoda conditions and inline comment punctuation fixed, useless method overrides removed.
  - [x] QRTrackrRewriteTest.php: All functions and member variables documented, Yoda conditions and inline comment punctuation fixed, useless method overrides removed.
  - [x] QRTrackrShortcodeTest.php: All functions and member variables documented, Yoda conditions and inline comment punctuation fixed, useless method overrides removed.
  - [x] QRTrackrStatsTest.php: All functions and member variables documented, Yoda conditions and inline comment punctuation fixed, useless method overrides removed.
  - [x] All functions/classes/variables in tests documented
  - [x] All functions/classes/variables in source documented (all source files checked and found compliant; no changes needed)

### 2. Yoda Conditions
- **Solution:**
  - Refactor all conditionals to use Yoda style (constant on the left).
- **Tracking:**
  - [ ] All conditionals in tests use Yoda style
  - [ ] All conditionals in source use Yoda style

### 3. Inline Comments Must End in Punctuation
- **Solution:**
  - Edit all inline comments to end with a period, exclamation mark, or question mark.
- **Tracking:**
  - [ ] All inline comments in tests fixed
  - [ ] All inline comments in source fixed

### 4. SQL Placeholders and $wpdb->prepare()
- **Solution:**
  - All SQL queries must use placeholders and $wpdb->prepare() for user input.
  - Table names must be constructed from $wpdb->prefix and static strings only.
- **Tracking:**
  - [x] All source files: All queries use $wpdb->prepare() or safe static table names; no raw user input is interpolated. No further remediation needed.

### 5. Direct Database Calls Without Caching
- **Solution:**
  - Where appropriate, wrap expensive/repeated queries with `wp_cache_get()`/`wp_cache_set()`.
- **Tracking:**
  - [ ] Caching reviewed for all direct DB calls

### 6. Nonce Verification and Input Sanitization
- **Solution:**
  - Add nonce checks and sanitize all input in form handlers, AJAX, and admin actions.
- **Tracking:**
  - [x] All source files: All AJAX handlers, admin forms, and meta boxes use proper nonce verification and input sanitization. No further remediation needed.

### 7. Output Escaping
- **Solution:**
  - All dynamic output must be escaped using esc_html(), esc_url(), esc_attr(), or equivalent utility functions.
- **Tracking:**
  - [x] All source files: All dynamic output is properly escaped; no unescaped user input is echoed or printed. No further remediation needed.

### 8. File/Class Naming Conventions
- **Solution:**
  - Rename files/classes to match WordPress conventions (e.g., `class-<class-name>.php`).
- **Tracking:**
  - [x] File/class naming: Renamed `class-wp-list-table.php` to `class-qr-trackr-list-table.php` and updated all references in `qr-trackr.php`, `wp-qr-trackr.php`, and `tests/QRTrackrListTableTest.php` for full WordPress Coding Standards compliance.

### 9. Discouraged Functions/Practices
- **Solution:**
  - Remove or replace discouraged functions (e.g., error_log, var_dump, print_r, eval, create_function, system calls, json_encode, date) with WordPress-safe alternatives, or PHPCS-ignore for development/debug only.
- **Tracking:**
  - [x] All source files: All uses are either PHPCS-ignored, development/debug only, or replaced with WordPress-safe alternatives. No further remediation needed.

### 10. "Possible Useless Method Overriding Detected" (in tests)
- **Solution:**
  - Remove empty `setUp()`/`tearDown()` overrides unless needed.
- **Tracking:**
  - [ ] All test classes reviewed for useless overrides

---

## Progress Checklist
- [ ] All issues above have been reviewed and resolved in all plugin and test files.
- [ ] This document is kept up to date as fixes are made.

---

## Notes
- This document is for tracking and guiding remediation of codegen-related issues only. For general coding standards, see `README.md` and `CONTRIBUTING.md`. 
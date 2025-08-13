# Build a New Plugin Using This Template

This guide explains how to create a new WordPress plugin using the WP QR Trackr repository as a foundation, aligned with current policies and tooling.

## Prerequisites
- Docker Desktop and Git installed
- GitHub repo (fork or new)
- Optional: GitHub CLI (`gh`) authenticated

## 1) Clone and Prepare
```bash
# Fork or create a new repo from this template, then clone
git clone <your-repo-url>
cd <your-repo>

# Start environments using control scripts
./scripts/setup-wordpress.sh dev
./scripts/setup-wordpress.sh nonprod
```

## 2) Update Plugin Identity
- Edit `wp-qr-trackr.php`:
  - Plugin Name, Description, Author, URI, Text Domain
  - Constants: change prefix (`QR_TRACKR_` and legacy `QRC_`) to your plugin prefix
- Search/replace across codebase for:
  - Text domain `wp-qr-trackr`
  - Table names `qr_trackr_links`
  - Function prefixes `qr_trackr_` and `qrc_`

Recommended command examples:
```bash
# Example: update text domain
rg -n "wp-qr-trackr" | cat
# Then perform controlled replacements in your editor
```

## 3) Database Schema
- Update `includes/module-activation.php` to create/upgrade your custom tables.
- Always use `$wpdb->prepare()` and `{$wpdb->prefix}your_table`.
- Add/modify indexes for query patterns.
- Add caching where appropriate; invalidate after writes.

## 4) Admin UI & Routing
- Update `includes/module-admin.php` menu labels and page slugs.
- Keep asset policy: local Select2 bundling, scoped enqueue by `$hook`.
- Clean URLs: register rewrite rules on `init`, handle on `template_redirect`.
- Redirect policy: `wp_redirect( esc_url_raw( $destination ), 302 )` for external; `wp_safe_redirect()` for internal/admin.

## 5) Security & Data Integrity
- Nonces for all forms/AJAX; sanitize (`wp_unslash` + type-specific) and escape outputs.
- Unique data constraints (e.g., referral codes) enforced on create/edit with prepared queries; provide UI feedback.

## 6) Coding Standards
- Run validation locally:
```bash
make validate       # PHPCS; E2E skipped
make validate-e2e   # PHPCS + Playwright E2E (local only)
```
- PHPCS errors must be 0 before merge; warnings addressed when feasible.

## 7) Documentation
- Update `.cursorrules` with your plugin’s policies if they differ.
- Update `docs/README.md` and dev guides with your plugin name and specifics.

## 8) Release Flow
```bash
# Bump version in wp-qr-trackr.php (both header and constant)
# Build release zip
./scripts/build-release.sh

# Tag + GitHub release
git tag -a vX.Y.Z -m 'Release X.Y.Z'
git push origin vX.Y.Z

gh release create vX.Y.Z dist/wp-qr-trackr-vX.Y.Z.zip \
  --title "Your Plugin X.Y.Z" \
  --notes 'Key changes and highlights.'
```

## 9) WordPress Environments
- Dev (8080): live-mount development; Playwright E2E supported via `make validate-e2e`.
- Nonprod (8081): no bind mounts; install via release zip; validate clean URLs and redirects.

## 10) Checklist
- [ ] Plugin headers updated
- [ ] Prefixes (functions, constants, tables) updated
- [ ] Rewrite rules and redirect handler wired
- [ ] Security: nonces, sanitization, escaping verified
- [ ] Select2 local and scoped; assets initialized correctly
- [ ] Unique fields enforced server-side and validated in UI
- [ ] PHPCS clean (`make validate`)
- [ ] E2E run locally if applicable (`make validate-e2e`)
- [ ] Release zip built and tested in nonprod

For deeper details, see:
- `docs/dev-guide/GETTING_STARTED.md`
- `docs/architecture/ARCHITECTURE.md`
- `docs/maintenance/LESSONS_LEARNED.md`

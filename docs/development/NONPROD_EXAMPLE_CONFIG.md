# Nonprod (8081) Example Configuration and Operations

This example captures the settings and steps that made the nonprod test environment reliable during recent sessions. Follow control scripts only.

## Goals

- Clean tracking URLs `/qr/{code}/` working via rewrites
- Native redirects: `wp_redirect()` for external, `wp_safe_redirect()` for internal/admin
- Stable plugin install/upgrade flow (release zips only; no bind-mounts)

## Docker Compose service environment (excerpt)

```yaml
services:
  db-nonprod:
    image: mariadb:10.5
    environment:
      MYSQL_DATABASE: wordpress
      MYSQL_USER: trackr
      MYSQL_PASSWORD: trackr
      MYSQL_ROOT_PASSWORD: trackr
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "127.0.0.1"]
      interval: 5s
      timeout: 3s
      retries: 20

  wordpress-nonprod:
    image: wordpress:php8.2-apache
    environment:
      WORDPRESS_DB_HOST: db-nonprod:3306
      WORDPRESS_DB_USER: trackr
      WORDPRESS_DB_PASSWORD: trackr
      WORDPRESS_DB_NAME: wordpress
      APACHE_DOCUMENT_ROOT: /var/www/html
    depends_on:
      db-nonprod:
        condition: service_healthy
    # No plugin bind-mounts in nonprod; install via release zip
```

## One-time WordPress setup (control scripts)

```bash
# Start nonprod
./scripts/setup-wordpress.sh nonprod

# Verify core is installed
./scripts/wp-operations.sh nonprod core is-installed

# Ensure upgrade directory is writable
./scripts/wp-operations.sh nonprod eval "echo is_writable('/var/www/html/wp-content/upgrade') ? 'writable' : 'not_writable';"

# Set pretty permalinks and flush
./scripts/wp-operations.sh nonprod rewrite structure '/%postname%/'
./scripts/wp-operations.sh nonprod rewrite flush --hard
```

## Plugin install/upgrade flow (release zips)

```bash
# Build a release locally
./scripts/build-release.sh

# Transfer the built zip into the nonprod container's plugins directory
# (Use your standard artifact transfer mechanism; avoid documenting direct docker commands.)
# Target path inside container:
#   /var/www/html/wp-content/plugins/wp-qr-trackr-vX.Y.Z.zip

# Install/activate via wp-cli (through control script)
./scripts/wp-operations.sh nonprod plugin install \
  /var/www/html/wp-content/plugins/wp-qr-trackr-vX.Y.Z.zip \
  --force --activate --allow-root
```

## Rewrite rules and redirects

- Canonical tracking URL: `/qr/{code}/` (alias `/qrcode/{code}/` optional).
- Rewrites registered on `init` with 'top' priority; query var `qr_tracking_code` added.
- Handler runs on `template_redirect` (early return for `is_admin()` or `wp_doing_ajax()`).
- External destinations: `wp_redirect( esc_url_raw( $destination_url ), 302 )`.
- Internal/admin destinations: `wp_safe_redirect()`.
- Flush rewrite rules only on activation/upgrade:

```bash
./scripts/wp-operations.sh nonprod rewrite flush --hard
```

## Known-good defaults

- DB credentials: `trackr:trackr`
- DB host: `db-nonprod:3306`
- WP uploads/upgrade owned by `www-data:www-data`
- PHP runs as `www-data` inside container
- No plugin bind-mount in nonprod

## Troubleshooting

- 404 on `/qr/{code}/`:
  - Confirm permalinks structure and flush.
  - Verify plugin active and rewrite rules registered on `init`.
- External URL doesn’t redirect:
  - Ensure handler uses `wp_redirect( esc_url_raw(...) )` for external destinations.
- Blank admin after edit:
  - Use `wp_safe_redirect()` for admin redirects; add JS/meta fallback if headers already sent.

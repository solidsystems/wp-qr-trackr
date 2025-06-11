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
- Creates a custom table `{prefix}_qr_trackr_scans` to store scan events.

## Security & Best Practices
- Follows WordPress coding standards and security best practices.
- All admin features are accessible only to users with `manage_options` capability.

## Roadmap
- Export scan stats
- Custom QR code styles
- REST API integration

---

For support or feature requests, open an issue or PR. 
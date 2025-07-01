# QR Trackr Architecture

## Overview

The QR Trackr plugin is designed with a modular, standards-driven architecture that integrates seamlessly into any WordPress environment. At a high level, the system consists of three main domains:

- **WordPress Site:** The core WordPress application, the QR Trackr plugin, the modern modal-based admin UI, the MySQL database with enhanced schema, and the uploads/filesystem for QR code images.
- **User Devices:** End users interact via web browsers or mobile devices, scanning QR codes and accessing tracked content with immediate scan count updates.
- **External Services:** Optional integrations such as CDN/static hosting for QR images, email services for notifications, and analytics/logging endpoints for tracking and reporting.

This architecture ensures clear separation of concerns, scalability, and easy integration with both internal and external systems. All major flows—admin management with modal interfaces, QR code generation, real-time scan tracking, advanced search/filtering, and comprehensive debugging—are handled by the plugin, with extensibility points for custom workflows and integrations.

## Key Architectural Components

### Modal-Based Admin Interface
- **AJAX-Powered Modals:** Clickable QR images open detailed management modals with real-time editing capabilities
- **Advanced Search System:** Global search across common names, referral codes, QR codes, and destination URLs
- **Filter Integration:** Referral code dropdown filtering with search-aware caching
- **Mobile-Responsive Design:** Touch-friendly interface optimized for all device sizes

### Enhanced Database Schema
- **New Fields:** Common name and referral code fields with proper indexing for performance
- **Automatic Migrations:** Version-based database upgrades with seamless field additions
- **Caching Strategy:** Search-aware cache keys with intelligent invalidation

### Debug Mode Integration
- **System Diagnostics:** Comprehensive health checks for database, rewrite rules, and file system
- **Live Testing:** QR generation testing with visual preview and validation
- **Troubleshooting Tools:** Force flush capabilities and detailed error reporting

## Architecture Diagram

```mermaid
flowchart TD
  subgraph "WordPress Site"
    WP["WordPress Core"]
    Plugin["QR Trackr Plugin"]
    Admin["WP Admin UI"]
    DB["MySQL Database"]
    Files["Uploads/Filesystem"]
  end

  subgraph "User Devices"
    UserWeb["Web Browser"]
    UserMobile["Mobile Device"]
    QRScan["QR Code Scanner App"]
  end

  subgraph "External Services"
    CDN["CDN/Static Hosting"]
    Email["Email Service"]
    Analytics["Analytics/Logging"]
  end

  UserWeb -->|"Admin/Stats UI"| Admin
  UserMobile -->|"Scans QR"| QRScan
  QRScan -->|"Redirect/Track"| Plugin
  Plugin -->|"DB Ops"| DB
  Plugin -->|"File Ops"| Files
  Plugin -->|"API/Webhook"| Analytics
  Plugin -->|"Send Email"| Email
  Files --> CDN
  Admin --> Plugin
  WP --> Plugin
  Plugin --> WP
``` 

## Environment Architecture Note

The project now supports parallel Docker Compose environments for development (dev, port 8080) and production-like testing (nonprod, port 8081). Use `./scripts/launch-all-docker.sh` to run both in isolation. This enables rapid iteration in dev while ensuring all releases are validated in a clean, production-like WordPress instance, supporting robust modularity and QA. 

### PHPCS and Static Table Assignments
- Static table assignments (e.g., `$table = $wpdb->prefix . 'table_name';`) are flagged by PHPCS as unsafe, even when built from safe components.
- Project uses local `// phpcs:disable`/`// phpcs:enable` suppression around these assignments in `module-admin.php`.
- `.phpcs.xml` includes multiple `<exclude-pattern>` entries for maintainability, but some PHPCS versions may still flag these lines.
- Upgraded to latest PHP_CodeSniffer and WordPress Coding Standards to minimize false positives.
- If PHPCS continues to flag these, commits may be made with `--no-verify` (with justification in commit message). 

## Docker Volume Mount Workflow

- The `ci-runner` container mounts the local project directory (`.:/usr/src/app`).
- All linting, formatting, and testing commands run inside the container, but changes are written to the local filesystem.
- This ensures that the development, CI, and production environments are consistent.
- See the diagram below for the architecture.

### Architecture Diagram

```mermaid
flowchart TD
    A[Local Source Code] -- Mounted as Volume --> B[ci-runner Docker Container]
    B -- Runs PHPCS/PHPCBF, Tests, Build --> A
    B -- Same Environment as CI/CD --> C[CI/CD Pipeline]
    A -- Changes Persist on Host --> D[Git Commit/Push]
    C -- Validates Code --> D
    subgraph Developer Workflow
        A
        B
    end
    subgraph CI/CD
        C
        D
    end
```
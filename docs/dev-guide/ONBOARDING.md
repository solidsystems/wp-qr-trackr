# 🚀 WP QR Trackr - Quick Start Guide

Welcome to WP QR Trackr! This guide will get you up and running with a production-ready QR code plugin in minutes.

## Current Status: Production Ready ✅
- **Version 1.2.4** - Stable and tested on live WordPress sites
- **All critical issues resolved** - No more activation errors or URL problems
- **Full functionality** - QR code generation, tracking, and analytics working

## For WordPress Users (Plugin Installation)

### Direct Installation
1. **Download** the latest release from GitHub releases page
2. **Upload** the plugin zip file via WordPress admin → Plugins → Add New → Upload
3. **Activate** the plugin (no fatal errors in v1.2.4!)
4. **Access** QR Trackr from your WordPress admin menu

### Manual Installation
1. **Download** or clone this repository
2. **Copy** the `wp-content/plugins/wp-qr-trackr/` folder to your WordPress plugins directory
3. **Activate** the plugin from WordPress admin
4. **Start creating** QR codes immediately

## For Developers (Local Development)

### Requirements
- Docker Desktop (latest)
- Git
- PHP 8.4+ (for local development)
- Composer (for dependency management)

### Development Workflow
- All development, linting, and testing can be done inside Docker containers
- No need to install PHP, Composer, Node, or CLI tools on your host
- All code changes persist on your local filesystem via Docker volume mounts

## Example Commands
```sh
docker compose run --rm ci-runner vendor/bin/phpcs
docker compose run --rm ci-runner vendor/bin/phpcbf
docker compose run --rm ci-runner bash ci.sh
```

## Why Containerize Everything?
- Ensures every developer and CI run uses the exact same environment.
- Eliminates "works on my machine" problems.
- No need for Homebrew or system package managers.
- Onboarding is as simple as installing Docker Desktop and Git.

## Automated Onboarding Check

- The script `scripts/check-onboarding.sh` runs automatically before every commit (pre-commit hook).
- You can run it manually at any time: `bash scripts/check-onboarding.sh`
- It checks for Docker, Docker running, and Git, and warns if local PHP, Composer, or Node is installed.

## Unified Code Validation

- To validate your code locally (lint, test, etc.), run:
  - `make validate`
  - or `docker compose run --rm ci-runner bash scripts/validate.sh`
- This is the same command used in CI/CD, ensuring consistency.

## See Also
- Architecture diagram and workflow: see `
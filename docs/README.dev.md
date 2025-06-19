## Parallel Docker Environments for Development

The dev environment is designed for rapid plugin iteration and debugging, running in parallel with a clean nonprod environment for release testing.

- **Dev environment** (port 8080):
  - Plugin is live-mounted for instant code changes.
  - Debug mode and dev tools enabled by default.
  - Use for all development, debugging, and local testing.
- **Nonprod environment** (port 8081):
  - Clean WordPress, plugin NOT preinstalled.
  - Use for uploading and testing release ZIPs as a real user would.

### Launching Both Environments

Use the launch script:

```sh
./scripts/launch-all-docker.sh
```

- Dev: http://localhost:8080
- Nonprod: http://localhost:8081

### Resetting/Stopping
- To reset dev: `./scripts/reset-docker.sh dev`
- To stop: `docker compose -p wpqrdev down`

### Troubleshooting
- If you see port conflicts, ensure no other services are using 8080 or 8081.
- Use `docker compose ps -a -p wpqrdev`/`-p wpqrnonprod` to inspect containers.
- The launch script uses `--remove-orphans` to clean up old containers.
- If you encounter issues, try stopping all containers and running the launch script again.

### Why This Matters
- Dev is always fast and iterative—live code, debug tools, no production risk.
- Nonprod is always clean—no dev artifacts, no plugin preinstalled.
- Parallel workflow supports robust QA and rapid development.

## Automated Dev Environment: No Setup Wizard

- The dev Docker container (port 8080) now auto-installs WordPress on startup.
- The admin user is always:
  - Username: `trackr`
  - Password: `trackr`
- The WordPress setup wizard is never shown. The site is always ready for use, automation, and testing.
- You can log in immediately at [http://localhost:8080/wp-login.php](http://localhost:8080/wp-login.php).

## Playwright Automation & User Flow Testing

- Playwright is installed in the dev container for automated UI testing and screenshot capture.
- To run the full user flow and capture screenshots:

```sh
./scripts/playwright-docker-userflow.sh
```

- This will:
  1. Reset the dev Docker environment
  2. Wait for the site to be ready
  3. Run the Playwright user flow script
  4. Save screenshots to `wp-content/plugins/wp-qr-trackr/assets/screenshots/`

- Use these screenshots for documentation, QA, or accessibility review. 

## Troubleshooting: Package Manager

- **Yarn is the only supported package manager for this project.**
- Only `yarn.lock` should be present in the project root.
- If you see `package-lock.json` or `pnpm-lock.yaml`, delete them to avoid conflicts.
- If using VS Code, set `"npm.packageManager": "yarn"` in `.vscode/settings.json` to enforce Yarn usage. 

## Multi-Project Playwright Orchestration

You can run automated documentation and UI tests for any plugin/project by setting the `PLUGIN_DIR` environment variable:

- The orchestrator script (`scripts/playwright-docs-orchestrator.sh`) will mount, activate, and test the specified plugin.
- The Playwright script must be named `scripts/playwright-<PLUGIN_DIR>-userflow.js`.

### Usage Example
```sh
PLUGIN_DIR=wp-qr-trackr ./scripts/playwright-docs-orchestrator.sh
PLUGIN_DIR=another-plugin ./scripts/playwright-docs-orchestrator.sh
```

To add a new project:
1. Place your plugin in `wp-content/plugins/<your-plugin-dir>`.
2. Create a Playwright script named `scripts/playwright-<your-plugin-dir>-userflow.js`.
3. Run the orchestrator with `PLUGIN_DIR=<your-plugin-dir>`. 
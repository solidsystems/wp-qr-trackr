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

- Playwright is available for local E2E via the Playwright runner container.
- Run lint-only validation: `make validate`.
- Run validation + E2E locally: `make validate-e2e`.
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

## Project Management & TODO Automation

### Dual TODO System for Development
This project includes a sophisticated project management system that combines modern structured todos with comprehensive documentation:

**🎯 Cursor Structured Todos (Active Development):**
- Real-time task management with dependency tracking
- Status management (pending, in_progress, completed, cancelled)
- Integration with Cursor IDE development workflow
- Automatic updates as you complete work

**📋 Traditional Documentation (Historical & Planning):**
- **TODO.md** - Comprehensive task lists and project achievements
- **STATUS.md** - High-level project health and version tracking
- **PROJECT_PLAN_MCP_ENHANCEMENTS.md** - Detailed project phases and roadmap

### TODO Automation Scripts

**Update TODO Index:**
```bash
# Syncs between Cursor todos and markdown files
# Includes automatic backup and validation
./scripts/update-todo-index.sh
```

**Setup GitHub Projects Integration:**
```bash
# Creates GitHub Project with proper field mapping
# Imports tasks from Cursor todos
# Sets up automation hooks
./scripts/setup-github-projects.sh
```

### Development Workflow with TODO System

**Starting Development:**
1. **Check available tasks** in Cursor todos or TODO.md
2. **Mark task as in-progress** when you start working
3. **Use dependency tracking** to work on tasks in proper order
4. **Update task status** as you complete work

**Maintaining Project State:**
1. **Run automation script** to sync both systems:
   ```bash
   ./scripts/update-todo-index.sh
   ```
2. **Commit changes** including updated documentation
3. **Review project summary** for completion tracking

**GitHub Projects Integration:**
- **Automated project creation** with custom fields (Priority, Phase, Effort)
- **Bidirectional sync** between Cursor todos and GitHub Projects
- **Status tracking** with proper field mapping
- **Automated hooks** for continuous synchronization

### Project Summary Dashboard
The automation system provides real-time project metrics:
- **Total Tasks:** Current count of all tasks
- **Completed:** Number of finished tasks
- **Active:** Current work in progress
- **Completion Rate:** Overall project progress percentage

### Benefits for Plugin Development
- **Professional project tracking** from day one
- **No manual todo maintenance** required
- **Historical documentation** of all project progress
- **Integration with popular development tools**
- **Automated backup** of all project documentation
- **Comprehensive error handling** and validation

### Usage in Your Plugin Projects
1. **Copy automation scripts** to your plugin project
2. **Customize task definitions** for your specific requirements
3. **Set up GitHub Projects** for your repository
4. **Use Cursor todos** for daily development workflow
5. **Maintain documentation** automatically with scripts

**Documentation Links:**
- 📖 [GitHub Projects Sync Guide](GITHUB_PROJECTS_SYNC.md)
- 📊 [Current Status Dashboard](../STATUS.md)
- 📋 [Project TODO List](../TODO.md)
- 🗺️ [MCP Enhancements Roadmap](../PROJECT_PLAN_MCP_ENHANCEMENTS.md) 
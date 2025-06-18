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
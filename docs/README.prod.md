## Parallel Docker Environments for Release Validation

Production and release testing now use a dedicated nonprod Docker Compose environment, fully isolated from development. This ensures that plugin releases are validated in a clean, production-like WordPress instance.

- **Dev environment** (port 8080): For active development, plugin is live-mounted.
- **Nonprod environment** (port 8081): Clean WordPress, plugin NOT preinstalled. Use for uploading and testing release ZIPs as a real user would.

### Launching Both Environments

Use the launch script:

```sh
./scripts/launch-all-docker.sh
```

- Dev: http://localhost:8080
- Nonprod: http://localhost:8081

### Resetting/Stopping
- To reset nonprod: `./scripts/reset-docker.sh nonprod`
- To stop: `docker compose -p wpqrnonprod down`

### Why This Matters
- Nonprod is always clean—no dev artifacts, no plugin preinstalled.
- Ensures release ZIPs are tested in a true production-like environment.
- Parallel workflow supports rapid iteration and robust QA. 
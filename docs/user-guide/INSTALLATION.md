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

## Migration Instructions (v1.1.0+)
If upgrading from an older version of QR Trackr:

1. **Deactivate and reactivate the plugin** in the WordPress admin to trigger the database migration. This will add a `qr_code` column and backfill codes for existing links.
2. Go to **Settings > Permalinks** and click **Save Changes** to flush rewrite rules.
3. Regenerate QR codes for existing links to ensure they use the new `/qr/<qr_code>` format.
4. Test by scanning a QR code or visiting a `/qr/<qr_code>` URL.

If you encounter 404 errors, ensure you have completed all steps above. 

## Troubleshooting: Package Manager

- **Yarn is the only supported package manager for this project.**
- Only `yarn.lock` should be present in the project root.
- If you see `package-lock.json` or `pnpm-lock.yaml`, delete them to avoid conflicts.
- If using VS Code, set `"npm.packageManager": "yarn"` in `.vscode/settings.json` to enforce Yarn usage. 
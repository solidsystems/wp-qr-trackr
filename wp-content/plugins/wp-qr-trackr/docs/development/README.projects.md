# GitHub Projects & MCP Automation Guide

This guide explains how to keep your repo's TODO lists and project plans in sync with GitHub Projects using MCP-driven automation.

---

## Prerequisites
- **GitHub CLI (`gh`)**: [Install instructions](https://cli.github.com/)
- **jq**: [Install instructions](https://stedolan.github.io/jq/download/)
- **Repo access**: You must have write access to the target repo and project
- **GitHub Project**: Create a project board (classic or beta) and note its number
- **Authentication**: Run `gh auth login` to authenticate with GitHub

---

## Usage: Syncing Tasks to GitHub Projects

The script `scripts/sync-todo-to-github-projects.sh` will:
- Parse all unchecked tasks from `TODO.md` and `PROJECT_PLAN_MCP_ENHANCEMENTS.md`
- Create new issues for each task (if not already present)
- Add each issue to the specified GitHub Project board
- Avoid duplicates (idempotent)

### Example
```sh
./scripts/sync-todo-to-github-projects.sh solidsystems/wp-qr-trackr 1
```
- `solidsystems/wp-qr-trackr`: The GitHub repo (org/user + repo)
- `1`: The project number (see your GitHub Projects URL)

### Output
- `[ADD]` lines for new tasks/issues added
- `[SKIP]` lines for tasks already present
- Summary of actions taken

---

## Troubleshooting
- **Missing tools:** Ensure `gh` and `jq` are installed and in your PATH
- **Authentication errors:** Run `gh auth login` and ensure you have repo/project write access
- **Project number:** Use the number from your GitHub Projects URL (e.g., `/projects/1`)
- **Script errors:** Check for typos in arguments and ensure you are in the repo root

---

## Best Practices: MCP-Driven Project Management
- Use markdown checklists in `TODO.md` and project plan files for all actionable items
- Run the sync script after updating TODOs or project plans to keep GitHub Projects up to date
- Use MCP servers (GitHub, Context7, DigitalOcean) to automate, monitor, and manage project progress
- Encourage contributors to check both the markdown TODOs and the GitHub Project board for the latest status

---

For more advanced automation, consider running the sync script in CI or as a scheduled job. 

> **Note:** The canonical project board for DevOps and migration tasks is the private GitHub Project **WP QR Trackr DevOps App**. All related tasks should be tracked and synced there. 
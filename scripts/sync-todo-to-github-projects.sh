#!/bin/bash
# sync-todo-to-github-projects.sh
# Syncs TODO.md and PROJECT_PLAN_MCP_ENHANCEMENTS.md tasks to a GitHub Project board
#
# Usage: ./scripts/sync-todo-to-github-projects.sh <org_or_user>/<repo> <project_number>
# Example: ./scripts/sync-todo-to-github-projects.sh solidsystems/wp-qr-trackr 1
#
# Requires: gh (GitHub CLI), jq

set -euo pipefail

REPO="${1:-solidsystems/wp-qr-trackr}"
PROJECT_NUMBER="${2:-1}"

# Check for required tools
tool_check() {
  for tool in "$@"; do
    if ! command -v "$tool" >/dev/null 2>&1; then
      echo "[ERROR] Required tool '$tool' not found. Please install it." >&2
      exit 1
    fi
  done
}
tool_check gh jq

# Extract all unchecked tasks from markdown checklists in a file
extract_tasks() {
  grep -E '^\s*- \[ \] ' "$1" | sed -E 's/^\s*- \[ \] //'
}

# Get all tasks from TODO.md and PROJECT_PLAN_MCP_ENHANCEMENTS.md
TASKS=$(extract_tasks TODO.md; extract_tasks PROJECT_PLAN_MCP_ENHANCEMENTS.md | sort | uniq)

# Get all existing issues in the repo (to avoid duplicates)
EXISTING_ISSUES=$(gh issue list -R "$REPO" --json title | jq -r '.[].title')

# Get all items in the project (to avoid duplicates)
EXISTING_PROJECT_ITEMS=$(gh project item-list "$PROJECT_NUMBER" -R "$REPO" --format json | jq -r '.[].title')

echo "[INFO] Syncing tasks to GitHub Project #$PROJECT_NUMBER in $REPO..."
NEW_COUNT=0
SKIP_COUNT=0
for TASK in $TASKS; do
  # Check if task already exists as an issue or project item
  if echo "$EXISTING_ISSUES" | grep -Fxq "$TASK" || echo "$EXISTING_PROJECT_ITEMS" | grep -Fxq "$TASK"; then
    echo "[SKIP] $TASK (already exists)"
    SKIP_COUNT=$((SKIP_COUNT+1))
    continue
  fi
  # Create a new issue for the task
  ISSUE_URL=$(gh issue create -R "$REPO" --title "$TASK" --body "Imported from TODO.md/PROJECT_PLAN_MCP_ENHANCEMENTS.md via automation." --label "mcp-todo" --json url | jq -r '.url')
  # Add the issue to the project
  gh project item-add "$PROJECT_NUMBER" -R "$REPO" --url "$ISSUE_URL"
  echo "[ADD] $TASK"
  NEW_COUNT=$((NEW_COUNT+1))
done

echo "[INFO] Sync complete: $NEW_COUNT new tasks added, $SKIP_COUNT skipped (already present)." 
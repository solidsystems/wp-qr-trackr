#!/bin/bash
# create-github-issues-from-project-plan.sh
#
# Creates GitHub issues for each actionable item in project plan files using the GitHub CLI (gh).
# - Idempotent: skips if an open issue with the same title exists.
# - Supports dry-run mode (no issues created, just print what would happen).
# - Optionally adds each created issue to a GitHub Project board if PROJECT_NUMBER is provided.
#
# Usage:
#   ./scripts/create-github-issues-from-project-plan.sh [--dry-run] [PROJECT_NUMBER]
#
# Requirements: gh CLI (https://cli.github.com/), jq
#
# Run from the repo root.

set -euo pipefail

REPO="solidsystems/wp-qr-trackr"  # Change if your repo slug is different
PLAN_FILES=(
  "docs/PROJECT_PLAN.md"
  "docs/PROJECT_PLAN_QR_ADMIN_CLEANUP.md"
  "TODO.md"
)
DRY_RUN="false"
PROJECT_NUMBER=""

# Parse args
for arg in "$@"; do
  if [[ "$arg" == "--dry-run" ]]; then
    DRY_RUN="true"
  elif [[ "$arg" =~ ^[0-9]+$ ]]; then
    PROJECT_NUMBER="$arg"
  fi
  # Ignore unknown args
done

if ! command -v gh >/dev/null 2>&1; then
  echo "[ERROR] gh CLI not found. Install from https://cli.github.com/" >&2
  exit 1
fi
if ! command -v jq >/dev/null 2>&1; then
  echo "[ERROR] jq not found. Install from https://stedolan.github.io/jq/download/" >&2
  exit 1
fi

# Extract actionable items (titles and descriptions) from a project plan file
parse_plan_items() {
  local file="$1"
  awk '/^- \[ \] \*\*/ { \
    title = gensub(/^- \[ \] \*\*(.*)\*\*.*/, "\\1", 1);
    getline; desc = "";
    while ($0 ~ /^  - \*\*Description:\*\*/) { \
      desc = gensub(/^  - \*\*Description:\*\* /, "", 1, $0); \
      getline;
    }
    print title "|" desc;
  }' "$file"
}

# Extract simple checklist items from TODO.md or similar
parse_todo_items() {
  local file="$1"
  awk '/^- \[ \] / { \
    line = gensub(/^- \[ \] /, "", 1, $0); \
    if (line ~ /\(.*\)/) { \
      # If line has (see ...), skip
      next;
    }
    print line "|";
  }' "$file"
}

# Also add the second section (Stability & Quality Project Plan) as simple lines
parse_quality_plan_items() {
  local file="$1"
  awk '/^## [0-9]+\./ {section=$0} /^[^-].*:.*/ {if (section && $0 !~ /^## /) {split($0, a, ":"); title=a[1]; desc=a[2]; gsub(/^ +| +$/, "", title); gsub(/^ +| +$/, "", desc); if (title!="" && desc!="") print title "|" desc}}' "$file"
}

ALL_ITEMS=()
for file in "${PLAN_FILES[@]}"; do
  if [[ -f "$file" ]]; then
    mapfile -t items < <(parse_plan_items "$file")
    ALL_ITEMS+=("${items[@]}")
    mapfile -t qitems < <(parse_quality_plan_items "$file")
    ALL_ITEMS+=("${qitems[@]}")
    # For TODO.md, also parse simple checklist items
    if [[ "$file" == *TODO.md ]]; then
      mapfile -t todoitems < <(parse_todo_items "$file")
      ALL_ITEMS+=("${todoitems[@]}")
    fi
  fi
  # else skip missing files
done

# Remove duplicates by title
declare -A SEEN
UNIQUE_ITEMS=()
for item in "${ALL_ITEMS[@]}"; do
  title="${item%%|*}"
  if [[ -n "$title" && -z "${SEEN[$title]:-}" ]]; then
    SEEN[$title]=1
    UNIQUE_ITEMS+=("$item")
  fi
done

echo "[INFO] Found ${#UNIQUE_ITEMS[@]} unique actionable items."

for item in "${UNIQUE_ITEMS[@]}"; do
  title="${item%%|*}"
  body="${item#*|}"
  # Check if an open issue with this title already exists
  if gh issue list --repo "$REPO" --state open --search "$title" | grep -q "^$title"; then
    echo "[SKIP] Issue already exists: $title"
    continue
  fi
  if [[ "$DRY_RUN" == "true" ]]; then
    echo "[DRY RUN] Would create issue: $title"
    continue
  fi
  echo "[CREATE] Creating issue: $title"
  issue_url=$(gh issue create --repo "$REPO" --title "$title" --body "$body" --json url --jq .url)
  if [[ -n "$PROJECT_NUMBER" ]]; then
    # Add the issue to the GitHub Project board
    issue_id=$(gh issue view "$issue_url" --json id --jq .id)
    gh project item-add "$PROJECT_NUMBER" --owner "solidsystems" --content-id "$issue_id"
    echo "[PROJECT] Added issue to project $PROJECT_NUMBER: $title"
  fi
done

echo "[DONE] Issue creation complete." 
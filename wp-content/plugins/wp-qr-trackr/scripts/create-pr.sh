#!/bin/bash
set -e

# Usage: scripts/create-pr.sh <branch> <title> <body-file>

if [ $# -ne 3 ]; then
  echo "Usage: $0 <branch> <title> <body-file>"
  exit 1
fi

BRANCH="$1"
TITLE="$2"
BODY_FILE="$3"

if [ ! -f "$BODY_FILE" ]; then
  echo "[ERROR] PR body file not found: $BODY_FILE"
  exit 1
fi

BODY_CONTENT=$(cat "$BODY_FILE")

gh pr create --title "$TITLE" --body "$BODY_CONTENT" --base main --head "$BRANCH"

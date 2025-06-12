#!/bin/bash
# pr-summary-comment.sh
# Usage: ./pr-summary-comment.sh <PR_NUMBER> [SUMMARY_TEXT]
# If GH CLI is available and PR_NUMBER is provided, posts the summary as a comment to the PR.
# Otherwise, outputs the formatted comment for manual use.
# If SUMMARY_TEXT is omitted, prompts for multi-line input (end with Ctrl+D).

set -e

PR_NUMBER="$1"
SUMMARY_TEXT="$2"

if [ -z "$PR_NUMBER" ]; then
  echo "Usage: $0 <PR_NUMBER> [SUMMARY_TEXT]"
  exit 1
fi

if [ -z "$SUMMARY_TEXT" ]; then
  echo "Enter your summary (end with Ctrl+D):"
  SUMMARY_TEXT=$(cat)
fi

# Escape newlines for gh CLI
ESCAPED_SUMMARY=$(echo "$SUMMARY_TEXT" | sed ':a;N;$!ba;s/\n/\\n/g')

if command -v gh >/dev/null 2>&1; then
  echo "Posting summary to PR #$PR_NUMBER via gh CLI..."
  gh pr comment "$PR_NUMBER" --body "$ESCAPED_SUMMARY"
  echo "Done."
else
  echo "---"
  echo "GH CLI not found. Copy and paste the following as your PR comment (or use in a gh CLI command):"
  echo
  echo "$ESCAPED_SUMMARY"
  echo
  echo "---"
  echo "Raw (for manual paste):"
  echo "$SUMMARY_TEXT"
  echo "---"
fi 
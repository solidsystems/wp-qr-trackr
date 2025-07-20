#!/bin/bash

# Script to create a feature branch and set up proper development workflow
# Usage: ./scripts/create-feature-branch.sh <branch-name> <description>

set -e

if [ $# -lt 2 ]; then
    echo "Usage: $0 <branch-name> <description>"
    echo "Example: $0 fix/phpcs-issues 'Fix remaining PHPCS errors'"
    exit 1
fi

BRANCH_NAME="$1"
DESCRIPTION="$2"

echo "=== Creating Feature Branch ==="
echo "Branch: $BRANCH_NAME"
echo "Description: $DESCRIPTION"
echo ""

# Ensure we're on main and up to date
echo "1. Switching to main branch..."
git checkout main
git pull origin main

# Create and switch to new branch
echo "2. Creating feature branch..."
git checkout -b "$BRANCH_NAME"

echo ""
echo "✅ Feature branch '$BRANCH_NAME' created successfully!"
echo ""
echo "Next steps:"
echo "1. Make your changes"
echo "2. Run: make validate (to check code quality)"
echo "3. Commit changes: git add . && git commit -m 'Your commit message'"
echo "4. Push branch: git push origin $BRANCH_NAME"
echo "5. Create PR: gh pr create --title 'Your PR title' --body 'Your PR description' --base main --head $BRANCH_NAME"
echo ""
echo "Branch description: $DESCRIPTION" 
#!/bin/bash
# Script to add project plan tasks as draft issues to a GitHub Project board using gh CLI
# Usage: ./create-github-project-tasks.sh [OWNER] [PROJECT_NUMBER]

set -e

OWNER="$1"
PROJECT_NUMBER="$2"

if [ -z "$OWNER" ]; then
  read -p "Enter your GitHub username or org: " OWNER
fi
if [ -z "$PROJECT_NUMBER" ]; then
  read -p "Enter your GitHub Project number (find in project URL): " PROJECT_NUMBER
fi

# Define tasks as associative array: [title]=body
# (You can expand this list as needed)
declare -A TASKS
TASKS["Cross-Platform Setup Script"]="Extend or create setup scripts for Linux and Windows, in addition to macOS. Deliverable: setup-linux.sh, setup-windows.ps1, or a unified script."
TASKS["Automated Dependency Installation"]="Script installs Yarn, Composer, PHP dependencies, and copies .env.example to .env if missing. Deliverable: Update setup scripts."
TASKS["Pre-commit Hooks"]="Use Husky/lint-staged to run linters and tests before commit. Deliverable: .husky/ config, lint-staged.config.js."
TASKS["CI/CD Integration"]="Set up GitHub Actions to run PHPUnit, JS tests, and linting on PRs. Deliverable: .github/workflows/ci.yml."
TASKS["Code Coverage Reporting"]="Integrate with Codecov or similar for automated coverage reports. Deliverable: Codecov config, badge in README."
TASKS["Automated Changelog Generation"]="Use a tool or GitHub Action to generate changelogs from PRs/commits. Deliverable: Changelog script or GitHub Action."
TASKS["Doc Linting"]="Lint PRs for missing or outdated documentation. Deliverable: Doc linter config or GitHub Action."
TASKS["Issue/PR Templates"]="Provide templates for issues and PRs to encourage linking to CONTRIBUTING.md tasks. Deliverable: .github/ISSUE_TEMPLATE/, .github/pull_request_template.md."
TASKS["Label Automation"]="Use bots to label PRs/issues based on keywords or file changes. Deliverable: GitHub Action or bot config."
TASKS["Welcome Bot"]="Welcome new contributors and point them to onboarding docs. Deliverable: GitHub Action or bot config."
TASKS["First-time Setup Checks"]="Script checks for required tools and prompts to install if missing. Deliverable: Update setup scripts."
TASKS["Scaffold Generator"]="CLI or script to scaffold a new plugin or pro extension based on this template. Deliverable: create-plugin.sh or similar."

for TITLE in "${!TASKS[@]}"; do
  BODY=${TASKS[$TITLE]}
  echo "Creating draft issue: $TITLE"
  ISSUE_URL=$(gh issue create --title "$TITLE" --body "$BODY" --repo "$OWNER/wp-qr-trackr" --assignee "$OWNER" --label "automation,project-plan" --draft --json url --jq .url)
  echo "Adding issue to project $PROJECT_NUMBER: $ISSUE_URL"
  gh project item-add $PROJECT_NUMBER --owner "$OWNER" --url "$ISSUE_URL"
done

echo "All tasks have been added as draft issues and linked to the project!" 
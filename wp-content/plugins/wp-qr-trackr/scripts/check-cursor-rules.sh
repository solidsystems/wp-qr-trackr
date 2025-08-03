#!/bin/bash
set -e

# Enforce Yarn-only package management
if grep -rE '(^|[^a-zA-Z0-9])(npm |npx |pnpm )' . --exclude-dir={.git,node_modules,vendor} --include='*.sh' --include='*.js' --include='*.ts' --include='*.json' --include='Dockerfile*' --include='Makefile' --include='*.yml' --include='*.yaml' | grep -v 'corepack enable'; then
  echo "\nERROR: Detected forbidden package manager usage (npm, npx, pnpm). Only Yarn is allowed. See .cursorrules." >&2
  exit 1
fi

# Enforce Yarn usage in package.json
if ! grep -q 'yarn' package.json; then
  echo "\nERROR: package.json must reference Yarn as the package manager. See .cursorrules." >&2
  exit 1
fi

# Placeholder: Add more .cursorrules checks here as needed

# Required files for Cursor project rules
REQUIRED_FILES=(".cursorrules" "config/editor/.editorconfig" "Makefile" "config/ci/lefthook.yml" ".github/PULL_REQUEST_TEMPLATE.md")

for file in "${REQUIRED_FILES[@]}"; do
  if [ ! -f "$file" ]; then
    echo "[ERROR] Required config file missing: $file"
    exit 1
  fi
  echo "[OK] Found $file"
  # Optionally, add content checks here
  # e.g., grep for key sections in .cursorrules
  if [ "$file" = ".cursorrules" ]; then
    grep -q 'PHPCS & WordPress Coding Standards Guardrails' .cursorrules || {
      echo "[ERROR] .cursorrules missing guardrails section."; exit 1; }
  fi
  # Check .editorconfig for PHP tab rule
  if [ "$file" = "config/editor/.editorconfig" ]; then
    grep -q 'indent_style = tab' config/editor/.editorconfig || {
      echo "[ERROR] .editorconfig missing PHP tab rule."; exit 1; }
  fi
  # Add more content checks as needed
  echo "[OK] $file content looks good."

done

echo "All required config files are present and valid."

exit 0

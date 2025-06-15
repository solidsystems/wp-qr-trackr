#!/bin/bash
# scripts/update-todo-index.sh
# Scans the codebase for TODOs and markdown checklists, groups by feature/module, and updates TODO.md.

set -e

OUTFILE="TODO.md"
DATE=$(date)

echo "# Project TODO Index" > "$OUTFILE"
echo "" >> "$OUTFILE"
echo "Last updated: $DATE" >> "$OUTFILE"
echo "" >> "$OUTFILE"

echo "## Code TODOs" >> "$OUTFILE"
# Group by top-level directory (feature/module)
find . \( -name node_modules -o -name vendor -o -name .git \) -prune -false -o -type f | while read -r file; do
  if grep -q 'TODO' "$file"; then
    module=$(echo "$file" | cut -d'/' -f2)
    grep -n 'TODO' "$file" | while read -r line; do
      lineno=$(echo $line | cut -d: -f1)
      todo=$(echo $line | cut -d: -f2-)
      echo "- [ ] $todo (_$file:$lineno, module: $module_)" >> "$OUTFILE"
    done
  fi
done

echo "" >> "$OUTFILE"
echo "## Markdown Checklists" >> "$OUTFILE"
# Find all markdown checklists (excluding TODO.md itself)
grep -r '\[ \]' . --include=*.md --exclude=TODO.md | while read -r line; do
  file=$(echo $line | cut -d: -f1)
  item=$(echo $line | cut -d: -f2-)
  section=$(grep -B 1 "$item" "$file" | head -1 | sed 's/^#* *//')
  echo "- [ ] $item (_$file, section: $section_)" >> "$OUTFILE"
done

echo "" >> "$OUTFILE"
echo "## Manual Cross-References" >> "$OUTFILE"
echo "- [ ] See CONTRIBUTING.md, PROJECT_PLAN.md, and PROJECT_PLAN_QR_ADMIN_CLEANUP.md for additional context and task details." >> "$OUTFILE"
echo "" >> "$OUTFILE"
echo "_See original files for full context and details. To claim a TODO, add your name in the original file or in this index._" >> "$OUTFILE" 
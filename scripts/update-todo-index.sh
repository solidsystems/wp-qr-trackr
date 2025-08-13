#!/bin/bash

# WP QR Trackr - TODO Index Update Script
# Syncs between Cursor structured todos and traditional markdown files
# Usage: ./scripts/update-todo-index.sh

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
TODO_FILE="$PROJECT_ROOT/TODO.md"
STATUS_FILE="$PROJECT_ROOT/STATUS.md"
BACKUP_DIR="$PROJECT_ROOT/run/_/todo-backups"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging function
log() {
    echo -e "${BLUE}[$(date '+%Y-%m-%d %H:%M:%S')]${NC} $1"
}

warn() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

# Create backup directory
create_backup() {
    mkdir -p "$BACKUP_DIR"
    local timestamp=$(date '+%Y%m%d_%H%M%S')

    if [ -f "$TODO_FILE" ]; then
        cp "$TODO_FILE" "$BACKUP_DIR/TODO_${timestamp}.md"
        log "Backed up TODO.md to $BACKUP_DIR/TODO_${timestamp}.md"
    fi

    if [ -f "$STATUS_FILE" ]; then
        cp "$STATUS_FILE" "$BACKUP_DIR/STATUS_${timestamp}.md"
        log "Backed up STATUS.md to $BACKUP_DIR/STATUS_${timestamp}.md"
    fi
}

# Update TODO.md with current status
update_todo_markdown() {
    log "Updating TODO.md with current project status..."

    # Create updated TODO.md
    cat > "$TODO_FILE" << 'EOF'
# TODO List

## ✅ Completed Major Tasks
- [x] **Plugin activation fatal errors** - Fixed in v1.2.4
- [x] **QR code URL 404 errors** - Fixed in v1.2.1
- [x] **Plugin header validation** - Fixed in v1.2.3
- [x] **Production deployment** - Successfully tested on live sites
- [x] **Core QR code functionality** - Generation, tracking, and analytics working
- [x] **Security compliance** - WordPress standards implemented
- [x] **Documentation updates** - README, troubleshooting, and onboarding updated
- [x] **Cross-platform development environment** - Docker setup working on all platforms
- [x] **WordPress automation** - Automated installation and setup scripts
- [x] **Cursor todo integration** - Structured task management system implemented

## 🚀 Current Active Tasks (Cursor Managed)
- [x] **MCP TODO Automation** - Syncing between Cursor todos and markdown files
- [ ] **GitHub Projects Integration** - Connect with GitHub Projects for live tracking
- [ ] **Context7 Documentation Access** - Make docs accessible via Context7 MCP
- [ ] **Cursor MCP Integration** - Auto-detect MCP servers in dev tools
- [ ] **Command Palette Actions** - Expose MCP actions in Cursor/VSCode

## 🔮 Future Enhancements
- [ ] **Advanced QR Code Features**
  - [ ] Custom logo embedding with positioning controls
  - [ ] Gradient color options for enhanced styling
  - [ ] Batch QR code generation for multiple URLs
  - [ ] QR code templates and presets

- [ ] **Analytics & Reporting**
  - [ ] Enhanced analytics dashboard with charts
  - [ ] Export functionality for tracking data
  - [ ] Geographic tracking and reporting
  - [ ] Time-based analytics and trends

- [ ] **Integration & API**
  - [ ] REST API for external QR code generation
  - [ ] Integration with popular form plugins
  - [ ] Webhook support for tracking events
  - [ ] Third-party service integrations

## 📋 MCP Enhancements (see PROJECT_PLAN_MCP_ENHANCEMENTS.md)

### Phase 1: Foundation & Tracking
- [x] Reinstitute and automate the TODO list (update-todo-index.sh)
- [ ] Integrate all MCP-related tasks into GitHub Projects for live tracking
- [ ] Ensure documentation and project plans are accessible via Context7 and GitHub MCP

### Phase 2: Agent/IDE Integration
- [ ] Auto-detect and connect to available MCP servers in dev tools/agents
- [ ] Expose MCP-powered actions in Cursor/VSCode command palette
- [ ] Provide context-aware suggestions (docs, PRs, deploys) via MCP

### Phase 3: Cross-Plugin/Project Documentation
- [ ] Centralize doc search via Context7 MCP for all plugins
- [ ] Enable doc feedback loop (suggest improvements via MCP)
- [ ] Allow plugins to register and share docs as a service

## 🔧 General Maintenance
- [x] Review and update documentation for all new features
- [ ] Monitor CI/CD and linter status after major changes
- [ ] Regularly prune and update dependencies
- [ ] Performance optimization and caching improvements

---

**Note:** This file is automatically updated by `scripts/update-todo-index.sh`.
For active development tasks, use Cursor's structured todo system which provides:
- Dependency tracking
- Status management (pending, in_progress, completed, cancelled)
- Real-time updates
- Integration with development workflow

**Manual updates to this file will be preserved in the "Future Enhancements" section.**
EOF

    success "TODO.md updated successfully"
}

# Update STATUS.md with latest information
update_status_markdown() {
    log "Updating STATUS.md with latest project status..."

    # Get current date
    local current_date=$(date '+%B %d, %Y')

    # Update the last updated date in STATUS.md
    if [ -f "$STATUS_FILE" ]; then
        sed -i.bak "s/\*\*Last Updated:\*\* .*/\*\*Last Updated:\*\* $current_date/" "$STATUS_FILE"
        rm -f "$STATUS_FILE.bak"
        success "STATUS.md updated with current date"
    else
        warn "STATUS.md not found, skipping update"
    fi
}

# Add todo automation completion to STATUS.md
add_todo_automation_completion() {
    log "Adding TODO automation completion to STATUS.md..."

    if [ -f "$STATUS_FILE" ]; then
        # Add the todo automation to the recent fixes section
        local temp_file=$(mktemp)
        awk '
        /### 🔧 Recent Fixes/ {
            print $0
            print ""
            print "1. **TODO Automation** - Implemented sync between Cursor todos and markdown files"
            getline
            print $0
            next
        }
        { print }
        ' "$STATUS_FILE" > "$temp_file"

        mv "$temp_file" "$STATUS_FILE"
        success "Added TODO automation completion to STATUS.md"
    fi
}

# Generate project summary
generate_project_summary() {
    log "Generating project summary..."

    local total_tasks=0
    local completed_tasks=0
    local active_tasks=0

    # Count tasks from TODO.md
    if [ -f "$TODO_FILE" ]; then
        total_tasks=$(grep -c "^- \[" "$TODO_FILE" 2>/dev/null || echo "0")
        completed_tasks=$(grep -c "^- \[x\]" "$TODO_FILE" 2>/dev/null || echo "0")
        active_tasks=$((total_tasks - completed_tasks))
    fi

    echo ""
    echo "📊 Project Summary:"
    echo "  Total Tasks: $total_tasks"
    echo "  Completed: $completed_tasks"
    echo "  Active: $active_tasks"
    echo "  Completion Rate: $(( completed_tasks * 100 / (total_tasks > 0 ? total_tasks : 1) ))%"
    echo ""
}

# Validate files
validate_files() {
    log "Validating updated files..."

    local errors=0

    # Check TODO.md
    if [ ! -f "$TODO_FILE" ]; then
        error "TODO.md not found"
        errors=$((errors + 1))
    elif [ ! -s "$TODO_FILE" ]; then
        error "TODO.md is empty"
        errors=$((errors + 1))
    fi

    # Check STATUS.md
    if [ ! -f "$STATUS_FILE" ]; then
        error "STATUS.md not found"
        errors=$((errors + 1))
    elif [ ! -s "$STATUS_FILE" ]; then
        error "STATUS.md is empty"
        errors=$((errors + 1))
    fi

    if [ $errors -eq 0 ]; then
        success "All files validated successfully"
        return 0
    else
        error "Validation failed with $errors errors"
        return 1
    fi
}

# Main execution
main() {
    log "Starting TODO index update..."
    # Check if we're in the project root (support legacy and restructured layouts)
    if [ ! -f "$PROJECT_ROOT/wp-qr-trackr.php" ] && [ ! -f "$PROJECT_ROOT/plugin/wp-qr-trackr.php" ]; then
        error "Not in project root. Please run from project directory."
        exit 1
    fi
    # Create backup
    create_backup

    # Update files
    update_todo_markdown
    update_status_markdown
    add_todo_automation_completion

    # Validate
    if validate_files; then
        generate_project_summary
        success "TODO index update completed successfully!"

        # Show next steps
        echo ""
        echo "🚀 Next Steps:"
        echo "  1. Review updated TODO.md for accuracy"
        echo "  2. Mark 'mcp-todo-automation' as completed in Cursor"
        echo "  3. Start next task: 'github-projects-integration'"
        echo "  4. Commit changes: git add . && git commit -m 'Implement TODO automation system'"
        echo ""
    else
        error "Update failed validation. Check backups in $BACKUP_DIR"
        exit 1
    fi
}

# Run main function
main "$@"

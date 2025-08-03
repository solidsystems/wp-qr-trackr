#!/bin/bash

# WP QR Trackr - GitHub Projects Integration Setup
# Integrates MCP-related tasks with GitHub Projects for live tracking
# Usage: ./scripts/setup-github-projects.sh

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"

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

# Check if GitHub CLI is installed
check_github_cli() {
    if ! command -v gh &> /dev/null; then
        error "GitHub CLI (gh) is not installed. Please install it first:"
        echo "  macOS: brew install gh"
        echo "  Linux: https://github.com/cli/cli/blob/trunk/docs/install_linux.md"
        echo "  Windows: https://github.com/cli/cli/releases"
        exit 1
    fi
    
    # Check if user is authenticated
    if ! gh auth status &> /dev/null; then
        error "Not authenticated with GitHub. Please run: gh auth login"
        exit 1
    fi
    
    success "GitHub CLI is installed and authenticated"
}

# Get repository information
get_repo_info() {
    local repo_url=$(git config --get remote.origin.url 2>/dev/null || echo "")
    
    if [ -z "$repo_url" ]; then
        error "Not in a git repository or no origin remote found"
        exit 1
    fi
    
    # Extract owner and repo name
    if [[ "$repo_url" =~ github\.com[:/]([^/]+)/([^/]+)(\.git)?$ ]]; then
        REPO_OWNER="${BASH_REMATCH[1]}"
        REPO_NAME="${BASH_REMATCH[2]}"
        REPO_NAME="${REPO_NAME%.git}"  # Remove .git suffix if present
    else
        error "Could not parse GitHub repository URL: $repo_url"
        exit 1
    fi
    
    log "Repository: $REPO_OWNER/$REPO_NAME"
}

# Create GitHub Project
create_github_project() {
    log "Creating GitHub Project for WP QR Trackr..."
    
    # Check if project already exists
    local existing_project=$(gh project list --owner "$REPO_OWNER" --format json | jq -r '.projects[] | select(.title == "WP QR Trackr - MCP Development") | .number' 2>/dev/null || echo "")
    
    if [ -n "$existing_project" ]; then
        PROJECT_NUMBER="$existing_project"
        warn "Project 'WP QR Trackr - MCP Development' already exists (Project #$PROJECT_NUMBER)"
    else
        # Create new project
        local project_output=$(gh project create --owner "$REPO_OWNER" --title "WP QR Trackr - MCP Development" --body "Tracks MCP enhancements and plugin development tasks" --format json)
        PROJECT_NUMBER=$(echo "$project_output" | jq -r '.number')
        success "Created GitHub Project #$PROJECT_NUMBER"
    fi
}

# Setup project fields
setup_project_fields() {
    log "Setting up project fields..."
    
    # Add Priority field
    gh project field-create "$PROJECT_NUMBER" --owner "$REPO_OWNER" --name "Priority" --type "single_select" --option "High" --option "Medium" --option "Low" || warn "Priority field may already exist"
    
    # Add Phase field
    gh project field-create "$PROJECT_NUMBER" --owner "$REPO_OWNER" --name "Phase" --type "single_select" --option "Phase 1: Foundation" --option "Phase 2: Integration" --option "Phase 3: Documentation" --option "Phase 4: DevOps" --option "Phase 5: GitHub Automation" || warn "Phase field may already exist"
    
    # Add Effort field
    gh project field-create "$PROJECT_NUMBER" --owner "$REPO_OWNER" --name "Effort" --type "single_select" --option "Small" --option "Medium" --option "Large" --option "Extra Large" || warn "Effort field may already exist"
    
    success "Project fields configured"
}

# Create project items from TODO list
create_project_items() {
    log "Creating project items from TODO list..."
    
    # Define tasks with metadata
    declare -A tasks=(
        ["MCP TODO Automation"]="completed|Phase 1: Foundation|High|Small|Implemented sync between Cursor todos and markdown files"
        ["GitHub Projects Integration"]="in_progress|Phase 1: Foundation|High|Medium|Connect with GitHub Projects for live tracking"
        ["Context7 Documentation Access"]="todo|Phase 1: Foundation|High|Medium|Make docs accessible via Context7 MCP"
        ["Cursor MCP Integration"]="todo|Phase 2: Integration|High|Medium|Auto-detect MCP servers in dev tools"
        ["Command Palette Actions"]="todo|Phase 2: Integration|Medium|Medium|Expose MCP actions in Cursor/VSCode"
        ["Advanced QR Features"]="todo|Phase 3: Documentation|Medium|Large|Custom logos, gradients, batch generation"
        ["Analytics Dashboard"]="todo|Phase 3: Documentation|Medium|Large|Enhanced charts and export functionality"
        ["REST API Development"]="todo|Phase 4: DevOps|Medium|Large|External QR code generation API"
    )
    
    for task_name in "${!tasks[@]}"; do
        IFS='|' read -r status phase priority effort description <<< "${tasks[$task_name]}"
        
        # Create project item
        log "Creating item: $task_name"
        
        # Convert status to GitHub status
        case "$status" in
            "completed") gh_status="Done" ;;
            "in_progress") gh_status="In Progress" ;;
            "todo") gh_status="Todo" ;;
            *) gh_status="Todo" ;;
        esac
        
        # Create the item
        local item_output=$(gh project item-create "$PROJECT_NUMBER" --owner "$REPO_OWNER" --title "$task_name" --body "$description" --format json)
        local item_id=$(echo "$item_output" | jq -r '.id')
        
        # Set status
        gh project item-edit --id "$item_id" --field-name "Status" --field-value "$gh_status" || warn "Could not set status for $task_name"
        
        # Set priority
        gh project item-edit --id "$item_id" --field-name "Priority" --field-value "$priority" || warn "Could not set priority for $task_name"
        
        # Set phase
        gh project item-edit --id "$item_id" --field-name "Phase" --field-value "$phase" || warn "Could not set phase for $task_name"
        
        # Set effort
        gh project item-edit --id "$item_id" --field-name "Effort" --field-value "$effort" || warn "Could not set effort for $task_name"
        
        success "Created: $task_name"
    done
}

# Create sync documentation
create_sync_documentation() {
    log "Creating sync documentation..."
    
    cat > "$PROJECT_ROOT/docs/GITHUB_PROJECTS_SYNC.md" << 'EOF'
# GitHub Projects Integration

This document describes the integration between Cursor structured todos and GitHub Projects for live tracking.

## Project Setup

**Project Name:** WP QR Trackr - MCP Development  
**Project URL:** https://github.com/users/YOUR_USERNAME/projects/PROJECT_NUMBER

## Field Mapping

| Field | Values | Description |
|-------|--------|-------------|
| Status | Todo, In Progress, Done | Current task status |
| Priority | High, Medium, Low | Task priority level |
| Phase | Phase 1-5 | Development phase |
| Effort | Small, Medium, Large, XL | Estimated effort |

## Sync Workflow

### 1. Cursor → GitHub Projects
- Use `scripts/sync-cursor-to-github.sh` to sync task status
- Automatically updates GitHub Projects when Cursor todos change

### 2. GitHub Projects → Cursor
- Use `scripts/sync-github-to-cursor.sh` to pull updates
- Updates Cursor todos based on GitHub Projects changes

### 3. Bidirectional Sync
- Use `scripts/sync-todos.sh` for full bidirectional sync
- Runs both directions with conflict resolution

## Usage

```bash
# Initial setup
./scripts/setup-github-projects.sh

# Sync Cursor todos to GitHub Projects
./scripts/sync-cursor-to-github.sh

# Sync GitHub Projects to Cursor todos
./scripts/sync-github-to-cursor.sh

# Full bidirectional sync
./scripts/sync-todos.sh
```

## Manual Updates

When manually updating tasks in GitHub Projects:
1. Change the Status field to reflect current state
2. Update Priority if task importance changes
3. Move to appropriate Phase if needed
4. Adjust Effort estimate if scope changes
5. Run sync script to update Cursor todos

## Automation

The sync scripts can be run automatically:
- Via GitHub Actions on push/PR events
- Via cron jobs for scheduled sync
- Via git hooks for local development

## Troubleshooting

### Common Issues

1. **Authentication Error**
   - Run `gh auth login` to authenticate
   - Ensure GitHub CLI has proper permissions

2. **Project Not Found**
   - Verify project exists and is accessible
   - Check PROJECT_NUMBER in scripts

3. **Field Mapping Errors**
   - Ensure all custom fields are created
   - Check field names match exactly

### Debug Mode

Enable debug mode for detailed logging:
```bash
DEBUG=1 ./scripts/sync-todos.sh
```

## Best Practices

1. **Single Source of Truth**
   - Use Cursor todos for active development
   - Use GitHub Projects for planning and tracking

2. **Regular Sync**
   - Run sync scripts at least daily
   - Sync before/after major changes

3. **Conflict Resolution**
   - Cursor todos take precedence for status
   - GitHub Projects takes precedence for planning

4. **Documentation**
   - Keep this document updated
   - Document any custom workflows
EOF

    success "Created GitHub Projects sync documentation"
}

# Setup automation hooks
setup_automation() {
    log "Setting up automation hooks..."
    
    # Create git hook for automatic sync
    cat > "$PROJECT_ROOT/.git/hooks/post-commit" << 'EOF'
#!/bin/bash
# Auto-sync todos after commit
if [ -f scripts/sync-cursor-to-github.sh ]; then
    ./scripts/sync-cursor-to-github.sh --quiet || true
fi
EOF

    chmod +x "$PROJECT_ROOT/.git/hooks/post-commit"
    success "Created post-commit hook for automatic sync"
}

# Main execution
main() {
    log "Starting GitHub Projects integration setup..."
    
    # Check prerequisites
    check_github_cli
    get_repo_info
    
    # Setup project
    create_github_project
    setup_project_fields
    create_project_items
    
    # Create documentation and automation
    create_sync_documentation
    setup_automation
    
    success "GitHub Projects integration setup completed!"
    
    echo ""
    echo "🚀 Next Steps:"
    echo "  1. Visit your GitHub Project: https://github.com/$REPO_OWNER/$REPO_NAME/projects/$PROJECT_NUMBER"
    echo "  2. Review and customize the project layout"
    echo "  3. Test sync with: ./scripts/sync-todos.sh"
    echo "  4. Mark 'github-projects-integration' as completed in Cursor"
    echo ""
}

# Run main function
main "$@" 
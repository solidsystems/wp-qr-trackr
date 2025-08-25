# Cursor Plugin Development Guide

A comprehensive guide for Cursor users on how to use the **WP QR Trackr** project as a foundation for building your own WordPress plugins.

> 💡 **Quick Start?** Check out the [**Cursor Quick Reference**](CURSOR_QUICK_REFERENCE.md) for essential prompts and commands!

## Table of Contents
1. [Quick Start for Cursor Users](#quick-start-for-cursor-users)
2. [Creating Your Plugin Plan](#creating-your-plugin-plan)
3. [Design Considerations Workflow](#design-considerations-workflow)
4. [Development Workflow with TODO System](#development-workflow-with-todo-system)
5. [Advanced Cursor Workflows](#advanced-cursor-workflows)
6. [Common Plugin Development Patterns](#common-plugin-development-patterns)
7. [Best Practices for Cursor AI Collaboration](#best-practices-for-cursor-ai-collaboration)

---

## Quick Start for Cursor Users

### Prerequisites
- **Cursor IDE** installed and configured
- **Docker Desktop** running
- **Basic familiarity** with WordPress plugin development
- **Git** configured for your development workflow

### Initial Setup
1. **Fork or clone** the wp-qr-trackr repository
2. **Open in Cursor** and let it index the codebase
3. **Run the setup script** to get the development environment ready:
   ```bash
   # For development environment
   ./scripts/setup-wordpress.sh dev

   # For testing environment
   ./scripts/setup-wordpress.sh nonprod
   ```
4. **Access your development environment**:
   - Dev environment: http://localhost:8080
   - Nonprod environment: http://localhost:8081

### **Available User Accounts**

Both environments are configured with two user accounts for testing different permission levels:

**Administrator Account** (Full Access):
- **Username**: `trackr`
- **Password**: `trackr`
- **Role**: Administrator
- **Capabilities**: Full plugin access, settings management, system configuration

**Editor Account** (QR Code Management):
- **Username**: `editor`
- **Password**: `editor`
- **Role**: Editor
- **Capabilities**: Create, edit, and delete QR codes (limited to `edit_posts` capability)

> 💡 **Testing Tip**: Use the editor account to test QR code management functionality with restricted permissions, ensuring your plugin works correctly for users with different role levels.

> ⚠️ **Critical**: The setup script automatically applies essential configurations including:
> - Directory permissions for plugin updates
> - Pretty permalinks for QR code redirects
> - Rewrite rules flushing
> - Plugin activation verification
>
> These configurations are **mandatory** for proper plugin functionality.

---

## Creating Your Plugin Plan

### Step 1: Define Your Plugin Requirements

**Recommended Cursor Prompt:**
```
I want to use the wp-qr-trackr repository as a foundation to build a WordPress plugin that does:
- [Feature X]: [Detailed description]
- [Feature Y]: [Detailed description]
- [Feature Z]: [Detailed description]

Please create a comprehensive project plan that includes:
1. Plugin identity changes needed (name, text domain, constants, etc.)
2. Database schema modifications required
3. Admin interface adaptations
4. Custom functionality implementation
5. Testing strategy
6. Deployment considerations

Use the existing TODO automation system to create structured tasks with dependencies.
```

**Example Plugin Ideas:**
- **Event Management Plugin**: Create, manage, and track events with RSVP functionality
- **Document Library**: Secure document storage and sharing with access controls
- **Customer Review System**: Product/service reviews with moderation and analytics
- **Booking System**: Appointment scheduling with calendar integration
- **Member Directory**: User profiles and directory with search functionality

### Step 2: Request Detailed Analysis

**Follow-up Cursor Prompt:**
```
Based on the plugin plan you created, please:
1. Analyze the existing wp-qr-trackr codebase architecture
2. Identify which components can be reused vs. need replacement
3. Create a migration roadmap from QR tracking to [your plugin functionality]
4. Highlight potential challenges and design decisions that need my input
5. Set up the structured TODO system with proper task dependencies

Please ask me specific questions about:
- User interface preferences
- Database design choices
- Integration requirements
- Security considerations
- Performance requirements
```

---

## Design Considerations Workflow

### Request Design Input Template

**Cursor Prompt for Design Decisions:**
```
I need your input on several design decisions for the [plugin name] plugin:

**Database Design:**
- Should we use custom tables or post meta for [specific data]?
- What relationships need to be established between entities?
- How should we handle data migrations and versioning?

**User Interface:**
- Should we use the existing modal system or create new interfaces?
- What admin menu structure makes sense for [your features]?
- How should we handle user permissions and role management?

**Integration Points:**
- Do you need integration with existing WordPress features (users, posts, etc.)?
- Should we maintain the existing AJAX patterns or use REST API?
- Are there any third-party services that need integration?

**Performance Considerations:**
- What are your expected data volumes and user loads?
- Should we implement caching for [specific operations]?
- Are there any real-time features that need WebSocket or similar?

Please provide your preferences and requirements for each area.
```

### Architecture Review Process

**Cursor Prompt for Architecture Validation:**
```
Before we start implementation, please review the planned architecture:

1. **Analyze the existing modular structure** in the `includes/` directory
2. **Recommend which modules to keep, modify, or replace** for [your plugin]
3. **Identify any new modules** we need to create
4. **Review the database schema changes** and suggest optimizations
5. **Evaluate the admin interface approach** and recommend improvements

Create a detailed architecture document that includes:
- Module responsibility matrix
- Database entity-relationship diagram
- User flow diagrams for main features
- Security considerations and implementation plan
- Performance optimization strategy

Add these architectural tasks to our TODO system with proper dependencies.
```

---

## Development Workflow with TODO System

### Initialize Your Plugin Development

**Cursor Prompt for Project Setup:**
```
Let's set up the development workflow for [your plugin name]:

1. **Create a structured TODO list** for the complete plugin development using the existing automation system
2. **Organize tasks by priority and dependencies** (setup, core features, testing, deployment)
3. **Set up GitHub Projects integration** for the new plugin repository
4. **Configure the development environment** with plugin-specific settings
5. **Create initial documentation structure** based on the wp-qr-trackr template

Please use the todo_write tool to create comprehensive tasks and mark the first task as in_progress.
```

### Development Iteration Workflow

**Daily Development Prompt:**
```
I'm ready to work on the next task in our plugin development. Please:

1. **Review the current TODO status** and identify the next priority task
2. **Mark the appropriate task as in_progress**
3. **Provide detailed implementation steps** for the current task
4. **Identify any dependencies** that need to be completed first
5. **Set up proper testing** for the feature being implemented

If there are any blockers or design decisions needed, please ask specific questions.
```

### Task Completion Workflow

**After Completing Work:**
```
I've completed work on [specific task]. Please:

1. **Review the implementation** for code quality and WordPress best practices
2. **Run any necessary tests** to validate the functionality
3. **Mark the task as completed** in the TODO system
4. **Update documentation** if needed
5. **Identify the next task** to work on
6. **Run the TODO automation script** to sync all systems

Please provide a summary of what was accomplished and what's next.
```

---

## Advanced Cursor Workflows

### Automated Plugin Scaffolding

**Cursor Prompt for Plugin Transformation:**
```
I want to transform the wp-qr-trackr codebase into a [plugin name] plugin. Please:

1. **Create a transformation script** that renames all files, classes, and constants
2. **Update all text domains** and translation strings
3. **Modify the main plugin file** with new plugin headers
4. **Update database table names** and related queries
5. **Modify admin menu structures** and page titles
6. **Update all documentation** to reflect the new plugin

Generate the script and ask for confirmation before executing the transformation.
```

### Feature Implementation Templates

**Cursor Prompt for New Feature Development:**
```
I need to implement [specific feature] for my plugin. Please:

1. **Analyze the existing codebase** for similar patterns
2. **Create a modular implementation** following the project structure
3. **Include proper WordPress hooks** and filters for extensibility
4. **Implement security measures** (nonces, sanitization, validation)
5. **Add comprehensive error handling** and logging
6. **Create corresponding admin interfaces** using the existing modal system
7. **Write unit tests** for the new functionality
8. **Update documentation** and user guides

Use the existing modules as templates and maintain consistency with the codebase.
```

### Testing and Quality Assurance

**Cursor Prompt for Comprehensive Testing:**
```
Please help me ensure the plugin meets production quality standards:

1. **Run PHPCS compliance checks** and fix any issues
2. **Execute the PHPUnit test suite** and verify all tests pass
3. **Test the plugin** in both dev and nonprod environments
4. **Verify database migrations** work correctly
5. **Check security implementations** (nonces, sanitization, permissions)
6. **Validate performance** with caching and optimization
7. **Test cross-browser compatibility** for admin interfaces
8. **Generate test coverage reports** and identify gaps

Create a testing checklist and update the TODO system with any issues found.
```

---

## Common Plugin Development Patterns

### Database-Heavy Plugins

**Example: Event Management System**
```
Create a plan for transforming wp-qr-trackr into an event management plugin:

1. **Database Schema**: Events table, registrations table, venues table
2. **Admin Interface**: Event creation, attendee management, reporting
3. **Frontend**: Event listing, registration forms, calendar view
4. **Features**: Email notifications, payment integration, capacity management
5. **Migration**: Transform QR tracking to event check-in system

Please create detailed tasks and identify reusable components from the existing codebase.
```

### Content Management Plugins

**Example: Document Library System**
```
Plan a document library plugin using the wp-qr-trackr foundation:

1. **File Management**: Upload, organize, and secure documents
2. **Access Control**: User permissions and role-based access
3. **Search and Filter**: Advanced document search functionality
4. **Version Control**: Document versioning and history tracking
5. **Analytics**: Download tracking and usage statistics

Identify which QR tracking components can be adapted for download tracking.
```

### Integration-Heavy Plugins

**Example: CRM Integration Plugin**
```
Design a WordPress CRM plugin that integrates with external services:

1. **API Integration**: Connect with popular CRM platforms
2. **Data Synchronization**: Two-way sync between WordPress and CRM
3. **Lead Management**: Capture and manage leads from WordPress
4. **Automation**: Workflow triggers and automated actions
5. **Reporting**: CRM analytics and performance tracking

Plan the integration architecture and identify existing patterns that can be reused.
```

---

## Best Practices for Cursor AI Collaboration

### Effective Prompting Strategies

**1. Be Specific About Context**
```
Good: "Using the wp-qr-trackr modal system as a template, create a booking form modal with date picker, time slots, and validation"

Bad: "Create a booking form"
```

**2. Reference Existing Patterns**
```
Good: "Following the pattern in module-admin.php, create a new admin module for inventory management with proper nonce handling"

Bad: "Add inventory management"
```

**3. Request Incremental Development**
```
Good: "Implement the database schema first, then create the admin interface, then add the frontend functionality"

Bad: "Build the entire inventory system"
```

### Collaborative Development Workflow

**1. Start with Architecture**
- Always begin with high-level design discussions
- Request multiple architectural options when appropriate
- Validate design decisions before implementation

**2. Implement Incrementally**
- Break large features into smaller, testable components
- Test each component before moving to the next
- Maintain the TODO system throughout development

**3. Maintain Quality Standards**
- Run PHPCS checks after each major change
- Execute tests frequently during development
- Update documentation as features are completed

### Common Pitfalls to Avoid

**1. Scope Creep**
- Define clear feature boundaries upfront
- Resist adding features without proper planning
- Use the TODO system to track scope changes

**2. Ignoring WordPress Standards**
- Always follow WordPress coding standards
- Use proper WordPress APIs and hooks
- Implement security measures from the start

**3. Neglecting Testing**
- Write tests as you develop features
- Test in both dev and nonprod environments
- Maintain test coverage throughout development

---

## Getting Started Checklist

### Before You Begin
- [ ] **Fork the wp-qr-trackr repository** to your GitHub account
- [ ] **Clone your fork** and open in Cursor
- [ ] **Set up the development environment** using the provided scripts
- [ ] **Verify both dev and nonprod environments** are working
- [ ] **Review the existing codebase** to understand the architecture

### Planning Phase
- [ ] **Define your plugin requirements** clearly
- [ ] **Create a comprehensive project plan** using Cursor
- [ ] **Set up the TODO system** with structured tasks
- [ ] **Establish GitHub Projects integration** for tracking
- [ ] **Create initial documentation** for your plugin

### Development Phase
- [ ] **Transform the codebase** to your plugin identity
- [ ] **Implement features incrementally** using the TODO system
- [ ] **Maintain code quality** with regular PHPCS checks
- [ ] **Write tests** for all new functionality
- [ ] **Update documentation** as features are completed

### Testing and Deployment
- [ ] **Run comprehensive testing** in both environments
- [ ] **Verify security implementations** and performance
- [ ] **Create deployment documentation** and procedures
- [ ] **Set up CI/CD workflows** for automated testing
- [ ] **Plan release and maintenance** procedures

---

## Additional Resources

### Documentation Links
- [Main README](../README.md) - Project overview and features
- [Architecture Guide](ARCHITECTURE.md) - Technical architecture details
- [Development Guide](README.dev.md) - Development environment setup
- [Contributing Guide](../CONTRIBUTING.md) - Contribution workflow
- [GitHub Projects Sync](GITHUB_PROJECTS_SYNC.md) - Project management integration
- [**Cursor Quick Reference**](CURSOR_QUICK_REFERENCE.md) - Essential prompts and commands cheat sheet ⭐

### Example Prompts Repository
Create a collection of your most effective prompts for future reference:

```bash
# Create a prompts directory for your plugin
mkdir prompts
echo "# Effective Cursor Prompts for [Plugin Name]" > prompts/README.md
```

### Community and Support
- Use GitHub Issues for bug reports and feature requests
- Join WordPress development communities for additional support
- Share your plugin development experience with the community

---

**Remember**: The wp-qr-trackr project is designed to be a comprehensive foundation. Take advantage of all the infrastructure, testing, and automation that's already built so you can focus on your plugin's unique features rather than boilerplate setup.

**Happy Plugin Development!** 🚀

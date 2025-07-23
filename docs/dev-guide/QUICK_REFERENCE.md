# Cursor Quick Reference

Essential commands and prompts for WP QR Trackr development with Cursor.

## 🚨 Critical Environment Configurations (MANDATORY)

### Setup Commands
```bash
# Development environment (port 8080)
./scripts/setup-wordpress.sh dev

# Testing environment (port 8081)
./scripts/setup-wordpress.sh nonprod

# Playwright testing environment (port 8087)
./scripts/setup-wordpress.sh playwright
```

### Manual Configuration (if setup script fails)
```bash
# Fix upgrade directory permissions
docker compose -f docker/docker-compose.dev.yml exec --user root wordpress-dev chown -R www-data:www-data /var/www/html/wp-content/upgrade
docker compose -f docker/docker-compose.dev.yml exec --user root wordpress-dev chmod 775 /var/www/html/wp-content/upgrade

# Set pretty permalinks (REQUIRED for QR redirects)
docker compose -f docker/docker-compose.dev.yml exec wpcli-dev wp rewrite structure '/%postname%/' --path=/var/www/html
docker compose -f docker/docker-compose.dev.yml exec wpcli-dev wp rewrite flush --hard --path=/var/www/html

# Verify plugin activation
docker compose -f docker/docker-compose.dev.yml exec wpcli-dev wp plugin list --name=wp-qr-trackr
```

### Verification Commands
```bash
# Check plugin status
docker compose -f docker/docker-compose.dev.yml exec wpcli-dev wp plugin list --name=wp-qr-trackr

# Check permalink structure
docker compose -f docker/docker-compose.dev.yml exec wpcli-dev wp option get permalink_structure --path=/var/www/html

# Check directory permissions
docker compose -f docker/docker-compose.dev.yml exec wordpress-dev ls -la /var/www/html/wp-content/upgrade
```

## Development Commands

### Daily Development Start
```
Review the current TODO status and identify the next priority task. Mark it as in_progress and provide detailed implementation steps.
```

### Task Completion
```
I've completed work on [task name]. Please review the implementation, mark the task as completed, update documentation, and identify the next priority task.
```

### Code Review Request
```
Please review this implementation for WordPress best practices, security compliance, and code quality. Run PHPCS checks and suggest improvements.
```

## 🏗️ Feature Development

### New Feature Planning
```
I need to implement [feature name] for my plugin. Please:
1. Analyze existing patterns in the codebase
2. Create a modular implementation plan
3. Include security measures and error handling
4. Design admin interfaces using the existing modal system
5. Plan unit tests for the functionality
```

### Database Schema Changes
```
I need to modify the database schema for [purpose]. Please:
1. Design the table structure and relationships
2. Create migration scripts following the existing pattern
3. Update the relevant modules for the new schema
4. Implement proper indexing and caching
```

### Admin Interface Development
```
Create an admin interface for [feature] following the existing modal system pattern. Include proper nonce handling, validation, and responsive design.
```

## 🧪 Testing & Quality

### Comprehensive Testing
```
Please help me ensure production quality:
1. Run PHPCS compliance checks
2. Execute PHPUnit tests
3. Test in both dev and nonprod environments
4. Verify security implementations
5. Check performance and caching
```

### Pre-deployment Check
```
Prepare this plugin for deployment by:
1. Running all quality checks
2. Verifying documentation is complete
3. Testing the build process
4. Creating deployment instructions
```

## 🔄 Automation & Management

### TODO System Sync
```
Run the TODO automation script to sync between Cursor todos and markdown files, then provide a project summary.
```

### GitHub Projects Setup
```
Set up GitHub Projects integration for this plugin repository with proper field mapping and task synchronization.
```

## 📝 Documentation

### Update Documentation
```
Update all project documentation to reflect the latest changes, including README, architecture docs, and user guides.
```

### Create Feature Documentation
```
Create comprehensive documentation for [feature name] including setup instructions, usage examples, and troubleshooting.
```

## 🛠️ Common Tasks

### Plugin Identity Transformation
```
Transform the wp-qr-trackr codebase into [plugin name] by:
1. Renaming files, classes, and constants
2. Updating text domains and translations
3. Modifying plugin headers and metadata
4. Updating documentation and branding
```

### Security Implementation
```
Implement WordPress security best practices for [feature/functionality]:
1. Add proper nonce verification
2. Implement input sanitization
3. Add output escaping
4. Verify user permissions
5. Add security logging
```

### Performance Optimization
```
Optimize the plugin performance by:
1. Implementing caching strategies
2. Optimizing database queries
3. Adding proper indexing
4. Minimizing resource usage
5. Adding performance monitoring
```

## 📋 Checklists

### Pre-Development Checklist
- [ ] Fork wp-qr-trackr repository
- [ ] Set up development environment
- [ ] Define plugin requirements clearly
- [ ] Create project plan with TODO system
- [ ] Set up GitHub Projects integration

### Development Checklist
- [ ] Transform codebase to plugin identity
- [ ] Implement features incrementally
- [ ] Write tests for all functionality
- [ ] Maintain PHPCS compliance
- [ ] Update documentation continuously

### Pre-Release Checklist
- [ ] Run comprehensive testing
- [ ] Verify security implementations
- [ ] Check performance optimization
- [ ] Validate documentation completeness
- [ ] Test deployment process

## 💡 Best Practices

### Effective Prompting
- **Be specific** about context and requirements
- **Reference existing patterns** in the codebase
- **Request incremental development** rather than large changes
- **Ask for multiple options** when appropriate
- **Validate architecture** before implementation

### Quality Maintenance
- **Run PHPCS** after each major change
- **Execute tests** frequently during development
- **Update documentation** as features are completed
- **Maintain the TODO system** throughout development
- **Review security** for every feature

### Common Pitfalls to Avoid
- **Scope creep** - Define clear boundaries
- **Ignoring WordPress standards** - Follow best practices
- **Neglecting testing** - Write tests as you develop
- **Poor documentation** - Document as you build
- **Security shortcuts** - Implement security from the start

---

**💾 Save this reference** for quick access during development!

**📖 For detailed instructions**, see the [Complete Cursor Plugin Development Guide](CURSOR_PLUGIN_DEVELOPMENT_GUIDE.md)

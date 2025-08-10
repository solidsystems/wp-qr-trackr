# Configuration Directory

This directory contains all configuration files for the WP QR Trackr project, organized by purpose for better maintainability and cleaner project structure.

## Directory Structure

### `ci/` - Continuous Integration Configuration
- `.phpcs.xml` - PHP CodeSniffer configuration for WordPress coding standards
- `lefthook.yml` - Git hooks configuration for pre-commit and pre-push validation

### `editor/` - Editor and IDE Configuration
- `.editorconfig` - Editor configuration for consistent code formatting
- `.vscode/` - Visual Studio Code settings and extensions
- `eslint.config.js` - ESLint configuration for JavaScript linting

### `build/` - Build and Release Configuration
- `.distignore` - Files to exclude from release packages (similar to .gitignore)

### `testing/` - Testing Configuration
- `e2e.config.json` - End-to-end testing configuration for Playwright
- `phpunit.xml.dist` - PHPUnit test suite configuration
- `.phpunit.result.cache` - PHPUnit test result cache

### Environment Configuration
- `.env` - Environment variables (not committed to git)
- `.env.example` - Example environment variables template

## Benefits of This Organization

1. **Cleaner Root Directory** - Reduces clutter in the main project directory
2. **Logical Grouping** - Related configuration files are grouped together
3. **Easier Maintenance** - Clear separation of concerns for different types of configuration
4. **Better Discoverability** - Contributors can easily find relevant configuration files
5. **Scalability** - Easy to add new configuration categories as the project grows

## File References

All scripts and documentation have been updated to reference these files in their new locations. If you need to modify any configuration:

1. **PHPCS Rules**: Edit `config/ci/.phpcs.xml`
2. **Editor Settings**: Edit `config/editor/.editorconfig` or `config/editor/.vscode/`
3. **Build Exclusions**: Edit `config/build/.distignore`
4. **Test Configuration**: Edit files in `config/testing/`
5. **Environment Variables**: Edit `config/.env` (copy from `config/.env.example` if needed)

## Migration Notes

This organization was implemented to clean up the repository for public consumption. All file references in scripts, documentation, and CI/CD workflows have been updated to reflect the new locations. 
# Release Workflow Documentation

This document explains how to use the automated release workflow for the WP QR Trackr plugin.

## Overview

The project now has two automated workflows for managing releases:

1. **Auto Release Workflow** (`auto-release.yml`) - Automatically creates releases when code is merged to main
2. **Version Bump Workflow** (`version-bump.yml`) - Manually bumps version numbers and creates PRs

## Workflow 1: Auto Release Workflow

### Trigger
- **When**: Automatically triggered on push to `main` branch
- **Exclusions**: Ignores pushes to documentation files, README, TODO, and maintenance files

### What it does
1. **Extracts version** from `plugin/wp-qr-trackr.php`
2. **Checks if release exists** to avoid duplicates
3. **Builds the plugin** using `scripts/build-release.sh`
4. **Generates release notes** from git commits since last tag
5. **Creates GitHub release** with the plugin zip file
6. **Updates CHANGELOG** with release date
7. **Skips if release already exists**

### Example
When you merge a PR to main with version `1.2.64`:
- Workflow automatically builds the plugin
- Creates release "WP QR Trackr v1.2.64"
- Uploads `wp-qr-trackr-v1.2.64.zip`
- Updates CHANGELOG with release date

## Workflow 2: Version Bump Workflow

### Trigger
- **When**: Manually triggered via GitHub Actions UI
- **Purpose**: Prepare for a new release by bumping version numbers

### Inputs
- **bump_type**: `patch` (default), `minor`, or `major`
- **dry_run**: `true` or `false` (default: `false`)

### What it does
1. **Extracts current version** from plugin file
2. **Calculates new version** based on bump type
3. **Creates version bump branch** (e.g., `version-bump/v1.2.65`)
4. **Updates plugin version** in header and constant
5. **Adds new version entry** to CHANGELOG.md
6. **Creates pull request** for review and merge

### Version Bump Types
- **patch**: `1.2.63` → `1.2.64` (bug fixes, small changes)
- **minor**: `1.2.63` → `1.3.0` (new features, backward compatible)
- **major**: `1.2.63` → `2.0.0` (breaking changes)

## Release Process

### Step 1: Prepare for Release
1. Ensure all features are complete and tested
2. Update CHANGELOG.md with detailed change notes
3. Commit all changes to your feature branch

### Step 2: Version Bump (Optional)
If you want to bump the version before merging:
1. Go to **Actions** → **Version Bump**
2. Select bump type (patch/minor/major)
3. Set dry_run to `false`
4. Click **Run workflow**
5. Review and merge the created PR

### Step 3: Merge to Main
1. Create PR from your feature branch to main
2. Ensure CI/CD checks pass
3. Merge the PR

### Step 4: Automatic Release
The Auto Release workflow will:
1. Trigger automatically on merge to main
2. Build the plugin
3. Create GitHub release
4. Upload plugin zip file
5. Update CHANGELOG with release date

## File Structure

```
.github/workflows/
├── auto-release.yml      # Automatic release on main merge
├── version-bump.yml      # Manual version bumping
└── release.yml          # Legacy manual release workflow

docs/development/
└── RELEASE_WORKFLOW.md  # This documentation

scripts/
└── build-release.sh     # Plugin build script
```

## Version Management

### Plugin Version Location
The version is stored in two places in `plugin/wp-qr-trackr.php`:
1. **Plugin header**: `* Version: 1.2.63`
2. **Constant**: `define( 'QR_TRACKR_VERSION', '1.2.63' );`

### CHANGELOG Structure
```markdown
## 1.2.64

### Added
- New feature description

### Changed
- Changed feature description

### Fixed
- Bug fix description

### Technical
- Technical details
```

## Best Practices

### Before Merging to Main
1. **Update CHANGELOG**: Add detailed change notes for the new version
2. **Test thoroughly**: Ensure all functionality works
3. **Check version**: Verify version number is correct
4. **Review changes**: Ensure all intended changes are included

### Version Bumping
1. **Use appropriate bump type**:
   - `patch` for bug fixes and small improvements
   - `minor` for new features (backward compatible)
   - `major` for breaking changes
2. **Update CHANGELOG** with meaningful descriptions
3. **Test the build** using dry run first

### Release Notes
- The auto-release workflow generates release notes from git commits
- Use conventional commit messages for better release notes
- Format: `type(scope): description`

## Troubleshooting

### Release Already Exists
If you get "Release already exists" error:
- Check if the version was already released
- Increment the version number if needed
- Use the version bump workflow to create a new version

### Build Failures
If the build fails:
- Check the build logs for specific errors
- Ensure all dependencies are properly installed
- Verify the plugin structure is correct

### Permission Issues
If you get permission errors:
- Ensure the workflow has `contents: write` permission
- Check that the GitHub token has appropriate permissions
- Verify the repository settings allow workflow actions

## Migration from Manual Releases

The legacy `release.yml` workflow is still available for manual releases, but the new automated workflows are recommended for:

- **Consistency**: Automated version management
- **Reliability**: Built-in duplicate checking
- **Efficiency**: No manual zip file creation
- **Documentation**: Automatic CHANGELOG updates

## Testing the Workflow

### Testing Auto Release Workflow
To test the auto-release workflow:
1. **Create a test branch** with version changes
2. **Update plugin version** in `plugin/wp-qr-trackr.php`
3. **Create PR to main** and merge it
4. **Monitor the workflow** in GitHub Actions
5. **Verify release creation** in GitHub Releases

### Testing Version Bump Workflow
To test the version bump workflow:
1. **Go to Actions** → **Version Bump**
2. **Set dry_run to `true`** for testing
3. **Select bump type** (patch/minor/major)
4. **Run workflow** and check output
5. **Verify version calculation** is correct

### Dry Run Testing
Both workflows support dry run testing:
- **Auto Release**: Will show what would happen without creating actual release
- **Version Bump**: Will show version changes without creating PR

## Current Implementation Status

### ✅ Completed Features
- **Auto Release Workflow**: Fully implemented and tested
- **Version Bump Workflow**: Fully implemented with dry run support
- **Documentation**: Comprehensive guides and examples
- **Error Handling**: Graceful failure handling and duplicate checking
- **Version Management**: Automated version extraction and updating

### 🔄 Workflow Integration
- **CI/CD Pipeline**: Integrated with existing GitHub Actions
- **Build Process**: Uses existing `scripts/build-release.sh`
- **Release Management**: Automated GitHub release creation
- **Documentation Updates**: Automatic CHANGELOG updates

### 📋 File Dependencies
The workflows depend on these files:
- `plugin/wp-qr-trackr.php` - Version extraction
- `scripts/build-release.sh` - Plugin building
- `docs/CHANGELOG.md` - Release documentation
- `.github/workflows/` - Workflow definitions

## Support

For issues with the release workflow:
1. Check the GitHub Actions logs
2. Review this documentation
3. Check the workflow files for configuration
4. Create an issue if problems persist

### Common Issues and Solutions

#### Release Already Exists
**Problem**: Workflow reports "Release already exists"
**Solution**: 
- Check if version was already released
- Increment version number if needed
- Use version bump workflow to create new version

#### Build Failures
**Problem**: Plugin build fails during release
**Solution**:
- Check build logs for specific errors
- Verify all dependencies are installed
- Test build script locally first

#### Permission Issues
**Problem**: Workflow fails with permission errors
**Solution**:
- Ensure workflow has `contents: write` permission
- Check GitHub token permissions
- Verify repository settings allow workflow actions

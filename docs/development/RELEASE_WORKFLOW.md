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

## Support

For issues with the release workflow:
1. Check the GitHub Actions logs
2. Review this documentation
3. Check the workflow files for configuration
4. Create an issue if problems persist

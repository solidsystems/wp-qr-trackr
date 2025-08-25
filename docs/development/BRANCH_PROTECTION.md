# Branch Protection and Development Workflow

This document explains the branch protection rules in place and the proper development workflow for the WP QR Trackr project.

## Branch Protection Rules

The `main` branch is protected with the following rules:

### Required Pull Request Reviews
- **Minimum reviews**: 1 approving review required
- **Dismiss stale reviews**: Enabled (reviews are dismissed when new commits are pushed)
- **Code owner reviews**: Not required
- **Last push approval**: Not required

### Status Checks
- **Strict status checks**: Enabled (branches must be up to date before merging)
- **Required contexts**: None currently configured (can be added for CI/CD)

### Additional Protections
- **Conversation resolution**: Required (all review comments must be resolved)
- **Force pushes**: Disabled
- **Branch deletions**: Disabled
- **Linear history**: Not required
- **Admin enforcement**: Disabled (admins can bypass restrictions if needed)

## Proper Development Workflow

### 1. Create a Feature Branch

```bash
# Start from main branch
git checkout main
git pull origin main

# Create and switch to a new feature branch
git checkout -b feature/your-feature-name
```

### 2. Make Your Changes

```bash
# Make your code changes
# Edit files as needed

# Stage and commit your changes
git add .
git commit -m "feat: your descriptive commit message"
```

### 3. Push Your Branch

```bash
# Push your feature branch to origin
git push origin feature/your-feature-name
```

### 4. Create a Pull Request

```bash
# Create a pull request using GitHub CLI
gh pr create --title "feat: your feature title" --body "Description of your changes" --base main --head feature/your-feature-name
```

Or create the PR through the GitHub web interface.

### 5. Get Review and Merge

- Request reviews from team members
- Address any review comments
- Once approved, merge the pull request
- Delete the feature branch after merging

## Branch Naming Conventions

Use descriptive branch names with prefixes:

- `feature/` - New features
- `fix/` - Bug fixes
- `docs/` - Documentation updates
- `refactor/` - Code refactoring
- `test/` - Adding or updating tests
- `chore/` - Maintenance tasks

Examples:
- `feature/editor-user-configuration`
- `fix/qr-code-generation-issue`
- `docs/update-installation-guide`

## What Happens If You Try to Push Directly to Main

If you attempt to push directly to the main branch, you'll get an error like:

```
! [rejected]          main -> main (non-fast-forward)
error: failed to push some refs to 'https://github.com/solidsystems/wp-qr-trackr.git'
```

This is the expected behavior - the branch protection is working correctly.

## Benefits of This Workflow

1. **Code Review**: All changes must be reviewed before merging
2. **Quality Control**: Prevents accidental pushes to main
3. **Collaboration**: Encourages team collaboration and knowledge sharing
4. **History**: Maintains clean git history with proper commit messages
5. **Rollback**: Easy to revert changes if needed
6. **Documentation**: Pull requests serve as documentation of changes

## Emergency Situations

In emergency situations where immediate access to main is needed:

1. **Admin Override**: Repository admins can temporarily disable branch protection
2. **Force Push**: Admins can force push if absolutely necessary (not recommended)
3. **Hotfix Branch**: Create a hotfix branch for urgent fixes

## Best Practices

1. **Small, Focused Changes**: Keep pull requests small and focused on a single feature or fix
2. **Descriptive Messages**: Use clear, descriptive commit messages and PR descriptions
3. **Test Before PR**: Ensure your changes work and pass tests before creating a PR
4. **Respond to Reviews**: Address review comments promptly
5. **Keep Branches Updated**: Regularly sync your feature branch with main to avoid conflicts

## Automation

The project includes automation scripts that work with this workflow:

- **Pre-commit hooks**: Run validation before commits
- **CI/CD**: Automated testing and validation
- **Automated PR creation**: Scripts for creating standardized PRs

## Troubleshooting

### Common Issues

1. **Branch behind main**: Pull latest changes from main and rebase your branch
2. **Merge conflicts**: Resolve conflicts locally before pushing
3. **Failed status checks**: Fix any failing tests or linting issues
4. **Review required**: Ensure your PR has the required number of approvals

### Commands for Common Scenarios

```bash
# Update your branch with latest main
git checkout main
git pull origin main
git checkout your-feature-branch
git rebase main

# Fix merge conflicts
git status  # See conflicted files
# Edit conflicted files
git add .
git rebase --continue

# Abort a rebase if needed
git rebase --abort
```

---

This workflow ensures code quality, collaboration, and maintainability while preventing accidental changes to the main branch.

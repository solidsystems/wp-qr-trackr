# WP QR Trackr

A professional WordPress plugin for QR code generation and tracking.

## Quick Start

1. **Requirements**
   - Docker Desktop
   - Git

2. **Development Setup**
   ```bash
   # Clone the repository
   git clone https://github.com/yourusername/wp-qr-trackr.git
   cd wp-qr-trackr

   # Check environment
   bash scripts/check-onboarding.sh

   # Start development environment (http://localhost:8080)
   bash scripts/setup-wordpress.sh dev

   # Start testing environment (http://localhost:8081)
   bash scripts/setup-wordpress.sh nonprod
   ```

3. **Access**
   - Development: http://localhost:8080/wp-admin
   - Testing: http://localhost:8081/wp-admin
   - Username: trackr
   - Password: trackr

## Documentation

- [Plugin User Guide](docs/USER_GUIDE.md)
- [Developer Guide](docs/DEVELOPER_GUIDE.md)
- [Contributing Guide](docs/CONTRIBUTING.md)
- [Architecture](docs/ARCHITECTURE.md)
- [Changelog](docs/CHANGELOG.md)
- [Troubleshooting](docs/TROUBLESHOOTING.md)

## Features

- Generate QR codes for any post, page, or custom URL
- Track QR code scans with detailed analytics
- Mobile-first admin interface
- Secure and efficient codebase
- Extensive developer API

## License

This project is licensed under the GPL v2 or later.

## Code Quality & Automation

All linting, formatting, and validation is performed in containers using Docker Compose. This ensures a consistent environment for all contributors and CI/CD. No PHP, Composer, or Node.js tools are required on the host—only Docker Desktop and a code editor (Cursor/VSCode).

### Belt-and-Suspenders Code Quality System

This project uses a comprehensive, container-based "belt and suspenders" code quality system:

- **All code quality checks, linting, formatting, and validation are performed inside Docker containers.**
  This ensures that every contributor, regardless of their local environment, is running the exact same tools and versions. No more "works on my machine" issues or dependency drift.

- **Pre-commit and pre-push hooks (via Lefthook) automatically run PHPCS, PHPCBF, and the full validation suite before code is committed or pushed.**
  This blocks code with style or security issues from ever entering your repository, saving countless hours of review and rework.

- **EditorConfig and VSCode settings enforce consistent code style and provide instant feedback as you type.**
  You'll see errors and warnings inline, and formatting is handled automatically on save.

- **A Makefile and scripts provide simple, memorable commands for all common tasks (`make fix`, `make lint`, `make validate`).**
  No need to remember long Docker or Composer commands.

- **A config check script ensures all guardrails and standards are present and up to date.**
  This prevents accidental drift or missing configuration as the project evolves.

- **A PR template and documentation make expectations clear for all contributors.**
  Every pull request must confirm that all checks have passed in containers, and that `.cursorrules` is followed.

#### How This Dramatically Reduces Time Spent on Code Quality Issues

- Automated enforcement means you catch issues immediately, not after a long review cycle or in production.
- Auto-fixing with PHPCBF resolves most style issues without manual intervention.
- Consistent environments eliminate "it works for me" problems and reduce onboarding time for new contributors.
- You spend less time fighting the linter and more time building features, knowing that your codebase will always meet your standards.
- Code reviews can focus on logic and architecture, not tabs vs. spaces or missing docblocks.

#### Benefits for Beginner Plugin Developers

- **Clear, actionable feedback:** Beginners see exactly what needs to be fixed, with error messages and inline highlights in their editor.
- **Automatic formatting and linting:** New developers don't need to memorize all the WordPress or PHPCS rules—automation handles it for them.
- **Safe learning environment:** Mistakes are caught early and fixed automatically, reducing frustration and building good habits.
- **Documentation and templates:** The README, PR template, and `.cursorrules` provide a roadmap for what's expected, making it easier to contribute confidently.
- **No local setup headaches:** All that's needed is Docker and a code editor—no PHP, Composer, or Node.js setup required.

#### How This Helps When Using AI Agents Like Cursor

- **AI-generated code is immediately checked and auto-fixed for style and security issues.**
  Even if an agent generates code that doesn't fully comply with your standards, the automation will catch and correct most issues before they reach your repo.
- **Agents can focus on logic and functionality, knowing that guardrails will enforce best practices.**
- **Reduced risk of "silent" technical debt:** Any code—human or AI-generated—that violates your standards will be flagged and blocked, ensuring long-term maintainability.
- **Faster iteration:** You can confidently accept and test AI-generated code, knowing that the containerized validation will catch anything that needs attention.
- **Consistent codebase:** Over time, your codebase remains clean, readable, and secure, regardless of who (or what) wrote the code.

### Workflow
- Use `make fix` to auto-fix code style issues (PHPCBF).
- Use `make lint` to check for PHPCS errors.
- Use `make validate` to run the full suite (PHPCS, Playwright, etc.).
- Pre-commit and pre-push hooks (via Lefthook) enforce these checks before code is committed or pushed.
- All rules and best practices are documented in `.cursorrules`.

### Editor Integration
- EditorConfig and VSCode settings ensure tabs, line endings, and inline PHPCS feedback.

### PR Requirements
- All PRs must pass container-based validation and comply with `.cursorrules`.

See `.cursorrules` and the Makefile for details.

### Lefthook & Git Hooks
- Lefthook is installed and runs only in the container/CI environment. Contributors do not need to install Lefthook locally.
- All pre-commit and pre-push hooks are enforced in the container and CI workflows.
- For local checks, use `make fix`, `make lint`, and `make validate`.

## Continuous Integration & Deployment

### CI/CD Workflow Overview

The project uses a robust, containerized CI/CD pipeline that ensures consistent testing across all environments:

#### **CI Environment Features:**
- **Containerized Testing:** All tests run in Docker containers with no local dependencies
- **WordPress Test Suite Integration:** Automated WordPress test environment setup
- **Database Integration:** MariaDB service for reliable database testing
- **Multi-Platform Support:** ARM64 and x86 compatibility
- **Robust Error Handling:** Comprehensive debugging and fallback mechanisms

#### **CI Pipeline Steps:**
1. **Build CI Image:** Creates a self-contained testing environment
2. **Install Dependencies:** Composer and Yarn packages installed in container
3. **Setup WordPress Test Suite:** Downloads and configures WordPress test environment
4. **Database Setup:** Creates test database with MariaDB
5. **Run PHPUnit Tests:** Executes WordPress plugin tests
6. **Code Quality Checks:** PHPCS validation (when enabled)

#### **Key Improvements Made:**
- **Fixed WordPress Bootstrap:** Resolved `add_action()` undefined function error
- **Database Host Configuration:** Updated to use `db` service instead of `localhost`
- **PHPUnit Detection:** Added robust fallback mechanisms for PHPUnit location
- **MariaDB Integration:** Switched from MySQL to MariaDB for ARM64 compatibility
- **Error Handling:** Enhanced debugging output and error recovery

### Testing Matrix

All code is automatically tested in CI against the following combinations:

- PHP 8.1 + WordPress 6.4
- PHP 8.1 + WordPress latest
- PHP 8.2 + WordPress 6.4
- PHP 8.2 + WordPress latest

This ensures the plugin is compatible with all currently supported PHP and WordPress versions, and helps catch issues early as new versions are released.

### Local CI Testing

You can test the CI environment locally before pushing:

```bash
# Test the complete CI workflow locally
docker compose -f docker/docker-compose.ci.yml run --rm ci-runner

# Test individual components
docker compose -f docker/docker-compose.ci.yml run --rm ci-runner bash -c "bash scripts/install-wp-tests.sh wpdb wpuser wppass db latest && ./vendor/bin/phpunit"
```

### Troubleshooting CI Issues

If you encounter CI failures:

1. **Test Locally First:** Always run the CI workflow locally before pushing
2. **Check Dependencies:** Ensure all required files are present in the repository
3. **Database Issues:** Verify MariaDB service is running and accessible
4. **WordPress Test Suite:** Check that test files are properly installed
5. **PHPUnit Issues:** Verify PHPUnit is installed and accessible

See the [Troubleshooting Guide](docs/TROUBLESHOOTING.md) for more detailed solutions.

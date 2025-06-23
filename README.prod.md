# QR Trackr Plugin Template (Production)

A modern, production-ready WordPress plugin template—featuring QR Trackr as an example. This version of the documentation is focused on secure, stable, and performant production deployment.

## Table of Contents
1. Project Overview
2. Quick Start / Onboarding (Production)
3. Usage
4. Production Deployment & Configuration
5. Infrastructure & Plumbing
6. Troubleshooting & FAQ (Production)
7. Links & Further Reading

---

## Project Overview

**QR Trackr** is a WordPress plugin for generating and tracking QR codes for posts, pages, and custom URLs. This repository also serves as a robust template for building any modern WordPress plugin.

**Key Features:**
- Modular, scalable plugin structure
- Secure, maintainable, and extensible codebase
- Hooks/filters for free/pro separation
- Mobile-first, accessible admin UI
- Automated setup and testing
- Comprehensive PHPUnit test suite
- DigitalOcean App Platform compatibility
- Example project plans and automation scripts

---

## Quick Start / Onboarding (Production)

### Prerequisites
- DigitalOcean App Platform account
- WordPress-compatible hosting
- [Yarn](https://yarnpkg.com/)
- [Composer](https://getcomposer.org/)

### Production Setup Steps
1. **Clone the repository and build the plugin as per release instructions.**
2. **Deploy WordPress to DigitalOcean App Platform or your production host.**
3. **Upload the built plugin zip via the WordPress admin or deploy as part of your CI/CD pipeline.**
4. **Configure environment variables and secrets in the DigitalOcean App Platform dashboard.**
5. **Ensure debug mode is disabled in production.**

### CI/CD Pipeline: A Pre-Built Container Approach

To accelerate testing and improve reliability, this project uses a sophisticated CI/CD architecture based on a pre-built Docker container. Instead of building the testing environment from scratch on every run, the pipeline pulls a stable, versioned CI container from the GitHub Container Registry (GHCR).

This approach provides two key benefits:
- **Speed:** CI checks start almost instantly, as the time-consuming step of building the Docker image is eliminated from the main workflow.
- **Reliability:** It completely avoids Docker caching issues and ensures that every test run—whether in CI or locally—uses the exact same, consistent environment.

#### Architecture Overview

```mermaid
graph TD
    subgraph "Developer"
        A[Push Code]
    end

    subgraph "GitHub Actions"
        A --> B{Dependency Files Changed?};
        B -- Yes --> C[Workflow: `publish-ci-image.yml`];
        B -- No --> D[Workflow: `ci.yml`];

        C --> E[1. Build Docker Image];
        E --> F[2. Push to GitHub Container Registry];

        D --> G[1. Pull Image from Registry];
        F --> G;
        G --> H[2. Run Tests];
    end

    style F fill:#f9f,stroke:#333,stroke-width:2px
    style C fill:#ccf,stroke:#333,stroke-width:2px
    style D fill:#cfc,stroke:#333,stroke-width:2px
    linkStyle 2 stroke-width:2px,stroke-dasharray: 3 3;
```

#### How It Works

The system is composed of two distinct GitHub Actions workflows:

1.  **`publish-ci-image.yml` (The Builder)**
    - **Purpose:** To build and publish the CI Docker image.
    - **Trigger:** This workflow runs *only* when files defining the CI environment are changed on the `main` branch (e.g., `Dockerfile.ci`, `composer.json`, `package.json`).
    - **Action:** It builds the `ci-runner` image and pushes it to the GitHub Container Registry, where it becomes the new standard for testing.

2.  **`ci.yml` (The Tester)**
    - **Purpose:** To run all linting and testing checks on the code.
    - **Trigger:** Runs on every push and pull request.
    - **Action:**
        - It logs into the GitHub Container Registry.
        - It uses `docker-compose.ci.yml` to pull the pre-built `ci-runner` image.
        - It executes the `ci.sh` script inside the container to run all checks.

This separation of concerns ensures that the time-intensive build process only happens when absolutely necessary, making the day-to-day development and testing cycle fast and efficient.

### Modular Linting & Formatting Configuration

To ensure code quality and consistency across all contributors and environments, this project uses a modular, extensible lint-staged configuration. This setup automatically lints and formats all relevant file types before each commit, using the right tool for each language or format. 

**Key points:**
- No `cd` commands are used in config files, avoiding path confusion and automation issues.
- All linting/formatting is run from the project root, ensuring compatibility with Husky, lint-staged, and CI/CD.
- The configuration is easily extendable for new file types or tools.
- This approach enforces standards, reduces review friction, and prevents common pitfalls in cross-platform and modular setups.

**Current `.lintstagedrc.json` config:**
```json
{
  "*.js": "eslint --fix",
  "*.jsx": "eslint --fix",
  "*.ts": "eslint --fix",
  "*.tsx": "eslint --fix",
  "*.php": "phpcbf",
  "*.css": "stylelint --fix",
  "*.scss": "stylelint --fix",
  "*.json": "prettier --write",
  "*.md": "prettier --write",
  "*.yml": "prettier --write",
  "*.yaml": "prettier --write"
}
```

**What each tool does:**
- `eslint --fix`: Lints and auto-formats JavaScript, JSX, TypeScript, and TSX files.
- `phpcbf`: Applies WordPress and project PHP coding standards automatically.
- `stylelint --fix`: Lints and auto-formats CSS and SCSS files.
- `prettier --write`: Formats JSON, Markdown, and YAML files for consistency.

**Significance:**
- **Reliability:** Avoids automation pitfalls (like infinite loops from `cd` in configs).
- **Consistency:** All code and docs are auto-formatted before commit.
- **Modularity:** Easy to add new file types or tools as the project grows.
- **Cross-platform:** Works on macOS, Linux, and CI/CD without modification.

See `scripts/.lintstagedrc.json` for the authoritative config. Update this file if you add new file types or want to change linting/formatting tools.

## Executive Summary: Production CI/CD Architecture

```mermaid
graph TD
    subgraph "Developer"
        A[Push Code]
    end

    subgraph "CI/CD Pipeline"
        A --> B{Dependency Files Changed?}
        B -- Yes --> C[Build & Publish CI Docker Image]
        B -- No --> D[Pull Pre-built CI Image]
        C --> E[Push to GHCR]
        E --> F[CI Workflow: Run Tests]
        D --> F
        F[Run Linting & Tests in Container]
    end
```

## CI/CD Memory Management

- The CI/CD pipeline uses a pre-built Docker image and enforces a 2G memory limit for Composer and PHPCS to ensure reliability.
- Only supported PHPCS sniffs (wpcs, phpcsutils) are used; legacy sniffs have been removed.
- These memory settings are not enforced in production or local development unless needed.

---

## Production Deployment & Configuration

**Recommended `
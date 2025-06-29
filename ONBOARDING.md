# 🚀 Onboarding: Zero Host Dependencies

Welcome to the project! This onboarding guide will get you set up in minutes with a fully containerized workflow.

## Requirements
- Docker Desktop (latest)
- Git

## Workflow
- All development, linting, and testing is done inside the Docker container (`ci-runner`).
- No need to install PHP, Composer, Node, or CLI tools on your host.
- All code changes, linting, and tests are performed in the container, but changes persist on your local filesystem via the Docker volume mount.

## Example Commands
```sh
docker compose run --rm ci-runner vendor/bin/phpcs
docker compose run --rm ci-runner vendor/bin/phpcbf
docker compose run --rm ci-runner bash ci.sh
```

## Why Containerize Everything?
- Ensures every developer and CI run uses the exact same environment.
- Eliminates "works on my machine" problems.
- No need for Homebrew or system package managers.
- Onboarding is as simple as installing Docker Desktop and Git.

## Automated Onboarding Check

- The script `scripts/check-onboarding.sh` runs automatically before every commit (pre-commit hook).
- You can run it manually at any time: `bash scripts/check-onboarding.sh`
- It checks for Docker, Docker running, and Git, and warns if local PHP, Composer, or Node is installed.

## Unified Code Validation

- To validate your code locally (lint, test, etc.), run:
  - `make validate`
  - or `docker compose run --rm ci-runner bash scripts/validate.sh`
- This is the same command used in CI/CD, ensuring consistency.

## See Also
- Architecture diagram and workflow: see `
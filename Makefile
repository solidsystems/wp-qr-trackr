# Makefile for QR Trackr
# Usage: make validate

.PHONY: validate test lint clean

lint:
	docker compose -f docker/docker-compose.playwright.yml run --rm playwright-runner ./vendor/bin/phpcs --standard=WordPress --extensions=php --ignore=node_modules,vendor wp-content/plugins/wp-qr-trackr/

fix:
	docker compose -f docker/docker-compose.playwright.yml run --rm playwright-runner ./vendor/bin/phpcbf --standard=WordPress --extensions=php --ignore=node_modules,vendor wp-content/plugins/wp-qr-trackr/

validate:
	RUN_E2E=0 docker compose -f docker/docker-compose.playwright.yml run --rm playwright-runner bash scripts/validate.sh

.PHONY: validate-e2e
validate-e2e:
	RUN_E2E=1 docker compose -f docker/docker-compose.playwright.yml run --rm playwright-runner

test:
	docker compose -f docker/docker-compose.dev.yml run --rm ci-runner vendor/bin/phpunit

clean:
	docker compose -f docker/docker-compose.dev.yml down -v
	docker compose -f docker/docker-compose.yml down -v
	rm -rf node_modules vendor

pr:
	@if [ -z "$(BRANCH)" ] || [ -z "$(TITLE)" ] || [ -z "$(BODY)" ]; then \
		echo "Usage: make pr BRANCH=<branch> TITLE='<title>' BODY=<body-file>"; \
		exit 1; \
	fi; \
	bash scripts/create-pr.sh $(BRANCH) "$(TITLE)" $(BODY)

# WordPress operations
wp-dev:
	@echo "WordPress operations for dev environment"
	@echo "Usage: make wp-dev COMMAND=<wp-command>"
	@if [ -n "$(COMMAND)" ]; then \
		./scripts/wp-operations.sh dev $(COMMAND); \
	else \
		echo "Available commands: plugin list, core is-installed, option get permalink_structure"; \
	fi

wp-nonprod:
	@echo "WordPress operations for nonprod environment"
	@echo "Usage: make wp-nonprod COMMAND=<wp-command>"
	@if [ -n "$(COMMAND)" ]; then \
		./scripts/wp-operations.sh nonprod $(COMMAND); \
	else \
		echo "Available commands: plugin list, core is-installed, option get permalink_structure"; \
	fi

# Debug operations
debug-dev:
	@echo "Debug operations for dev environment"
	@echo "Usage: make debug-dev COMMAND=<debug-command>"
	@if [ -n "$(COMMAND)" ]; then \
		./scripts/debug.sh dev $(COMMAND); \
	else \
		echo "Available commands: dependencies, container-status, logs, health, diagnose, wordpress, database, plugin, permissions"; \
	fi

debug-nonprod:
	@echo "Debug operations for nonprod environment"
	@echo "Usage: make debug-nonprod COMMAND=<debug-command>"
	@if [ -n "$(COMMAND)" ]; then \
		./scripts/debug.sh nonprod $(COMMAND); \
	else \
		echo "Available commands: dependencies, container-status, logs, health, diagnose, wordpress, database, plugin, permissions"; \
	fi

# Container management
containers-dev:
	@echo "Container management for dev environment"
	@echo "Usage: make containers-dev COMMAND=<container-command>"
	@if [ -n "$(COMMAND)" ]; then \
		./scripts/manage-containers.sh $(COMMAND) dev; \
	else \
		echo "Available commands: start, stop, restart, redeploy, health, monitor, diagnose, logs, status, wp-install, wp-status, wp-reset, wp-plugin-status"; \
	fi

containers-nonprod:
	@echo "Container management for nonprod environment"
	@echo "Usage: make containers-nonprod COMMAND=<container-command>"
	@if [ -n "$(COMMAND)" ]; then \
		./scripts/manage-containers.sh $(COMMAND) nonprod; \
	else \
		echo "Available commands: start, stop, restart, redeploy, health, monitor, diagnose, logs, status, wp-install, wp-status, wp-reset, wp-plugin-status"; \
	fi

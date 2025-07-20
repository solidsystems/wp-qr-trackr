# Makefile for QR Trackr
# Usage: make validate

.PHONY: validate test lint clean

lint:
	docker compose -f docker/docker-compose.playwright.yml run --rm playwright-runner ./vendor/bin/phpcs --standard=WordPress --extensions=php --ignore=node_modules,vendor wp-content/plugins/wp-qr-trackr/

fix:
	docker compose -f docker/docker-compose.playwright.yml run --rm playwright-runner ./vendor/bin/phpcbf --standard=WordPress --extensions=php --ignore=node_modules,vendor wp-content/plugins/wp-qr-trackr/

validate:
	docker compose -f docker/docker-compose.playwright.yml run --rm playwright-runner bash scripts/validate.sh

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

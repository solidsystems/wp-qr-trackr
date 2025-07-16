# Makefile for QR Trackr
# Usage: make validate

.PHONY: validate test lint clean

lint:
	docker compose -f docker/docker-compose.playwright.yml run --rm playwright-runner ./vendor/bin/phpcs

fix:
	docker compose -f docker/docker-compose.playwright.yml run --rm playwright-runner ./vendor/bin/phpcbf

validate:
	docker compose -f docker/docker-compose.playwright.yml run --rm playwright-runner ./vendor/bin/phpcs
	docker compose -f docker/docker-compose.playwright.yml run --rm playwright-runner yarn playwright test

test:
	docker compose -f docker/docker-compose.dev.yml run --rm ci-runner vendor/bin/phpunit

clean:
	docker compose -f docker/docker-compose.dev.yml down -v
	docker compose -f docker/docker-compose.yml down -v
	rm -rf node_modules vendor

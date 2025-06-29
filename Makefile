# Makefile for QR Trackr
# Usage: make validate

validate:
	docker compose run --rm ci-runner bash scripts/validate.sh 
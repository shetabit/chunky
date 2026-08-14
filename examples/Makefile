# Runs the example of the package.
#
#   make up      start it on http://localhost:8080
#   make down    stop it again
#
# Another port can be used per invocation:
#
#   make up PORT=9000

PORT ?= 8080

COMPOSE = PORT=$(PORT) docker compose

.DEFAULT_GOAL := help

.PHONY: help up down restart logs shell clean

help: ## Show this help
	@grep -E '^[a-zA-Z-]+:.*?## .*$$' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-10s\033[0m %s\n", $$1, $$2}'

up: ## Start the example, on http://localhost:8080 unless PORT says otherwise
	$(COMPOSE) up --build

down: ## Stop the example
	$(COMPOSE) down

restart: down up ## Stop the example and start it again

logs: ## Follow the log of the example
	$(COMPOSE) logs --follow

shell: ## Open a shell inside the container
	$(COMPOSE) run --rm --entrypoint sh example

clean: down ## Stop the example and throw away its dependencies, uploads and image
	rm -rf vendor composer.lock
	find uploads -type f ! -name '.gitignore' -delete
	docker image rm --force chunky-example

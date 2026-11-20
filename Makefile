.PHONY: help build up down restart logs ps shell shell-db artisan composer migrate migrate-fresh seed rollback tinker route-list chat chat-new install update dump cache-clear cache optimize queue-restart scheduler scheduler-stop test test-filter setup copy-env key-generate ide-helper db-dump db-restore permissions

# Colors
GREEN  := $(shell tput -Txterm setaf 2)
YELLOW := $(shell tput -Txterm setaf 3)
RESET  := $(shell tput -Txterm sgr0)

# Default target
.DEFAULT_GOAL := help

# Same targets everywhere: on the server .env.production exists, so the production stack is used
ifneq (,$(wildcard .env.production))
COMPOSE := docker compose -f compose.production.yml --env-file .env.production
else
COMPOSE := docker compose
endif

## —— Docker ——
build: ## Build all containers
	$(COMPOSE) build

up: ## Start all containers
	$(COMPOSE) up -d

down: ## Stop all containers
	$(COMPOSE) down

restart: down up ## Restart all containers

logs: ## Show logs (use: make logs s=php)
	$(COMPOSE) logs -f $(s)

ps: ## Show running containers
	$(COMPOSE) ps

## —— Shell Access ——
shell: ## Access PHP container shell
	$(COMPOSE) exec php sh

shell-db: ## Access MariaDB shell
	$(COMPOSE) exec db sh -c 'mariadb -uroot -p"$$MYSQL_ROOT_PASSWORD" "$$MYSQL_DATABASE"'

## —— Artisan Commands ——
artisan: ## Run artisan command (use: make artisan c="migrate")
	$(COMPOSE) exec php php artisan $(c)

migrate: ## Run migrations
	$(COMPOSE) exec php php artisan migrate

migrate-fresh: ## Fresh migration with seed
	$(COMPOSE) exec php php artisan migrate:fresh --seed

seed: ## Run seeders
	$(COMPOSE) exec php php artisan db:seed

rollback: ## Rollback last migration
	$(COMPOSE) exec php php artisan migrate:rollback

tinker: ## Run Laravel Tinker
	$(COMPOSE) exec php php artisan tinker

chat: ## Send a chat message as a client (use: make chat client=806 text="Привет")
	$(COMPOSE) exec php php artisan chat:client-message $(if $(client),--client=$(client)) $(if $(text),--text="$(text)")

chat-new: ## Open a new chat from a random client without one
	$(COMPOSE) exec php php artisan chat:client-message --new $(if $(text),--text="$(text)")

route-list: ## Show all routes
	$(COMPOSE) exec php php artisan route:list

## —— Composer Commands ——
composer: ## Run composer command (use: make composer c="require package")
	$(COMPOSE) exec php composer $(c)

install: ## Install composer dependencies
	$(COMPOSE) exec php composer install

update: ## Update composer dependencies
	$(COMPOSE) exec php composer update

dump: ## Composer dump-autoload
	$(COMPOSE) exec php composer dump-autoload

## —— Cache ——
cache-clear: ## Clear all cache
	$(COMPOSE) exec php php artisan cache:clear
	$(COMPOSE) exec php php artisan config:clear
	$(COMPOSE) exec php php artisan route:clear
	$(COMPOSE) exec php php artisan view:clear

cache: ## Cache config and routes
	$(COMPOSE) exec php php artisan config:cache
	$(COMPOSE) exec php php artisan route:cache

optimize: ## Optimize application
	$(COMPOSE) exec php php artisan optimize

## —— Queue ——
queue-restart: ## Restart queue worker and Reverb (pick up code changes)
	$(COMPOSE) restart queue reverb

scheduler: ## Start scheduler (profile, as on prod)
	$(COMPOSE) --profile scheduler up -d scheduler

scheduler-stop: ## Stop scheduler
	$(COMPOSE) --profile scheduler stop scheduler

## —— Testing ——
test: ## Run tests
	$(COMPOSE) exec php php artisan test

test-filter: ## Run filtered tests (use: make test-filter f="TestName")
	$(COMPOSE) exec php php artisan test --filter=$(f)

## —— Setup ——
setup: ## Initial project setup
	@make copy-env
	@make build
	@make up
	@make install
	@make key-generate
	@make migrate
	@echo "$(GREEN)Setup completed!$(RESET)"

copy-env: ## Copy .env.docker or .env.example to .env
	@if [ ! -f .env ]; then \
		if [ -f .env.docker ]; then \
			cp .env.docker .env; \
		else \
			cp .env.example .env; \
		fi \
	fi

key-generate: ## Generate application key
	$(COMPOSE) exec php php artisan key:generate

ide-helper: ## Generate IDE helper files
	$(COMPOSE) exec php php artisan ide-helper:generate

## —— Database ——
db-dump: ## Dump database to backup.sql
	$(COMPOSE) exec -T db sh -c 'mariadb-dump -uroot -p"$$MYSQL_ROOT_PASSWORD" --single-transaction --routines --triggers "$$MYSQL_DATABASE"' > backup.sql

db-restore: ## Restore database from backup.sql (blocked on prod)
	@if [ -f .env.production ]; then echo "db-restore is disabled on production"; exit 1; fi
	$(COMPOSE) exec -T db sh -c 'mariadb -uroot -p"$$MYSQL_ROOT_PASSWORD" "$$MYSQL_DATABASE"' < backup.sql

## —— Helpers ——
permissions: ## Fix storage permissions
	$(COMPOSE) exec php chmod -R 775 storage bootstrap/cache
	$(COMPOSE) exec php chown -R laravel:laravel storage bootstrap/cache

help: ## Show this help
	@echo ''
	@echo 'Usage:'
	@echo '  ${YELLOW}make${RESET} ${GREEN}<target>${RESET}'
	@echo ''
	@echo 'Targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  ${YELLOW}%-15s${RESET} %s\n", $$1, $$2}' $(MAKEFILE_LIST)

.PHONY: help build up down restart logs ps shell shell-db artisan composer migrate migrate-fresh seed rollback tinker route-list install update dump cache-clear cache optimize queue-restart scheduler scheduler-stop test test-filter setup copy-env key-generate ide-helper db-dump db-restore permissions

# Colors
GREEN  := $(shell tput -Txterm setaf 2)
YELLOW := $(shell tput -Txterm setaf 3)
RESET  := $(shell tput -Txterm sgr0)

# Default target
.DEFAULT_GOAL := help

## —— Docker ——
build: ## Build all containers
	docker compose build

up: ## Start all containers
	docker compose up -d

down: ## Stop all containers
	docker compose down

restart: down up ## Restart all containers

logs: ## Show logs (use: make logs s=php)
	docker compose logs -f $(s)

ps: ## Show running containers
	docker compose ps

## —— Shell Access ——
shell: ## Access PHP container shell
	docker compose exec php sh

shell-db: ## Access MariaDB shell
	docker compose exec db mariadb -u root -p$${DB_ROOT_PASSWORD:-root} $${DB_DATABASE:-fitness}

## —— Artisan Commands ——
artisan: ## Run artisan command (use: make artisan c="migrate")
	docker compose exec php php artisan $(c)

migrate: ## Run migrations
	docker compose exec php php artisan migrate

migrate-fresh: ## Fresh migration with seed
	docker compose exec php php artisan migrate:fresh --seed

seed: ## Run seeders
	docker compose exec php php artisan db:seed

rollback: ## Rollback last migration
	docker compose exec php php artisan migrate:rollback

tinker: ## Run Laravel Tinker
	docker compose exec php php artisan tinker

route-list: ## Show all routes
	docker compose exec php php artisan route:list

## —— Composer Commands ——
composer: ## Run composer command (use: make composer c="require package")
	docker compose exec php composer $(c)

install: ## Install composer dependencies
	docker compose exec php composer install

update: ## Update composer dependencies
	docker compose exec php composer update

dump: ## Composer dump-autoload
	docker compose exec php composer dump-autoload

## —— Cache ——
cache-clear: ## Clear all cache
	docker compose exec php php artisan cache:clear
	docker compose exec php php artisan config:clear
	docker compose exec php php artisan route:clear
	docker compose exec php php artisan view:clear

cache: ## Cache config and routes
	docker compose exec php php artisan config:cache
	docker compose exec php php artisan route:cache

optimize: ## Optimize application
	docker compose exec php php artisan optimize

## —— Queue ——
queue-restart: ## Restart queue worker and Reverb (pick up code changes)
	docker compose restart queue reverb

scheduler: ## Start scheduler (profile, as on prod)
	docker compose --profile scheduler up -d scheduler

scheduler-stop: ## Stop scheduler
	docker compose --profile scheduler stop scheduler

## —— Testing ——
test: ## Run tests
	docker compose exec php php artisan test

test-filter: ## Run filtered tests (use: make test-filter f="TestName")
	docker compose exec php php artisan test --filter=$(f)

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
	docker compose exec php php artisan key:generate

ide-helper: ## Generate IDE helper files
	docker compose exec php php artisan ide-helper:generate

## —— Database ——
db-dump: ## Dump database to backup.sql
	docker compose exec -T db mariadb-dump -u root -p$${DB_ROOT_PASSWORD:-root} --single-transaction --routines --triggers $${DB_DATABASE:-fitness} > backup.sql

db-restore: ## Restore database from backup.sql
	docker compose exec -T db mariadb -u root -p$${DB_ROOT_PASSWORD:-root} $${DB_DATABASE:-fitness} < backup.sql

## —— Helpers ——
permissions: ## Fix storage permissions
	docker compose exec php chmod -R 775 storage bootstrap/cache
	docker compose exec php chown -R laravel:laravel storage bootstrap/cache

help: ## Show this help
	@echo ''
	@echo 'Usage:'
	@echo '  ${YELLOW}make${RESET} ${GREEN}<target>${RESET}'
	@echo ''
	@echo 'Targets:'
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "  ${YELLOW}%-15s${RESET} %s\n", $$1, $$2}' $(MAKEFILE_LIST)

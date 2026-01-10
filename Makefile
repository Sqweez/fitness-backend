.PHONY: help build up down restart logs shell artisan composer migrate seed fresh test cache-clear queue tinker install

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

shell-mysql: ## Access MySQL container shell
	docker compose exec mysql mysql -u root -p

## —— Artisan Commands ——
artisan: ## Run artisan command (use: make artisan c="migrate")
	docker compose run --rm artisan $(c)

migrate: ## Run migrations
	docker compose run --rm artisan migrate

migrate-fresh: ## Fresh migration with seed
	docker compose run --rm artisan migrate:fresh --seed

seed: ## Run seeders
	docker compose run --rm artisan db:seed

rollback: ## Rollback last migration
	docker compose run --rm artisan migrate:rollback

tinker: ## Run Laravel Tinker
	docker compose exec php php artisan tinker

route-list: ## Show all routes
	docker compose run --rm artisan route:list

## —— Composer Commands ——
composer: ## Run composer command (use: make composer c="require package")
	docker compose run --rm composer $(c)

install: ## Install composer dependencies
	docker compose run --rm composer install

update: ## Update composer dependencies
	docker compose run --rm composer update

dump: ## Composer dump-autoload
	docker compose run --rm composer dump-autoload

## —— Cache ——
cache-clear: ## Clear all cache
	docker compose run --rm artisan cache:clear
	docker compose run --rm artisan config:clear
	docker compose run --rm artisan route:clear
	docker compose run --rm artisan view:clear

cache: ## Cache config and routes
	docker compose run --rm artisan config:cache
	docker compose run --rm artisan route:cache

optimize: ## Optimize application
	docker compose run --rm artisan optimize

## —— Queue ——
queue: ## Start queue worker
	docker compose --profile worker up -d queue

queue-stop: ## Stop queue worker
	docker compose stop queue

queue-restart: ## Restart queue worker
	docker compose restart queue

## —— Testing ——
test: ## Run tests
	docker compose exec php php artisan test

test-filter: ## Run filtered tests (use: make test-filter f="TestName")
	docker compose exec php php artisan test --filter=$(f)

## —— Setup ——
setup: ## Initial project setup
	@make build
	@make up
	@make install
	@make copy-env
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
	docker compose run --rm artisan key:generate

jwt-secret: ## Generate JWT secret
	docker compose run --rm artisan jwt:secret

ide-helper: ## Generate IDE helper files
	docker compose run --rm artisan ide-helper:generate

## —— Database ——
db-dump: ## Dump database to backup.sql
	docker compose exec mysql mysqldump -u root -p$${DB_ROOT_PASSWORD:-root} $${DB_DATABASE:-fitness} > backup.sql

db-restore: ## Restore database from backup.sql
	docker compose exec -T mysql mysql -u root -p$${DB_ROOT_PASSWORD:-root} $${DB_DATABASE:-fitness} < backup.sql

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

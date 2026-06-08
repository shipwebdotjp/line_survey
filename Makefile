.PHONY: up build init down fresh restart ps logs composer-install test npm-install npm-dev npm-build migrate seed

# --- Docker Commands ---
up:
	docker compose up -d

build:
	docker compose build --no-cache --force-rm

init:
	docker compose up -d --build
	docker compose exec web composer install -d /var/www/backend
	docker compose exec web sh -lc 'if [ ! -f /var/www/backend/.env ]; then cp /var/www/backend/.env.example /var/www/backend/.env; fi'

down:
	docker compose down --remove-orphans

fresh:
	docker compose down -v --remove-orphans
	docker compose up -d --build

restart:
	@make down
	@make up

ps:
	docker compose ps

logs:
	docker compose logs -f

# --- Backend Commands ---
composer-install:
	docker compose exec web composer install -d /var/www/backend

test:
	docker compose exec web /var/www/backend/vendor/bin/phpunit -c /var/www/backend/phpunit.xml

# --- Frontend Commands ---
npm-install:
	npm --prefix frontend install

npm-dev:
	npm --prefix frontend run dev

npm-build:
	npm --prefix frontend run build

# --- Database Commands ---
migrate:
	docker compose exec web /var/www/backend/vendor/bin/phinx migrate -c /var/www/backend/phinx.php

seed:
	docker compose exec web /var/www/backend/vendor/bin/phinx seed:run -c /var/www/backend/phinx.php

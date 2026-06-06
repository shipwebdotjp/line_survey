# --- Docker Commands ---
up:
	docker-compose up -d

build:
	docker-compose build --no-cache --force-rm

init:
	docker-compose up -d --build
	docker-compose exec web composer install -d backend
	if [ ! -f backend/.env ]; then cp backend/.env.example backend/.env; fi

down:
	docker-compose down --remove-orphans

restart:
	@make down
	@make up

ps:
	docker-compose ps

logs:
	docker-compose logs -f

# --- Backend Commands ---
composer-install:
	docker-compose exec web composer install -d backend

test:
	docker-compose exec web backend/vendor/bin/phpunit -c backend/phpunit.xml

# --- Frontend Commands ---
npm-install:
	docker-compose exec web npm install --prefix frontend

npm-dev:
	docker-compose exec web npm run dev --prefix frontend

npm-build:
	docker-compose exec web npm run build --prefix frontend

# --- Database Commands (Placeholder for now) ---
migrate:
	@echo "Migration command placeholder"

seed:
	@echo "Seed command placeholder"

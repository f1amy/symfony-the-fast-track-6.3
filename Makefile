init:
	cp -u .env.local.example .env.local

init-tests:
	docker compose exec app symfony console doctrine:database:create --if-not-exists --env=test
	docker compose exec app symfony console doctrine:migrations:migrate -n --env=test
	docker compose exec app symfony console doctrine:fixtures:load --group test -n --env=test

build:
	docker compose build

up:
	docker compose up -d

down:
	docker compose down

start:
	docker compose start

stop:
	docker compose stop

restart:
	docker compose restart

recreate: down up

logs:
	docker compose logs -f

exec-app:
	docker compose exec app ash

exec-spa-app:
	docker compose exec spa-app ash

composer:
	docker compose exec app symfony composer install

npm-install:
	docker compose exec app symfony run npm install

npm-spa-install:
	docker compose exec spa-app symfony run npm install

migration:
	docker compose exec app symfony console make:migration

migrate:
	docker compose exec app symfony console doctrine:migrations:migrate -n

tests: init-tests
	docker compose exec app php bin/phpunit
.PHONY: tests

load-fixtures:
	docker compose exec app symfony console doctrine:fixtures:load --group AppFixtures

assets:
	docker compose exec app symfony run npm run dev
.PHONY: assets

watch-assets:
	docker compose exec app symfony run npm run watch

spa-assets:
	docker compose exec spa-app symfony run npm run dev

watch-spa-assets:
	docker compose exec spa-app symfony run --watch=webpack.config.js npm run watch

clear-cache:
	docker compose exec app symfony console cache:clear

messenger-retry-failed:
	docker compose exec app symfony console messenger:failed:retry

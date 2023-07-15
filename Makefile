.PHONY: init init-tests build up down start stop restart recreate logs exec-app composer migration migrate tests

init:
	cp -u .env.local.example .env.local

init-tests:
	docker compose exec app symfony console doctrine:database:create --if-not-exists --env=test
	docker compose exec app symfony console doctrine:migrations:migrate -n --env=test
	docker compose exec app symfony console doctrine:fixtures:load -n --env=test

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
	docker compose logs

exec-app:
	docker compose exec app ash

composer:
	docker compose exec app symfony composer install

migration:
	docker compose exec app symfony console make:migration

migrate:
	docker compose exec app symfony console doctrine:migrations:migrate -n

tests: init-tests
	docker compose exec app php bin/phpunit

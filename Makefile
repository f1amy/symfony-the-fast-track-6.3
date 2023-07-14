.PHONY: build up down start stop restart recreate logs exec-app

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

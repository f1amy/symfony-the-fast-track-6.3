# symfony-the-fast-track-6.3

Using 6.2 book and php 8.2

https://symfony.com/doc/6.2/the-fast-track/en/index.html

## Symfony app
URL: [http://localhost](http://localhost)

### Setup

```shell
make init
make build
make up
make composer
make migrate
make load-fixtures
make npm-install
make assets
```

## SPA
URL: [http://localhost:81](http://localhost:81)

### Setup
```shell
make npm-spa-install
make spa-assets
```

## Symfony app tests
```shell
make tests
```

## Admin
URL: [http://localhost/login](http://localhost/login)

- Login: `admin`
- password: `admin`

## Infrastructure
- RabbitMQ Management UI: [http://localhost:5672](http://localhost:5672)
- Postgres: [http://localhost:5432](http://localhost:5432)
- Mailcatcher UI: [http://localhost:1080](http://localhost:1080)

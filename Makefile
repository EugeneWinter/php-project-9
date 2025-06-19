PORT ?= 8080

start:
	PHP_CLI_SERVER_WORKERS=5 php -S 0.0.0.0:$(PORT) -t public

install:
	composer install --ignore-platform-reqs --no-scripts
	composer dump-autoload --optimize

setup: install
	createdb url_checker || true
	psql url_checker < database.sql

lint:
	composer exec --verbose phpcs -- --standard=PSR12 src public templates

lint-fix:
	composer exec --verbose phpcbf -- --standard=PSR12 src public templates

test:
	composer exec phpunit

docker-build:
	docker-compose build

docker-start:
	docker-compose up

docker-stop:
	docker-compose down

clean:
	rm -rf vendor/
	rm -rf composer.lock

.PHONY: start install setup lint lint-fix test docker-build docker-start docker-stop clean
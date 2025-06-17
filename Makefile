PORT ?= 8080

start:
	PHP_CLI_SERVER_WORKERS=5 php -S 0.0.0.0:$(PORT) -t public

lint:
	composer exec --verbose phpcs -- --standard=PSR12 src public templates

lint-fix:
	composer exec --verbose phpcbf -- --standard=PSR12 src public templates

install:
	git config --global --add safe.directory /app
	composer install --ignore-platform-reqs --no-scripts
	composer dump-autoload --optimize

setup:
	docker build -t url-checker .
	docker run -it --rm \
		-p $(PORT):$(PORT) \
		-v "$(CURDIR):/app" \
		-e PORT=$(PORT) \
		url-checker \
		sh -c "git config --global --add safe.directory /app && make install && make start"

docker-setup:
	docker-compose down
	docker-compose build
	docker-compose up

test:
	composer exec phpunit

.PHONY: start install setup docker-setup lint lint-fix test
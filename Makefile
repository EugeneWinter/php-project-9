PORT ?= 8000
start:
	PHP_CLI_SERVER_WORKERS=5 php -S 0.0.0.0:$(PORT) -t public

lint:
	composer exec --verbose phpcs -- --standard=PSR12 src public

lint-fix:
	composer exec --verbose phpcbf -- --standard=PSR12 src public

install:
	composer install --ignore-platform-reqs

setup:
	docker build -t url-checker .
	docker run -it --rm -p 8000:8000 -v $(pwd):/app url-checker

.PHONY: start install setup lint lint-fix
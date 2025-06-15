start:
	php -S 0.0.0.0:8000 -t public

install:
	composer install --ignore-platform-reqs

setup:
	docker build -t url-checker .
	docker run -it --rm -p 8000:8000 -v $(pwd):/app url-checker

lint:
	composer run phpcs -- --standard=PSR12 src public

lint-fix:
	composer run phpcbf -- --standard=PSR12 src public

.PHONY: start install setup lint lint-fix
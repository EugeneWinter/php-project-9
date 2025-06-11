PORT ?= 8000
start:
	PHP_CLI_SERVER_WORKERS=5 php -S 0.0.0.0:$(PORT) -t public

install:
	composer install

lint:
	composer run phpcs

lint-fix:
	composer run phpcbf

.PHONY: start install lint lint-fix
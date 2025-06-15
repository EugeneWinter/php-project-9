start:
	php -S localhost:8000 -t public

install:
	composer install

lint:
	composer run phpcs

lint-fix:
	composer run phpcbf

.PHONY: start install lint lint-fix
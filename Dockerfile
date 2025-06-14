FROM php:8.1-cli
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql
WORKDIR /app
COPY . .
RUN composer install
CMD ["make", "start"]
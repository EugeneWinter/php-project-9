FROM php:8.3-cli

# Установка зависимостей
RUN apt-get update && apt-get install -y \
    git \
    libpq-dev \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

# Настройка Git и Composer
RUN git config --global --add safe.directory /app && \
    curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer

WORKDIR /app

COPY . .

# Установка зависимостей с очисткой кеша
RUN composer clear-cache && \
    composer install --ignore-platform-reqs --no-scripts && \
    composer dump-autoload --optimize

CMD ["make", "start"]
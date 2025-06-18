FROM php:8.3-cli

# Установка зависимостей
RUN apt-get update && apt-get install -y \
    git unzip zip libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Установка Composer
RUN curl -sS https://getcomposer.org/installer | php && mv composer.phar /usr/local/bin/composer

# Копируем зависимости
COPY composer.json composer.lock ./

# Установка пакетов
RUN composer install --no-interaction --prefer-dist --ignore-platform-reqs

# Копируем весь проект
COPY . .

CMD ["make", "start"]
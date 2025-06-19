FROM php:8.3-cli

WORKDIR /app

# Установка зависимостей и очистка кеша в одном RUN слое
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Установка Composer (официальный метод)
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Копируем только файлы, необходимые для установки зависимостей
COPY composer.json composer.lock ./

# Установка пакетов без dev-зависимостей (для production)
RUN composer install --no-dev --no-interaction --prefer-dist --ignore-platform-reqs --no-scripts

# Копируем остальные файлы проекта
COPY . .

# Установка прав (если нужно)
RUN chown -R www-data:www-data /app

# Явное указание порта через переменную окружения
ENV PORT=8002

CMD ["make", "start"]
FROM php:8.3-cli

# Установка зависимостей и расширений PostgreSQL
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# Установка Composer
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer

# Настройка рабочей директории
WORKDIR /app
COPY . .

# Установка зависимостей
RUN composer install --no-dev --optimize-autoloader

# Запуск приложения
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
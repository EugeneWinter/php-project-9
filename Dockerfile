FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
    git \
    libpq-dev \
    unzip \
    && docker-php-ext-install pdo pdo_pgsql \
    && rm -rf /var/lib/apt/lists/*

RUN git config --global --add safe.directory /project/code

WORKDIR /project/code

RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer

COPY . .

RUN composer install --ignore-platform-reqs --no-scripts \
    && composer dump-autoload --optimize

CMD ["make", "start"]
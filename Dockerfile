FROM php:8.1-cli
WORKDIR /app
COPY . .
RUN composer install
CMD ["make", "start"]
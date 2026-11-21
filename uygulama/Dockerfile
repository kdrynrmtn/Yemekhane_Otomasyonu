FROM php:8.3-cli-alpine

RUN docker-php-ext-install pdo_mysql
WORKDIR /var/www/uygulama
COPY . .
EXPOSE 8000
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public", "public/index.php"]

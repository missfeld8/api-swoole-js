FROM php:7.4-cli

RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_mysql

WORKDIR /app

COPY . /app

CMD ["php", "server1.php"]

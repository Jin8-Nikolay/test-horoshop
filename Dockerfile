FROM php:8.3-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libzip-dev libonig-dev \
    && docker-php-ext-install pdo_mysql mbstring \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Install dependencies first to leverage Docker layer caching.
COPY composer.json composer.lock* ./
RUN composer install --no-interaction --no-scripts --no-progress --prefer-dist

COPY . .

RUN composer dump-autoload --optimize

EXPOSE 8000

# On a fresh volume MySQL may still be initialising, so retry the first DB call
# until it connects, then migrate and serve.
CMD ["sh", "-c", "until php bin/console doctrine:database:create --if-not-exists --no-interaction; do echo 'Waiting for database...'; sleep 2; done && php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration && php -S 0.0.0.0:8000 public/index.php"]

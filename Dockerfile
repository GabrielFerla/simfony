# Symfony em Docker — desenvolvimento (PHP 8.4)
FROM php:8.4-cli-alpine

# Dependências e extensões PHP para Symfony + PostgreSQL
# postgresql-libs (libpq) fica instalado para o pdo_pgsql carregar em runtime
RUN apk add --no-cache \
    git \
    unzip \
    postgresql-dev \
    postgresql-libs \
    libzip-dev \
    libxml2-dev \
    icu-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j$(nproc) \
        pdo_pgsql \
        intl \
        zip \
        opcache \
    && docker-php-ext-enable pdo_pgsql intl zip opcache \
    && apk del postgresql-dev

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /app

# Servidor embutido na pasta public (para dev)
EXPOSE 8000
CMD ["sh", "-c", "composer install --no-interaction && php -S 0.0.0.0:8000 -t public"]

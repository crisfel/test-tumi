# syntax=docker/dockerfile:1.7
#
# Imagen PHP para la plataforma PayIn.
#
# Targets:
#   - development (default): PHP-FPM 8.3 + Composer + herramientas QA. El código
#     fuente se monta como volumen (docker compose up).
#   - production: imagen lista para producción con vendor y código copiados.

# ---------------------------------------------------------------------------
# Etapa 1: Dependencias de producción (optimizadas para caché de capas)
# ---------------------------------------------------------------------------
FROM composer:2 AS vendor

WORKDIR /tmp

COPY composer.json composer.lock ./

RUN composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --prefer-dist \
        --no-scripts \
        --no-autoloader \
        --ignore-platform-reqs

# ---------------------------------------------------------------------------
# Etapa 2: Base PHP-FPM 8.3 con extensiones requeridas por la plataforma
# ---------------------------------------------------------------------------
FROM php:8.3-fpm-alpine AS base

COPY docker/php/php.ini /usr/local/etc/php/conf.d/payin.ini

RUN apk add --no-cache \
        icu-dev \
        libzip-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        intl \
        pdo_mysql \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS

WORKDIR /var/www/html

# ---------------------------------------------------------------------------
# Etapa 3: Entorno de desarrollo (Composer + herramientas)
# ---------------------------------------------------------------------------
FROM base AS development

RUN apk add --no-cache bash git

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ---------------------------------------------------------------------------
# Etapa 4: Producción (código y vendor copiados, sin herramientas de dev)
# ---------------------------------------------------------------------------
FROM base AS production

COPY --from=vendor /tmp/vendor /var/www/html/vendor
COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R u=rwX,go=rX /var/www/html

EXPOSE 9000

CMD ["php-fpm"]

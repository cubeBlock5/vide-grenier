# --- Stage 1: fetch the app source from the main branch on GitHub ---
# Cloned fresh at build time so the image never depends on the local
# working copy (which may be on any branch, with uncommitted changes).
FROM alpine/git:latest AS source
RUN git clone --branch main --single-branch --depth 1 \
    https://github.com/cubeBlock5/vide-grenier.git /repo

# --- Stage 2: install PHP dependencies ---
FROM composer:2 AS vendor
WORKDIR /app
COPY --from=source /repo/src /app
RUN composer install --no-interaction --no-progress --optimize-autoloader

# --- Stage 3: runtime image ---
FROM php:8.4-apache
RUN docker-php-ext-install pdo pdo_mysql
RUN a2enmod rewrite
COPY apache/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY --from=vendor /app /var/www/html

EXPOSE 80

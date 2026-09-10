FROM php:8.2-apache

# Install PostgreSQL extensions para sa PHP
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql

# syntax=docker/dockerfile:1
FROM php:8.2-apache

# Instala dependencias del sistema necesarias para extensiones PHP
RUN apt-get update && apt-get install -y \
        libonig-dev \
        libzip-dev \
        libcurl4-openssl-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        unzip \
        git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mysqli curl mbstring zip gd \
    && a2enmod rewrite headers expires \
    && rm -rf /var/lib/apt/lists/*

# Copia configuración personalizada de Apache y PHP
COPY .docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY .docker/php/php.ini /usr/local/etc/php/php.ini

# Establece el directorio de trabajo y copia el código de la aplicación
WORKDIR /var/www/html
COPY . .

# Ajusta permisos de carpetas que requieren escritura
RUN chown -R www-data:www-data storage logs uploads || true

# Healthcheck básico
HEALTHCHECK --interval=30s --timeout=5s --start-period=30s CMD curl -f http://localhost/health || exit 1

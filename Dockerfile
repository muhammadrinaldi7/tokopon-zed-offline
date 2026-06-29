# Menggunakan PHP 8.4 FPM berbasis Alpine Linux (ringan dan aman)
FROM php:8.4-fpm-alpine

# Install system dependencies yang dibutuhkan oleh PHP extensions & Composer
RUN apk add --no-cache \
    bash \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    git

# Install PHP extensions yang diwajibkan oleh Laravel
RUN docker-php-ext-install pdo_mysql bcmath gd

# Mengambil Composer resmi dari image Composer (Multi-stage technique)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Mengambil Node.js 25 & NPM dari image resmi Node
COPY --from=node:25-alpine /usr/local/bin/node /usr/local/bin/node
COPY --from=node:25-alpine /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm

# Tentukan working directory aplikasi di dalam container
WORKDIR /var/www/html

EXPOSE 9000
# Étape 1 : Construction des assets frontend avec Node.js
FROM node:20-alpine AS frontend
WORKDIR /app
COPY package*.json vite.config.js ./
RUN npm install
COPY resources/ ./resources/
COPY app/ ./app/
RUN npm run build

# Étape 2 : Image d'exécution PHP 8.2 Apache
FROM php:8.2-apache

# Installation des dépendances système nécessaires
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libpq-dev \
    zip \
    unzip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Installation des extensions PHP requises pour Laravel et les bases de données (MySQL et PostgreSQL)
RUN docker-php-ext-install pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd zip opcache

# Installation de Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configuration d'Apache pour pointer vers le sous-dossier public/ de Laravel
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
RUN a2enmod rewrite

# Répertoire de travail
WORKDIR /var/www/html

# Copie des fichiers sources de l'application
COPY . /var/www/html

# Copie des assets frontend compilés depuis l'étape 1
COPY --from=frontend /app/public/build /var/www/html/public/build

# Installation des dépendances PHP pour la production (sans dev et sans exécuter les scripts artisan)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Permissions pour le stockage et le cache Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Script de démarrage
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80 10000

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]

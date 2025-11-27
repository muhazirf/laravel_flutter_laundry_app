FROM php:8.3-apache

RUN apt-get update && apt-get install -y \
    zip unzip git curl libpng-dev libonig-dev libxml2-dev libicu-dev libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd intl zip \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm@latest


COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Aktifkan mod_rewrite untuk Laravel
RUN a2enmod rewrite
# Copy project files
COPY laravel_laundry .

# Install dependencies sesuai composer.lock
# RUN composer update --no-dev --optimize-autoloader
# Dependencies will be installed at runtime via init.sh

# Salin konfigurasi Apache
COPY php/apache.conf /etc/apache2/sites-available/000-default.conf

# salin entrypoint
COPY script/init.sh /usr/local/bin/init.sh
RUN chmod +x /usr/local/bin/init.sh
ENTRYPOINT ["/usr/local/bin/init.sh"]

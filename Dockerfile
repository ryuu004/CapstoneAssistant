FROM richarvey/nginx-php-fpm:3.2.0

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libpq-dev \
    && docker-php-ext-install pdo_mysql pdo_pgsql zip exif pcntl gd

# Set working directory
WORKDIR /var/www/html

# Copy source code
COPY . /var/www/html

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install PHP dependencies
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port 80 and start nginx
EXPOSE 80
CMD ["/usr/bin/run.sh"]
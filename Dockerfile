FROM richarvey/nginx-php-fpm:latest

# Copy custom Nginx configuration
COPY nginx.conf /etc/nginx/sites-available/default.conf
RUN ln -sf /etc/nginx/sites-available/default.conf /etc/nginx/sites-enabled/default.conf

# Install system dependencies
RUN apk update && \
    apk add --no-cache \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    libpq \
    php-pdo_pgsql \
    php-zip \
    php-exif \
    php-pcntl \
    php-gd

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
RUN touch .env
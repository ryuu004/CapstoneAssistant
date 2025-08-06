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

# Create an empty .env file
RUN touch .env

# Copy source code and composer files
COPY . /var/www/html
COPY composer.json composer.lock ./

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Generate application key
RUN php artisan key:generate

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port 80
EXPOSE 80
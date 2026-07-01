FROM php:8.2-apache

# 1. Enable Apache rewrite module for Slim Framework routing
RUN a2enmod rewrite

# 2. Install zip system utilities often required by Composer
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    && docker-php-ext-install zip

# 3. Install Composer globally
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 4. Set the working directory
WORKDIR /var/www/html

# 5. Copy the backend source files
COPY . /var/www/html

# 6. Run composer install to install PHP dependencies
RUN composer install --no-interaction --optimize-autoloader

# 7. Configure Apache to look directly at the public/ folder for security
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

# 8. Expose port 80 for Render
EXPOSE 80
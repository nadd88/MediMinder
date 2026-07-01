FROM php:8.2-apache

# 1. Enable Apache rewrite module for Slim Framework routing
RUN a2enmod rewrite

# 2. Allow .htaccess overrides so Apache can route requests through index.php
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf /etc/apache2/sites-available/*.conf

# 3. Install zip and PostgreSQL system utilities often required by Composer
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    libpq-dev \
    && docker-php-ext-install zip pdo pdo_pgsql

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

# 7b. Allow .htaccess overrides in the document root so Slim's front-controller
# rewrite rule (public/.htaccess) actually gets applied - without this, Apache
# ignores .htaccess and returns its own 404 (no CORS headers) for every route
# other than "/".
RUN printf '<Directory "%s">\n\tAllowOverride All\n\tRequire all granted\n</Directory>\n' "$APACHE_DOCUMENT_ROOT" > /etc/apache2/conf-available/zz-allow-override.conf \
    && a2enconf zz-allow-override

# 8. Expose port 80 for Render
EXPOSE 80
FROM php:8.2-apache

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache rewrite module
RUN a2enmod rewrite

# Adapt Apache port dynamically for cloud environments (Render, Railway, Fly.io)
RUN sed -i 's/80/${PORT:-80}/g' /etc/apache2/ports.conf /etc/apache2/sites-available/*.conf

# Set document root
WORKDIR /var/www/html

# Copy all project files
COPY . /var/www/html/

# Ensure uploads and config directories are writable by Apache
RUN mkdir -p /var/www/html/uploads/resumes /var/www/html/config \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/uploads /var/www/html/config

EXPOSE 80

CMD ["apache2-foreground"]

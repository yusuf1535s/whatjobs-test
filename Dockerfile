FROM php:8.2-apache

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache rewrite module
RUN a2enmod rewrite

# Set document root
WORKDIR /var/www/html

# Copy all project files
COPY . /var/www/html/

# Ensure uploads and config directories are writable by Apache and entrypoint is executable
RUN mkdir -p /var/www/html/uploads/resumes /var/www/html/config \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/uploads /var/www/html/config \
    && chmod +x /var/www/html/entrypoint.sh

EXPOSE 10000

CMD ["/var/www/html/entrypoint.sh"]

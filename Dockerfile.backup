FROM php:8.2-apache

# Install required PHP extensions
RUN docker-php-ext-install pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Configure Apache to use index.php as default
RUN echo "DirectoryIndex index.php" >> /etc/apache2/apache2.conf

# Use the default Apache port
EXPOSE 80

# Start Apache in foreground
CMD ["apache2-foreground"]

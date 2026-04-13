FROM php:8.2-apache

# Install required PHP extensions
RUN docker-php-ext-install pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy application files
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Configure Apache to use index.php as default
RUN echo "DirectoryIndex index.php" >> /etc/apache2/apache2.conf

# FORCE IPv4 ONLY - Replace ports.conf
RUN echo "Listen 0.0.0.0:80" > /etc/apache2/ports.conf

# Add ServerName to suppress warnings
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Disable IPv6 completely in Apache
RUN echo "NameVirtualHost *:80" >> /etc/apache2/apache2.conf

# Pass environment variables to Apache
RUN echo "SetEnv DB_HOST ${DB_HOST}" >> /etc/apache2/conf-enabled/environment.conf \
    && echo "SetEnv DB_NAME ${DB_NAME}" >> /etc/apache2/conf-enabled/environment.conf \
    && echo "SetEnv DB_USER ${DB_USER}" >> /etc/apache2/conf-enabled/environment.conf \
    && echo "SetEnv DB_PASS ${DB_PASS}" >> /etc/apache2/conf-enabled/environment.conf

EXPOSE 80
CMD ["apache2-foreground"]

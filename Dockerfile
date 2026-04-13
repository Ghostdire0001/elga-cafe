FROM php:8.2-apache

# Install required PHP extensions
RUN docker-php-ext-install pdo_mysql

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set ServerName to suppress warnings
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Copy application files
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Ensure Apache stays running and handles signals properly
RUN echo "LockFile /var/lock/apache2/accept.lock" >> /etc/apache2/apache2.conf

EXPOSE 80

# Use exec to ensure Apache handles signals correctly
CMD ["/usr/sbin/apache2ctl", "-D", "FOREGROUND"]

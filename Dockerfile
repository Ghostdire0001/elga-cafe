FROM php:8.2

# Install required PHP extensions
RUN docker-php-ext-install pdo_mysql

# Copy application files
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html

# Expose port
EXPOSE 10000

# Run PHP built-in server
CMD ["php", "-S", "0.0.0.0:10000", "-t", "/var/www/html"]

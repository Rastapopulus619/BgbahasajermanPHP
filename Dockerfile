FROM php:8.2-apache

# Install required PHP extensions
RUN docker-php-ext-install mysqli

# Enable Apache mod_rewrite for clean URLs
RUN a2enmod rewrite

# Copy only what's needed first to leverage layer caching
COPY html/ /var/www/html/

# Set proper file permissions
RUN chown -R www-data:www-data /var/www/html

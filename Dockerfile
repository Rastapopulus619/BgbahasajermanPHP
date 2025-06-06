FROM php:8.2-apache

# Install mysqli extension to allow PHP to talk to MySQL
RUN docker-php-ext-install mysqli

# Optional: enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy your PHP app into the container's web root
COPY html/ /var/www/html/

# Set correct permissions (optional but good practice)
RUN chown -R www-data:www-data /var/www/html


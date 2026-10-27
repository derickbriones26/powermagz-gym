FROM php:8.2-apache

# 1. Install system dependencies, MariaDB (MySQL), and PHP extensions
RUN apt-get update && apt-get install -y \
    mariadb-server \
    mariadb-client \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_mysql zip

# 2. Enable Apache mod_rewrite
RUN a2enmod rewrite

# 3. Copy the entire project into the web root
COPY . /var/www/html/

# 4. Create storage directories and set correct permissions
RUN mkdir -p /var/www/html/storage/logs /var/www/html/storage/uploads /var/www/html/storage/backups
RUN chown -R www-data:www-data /var/www/html/storage

# 5. Change Apache DocumentRoot to the project root
ENV APACHE_DOCUMENT_ROOT /var/www/html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 6. Setup the startup script
COPY start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# 7. Start the container
CMD ["/usr/local/bin/start.sh"]
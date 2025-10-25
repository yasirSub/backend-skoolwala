FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install required PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql pdo_pgsql

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set working directory
WORKDIR /var/www/html

# Copy application files first (composer.json might not exist)
COPY . .

# Create composer.json if it doesn't exist
RUN if [ ! -f composer.json ]; then \
    echo '{"require":{"php":">=8.0"}}' > composer.json; \
    fi

# Install dependencies (or skip if no composer.json)
RUN composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs || true

# Set permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html
RUN chmod -R 777 /var/www/html/application/logs
RUN chmod -R 777 /var/www/html/uploads

# Enable mod_rewrite
RUN a2enmod rewrite

# Configure Apache
RUN echo '<Directory /var/www/html>' >> /etc/apache2/apache2.conf && \
    echo '    Options Indexes FollowSymLinks' >> /etc/apache2/apache2.conf && \
    echo '    AllowOverride All' >> /etc/apache2/apache2.conf && \
    echo '    Require all granted' >> /etc/apache2/apache2.conf && \
    echo '</Directory>' >> /etc/apache2/apache2.conf

# Expose port (Render will override this with PORT env var)
EXPOSE 80

# Start Apache with PORT from environment
CMD /bin/bash -c "sed -i 's/Listen 80/Listen '\$PORT'/g' /etc/apache2/ports.conf && sed -i 's/:80/:'\$PORT'/g' /etc/apache2/sites-available/000-default.conf && apache2-foreground"

FROM php:8.4-apache

ARG WWWUSER=1337
ARG WWWGROUP=33

ENV DEBIAN_FRONTEND=noninteractive \
    APACHE_DOCUMENT_ROOT=/var/www/html/public \
    COMPOSER_ALLOW_SUPERUSER=1 \
    NODE_MAJOR=22

# Install system dependencies, PHP extensions, Node.js and Composer.
RUN apt-get update && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        gnupg \
        git \
        unzip \
        zip \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libzip-dev \
        libonig-dev \
        libxml2-dev \
        libsqlite3-dev \
        libicu-dev \
        sqlite3 \
        supervisor \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        gd \
        pdo \
        pdo_sqlite \
        mbstring \
        zip \
        exif \
        pcntl \
        bcmath \
        intl \
        opcache \
    && a2enmod rewrite headers \
    && mkdir -p /etc/apt/keyrings \
    && curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg \
    && echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_${NODE_MAJOR}.x nodistro main" > /etc/apt/sources.list.d/nodesource.list \
    && apt-get update && apt-get install -y --no-install-recommends nodejs \
    && corepack enable \
    && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Install Composer.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configure Apache document root.
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Create a non-root user that matches common Laravel/Sail conventions.
RUN useradd -G www-data,root -u ${WWWUSER} -d /home/sail sail \
    && mkdir -p /home/sail/.composer /var/www/html \
    && chown -R sail:www-data /home/sail /var/www/html

WORKDIR /var/www/html

# Copy supervisord and entrypoint scripts.
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY --chmod=0755 docker/entrypoint.sh /usr/local/bin/entrypoint.sh

EXPOSE 80 5173

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

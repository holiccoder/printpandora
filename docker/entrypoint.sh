#!/usr/bin/env bash

set -e

# Use the owner of the mounted source directory as the runtime user when possible.
WWWUSER="${WWWUSER:-$(stat -c '%u' /var/www/html 2>/dev/null || echo 1337)}"
WWWGROUP="${WWWGROUP:-$(stat -c '%g' /var/www/html 2>/dev/null || echo 33)}"

export APACHE_RUN_USER=sail
export APACHE_RUN_GROUP=www-data

# Ensure the runtime user exists inside the container.
if ! id -u sail > /dev/null 2>&1; then
    useradd -G www-data,root -u "${WWWUSER}" -d /home/sail sail
fi

# Create required directories if they are missing (e.g. on first run with named volumes).
mkdir -p /var/www/html/vendor /var/www/html/node_modules
mkdir -p /var/www/html/storage/framework/{cache,sessions,views,testing}
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/bootstrap/cache
mkdir -p /var/www/html/public/images/product-options/uploads

# Make sure the SQLite database file exists and is writable.
if [ ! -f /var/www/html/database/database.sqlite ]; then
    touch /var/www/html/database/database.sqlite
fi

# Fix ownership so both the web server and the dev user can write.
chown -R sail:www-data /var/www/html
chmod -R ug+rwx /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database/database.sqlite 2>/dev/null || true
chmod -R ug+rwx /var/www/html/public/images/product-options/uploads 2>/dev/null || true

# Install PHP dependencies when the named volume is empty.
if [ ! -f /var/www/html/vendor/autoload.php ]; then
    echo "Installing Composer dependencies..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Install Node dependencies when the named volume is empty.
if [ ! -d /var/www/html/node_modules/.bin ]; then
    echo "Installing Node dependencies..."
    npm install
fi

# Re-apply ownership after dependency installs so the web server and dev user can read them.
chown -R sail:www-data /var/www/html/vendor /var/www/html/node_modules 2>/dev/null || true

# Run migrations automatically in local development.
if [ "${APP_ENV:-local}" = "local" ] || [ "${APP_ENV:-}" = "" ]; then
    php artisan migrate --force || true
fi

# Execute the container's main process.
exec "$@"

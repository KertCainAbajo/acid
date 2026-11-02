#!/bin/sh
# Container start-up: listen on Render's port, cache config, update the database, then serve.
set -e

# Values pasted into a hosting dashboard often carry a hidden trailing Enter or tab, which breaks URLs and passwords.
# Trim every environment variable before Laravel caches its config, and report which ones were fixed (names only).
eval "$(php -r 'foreach (getenv() as $name => $value) { $clean = trim($value); if ($clean !== $value && preg_match("/^[A-Za-z_][A-Za-z0-9_]*$/", $name)) { fwrite(STDERR, "Trimmed stray whitespace from {$name}\n"); echo "export {$name}=\x27".str_replace("\x27", "\x27\\\x27\x27", $clean)."\x27\n"; } }')"

PORT="${PORT:-10000}"
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

php artisan package:discover --ansi
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Safe on every deploy: migrations only add what is missing, and the seeders never overwrite existing data.
php artisan db:ensure-schema
php artisan migrate --force
php artisan db:seed --force

chown -R www-data:www-data storage bootstrap/cache

exec apache2-foreground

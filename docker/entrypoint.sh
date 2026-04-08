#!/bin/sh
set -e

echo "==> Waiting for PostgreSQL..."
until php -r "new PDO('pgsql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}');" 2>/dev/null; do
    echo "    PostgreSQL not ready — retrying in 2s..."
    sleep 2
done
echo "==> PostgreSQL is ready."


echo "==> Running migrations..."
php artisan migrate --force

echo "==> Checking if seed is needed..."
USER_COUNT=$(php artisan tinker --execute="echo \App\Models\User::count();" 2>/dev/null | tail -1 || echo "0")
if [ "$USER_COUNT" = "0" ]; then
    echo "==> Seeding production data..."
    php artisan db:seed --class=ProductionDataSeeder --force
else
    echo "==> Data already present (${USER_COUNT} users found), skipping seed."
fi

echo "==> Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Starting services..."
exec "$@"
